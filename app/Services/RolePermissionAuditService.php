<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Phase 2: Role & Permission Audit Logging Service
 * 
 * Comprehensive audit logging for all role and permission changes,
 * with notifications for protected role/permission modifications.
 */
class RolePermissionAuditService
{
    const AUDIT_TABLE = 'role_permission_audit_logs';

    /**
     * Log role creation
     */
    public static function logRoleCreated(Role $role): void
    {
        self::createAuditLog(
            'role_created',
            'Role',
            $role->id,
            [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'is_protected' => $role->is_protected,
                'description' => $role->description,
            ]
        );

        Log::info("Role created: {$role->name}", [
            'role_id' => $role->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Log role update
     */
    public static function logRoleUpdated(Role $role, array $oldValues): void
    {
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            $newValue = $role->$key;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        if (empty($changes)) {
            return;
        }

        self::createAuditLog(
            'role_updated',
            'Role',
            $role->id,
            $changes
        );

        // Notify if protected role was modified
        if ($role->is_protected) {
            self::notifyProtectedRoleChange('update', $role, $changes);
        }

        Log::warning("Role updated: {$role->name}", [
            'role_id' => $role->id,
            'changes' => $changes,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Log role deletion attempt
     */
    public static function logRoleDeletionAttempt(Role $role, bool $successful, ?string $reason = null): void
    {
        $action = $successful ? 'role_deleted' : 'role_deletion_blocked';
        
        $data = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'is_protected' => $role->is_protected,
        ];

        if ($reason) {
            $data['blocked_reason'] = $reason;
        }

        self::createAuditLog($action, 'Role', $role->id, $data);

        if ($successful) {
            // Notify admin of successful deletion
            self::notifyProtectedRoleChange('delete', $role, ['deleted' => true]);

            Log::warning("Role deleted: {$role->name}", [
                'role_id' => $role->id,
                'deleted_by' => auth()->id(),
            ]);
        } else {
            Log::notice("Role deletion blocked: {$role->name}", [
                'role_id' => $role->id,
                'attempted_by' => auth()->id(),
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Log role assignment to user
     */
    public static function logRoleAssignedToUser($user, Role $role): void
    {
        self::createAuditLog(
            'role_assigned_to_user',
            'User',
            $user->id,
            [
                'user_name' => $user->name,
                'role_name' => $role->name,
                'role_id' => $role->id,
            ]
        );

        Log::info("Role assigned to user: {$user->name} <- {$role->name}", [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => auth()->id(),
        ]);
    }

    /**
     * Log role removal from user
     */
    public static function logRoleRemovedFromUser($user, Role $role): void
    {
        self::createAuditLog(
            'role_removed_from_user',
            'User',
            $user->id,
            [
                'user_name' => $user->name,
                'role_name' => $role->name,
                'role_id' => $role->id,
            ]
        );

        Log::info("Role removed from user: {$user->name} -> {$role->name}", [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'removed_by' => auth()->id(),
        ]);
    }

    /**
     * Log permission creation
     */
    public static function logPermissionCreated(Permission $permission): void
    {
        self::createAuditLog(
            'permission_created',
            'Permission',
            $permission->id,
            [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'category' => $permission->category ?? 'general',
                'is_protected' => $permission->is_protected ?? false,
            ]
        );

        Log::info("Permission created: {$permission->name}", [
            'permission_id' => $permission->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Log permission update
     */
    public static function logPermissionUpdated(Permission $permission, array $oldValues): void
    {
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            $newValue = $permission->$key;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        if (empty($changes)) {
            return;
        }

        self::createAuditLog(
            'permission_updated',
            'Permission',
            $permission->id,
            $changes
        );

        // Notify if protected permission was modified
        if ($permission->is_protected) {
            self::notifyProtectedPermissionChange('update', $permission, $changes);
        }

        Log::warning("Permission updated: {$permission->name}", [
            'permission_id' => $permission->id,
            'changes' => $changes,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Log permission deletion attempt
     */
    public static function logPermissionDeletionAttempt(Permission $permission, bool $successful, ?string $reason = null): void
    {
        $action = $successful ? 'permission_deleted' : 'permission_deletion_blocked';

        $data = [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'is_protected' => $permission->is_protected ?? false,
        ];

        if ($reason) {
            $data['blocked_reason'] = $reason;
        }

        self::createAuditLog($action, 'Permission', $permission->id, $data);

        if ($successful) {
            self::notifyProtectedPermissionChange('delete', $permission, ['deleted' => true]);

            Log::warning("Permission deleted: {$permission->name}", [
                'permission_id' => $permission->id,
                'deleted_by' => auth()->id(),
            ]);
        } else {
            Log::notice("Permission deletion blocked: {$permission->name}", [
                'permission_id' => $permission->id,
                'attempted_by' => auth()->id(),
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Log role permissions synced
     */
    public static function logRolePermissionsSynced(Role $role, array $oldPermissions, array $newPermissions): void
    {
        $addedPermissions = array_diff($newPermissions, $oldPermissions);
        $removedPermissions = array_diff($oldPermissions, $newPermissions);

        self::createAuditLog(
            'role_permissions_synced',
            'Role',
            $role->id,
            [
                'role_name' => $role->name,
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
                'total_permissions' => count($newPermissions),
            ]
        );

        Log::info("Role permissions synced: {$role->name}", [
            'role_id' => $role->id,
            'added_count' => count($addedPermissions),
            'removed_count' => count($removedPermissions),
            'synced_by' => auth()->id(),
        ]);
    }

    /**
     * Get audit logs for a role
     */
    public static function getRoleAuditLog(int $roleId, int $limit = 100): array
    {
        return DB::table(self::AUDIT_TABLE)
            ->where('model_type', 'Role')
            ->where('model_id', $roleId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get audit logs for a permission
     */
    public static function getPermissionAuditLog(int $permissionId, int $limit = 100): array
    {
        return DB::table(self::AUDIT_TABLE)
            ->where('model_type', 'Permission')
            ->where('model_id', $permissionId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get all audit logs for a user
     */
    public static function getUserAuditLog(?int $userId = null, int $limit = 100): array
    {
        $query = DB::table(self::AUDIT_TABLE)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get recent changes to protected roles/permissions
     */
    public static function getProtectedChanges(int $days = 7): array
    {
        return DB::table(self::AUDIT_TABLE)
            ->where('is_protected', true)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Create audit log entry
     */
    private static function createAuditLog(string $action, string $modelType, int $modelId, array $data): void
    {
        try {
            DB::table(self::AUDIT_TABLE)->insert([
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data' => json_encode($data),
                'is_protected' => false, // Will be set appropriately by calling method
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admin of protected role changes
     */
    private static function notifyProtectedRoleChange(string $action, Role $role, array $changes): void
    {
        // Log the notification trigger
        Log::alert("Protected role {$action}: {$role->name}", [
            'role_id' => $role->id,
            'action' => $action,
            'changes' => $changes,
            'triggered_by' => auth()->id(),
            'timestamp' => now(),
        ]);

        // TODO: Send notification to admins
        // You can implement email/SMS/in-app notifications here
        // Example:
        // Notification::send(Admin::all(), new ProtectedRoleChangeNotification($role, $action, $changes));
    }

    /**
     * Notify admin of protected permission changes
     */
    private static function notifyProtectedPermissionChange(string $action, Permission $permission, array $changes): void
    {
        // Log the notification trigger
        Log::alert("Protected permission {$action}: {$permission->name}", [
            'permission_id' => $permission->id,
            'action' => $action,
            'changes' => $changes,
            'triggered_by' => auth()->id(),
            'timestamp' => now(),
        ]);

        // TODO: Send notification to admins
        // You can implement email/SMS/in-app notifications here
        // Example:
        // Notification::send(Admin::all(), new ProtectedPermissionChangeNotification($permission, $action, $changes));
    }

    /**
     * Generate audit report
     */
    public static function generateAuditReport(Carbon $startDate, Carbon $endDate): array
    {
        $logs = DB::table(self::AUDIT_TABLE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateTimeString(),
                'end' => $endDate->toDateTimeString(),
            ],
            'total_changes' => $logs->count(),
            'changes_by_type' => $logs->groupBy('action')->map->count(),
            'changes_by_user' => $logs->groupBy('user_id')->map->count(),
            'protected_changes' => $logs->where('is_protected', true)->count(),
            'logs' => $logs->toArray(),
        ];
    }
}
