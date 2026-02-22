<?php

namespace App\Traits;

use App\Models\ApprovalAuditRequest;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * AuditableSyncTrait
 *
 * Provides convenient methods for syncing relationships with automatic audit logging.
 * Useful for Livewire components that need to sync roles, permissions, or other M2M relationships.
 *
 * Usage in Livewire components:
 * ```php
 * $this->syncWithAudit($employee, 'roles', $selectedRoles, 'Updated employee roles');
 *
 * // For sensitive operations requiring approval:
 * $this->syncWithAuditRequiringApproval($employee, 'roles', $selectedRoles, 'Updated roles', true);
 * ```
 */
trait AuditableSyncTrait
{
    /**
     * Sync a relationship and log the operation
     *
     * @param Model $model              The model whose relationship is being synced
     * @param string $relationship      The relationship name (e.g., 'roles', 'permissions')
     * @param array $syncData           The data to sync (IDs or [id => attributes])
     * @param string|null $description  Description of the sync operation
     * @param Model|null $causer        The user/employee performing the operation (defaults to current actor)
     *
     * @return array Return value from sync() - [attached, detached, updated]
     *
     * @example
     * // Simple role sync
     * $result = $this->syncWithAudit($employee, 'roles', [1, 2, 3], 'Assigned new roles');
     *
     * // With pivot attributes
     * $result = $this->syncWithAudit($employee, 'roles', [
     *     1 => ['assigned_at' => now()],
     *     2 => ['assigned_at' => now()],
     * ], 'Assigned roles with assignments');
     */
    public function syncWithAudit(
        Model $model,
        string $relationship,
        array $syncData,
        ?string $description = null,
        ?Model $causer = null
    ): array {
        // Get current actor if not provided
        if (!$causer) {
            $causer = auth()->user() ?? auth('web')->user();
        }

        // Get previous data for comparison
        $previousData = $model->{$relationship}()
            ->pluck('id')
            ->mapWithKeys(fn($id) => [$id => null])
            ->toArray();

        return DB::transaction(function () use ($model, $relationship, $syncData, $description, $causer, $previousData) {
            // Perform the sync
            $result = $model->{$relationship}()->sync($syncData);

            // Clear permission cache if syncing roles or permissions
            if ($relationship === 'roles' || $relationship === 'permissions') {
                $model->forgetCachedPermissions();
            }

            // Log the sync operation
            AuditService::logSync(
                $causer,
                $model,
                $relationship,
                is_array($syncData) ? array_keys($syncData) : $syncData,
                array_keys($previousData),
                $description,
                'completed'
            );

            return $result;
        });
    }

    /**
     * Sync a relationship with approval requirement
     *
     * If the operation requires approval, creates an ApprovalAuditRequest and does NOT execute the sync.
     * Once approved, the approver can execute the sync via the AuditManagement component.
     *
     * @param Model $model                  The model whose relationship is being synced
     * @param string $relationship          The relationship name
     * @param array $syncData               The data to sync
     * @param string|null $description      Description for the approval request
     * @param bool $requiresApproval        Whether this operation requires approval
     * @param Model|null $causer            The user/employee performing the operation
     *
     * @return array ['status' => 'completed'|'pending', 'approval_request' => ApprovalAuditRequest|null]
     *
     * @example
     * $result = $this->syncWithAuditRequiringApproval(
     *     $employee,
     *     'roles',
     *     [1, 2, 3],
     *     'Assigning admin roles',
     *     true  // Always requires approval
     * );
     *
     * if ($result['status'] === 'pending') {
     *     $this->toast()->info('Role sync pending approval')->send();
     * }
     */
    public function syncWithAuditRequiringApproval(
        Model $model,
        string $relationship,
        array $syncData,
        ?string $description = null,
        bool $requiresApproval = true,
        ?Model $causer = null
    ): array {
        if (!$causer) {
            $causer = auth()->user() ?? auth('web')->user();
        }

        // Get previous data for comparison
        $previousData = $model->{$relationship}()
            ->pluck('id')
            ->mapWithKeys(fn($id) => [$id => null])
            ->toArray();

        // Prepare payload
        $payload = [
            'id' => $model->id,
            'sync_data' => is_array($syncData) ? array_keys($syncData) : $syncData,
            'previous_data' => array_keys($previousData),
            $relationship => $syncData,
        ];

        if (!$requiresApproval) {
            // Execute immediately
            return [
                'status' => 'completed',
                'approval_request' => null,
                'result' => $this->syncWithAudit($model, $relationship, $syncData, $description, $causer),
            ];
        }

        // Create approval request
        $approvalRequest = ApprovalAuditRequest::create([
            'branch_id' => $model->branch_id ?? current_branch_id(),
            'requester_type' => get_class($causer),
            'requester_id' => $causer->id,
            'action' => "sync:" . get_class($model) . ":{$relationship}",
            'description' => $description,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Log as pending
        AuditService::logSync(
            $causer,
            $model,
            $relationship,
            is_array($syncData) ? array_keys($syncData) : $syncData,
            array_keys($previousData),
            $description,
            'pending'
        );

        return [
            'status' => 'pending',
            'approval_request' => $approvalRequest,
        ];
    }

    /**
     * Sync multiple relationships in a transaction
     *
     * Useful for complex operations involving multiple relationships.
     *
     * @param Model $model                  The model
     * @param array $syncs                  Array of ['relationship' => syncData, ...]
     * @param string|null $description      Description for audit
     * @param Model|null $causer            The actor
     *
     * @return array Results keyed by relationship name
     *
     * @example
     * $results = $this->syncMultipleWithAudit($employee, [
     *     'roles' => [1, 2, 3],
     *     'permissions' => [10, 20, 30],
     * ], 'Bulk role and permission sync');
     */
    public function syncMultipleWithAudit(
        Model $model,
        array $syncs,
        ?string $description = null,
        ?Model $causer = null
    ): array {
        if (!$causer) {
            $causer = auth()->user() ?? auth('web')->user();
        }

        $results = [];

        return DB::transaction(function () use ($model, $syncs, $description, $causer, &$results) {
            foreach ($syncs as $relationship => $syncData) {
                // Get previous data
                $previousData = $model->{$relationship}()
                    ->pluck('id')
                    ->mapWithKeys(fn($id) => [$id => null])
                    ->toArray();

                // Sync
                $results[$relationship] = $model->{$relationship}()->sync($syncData);

                // Log each sync
                AuditService::logSync(
                    $causer,
                    $model,
                    $relationship,
                    is_array($syncData) ? array_keys($syncData) : $syncData,
                    array_keys($previousData),
                    $description ? "{$description} ({$relationship})" : null,
                    'completed'
                );
            }

            return $results;
        });
    }
}
