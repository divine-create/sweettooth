<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Employee;
use App\Models\User;

class RolePermission
{
    // Cache duration in seconds (10 minutes)
    private const CACHE_TTL = 600;

    /**
     * Get the current authenticated user (either User or Employee)
     */
    public static function user(?string $guard = null)
    {
        if ($guard) {
            return Auth::guard($guard)->user();
        }

        // Unified system - all users authenticated via web guard
        return Auth::guard('web')->user();
    }

    /**
     * Get the guard name for the current authenticated user
     */
    public static function getGuardName(): ?string
    {
        // Unified system - all users authenticated via web guard
        if (Auth::guard('web')->check()) {
            return 'web';
        }

        return null;
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole(string $role, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole(array $roles, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has all of the specified roles
     */
    public static function hasAllRoles(array $roles, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasAllRoles($roles);
    }

    /**
     * Check if user has a specific permission
     */
    public static function hasPermission(string $permission, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasPermissionTo($permission);
    }

    /**
     * Check if user has any of the specified permissions
     */
    public static function hasAnyPermission(array $permissions, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if user has all of the specified permissions
     */
    public static function hasAllPermissions(array $permissions, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        return $user->hasAllPermissions($permissions);
    }

    /**
     * Check if user is a super admin
     */
    public static function isSuperAdmin(?string $guard = null): bool
    {
        return self::hasAnyRole(['Super Admin', 'Managing Director', 'Admin'], $guard);
    }

    /**
     * Check if user is an admin
     */
    public static function isAdmin(?string $guard = null): bool
    {
        return self::hasAnyRole(['Admin', 'Super Admin'], $guard);
    }

    /**
     * Check if user is a managing director role
     */
    public static function isManagingDirector(?string $guard = null): bool
    {
        return self::hasRole('Managing Director', $guard);
    }

    /**
     * Check if user is a manager (any manager role)
     */
    public static function isManager(?string $guard = null): bool
    {
        $managerRoles = [
            'Managing Director',
            'Admin',
            'Accounting Manager',
            'Production Manager',
            'Sales Manager',
            'HR Manager',
            'Inventory Manager',
            'Head Chef',
            'Gelato Chef',
            'Floor Manager',
        ];

        return self::hasAnyRole($managerRoles, $guard);
    }

    /**
     * Check if user is an HR Manager
     */
    public static function isHRManager(?string $guard = null): bool
    {
        return self::hasRole('HR Manager', $guard);
    }

    /**
     * Check if user is an Accounting Manager
     */
    public static function isAccountingManager(?string $guard = null): bool
    {
        return self::hasRole('Accounting Manager', $guard);
    }

    /**
     * Check if user is a Production Helper
     */
    public static function isProductionHelper(?string $guard = null): bool
    {
        return self::hasRole('Production Helper', $guard);
    }

    /**
     * Check if user has cross-department access (for accounting purposes)
     */
    public static function hasCrossDepartmentAccess(?string $guard = null): bool
    {
        return self::isAccountingManager($guard) || self::isSuperAdmin($guard) || self::isManagingDirector($guard);
    }

    /**
     * Check if user is a supervisor/head
     */
    public static function isSupervisor(?string $guard = null): bool
    {
        $supervisorRoles = [
            'HR Officer',
            'Accountant',
            'Cost Accountant',
            'Till Supervisor',
            'Cornerstore Supervisor',
            'Consession Supervisor',
            'Coffee Barista Trainer',
            'Lobby Host Supervisor',
            'Kitchen Assistant Supervisor',
            'Hot Kitchen Chef',
            'Pastry Chef',
            'Assistant Shop Floor Manager',
            'Inventory Team Lead',
            'Procurement Officer',
            'Facility Officer',
            'Cleaners Supervisor',
            'Chief Security Officer',
            'Social Media Manager',
        ];

        return self::hasAnyRole($supervisorRoles, $guard);
    }

    /**
     * Check if user is staff level
     */
    public static function isStaff(?string $guard = null): bool
    {
        $staffRoles = [
            'Production Staff',
            'Sales Staff',
            'Inventory Staff',
            'Kitchen Assistant',
            'Data Processor',
            'Coffee Barista',
            'Cashier',
            'Wait Staff',
            'Lobby Host',
            'Consession Attendant',
            'Store Keeper',
            'Security Officer',
            'Driver',
        ];

        return self::hasAnyRole($staffRoles, $guard);
    }

    /**
     * Get all roles for the current user
     */
    public static function getUserRoles(?string $guard = null): array
    {
        $user = self::user($guard);

        if (!$user) {
            return [];
        }

        return $user->getRoleNames()->toArray();
    }

    /**
     * Get all permissions for the current user
     */
    public static function getUserPermissions(?string $guard = null): array
    {
        $user = self::user($guard);

        if (!$user) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Get all direct permissions (not through roles) for the current user
     */
    public static function getDirectPermissions(?string $guard = null): array
    {
        $user = self::user($guard);

        if (!$user) {
            return [];
        }

        return $user->getDirectPermissions()->pluck('name')->toArray();
    }

    /**
     * Get all permissions through roles
     */
    public static function getPermissionsViaRoles(?string $guard = null): array
    {
        $user = self::user($guard);

        if (!$user) {
            return [];
        }

        return $user->getPermissionsViaRoles()->pluck('name')->toArray();
    }

    /**
     * Get all available roles for a specific guard (with caching)
     */
    public static function getAllRoles(?string $guard = null): array
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        $cacheKey = "roles_{$guardName}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($guardName) {
            return Role::where('guard_name', $guardName)
                ->orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'permissions_count' => $role->permissions()->count(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get all available permissions for a specific guard (with caching)
     */
    public static function getAllPermissions(?string $guard = null): array
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        $cacheKey = "permissions_{$guardName}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($guardName) {
            return Permission::where('guard_name', $guardName)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        });
    }

    /**
     * Get permissions grouped by category
     */
    public static function getPermissionsByCategory(?string $guard = null): array
    {
        $permissions = self::getAllPermissions($guard);
        $grouped = [];

        foreach ($permissions as $permission) {
            // Extract category from permission name (e.g., 'view-employees' -> 'employees')
            $parts = explode('-', $permission);
            $action = $parts[0] ?? 'other';
            $resource = implode('-', array_slice($parts, 1)) ?: 'general';

            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [];
            }

            $grouped[$resource][] = $permission;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Assign role to a user
     */
    public static function assignRole($user, string $role): bool
    {
        try {
            $user->assignRole($role);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove role from a user
     */
    public static function removeRole($user, string $role): bool
    {
        try {
            $user->removeRole($role);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync roles for a user (removes all current roles and assigns new ones)
     */
    public static function syncRoles($user, array $roles): bool
    {
        try {
            $user->syncRoles($roles);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Give permission to a user
     */
    public static function givePermission($user, string $permission): bool
    {
        try {
            $user->givePermissionTo($permission);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Revoke permission from a user
     */
    public static function revokePermission($user, string $permission): bool
    {
        try {
            $user->revokePermissionTo($permission);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync permissions for a user
     */
    public static function syncPermissions($user, array $permissions): bool
    {
        try {
            $user->syncPermissions($permissions);
            self::clearUserCache($user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a role exists
     */
    public static function roleExists(string $role, ?string $guard = null): bool
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        return Role::where('name', $role)
            ->where('guard_name', $guardName)
            ->exists();
    }

    /**
     * Check if a permission exists
     */
    public static function permissionExists(string $permission, ?string $guard = null): bool
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        return Permission::where('name', $permission)
            ->where('guard_name', $guardName)
            ->exists();
    }

    /**
     * Get a role by name
     */
    public static function getRole(string $name, ?string $guard = null): ?Role
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        return Role::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    /**
     * Get a permission by name
     */
    public static function getPermission(string $name, ?string $guard = null): ?Permission
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        return Permission::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    /**
     * Get users with a specific role
     */
    public static function getUsersWithRole(string $role, ?string $guard = null): array
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        $modelClass = $guardName === 'web' ? User::class : Employee::class;

        return $modelClass::role($role)->get()->toArray();
    }

    /**
     * Get users with a specific permission
     */
    public static function getUsersWithPermission(string $permission, ?string $guard = null): array
    {
        $guardName = $guard ?? self::getGuardName() ?? 'employees';

        $modelClass = $guardName === 'web' ? User::class : Employee::class;

        return $modelClass::permission($permission)->get()->toArray();
    }

    /**
     * Clear all role/permission caches
     */
    public static function clearCache(): void
    {
        // Clear Spatie's permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear our custom caches
        Cache::forget('roles_employees');
        Cache::forget('roles_web');
        Cache::forget('permissions_employees');
        Cache::forget('permissions_web');
    }

    /**
     * Clear cache for a specific user
     */
    private static function clearUserCache($user): void
    {
        // Spatie automatically clears its cache when roles/permissions change
        // But we can force it
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Get hierarchical level of a role (for authorization logic)
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
     * Check if user's role level is higher than specified level
     */
    public static function hasRoleLevel(int $minLevel, ?string $guard = null): bool
    {
        $user = self::user($guard);

        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoleNames();

        foreach ($userRoles as $role) {
            if (self::getRoleLevel($role) >= $minLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can manage another user (based on role hierarchy)
     */
    public static function canManageUser($targetUser, ?string $guard = null): bool
    {
        $currentUser = self::user($guard);

        if (!$currentUser || !$targetUser) {
            return false;
        }

        // Super admins and Managing Directors can manage anyone
        if (self::isSuperAdmin() || self::isManagingDirector()) {
            return true;
        }

        // Get highest role level for both users
        $currentUserLevel = 0;
        foreach ($currentUser->getRoleNames() as $role) {
            $currentUserLevel = max($currentUserLevel, self::getRoleLevel($role));
        }

        $targetUserLevel = 0;
        foreach ($targetUser->getRoleNames() as $role) {
            $targetUserLevel = max($targetUserLevel, self::getRoleLevel($role));
        }

        // Can manage if current user has higher level
        return $currentUserLevel > $targetUserLevel;
    }

    /**
     * Get role description (helpful for UI)
     */
    public static function getRoleDescription(string $role): string
    {
        $descriptions = [
            'Super Admin' => 'Full system access with all permissions',
            'Admin' => 'Administrative access to system settings',
            'Managing Director' => 'Executive level with full operational control',
            'Head of Production' => 'Oversees production operations',
            'Sales Manager' => 'Manages sales operations and staff',
            'HR Manager' => 'Manages human resources and employee operations',
            'Inventory Manager' => 'Manages inventory and stock control',
            'Accounting Manager' => 'Manages accounting operations',
            'Production Supervisor' => 'Supervises production team',
            'Sales Supervisor' => 'Supervises sales team',
            'Inventory Supervisor' => 'Supervises inventory team',
            'HR Officer' => 'Handles HR administrative tasks',
            'Accountant' => 'Accounting operations and reporting',
            'Production Staff' => 'Production team member',
            'Sales Staff' => 'Sales team member',
            'Inventory Staff' => 'Inventory team member',
        ];

        return $descriptions[$role] ?? 'No description available';
    }

    /**
     * Check if user has access to a specific module
     */
    public static function canAccessModule(string $module, ?string $guard = null): bool
    {
        $modulePermissions = [
            'production' => ['view-production', 'manage-production'],
            'sales' => ['view-sales', 'process-sales', 'manage-sales'],
            'inventory' => ['view-inventory', 'manage-inventory'],
            'employees' => ['view-employees', 'manage-employees'],
            'reports' => ['view-reports', 'view-analytics'],
            'settings' => ['manage-settings'],
        ];

        if (!isset($modulePermissions[$module])) {
            return false;
        }

        return self::hasAnyPermission($modulePermissions[$module], $guard);
    }
}
