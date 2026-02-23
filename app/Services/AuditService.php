<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * AuditService
 *
 * Centralized service for handling audit logging across the entire application.
 * Replaces AuditHelper and ActionRequestHelper with a unified, model-agnostic approach.
 *
 * Works seamlessly with:
 * - All models using morphic *_by_id / *_by_type relationships
 * - Sensitive actions requiring approval
 * - Multi-guard authentication (Users & Employees)
 * - Action-specific reasoning and metadata
 *
 * @see MDs/Auditflow.md for usage documentation
 */
class AuditService
{
    /**
     * Log an action in the audit trail.
     *
     * This is the primary method for recording any action that affects data.
     * It handles:
     * - Recording who performed the action (causer)
     * - What was affected (auditable)
     * - Changes made (old/new values)
     * - Approval status
     * - Request metadata
     *
     * @param Model|null $causer         The user/employee performing the action
     * @param string $action             The action type (e.g., 'create', 'update', 'delete')
     * @param Model|null $auditable      The model being affected
     * @param string|null $description   Reason/description for the action
     * @param string $status             Status: 'pending', 'completed', 'rejected', 'approved'
     * @param ApprovalRequest|null $approvalRequest Linked approval request
     * @param array $metadata            Additional context data
     *
     * @return AuditLog
     */
    public static function log(
        Model|string|null $causer,
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        string $status = 'completed',
        ?ApprovalRequest $approvalRequest = null,
        array $metadata = []
    ): AuditLog {
        try {
            if (is_string($causer)) {
                \Log::error('AuditService::log received string causer', [
                    'causer' => $causer,
                    'action' => $action,
                ]);
                // Try to find the user by ID or name
                $user = \App\Models\User::find($causer) ?? \App\Models\User::where('name', $causer)->first();
                if ($user) {
                    $causer = $user;
                } else {
                    \Log::error('Could not find user for causer string', ['causer' => $causer]);
                    $causer = null;
                }
            }

            \Log::info('🔵 [AUDIT SERVICE] Creating audit log', [
                'action' => $action,
                'status' => $status,
                'causer_id' => $causer?->id,
                'causer_type' => $causer ? (is_object($causer) ? get_class($causer) : gettype($causer)) : null,
                'auditable_id' => $auditable?->id,
                'auditable_type' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : null,
            ]);

            // Extract branch_id from auditable model if available
            $branchId = null;
            if ($auditable) {
                $branchId = $auditable->branch_id ?? $auditable->branch ?? null;
                if ($branchId instanceof Model) {
                    $branchId = $branchId->id;
                }
            }
            // Fallback to current branch context if available
            if (!$branchId && function_exists('current_branch_id')) {
                $branchId = current_branch_id();
            }

            $auditLog = AuditLog::create([
                'branch_id' => $branchId,
                'causer_type' => $causer ? (is_object($causer) ? get_class($causer) : gettype($causer)) : null,
                'causer_id' => $causer?->id,
                'auditable_type' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : null,
                'auditable_id' => $auditable?->id,
                'action' => $action,
                'description' => $description,
                'old_values' => $auditable?->getOriginal() ?? null,
                'new_values' => $auditable?->getDirty() ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status' => $status,
                'approval_request_id' => $approvalRequest?->id,
                'logged_at' => now(),
                'details' => array_merge($metadata, [
                    'causer' => $causer ? (is_object($causer) ? get_class($causer) : gettype($causer)) : null,
                    'auditable' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : null,
                ]),
            ]);

            \Log::info('✅ [AUDIT SERVICE] Audit log created successfully', [
                'audit_log_id' => $auditLog->id,
                'action' => $action,
            ]);

            return $auditLog;
        } catch (\Exception $e) {
            \Log::error('❌ [AUDIT SERVICE] Failed to create audit log', [
                'action' => $action,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to create audit log for action '{$action}': " . $e->getMessage());
        }
    }

    /**
     * Log a sensitive action that may require approval.
     *
     * This method is specifically for actions that require supervisory approval.
     * It will:
     * 1. Check if the causer has permission to bypass approval
     * 2. Check if the causer has direct permission to execute
     * 3. Create an approval request if needed
     * 4. Log the audit trail accordingly
     *
     * @param Model $causer              The actor performing the action
     * @param string $action             The action type
     * @param Model $auditable           The model being affected
     * @param string|null $reason        Mandatory reason for sensitive action
     * @param array $context             Additional context (branch_id, etc.)
     * @param array $metadata            Additional metadata to store
     *
     * @return array ['status' => 'completed'|'pending', 'approval_request' => ApprovalRequest|null]
     */
    public static function logSensitiveAction(
        Model $causer,
        string $action,
        Model $auditable,
        ?string $reason = null,
        array $context = []
    ): array {
        // Determine if action requires approval
        $requiresApproval = self::actionRequiresApproval($causer, $action);

        if ($requiresApproval && !self::canBypassApproval($causer, $action)) {
            // Create approval request
            $approvalRequest = ApprovalRequest::create([
                'requested_by_id' => $causer->id,
                'requested_by_type' => is_object($causer) ? get_class($causer) : gettype($causer),
                'action' => $action,
                'auditable_id' => $auditable->id,
                'auditable_type' => is_object($auditable) ? get_class($auditable) : gettype($auditable),
                'status' => 'pending',
                'reason' => $reason,
                'metadata' => $context,
            ]);

            // Log as pending
            self::log(
                $causer,
                $action,
                $auditable,
                $reason,
                'pending',
                $approvalRequest,
                $context
            );

            return [
                'status' => 'pending',
                'approval_request' => $approvalRequest,
            ];
        }

        // Action approved or doesn't require approval
        self::log(
            $causer,
            $action,
            $auditable,
            $reason,
            'completed',
            null,
            $context
        );

        return [
            'status' => 'completed',
            'approval_request' => null,
        ];
    }

    /**
     * Update an actor reference in a model using morphic columns.
     *
     * This method safely updates *_by_id and *_by_type columns on any model,
     * and creates an audit log entry for the change.
     *
     * Works with: ProductDispatch, ProductionRecord, LeaveApplication, etc.
     *
     * @param Model $target              The model being updated
     * @param string $relationshipName   The relationship name (e.g., 'dispatched_by', 'recorded_by')
     * @param Model $actor               The new actor
     * @param string|null $description   Reason for the change
     * @param array $metadata            Additional context
     *
     * @return void
     *
     * @example
     * // Record who produced daily output
     * AuditService::updateActorReference(
     *     $dailyProduce,
     *     'produced_by',
     *     $employee,
     *     'Recorded production after morning shift'
     * );
     */
    public static function updateActorReference(
        Model $target,
        string $relationshipName,
        Model $actor,
        ?string $description = null
    ): void {
        DB::transaction(function () use ($target, $relationshipName, $actor, $description) {
            // Store old values before update
            $oldId = $target->{$relationshipName . '_id'};
            $oldType = $target->{$relationshipName . '_type'};

            // Update the morphic columns
            $target->update([
                $relationshipName . '_id' => $actor->id,
                $relationshipName . '_type' => is_object($actor) ? get_class($actor) : gettype($actor),
            ]);

            // Log the change
            self::log(
                current_actor(),
                "update_{$relationshipName}",
                $target,
                $description,
                'completed'
            );
        });
    }

    /**
     * Bulk update actor references across multiple records.
     *
     * Efficiently updates multiple records' actor references in a single transaction.
     *
     * @param string $modelClass         Full model class name (e.g., \App\Models\ProductDispatch::class)
     * @param array $ids                 Array of model IDs to update
     * @param string $relationshipName   The relationship name
     * @param Model $actor               The new actor
     * @param string|null $description   Reason for bulk update
     * @param array $metadata            Additional context
     *
     * @return int Number of records updated
     *
     * @example
     * // Reassign all pending callbacks to a new approver
     * AuditService::bulkUpdateActorReference(
     *     ProductDispatchCallback::class,
     *     $callbackIds,
     *     'approved_by',
     *     $newApprover,
     *     'Bulk reassignment due to staff change',
     *     ['reason_code' => 'staff_change']
     * );
     */
    public static function bulkUpdateActorReference(
        string $modelClass,
        array $ids,
        string $relationshipName,
        Model $actor,
        ?string $description = null
    ): int {
        return DB::transaction(function () use ($modelClass, $ids, $relationshipName, $actor, $description) {
            $updated = $modelClass::whereIn('id', $ids)->update([
                $relationshipName . '_id' => $actor->id,
                $relationshipName . '_type' => is_object($actor) ? get_class($actor) : gettype($actor),
            ]);

            // Log bulk action
            self::log(
                current_actor(),
                "bulk_update_{$relationshipName}",
                null,
                $description,
                'completed'
            );

            return $updated;
        });
    }

    /**
     * Execute an approved sensitive action.
     *
     * After an approval request has been approved, use this to execute the action
     * and mark it as completed in the audit trail.
     *
     * @param ApprovalRequest $approvalRequest The approval request to execute
     * @param Model|null $approver            The user/employee approving the action
     *
     * @return void
     *
     * @throws \Exception If the request is not approved or already executed
     */
    public static function executeApprovedAction(
        ApprovalRequest $approvalRequest,
        ?Model $approver = null
    ): void {
        if ($approvalRequest->status !== 'approved') {
            throw new \Exception("Action is not approved. Current status: {$approvalRequest->status}");
        }

        if ($approvalRequest->executed_at) {
            throw new \Exception("This action has already been executed.");
        }

        DB::transaction(function () use ($approvalRequest, $approver) {
            // Mark as executed
            $approvalRequest->update([
                'executed_at' => now(),
                'executed_by_id' => $approver?->id,
                'executed_by_type' => $approver ? (is_object($approver) ? get_class($approver) : gettype($approver)) : null,
            ]);

            // Log execution
            $auditable = $approvalRequest->auditable_type::find($approvalRequest->auditable_id);

            self::log(
                $approver,
                $approvalRequest->action . '_executed',
                $auditable,
                'Approved action executed',
                'completed',
                $approvalRequest
            );
        });
    }

    /**
     * Get audit logs for a specific model instance.
     *
     * Retrieve all audit trails related to a particular model.
     *
     * @param Model $auditable
     * @param string|null $action Filter by specific action
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLogsFor(Model $auditable, ?string $action = null, int $limit = 100)
    {
        $query = AuditLog::where('auditable_type', is_object($auditable) ? get_class($auditable) : gettype($auditable))
            ->where('auditable_id', $auditable->id);

        if ($action) {
            $query->where('action', $action);
        }

        return $query->orderBy('logged_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get audit logs by actor (user/employee).
     *
     * Retrieve all actions performed by a specific user or employee.
     *
     * @param Model $causer
     * @param string|null $action Filter by specific action
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLogsByActor(Model $causer, ?string $action = null, int $limit = 100)
    {
        $query = AuditLog::where('causer_type', is_object($causer) ? get_class($causer) : gettype($causer))
            ->where('causer_id', $causer->id);

        if ($action) {
            $query->where('action', $action);
        }

        return $query->orderBy('logged_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get all pending approval requests.
     *
     * @param Model|null $actor Filter by specific actor (optional)
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @deprecated This uses ApprovalRequest model. Use ApprovalAuditRequest instead.
     */
    public static function getPendingApprovals(?Model $actor = null, int $limit = 100)
    {
        $query = ApprovalRequest::where('status', 'pending');

        if ($actor) {
            $query->where('requested_by_type', is_object($actor) ? get_class($actor) : gettype($actor))
                  ->where('requested_by_id', $actor->id);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Log a sync operation on a many-to-many relationship.
     *
     * Tracks which related models were attached, detached, or updated.
     *
     * @param Model $causer             The user/employee performing the sync
     * @param Model $auditable          The model whose relationship is being synced
     * @param string $relationship      The relationship name (e.g., 'roles', 'permissions')
     * @param array $syncData           The data being synced (IDs or [id => attributes])
     * @param array $previousData       The previous data (for comparison)
     * @param string|null $description  Reason for the sync
     * @param string $status            Status: 'completed', 'pending'
     *
     * @return AuditLog
     */
    public static function logSync(
        ?Model $causer,
        Model $auditable,
        string $relationship,
        array $syncData,
        array $previousData = [],
        ?string $description = null,
        string $status = 'completed'
    ): AuditLog {
        // Extract branch_id
        $branchId = null;
        if ($auditable) {
            $branchId = $auditable->branch_id ?? $auditable->branch ?? null;
            if ($branchId instanceof Model) {
                $branchId = $branchId->id;
            }
        }
        if (!$branchId && function_exists('current_branch_id')) {
            $branchId = current_branch_id();
        }

        // Calculate differences
        $attached = array_diff_key($syncData, $previousData);
        $detached = array_diff_key($previousData, $syncData);
        $updated = array_intersect_key($syncData, $previousData);

        $metadata = [
            'relationship' => $relationship,
            'attached' => $attached,
            'detached' => $detached,
            'updated' => $updated,
            'sync_data' => $syncData,
            'previous_data' => $previousData,
            'causer' => $causer ? (is_object($causer) ? get_class($causer) : gettype($causer)) : null,
            'auditable' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : null,
        ];

        return AuditLog::create([
            'branch_id' => $branchId,
            'causer_type' => $causer ? (is_object($causer) ? get_class($causer) : gettype($causer)) : null,
            'causer_id' => $causer?->id,
            'auditable_type' => is_object($auditable) ? get_class($auditable) : gettype($auditable),
            'auditable_id' => $auditable->id,
            'action' => "sync_{$relationship}",
            'description' => $description ?? "Synced {$relationship}",
            'old_values' => $previousData,
            'new_values' => $syncData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'logged_at' => now(),
            'details' => $metadata,
        ]);
    }

    /**
     * Determine if an action requires approval.
     *
     * Can be overridden per application needs.
     * Currently checks if the model/action is in a sensitive actions list.
     *
     * @param Model $causer
     * @param string $action
     *
     * @return bool
     */
    protected static function actionRequiresApproval(Model $causer, string $action): bool
    {
        $sensitiveActions = [
            'delete_product',
            'negative_stock_adjustment',
            'price_drop_below_cost',
            'mass_stock_reset',
            'delete_department',
            'disable_branch',
            'approve_sensitive_leave',
            'sync_roles',
            'sync_permissions',
        ];

        return in_array($action, $sensitiveActions);
    }

    /**
     * Check if a causer can bypass approval for an action.
     *
     * @param Model $causer
     * @param string $action
     *
     * @return bool
     */
    protected static function canBypassApproval(Model $causer, string $action): bool
    {
        // Super admins always bypass
        if (is_super_admin()) {
            return true;
        }

        // Check if model has a canBypassApproval method
        if (method_exists($causer, 'canBypassApproval')) {
            return $causer->canBypassApproval($action);
        }

        return false;
    }
}
