<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;
use App\Services\RolePermissionAuditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2: Permission Observer
 *
 * Watches for changes to permissions and logs them for audit purposes.
 * Especially monitors protected permissions.
 */
class PermissionObserver
{
    /**
     * Store original values temporarily (not on model to avoid persistence)
     */
    protected static array $originalValues = [];

    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        RolePermissionAuditService::logPermissionCreated($permission);
    }

    /**
     * Handle the Permission "updating" event.
     * Capture old values before update
     */
    public function updating(Permission $permission): void
    {
        // Store original values in static array (not on model to avoid DB persistence)
        static::$originalValues[$permission->id] = $permission->getOriginal();
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        $oldValues = static::$originalValues[$permission->id] ?? [];
        unset(static::$originalValues[$permission->id]); // Clean up
        RolePermissionAuditService::logPermissionUpdated($permission, $oldValues);

        // Alert on protected permission changes
        if (($permission->is_protected ?? false) && !empty($oldValues)) {
            Log::alert("Protected permission modified: {$permission->name}", [
                'permission_id' => $permission->id,
                'old_values' => $oldValues,
                'new_values' => $permission->toArray(),
                'modified_by' => auth()?->id(),
            ]);
        }
    }

    /**
     * Handle the Permission "deleting" event.
     * Block deletion of protected permissions
     */
    public function deleting(Permission $permission): ?bool
    {
        // Prevent deletion of protected permissions
        if ($permission->is_protected ?? false) {
            Log::error("Attempted deletion of protected permission: {$permission->name}", [
                'permission_id' => $permission->id,
                'attempted_by' => auth()?->id(),
                'ip_address' => request()?->ip(),
            ]);

            RolePermissionAuditService::logPermissionDeletionAttempt(
                $permission,
                false,
                'Permission is protected and cannot be deleted'
            );

            return false;
        }

        // Check if permission is assigned to roles
        $roleCount = $permission->roles()->count();
        if ($roleCount > 0) {
            Log::warning("Attempted deletion of permission with assigned roles: {$permission->name}", [
                'permission_id' => $permission->id,
                'role_count' => $roleCount,
                'attempted_by' => auth()?->id(),
            ]);

            RolePermissionAuditService::logPermissionDeletionAttempt(
                $permission,
                false,
                "Permission is assigned to {$roleCount} role(s)"
            );

            return false;
        }

        return null; // Allow deletion
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        RolePermissionAuditService::logPermissionDeletionAttempt($permission, true);
    }

    /**
     * Handle the Permission "restored" event.
     */
    public function restored(Permission $permission): void
    {
        Log::info("Permission restored: {$permission->name}", [
            'permission_id' => $permission->id,
            'restored_by' => auth()?->id(),
        ]);
    }

    /**
     * Handle the Permission "force deleted" event.
     */
    public function forceDeleted(Permission $permission): void
    {
        Log::warning("Permission force deleted: {$permission->name}", [
            'permission_id' => $permission->id,
            'force_deleted_by' => auth()?->id(),
        ]);

        RolePermissionAuditService::logPermissionDeletionAttempt($permission, true);
    }
}
