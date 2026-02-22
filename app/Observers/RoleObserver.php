<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;
use App\Services\RolePermissionAuditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2: Role Observer
 *
 * Watches for changes to roles and logs them for audit purposes.
 * Especially monitors protected roles.
 */
class RoleObserver
{
    /**
     * Store original values temporarily (not on model to avoid persistence)
     */
    protected static array $originalValues = [];

    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        RolePermissionAuditService::logRoleCreated($role);
    }

    /**
     * Handle the Role "updating" event.
     * Capture old values before update
     */
    public function updating(Role $role): void
    {
        // Store original values in static array (not on model to avoid DB persistence)
        static::$originalValues[$role->id] = $role->getOriginal();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $oldValues = static::$originalValues[$role->id] ?? [];
        unset(static::$originalValues[$role->id]); // Clean up
        RolePermissionAuditService::logRoleUpdated($role, $oldValues);

        // Alert on protected role changes
        if ($role->is_protected && !empty($oldValues)) {
            Log::alert("Protected role modified: {$role->name}", [
                'role_id' => $role->id,
                'old_values' => $oldValues,
                'new_values' => $role->toArray(),
                'modified_by' => auth()?->id(),
            ]);
        }
    }

    /**
     * Handle the Role "deleting" event.
     * Block deletion of protected roles
     */
    public function deleting(Role $role): ?bool
    {
        // Prevent deletion of protected roles
        if ($role->is_protected) {
            Log::error("Attempted deletion of protected role: {$role->name}", [
                'role_id' => $role->id,
                'attempted_by' => auth()?->id(),
                'ip_address' => request()?->ip(),
            ]);

            RolePermissionAuditService::logRoleDeletionAttempt(
                $role,
                false,
                'Role is protected and cannot be deleted'
            );

            // Return false to prevent deletion
            return false;
        }

        // Check if role has users assigned
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            Log::warning("Attempted deletion of role with assigned users: {$role->name}", [
                'role_id' => $role->id,
                'user_count' => $userCount,
                'attempted_by' => auth()?->id(),
            ]);

            RolePermissionAuditService::logRoleDeletionAttempt(
                $role,
                false,
                "Role is assigned to {$userCount} user(s)"
            );

            return false;
        }

        return null; // Allow deletion
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        RolePermissionAuditService::logRoleDeletionAttempt($role, true);
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        Log::info("Role restored: {$role->name}", [
            'role_id' => $role->id,
            'restored_by' => auth()?->id(),
        ]);
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        Log::warning("Role force deleted: {$role->name}", [
            'role_id' => $role->id,
            'force_deleted_by' => auth()?->id(),
        ]);

        RolePermissionAuditService::logRoleDeletionAttempt($role, true);
    }
}
