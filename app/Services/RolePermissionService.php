<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Models\User;
use App\Models\RoleCategoryConstraint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    const CACHE_TTL = 600;

    const PROTECTED_ROLES = ['Super Admin', 'Managing Director', 'Admin'];

    /**
     * Get current authenticated user
     */
    public static function user(?string $guard = null): ?Model
    {
        if ($guard) {
            return Auth::guard($guard)->user();
        }

        return Auth::guard('web')->user() ?? Auth::guard('web')->user();
    }

    /**
     * Get the current guard name
     */
    public static function getGuardName(): ?string
    {
        if (Auth::guard('web')->check()) {
            return 'employees';
        }
        if (Auth::guard('web')->check()) {
            return 'web';
        }

        return null;
    }

    /**
     * Check if role is protected (system core role)
     */
    public static function isProtectedRole(string $roleName): bool
    {
        $role = Role::where('name', $roleName)->first();

        return $role && $role->is_protected;
    }

    /**
     * Check if permission is protected
     */
    public static function isProtectedPermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();

        return $permission && $permission->is_protected;
    }

    /**
     * Create new role with validation
     *
     * @throws \Exception
     */
    public static function createRole(
        string $name,
        string $guardName,
        array $permissions = [],
        string $description = ''
    ): ?Role {
        // Validate role name
        if (empty(trim($name))) {
            CleanError::show('Role name cannot be empty.');
            return null;
        }

        if (strlen($name) > 255) {
            CleanError::show('Role name too long (max 255 characters).');
            return null;
        }

        // Check if role already exists
        if (Role::where('name', $name)->where('guard_name', $guardName)->exists()) {
            CleanError::show("Role '{$name}' already exists for guard '{$guardName}'.");
            return null;
        }

        // Log role creation
        Log::info('Role created', [
            'name' => $name,
            'guard' => $guardName,
            'created_by' => self::user()?->id,
            'created_at' => now(),
        ]);

        $role = Role::create([
            'name' => $name,
            'guard_name' => $guardName,
            'is_protected' => false,
            'description' => $description,
        ]);

        // Add permissions
        if (! empty($permissions)) {
            $role->givePermissionTo($permissions);
        }

        self::clearCache();

        return $role;
    }

    /**
     * Update role with protection checks
     *
     * @throws \Exception
     */
    public static function updateRole(int $roleId, array $updates): ?Role
    {
        $role = Role::find($roleId);
        if (!$role) {
            CleanError::show('Role not found. Please refresh and try again.');
            return null;
        }

        // Prevent modifying protected roles
        if ($role->is_protected && ! self::isSuperAdmin()) {
            CleanError::show("Cannot modify protected role: {$role->name}.");
            return null;
        }

        // Log update
        Log::info('Role updated', [
            'role_id' => $roleId,
            'role_name' => $role->name,
            'updated_by' => self::user()?->id,
            'changes' => $updates,
            'updated_at' => now(),
        ]);

        $role->update($updates);
        self::clearCache();

        return $role;
    }

    /**
     * Delete role with protection
     *
     * @throws \Exception
     */
    public static function deleteRole(int $roleId): bool
    {
        $role = Role::find($roleId);
        if (!$role) {
            CleanError::show('Role not found. Please refresh and try again.');
            return false;
        }

        // Prevent deleting protected roles
        if ($role->is_protected) {
            CleanError::show("Cannot delete protected role: {$role->name}.");
            return false;
        }

        // Check if role is assigned to users
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            CleanError::show(
                "Cannot delete role assigned to {$userCount} user(s). Remove the role from all users first."
            );
            return false;
        }

        // Log deletion
        Log::warning('Role deleted', [
            'role_id' => $roleId,
            'role_name' => $role->name,
            'deleted_by' => self::user()?->id,
            'deleted_at' => now(),
        ]);

        $deleted = $role->delete();
        self::clearCache();

        return $deleted;
    }

    /**
     * Assign role to user with validation
     *
     * @throws \Exception
     */
    public static function assignRoleToUser(Model $user, string $roleName): bool
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            CleanError::show("Role '{$roleName}' not found.");
            return false;
        }

        // Check authorization
        if (! self::canAssignRole($role)) {
            CleanError::show("You don't have permission to assign '{$roleName}' role.");
            return false;
        }

        // Validate department-role compatibility
        if (! self::validateRoleForDepartment($user, $roleName)) {
            return false;
        }

        // Log assignment
        Log::info('Role assigned to user', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_name' => $roleName,
            'assigned_by' => self::user()?->id,
            'assigned_at' => now(),
        ]);

        $user->assignRole($roleName);
        self::clearCache();

        return true;
    }

    /**
     * Remove role from user with validation
     *
     * @throws \Exception
     */
    public static function removeRoleFromUser(Model $user, string $roleName): bool
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            CleanError::show("Role '{$roleName}' not found.");
            return false;
        }

        // Prevent removing protected roles from last admin
        if ($role->is_protected && $user->hasRole($roleName)) {
            $adminCount = Role::where('name', $roleName)
                ->withCount('users')
                ->first()
                ->users_count ?? 0;

            if ($adminCount <= 1) {
                CleanError::show(
                    "Cannot remove last '{$roleName}' from the system. At least one user must have this role."
                );
                return false;
            }
        }

        // Log removal
        Log::info('Role removed from user', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_name' => $roleName,
            'removed_by' => self::user()?->id,
            'removed_at' => now(),
        ]);

        $user->removeRole($roleName);
        self::clearCache();

        return true;
    }

    /**
     * Check if current user can assign a specific role
     */
    public static function canAssignRole(Role $role): bool
    {
        $user = self::user();

        if (! $user) {
            return false;
        }

        // Only Super Admin can assign protected roles
        if ($role->is_protected && ! $user->hasAnyRole(['Super Admin', 'Managing Director', 'Admin'])) {
            return false;
        }

        return true;
    }

    /**
     * Sync permissions for a role with validation
     *
     * @throws \Exception
     */
    public static function syncRolePermissions(int $roleId, array $permissionIds): ?Role
    {
        $role = Role::find($roleId);
        if (!$role) {
            CleanError::show('Role not found. Please refresh and try again.');
            return null;
        }

        // Prevent modifying protected roles
        if ($role->is_protected && ! self::isSuperAdmin()) {
            CleanError::show("Cannot modify permissions for protected role: {$role->name}.");
            return null;
        }

        $permissions = Permission::whereIn('id', $permissionIds)
            ->where('guard_name', 'web')
            ->get();

        // Log permission sync
        Log::info('Role permissions synced', [
            'role_id' => $roleId,
            'role_name' => $role->name,
            'permission_count' => count($permissionIds),
            'synced_by' => self::user()?->id,
            'synced_at' => now(),
        ]);

        $role->syncPermissions($permissions);
        self::clearCache();

        return $role;
    }

    /**
     * Create permission with validation
     *
     * @throws \Exception
     */
    public static function createPermission(
        string $name,
        string $guardName,
        string $description = '',
        string $category = 'general'
    ): ?Permission {
        if (empty(trim($name))) {
            CleanError::show('Permission name cannot be empty.');
            return null;
        }

        if (strlen($name) > 255) {
            CleanError::show('Permission name too long (max 255 characters).');
            return null;
        }

        // Validate permission naming convention
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            CleanError::show('Permission name must contain only lowercase letters, numbers, and hyphens.');
            return null;
        }

        if (Permission::where('name', $name)->where('guard_name', $guardName)->exists()) {
            CleanError::show("Permission '{$name}' already exists.");
            return null;
        }

        // Log creation
        Log::info('Permission created', [
            'name' => $name,
            'guard' => $guardName,
            'category' => $category,
            'created_by' => self::user()?->id,
            'created_at' => now(),
        ]);

        $permission = Permission::create([
            'name' => $name,
            'guard_name' => $guardName,
            'description' => $description,
            'category' => $category,
            'is_protected' => false,
        ]);

        self::clearCache();

        return $permission;
    }

    /**
     * Delete permission with validation
     *
     * @throws \Exception
     */
    public static function deletePermission(int $permissionId): bool
    {
        $permission = Permission::find($permissionId);
        if (!$permission) {
            CleanError::show('Permission not found. Please refresh and try again.');
            return false;
        }

        // Prevent deleting protected permissions
        if ($permission->is_protected) {
            CleanError::show("Cannot delete protected permission: {$permission->name}.");
            return false;
        }

        // Check if permission is in use
        $roleCount = $permission->roles()->count();
        if ($roleCount > 0) {
            CleanError::show(
                "Cannot delete permission assigned to {$roleCount} role(s). Remove the permission from all roles first."
            );
            return false;
        }

        // Log deletion
        Log::warning('Permission deleted', [
            'permission_id' => $permissionId,
            'permission_name' => $permission->name,
            'deleted_by' => self::user()?->id,
            'deleted_at' => now(),
        ]);

        $deleted = $permission->delete();
        self::clearCache();

        return $deleted;
    }

    /**
     * Get all roles grouped by protection status
     */
    public static function getRolesByProtection(string $guardName = 'web'): array
    {
        return Cache::remember("roles_by_protection_{$guardName}", self::CACHE_TTL, function () use ($guardName) {
            $roles = Role::where('guard_name', $guardName)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

            return [
                'protected' => $roles->where('is_protected', true)->values()->toArray(),
                'custom' => $roles->where('is_protected', false)->values()->toArray(),
            ];
        });
    }

    /**
     * Get all permissions grouped by category
     */
    public static function getPermissionsByCategory(string $guardName = 'web'): array
    {
        return Cache::remember("permissions_by_category_{$guardName}", self::CACHE_TTL, function () use ($guardName) {
            return Permission::where('guard_name', $guardName)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
                ->mapWithKeys(function ($permissions, $category) {
                    return [$category => $permissions->toArray()];
                })
                ->toArray();
        });
    }

    /**
     * Clear all role/permission caches
     */
    public static function clearCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('roles_by_protection_web');
        Cache::forget('permissions_by_category_employees');
        Cache::forget('permissions_by_category_web');
    }

    /**
     * Check if current user is super admin
     * Super Admin role can be in either 'web' or 'employees' guard
     */
    public static function isSuperAdmin(): bool
    {
        // Check web guard first (web users are super admins)
        $webUser = Auth::guard('web')->user();
        if ($webUser && $webUser->hasAnyRole(['Super Admin', 'Managing Director', 'Admin'], 'web')) {
            return true;
        }

        // Check employees guard
        $employeeUser = Auth::guard('web')->user();
        if ($employeeUser && $employeeUser->hasAnyRole(['Super Admin', 'Managing Director', 'Admin'], 'employees')) {
            return true;
        }

        return false;
    }

    /**
     * Get role change history
     */
    public static function getRoleHistory(int $roleId, int $limit = 50): array
    {
        return \DB::table('audit_logs')
            ->where('model_type', Role::class)
            ->where('model_id', $roleId)
            ->where('event', 'updated')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get all users with a specific role
     */
    public static function getUsersWithRole(string $roleName, ?string $guardName = null): array
    {
        $query = Role::where('name', $roleName);

        if ($guardName) {
            $query->where('guard_name', $guardName);
        }

        $role = $query->first();

        if (! $role) {
            return [];
        }

        return $role->users()->get()->toArray();
    }

    /**
     * Get all permissions for a role
     */
    public static function getRolePermissions(int $roleId): array
    {
        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        return $role->permissions()->get()->toArray();
    }

    /**
     * Check if role has all required permissions
     */
    public static function roleHasAllPermissions(int $roleId, array $permissionNames): bool
    {
        $role = Role::find($roleId);
        if (!$role) {
            return false;
        }
        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        foreach ($permissionNames as $permission) {
            if (! in_array($permission, $rolePermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get role by name with guard
     */
    public static function getRoleByName(string $name, string $guardName = 'web'): ?Role
    {
        return Role::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    /**
     * Verify system has at least one super admin
     */
    public static function hasSuperAdmin(): bool
    {
        return Role::whereIn('name', ['Super Admin', 'Managing Director', 'Admin'])
            ->withCount('users')
            ->get()
            ->sum('users_count') > 0;
    }

    /**
     * Get role hierarchy level
     */
    public static function getRoleLevel(string $role): int
    {
        $hierarchy = [
            // Level 5 - Executive
            'Super Admin' => 5,
            'Managing Director' => 5,

            // Level 4 - Admin
            'Admin' => 4,
            'Accounting Manager' => 4,

            // Level 3 - Managers
            'Production Manager' => 3,
            'Sales Manager' => 3,
            'HR Manager' => 3,
            'Inventory Manager' => 3,
            'Head Chef' => 3,
            'Gelato Chef' => 3,
            'Floor Manager' => 3,

            // Level 2 - Supervisors
            'HR Officer' => 2,
            'Accountant' => 2,
            'Cost Accountant' => 2,
            'Till Supervisor' => 2,
            'Cornerstore Supervisor' => 2,
            'Consession Supervisor' => 2,
            'Coffee Barista Trainer' => 2,
            'Lobby Host Supervisor' => 2,
            'Kitchen Assistant Supervisor' => 2,
            'Hot Kitchen Chef' => 2,
            'Pastry Chef' => 2,
            'Assistant Shop Floor Manager' => 2,
            'Inventory Team Lead' => 2,
            'Procurement Officer' => 2,
            'Facility Officer' => 2,
            'Cleaners Supervisor' => 2,
            'Chief Security Officer' => 2,
            'Social Media Manager' => 2,

            // Level 1 - Staff
            'Production Staff' => 1,
            'Sales Staff' => 1,
            'Inventory Staff' => 1,
            'Kitchen Assistant' => 1,
            'Data Processor' => 1,
            'Coffee Barista' => 1,
            'Cashier' => 1,
            'Wait Staff' => 1,
            'Lobby Host' => 1,
            'Consession Attendant' => 1,
            'Store Keeper' => 1,
            'Security Officer' => 1,
            'Driver' => 1,
        ];

        return $hierarchy[$role] ?? 0;
    }

    /**
     * Get role-to-department mappings
     * IMPORTANT: Role names must match database exactly (case-sensitive)
     * but validation is case-insensitive for flexibility
     */
    public static function getRoleDepartmentMappings(): array
    {
        return [
            // All roles are department-agnostic by default
            'Super Admin' => ['*'],
            'Managing Director' => ['*'],
            'Admin' => ['*'],

            'Production Manager' => ['*'],
            'Head Chef' => ['*'],
            'Gelato Chef' => ['*'],
            'Hot Kitchen Chef' => ['*'],
            'Pastry Chef' => ['*'],
            'Kitchen Assistant Supervisor' => ['*'],
            'Kitchen Assistant' => ['*'],
            'Data Processor' => ['*'],
            'Production Staff' => ['*'],

            'Sales Manager' => ['*'],
            'Floor Manager' => ['*'],
            'Assistant Shop Floor Manager' => ['*'],
            'Till Supervisor' => ['*'],
            'Cornerstore Supervisor' => ['*'],
            'Consession Supervisor' => ['*'],
            'Coffee Barista Trainer' => ['*'],
            'Lobby Host Supervisor' => ['*'],
            'Sales Staff' => ['*'],
            'Coffee Barista' => ['*'],
            'Cashier' => ['*'],
            'Wait Staff' => ['*'],
            'Lobby Host' => ['*'],
            'Consession Attendant' => ['*'],

            'Inventory Manager' => ['*'],
            'Inventory Team Lead' => ['*'],
            'Inventory Staff' => ['*'],
            'Procurement Officer' => ['*'],
            'Store Keeper' => ['*'],

            'HR Manager' => ['*'],
            'HR Officer' => ['*'],

            'Accounting Manager' => ['*'],
            'Accountant' => ['*'],
            'Cost Accountant' => ['*'],
            'Facility Officer' => ['*'],
            'Cleaners Supervisor' => ['*'],
            'Chief Security Officer' => ['*'],
            'Security Officer' => ['*'],
            'Social Media Manager' => ['*'],
            'Driver' => ['*'],
        ];
    }

    /**
     * Validate that a role is compatible with user's department
     *
     * @throws \Exception
     */
    public static function validateRoleForDepartment(Model $user, string $roleName): bool
    {
        // Super admins can assign any role regardless of department constraints
        if (self::isSuperAdmin()) {
            return true;
        }

        // If there are no active constraints for this role, allow assignment
        $constraints = RoleCategoryConstraint::active()
            ->whereRaw('LOWER(role_name) = ?', [strtolower($roleName)])
            ->get();

        if ($constraints->isEmpty()) {
            return true;
        }

        if (! isset($user->department_id) || ! $user->department_id) {
            CleanError::show('User must be assigned to a department before assigning roles.');
            return false;
        }

        $department = \App\Models\Department::find($user->department_id);
        if (! $department) {
            CleanError::show('User department not found.');
            return false;
        }

        foreach ($constraints as $constraint) {
            if ($constraint->department_type === 'branch_wide') {
                return true;
            }

            if ($constraint->department_type === 'category_wide') {
                if (! $constraint->category_id || $department->category_id === $constraint->category_id) {
                    return true;
                }
            }

            if ($constraint->department_type === 'specific') {
                if (in_array($department->slug, $constraint->allowed_department_slugs ?? [], true)) {
                    return true;
                }
            }
        }

        CleanError::show("The \"{$roleName}\" role is not allowed for this employee's department.");
        return false;
    }
}
