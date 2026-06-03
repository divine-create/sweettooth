<?php

namespace App\Services;

use App\Models\ApprovalAuditRequest;
use App\Services\InventoryApprovalService;
use App\Services\PurchaseAuditApprovalService;
use App\Services\ProductionApprovalService;
use App\Services\EmployeeApprovalService;
use App\Services\DepartmentApprovalService;
use App\Services\DepartmentCategoryApprovalService;
use App\Services\CallbackApprovalService;
use Illuminate\Support\Str;

/**
 * ApprovalExecutionService
 *
 * Single source of truth for executing an approved (or auto-approved) action.
 *
 * Given an ApprovalAuditRequest, it parses the `action` string and dispatches
 * to the correct handler/service. This is used in two places:
 *
 *  1. AuditManagement\Index  — when an approver clicks "Approve".
 *  2. ApprovalAuditRequest   — when the global approval workflow is disabled,
 *                              requests auto-execute on creation.
 *
 * Action format: "action:model" or "action:model:relationship:id"
 *   e.g. "create:department", "product:create", "update_item:123",
 *        "sync:App\Models\Employee:roles:123", "department_category:update:7"
 */
class ApprovalExecutionService
{
    /**
     * Execute the action described by an approval request.
     *
     * @param ApprovalAuditRequest $request  The (pending) request to execute.
     * @param mixed                $approver  The actor executing the action.
     * @return mixed The affected (auditable) model, or null.
     */
    public static function execute(ApprovalAuditRequest $request, $approver)
    {
        try {
            \Log::info('🔵 [EXECUTE ACTION] Starting action execution', [
                'action' => $request->action,
                'request_id' => $request->id,
            ]);

            // Parse action format: "action:model" or "action:model:relationship:id"
            $parts = explode(':', $request->action);

            $action = $parts[0];
            $model = null;
            $relationship = null;
            $modelId = $request->payload['id'] ?? null;

            // For sync actions with full namespace: "sync:App\Models\Employee:roles:id"
            if ($action === 'sync' && count($parts) >= 3) {
                $model = $parts[1];
                $relationship = $parts[2];
            } else {
                $model = $parts[1] ?? null;
                $relationship = $parts[2] ?? null;
            }

            // Extract ID from action string for inventory operations (e.g., "update_item:123")
            if (count($parts) > 1 && !$modelId && in_array($action, ['delete_item', 'delete_purchase', 'update_item'])) {
                $modelId = $parts[1];
            }

            $auditable = match ($action) {
                'create' => self::handleCreateAction($model, $request->payload),
                'update' => self::handleUpdateAction($model, $modelId, $request->payload),
                'delete' => self::handleDeleteAction($model, $modelId),
                'sync' => self::handleSyncAction($model, $modelId, $relationship, $request->payload),
                'stock_adjustment' => InventoryApprovalService::executeStockAdjustment($request),
                'create_item' => InventoryApprovalService::executeItemCreation($request),
                'update_item' => InventoryApprovalService::executeItemUpdate($request),
                'delete_item' => InventoryApprovalService::executeItemDeletion($request, $approver),
                'create_purchase' => InventoryApprovalService::executePurchaseCreation($request, $approver),
                'delete_purchase' => InventoryApprovalService::executePurchaseDeletion($request, $approver),
                'approve_purchase' => PurchaseAuditApprovalService::approvePurchase($request, $approver),
                // Production module handlers
                'product' => self::handleProductAction($request),
                'recipe' => self::handleRecipeAction($request),
                // Employee module handlers
                'employee' => self::handleEmployeeAction($request, $approver),
                // Department module handlers
                'department' => self::handleDepartmentAction($request, $approver),
                'department_category' => self::handleDepartmentCategoryAction($request, $approver),
                // Callback handlers
                'callback' => self::handleCallbackAction($request, $approver),
                default => throw new \Exception("Unknown action type: {$action}"),
            };

            \Log::info('✅ [EXECUTE ACTION] Action executed successfully', [
                'action' => $action,
                'auditable_type' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : 'null',
            ]);

            return $auditable;
        } catch (\Exception $e) {
            \Log::error('❌ [EXECUTE ACTION] Action execution failed', [
                'action' => $request->action ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw new \Exception("Failed to execute action '{$request->action}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle create actions for any model.
     */
    private static function handleCreateAction(string $modelName, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass) {
            return null;
        }

        try {
            $modelClass = "\\$modelClass";
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $createData = array_intersect_key($payload, array_flip($fillable));

            if (isset($createData['name']) && in_array('slug', $fillable)) {
                $createData['slug'] = Str::slug($createData['name']);
            }

            $auditable = $modelClass::create($createData);

            if (isset($payload['selectedRoles'])) {
                self::syncRolesFromPayload($auditable, $payload['selectedRoles']);
            }

            if (isset($payload['selectedPermissions'])) {
                self::syncPermissionsFromPayload($auditable, $payload['selectedPermissions']);
            }

            foreach ($payload as $key => $value) {
                if (str_starts_with($key, 'selected') && is_array($value)) {
                    $relationshipName = lcfirst(str_replace('selected', '', $key));
                    if (method_exists($auditable, $relationshipName) && $relationshipName !== 'roles' && $relationshipName !== 'permissions') {
                        self::syncRelationship($auditable, $relationshipName, $value);
                    }
                }
            }

            return $auditable;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create {$modelClass}: " . $e->getMessage());
        }
    }

    /**
     * Handle update actions for any model.
     */
    private static function handleUpdateAction(string $modelName, ?string $modelId, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass || !$modelId) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if (!$auditable) {
            return null;
        }

        try {
            $fillable = $auditable->getFillable();
            $updateData = array_intersect_key($payload, array_flip($fillable));

            if (!empty($updateData)) {
                $auditable->update($updateData);
            }

            if (isset($payload['selectedRoles'])) {
                self::syncRolesFromPayload($auditable, $payload['selectedRoles']);
            }

            if (isset($payload['selectedPermissions'])) {
                self::syncPermissionsFromPayload($auditable, $payload['selectedPermissions']);
            }

            foreach ($payload as $key => $value) {
                if (str_starts_with($key, 'selected') && is_array($value)) {
                    $relationshipName = lcfirst(str_replace('selected', '', $key));
                    if (method_exists($auditable, $relationshipName) && $relationshipName !== 'roles' && $relationshipName !== 'permissions') {
                        self::syncRelationship($auditable, $relationshipName, $value);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to update {$modelClass}: " . $e->getMessage());
        }

        return $auditable;
    }

    /**
     * Handle delete actions for any model.
     */
    private static function handleDeleteAction(string $modelName, ?string $modelId)
    {
        $modelClass = $modelName;
        if (!$modelClass || !$modelId) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if ($auditable) {
            $auditable->delete();
        }

        return $auditable;
    }

    /**
     * Sync roles for an auditable model (handles both role IDs and names).
     */
    private static function syncRolesFromPayload($auditable, $roleData)
    {
        if (empty($roleData)) {
            return;
        }

        $roleNames = [];
        $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'employees';

        foreach ($roleData as $role) {
            if (is_numeric($role)) {
                $roleObj = \Spatie\Permission\Models\Role::where('id', $role)
                    ->where('guard_name', $guardName)
                    ->first();
                if ($roleObj) {
                    $roleNames[] = $roleObj->name;
                }
            } else {
                $roleNames[] = $role;
            }
        }

        if (!empty($roleNames)) {
            $auditable->syncRoles($roleNames);
        }
    }

    /**
     * Sync permissions for an auditable model (handles both IDs and names).
     */
    private static function syncPermissionsFromPayload($auditable, $permissionData)
    {
        if (empty($permissionData)) {
            return;
        }

        $permissionNames = [];
        $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'web';

        foreach ($permissionData as $permission) {
            if (is_numeric($permission)) {
                $permissionObj = \Spatie\Permission\Models\Permission::where('id', $permission)
                    ->where('guard_name', $guardName)
                    ->first();
                if ($permissionObj) {
                    $permissionNames[] = $permissionObj->name;
                }
            } else {
                $permissionNames[] = $permission;
            }
        }

        if (!empty($permissionNames)) {
            $auditable->syncPermissions($permissionNames);
        }
    }

    /**
     * Sync a generic many-to-many relationship.
     */
    private static function syncRelationship($auditable, string $relationshipName, array $syncData)
    {
        if (empty($syncData)) {
            return;
        }

        try {
            if (method_exists($auditable, $relationshipName)) {
                $auditable->{$relationshipName}()->sync($syncData);
            }
        } catch (\Exception $e) {
            // Silently fail for relationships that don't exist.
        }
    }

    /**
     * Handle sync actions for many-to-many relationships.
     */
    private static function handleSyncAction(string $modelName, int|string|null $modelId, ?string $relationship, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass || !$modelId || !$relationship) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if (!$auditable) {
            return null;
        }

        $syncData = $payload['sync_data'] ?? $payload[$relationship] ?? [];

        if (empty($syncData)) {
            return $auditable;
        }

        try {
            if ($relationship === 'roles') {
                $roleNames = [];
                $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'employees';

                foreach ($syncData as $role) {
                    if (is_numeric($role)) {
                        $roleObj = \Spatie\Permission\Models\Role::where('id', $role)
                            ->where('guard_name', $guardName)
                            ->first();
                        if ($roleObj) {
                            $roleNames[] = $roleObj->name;
                        }
                    } else {
                        $roleNames[] = $role;
                    }
                }
                if (!empty($roleNames)) {
                    $auditable->syncRoles($roleNames);
                }
            } else if ($relationship === 'permissions') {
                $auditable->syncPermissions($syncData);
            } else {
                if (method_exists($auditable, $relationship)) {
                    $auditable->{$relationship}()->sync($syncData);
                } else {
                    $auditable->$relationship()->sync($syncData);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to sync {$relationship}: " . $e->getMessage());
        }

        return $auditable;
    }

    /**
     * Handle product-related approval actions (create, edit, delete).
     */
    private static function handleProductAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';

        return match ($subAction) {
            'create' => ProductionApprovalService::executeProductCreation($request),
            'edit' => ProductionApprovalService::executeProductUpdate($request),
            'delete' => ProductionApprovalService::executeProductDeletion($request),
            'bulk_delete' => ProductionApprovalService::executeProductBulkDeletion($request),
            'assignments' => ProductionApprovalService::executeProductAssignments($request),
            default => throw new \Exception("Unknown product action: {$subAction}"),
        };
    }

    /**
     * Handle recipe-related approval actions (create, edit, delete).
     */
    private static function handleRecipeAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create_recipe';

        return match ($subAction) {
            'create_recipe' => ProductionApprovalService::executeRecipeCreation($request),
            'edit_recipe' => ProductionApprovalService::executeRecipeUpdate($request),
            'delete_recipe' => ProductionApprovalService::executeRecipeDeletion($request),
            'bulk_delete_recipe' => ProductionApprovalService::executeRecipeBulkDeletion($request),
            default => throw new \Exception("Unknown recipe action: {$subAction}"),
        };
    }

    /**
     * Handle employee-related approval actions.
     */
    private static function handleEmployeeAction(ApprovalAuditRequest $request, $approver)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';

        return match ($subAction) {
            'create' => EmployeeApprovalService::executeCreate($request, $approver),
            'update' => EmployeeApprovalService::executeUpdate($request, $approver),
            'delete' => EmployeeApprovalService::executeDelete($request, $approver),
            'sync_roles' => EmployeeApprovalService::executeRoleSync($request, $approver),
            'sync_permissions' => EmployeeApprovalService::executePermissionSync($request, $approver),
            default => throw new \Exception("Unknown employee action: {$subAction}"),
        };
    }

    /**
     * Handle department-related approval actions.
     */
    private static function handleDepartmentAction(ApprovalAuditRequest $request, $approver)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';

        return match ($subAction) {
            'create' => DepartmentApprovalService::executeCreate($request, $approver),
            'update' => DepartmentApprovalService::executeUpdate($request, $approver),
            'delete' => DepartmentApprovalService::executeDelete($request, $approver),
            default => throw new \Exception("Unknown department action: {$subAction}"),
        };
    }

    /**
     * Handle department category-related approval actions.
     */
    private static function handleDepartmentCategoryAction(ApprovalAuditRequest $request, $approver)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';

        return match ($subAction) {
            'create' => DepartmentCategoryApprovalService::executeCreate($request, $approver),
            'update' => DepartmentCategoryApprovalService::executeUpdate($request, $approver),
            'delete' => DepartmentCategoryApprovalService::executeDelete($request, $approver),
            default => throw new \Exception("Unknown department category action: {$subAction}"),
        };
    }

    /**
     * Handle callback-related approval actions (inventory, production).
     */
    private static function handleCallbackAction(ApprovalAuditRequest $request, $approver)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'inventory';

        return match ($subAction) {
            'inventory' => CallbackApprovalService::executeInventoryCallback($request, $approver),
            'production' => CallbackApprovalService::executeProductionCallback($request, $approver),
            default => throw new \Exception("Unknown callback action: {$subAction}"),
        };
    }
}
