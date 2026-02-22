<?php

namespace App\Providers;

use App\Helpers\RolePermission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class RolePermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom Blade directives for role/permission checks

        /**
         * @role directive - Check if user has a specific role
         * Usage: @role('Admin') ... @endrole
         */
        Blade::if('role', function (string $role, ?string $guard = null) {
            return RolePermission::hasRole($role, $guard);
        });

        /**
         * @anyrole directive - Check if user has any of the specified roles
         * Usage: @anyrole(['Admin', 'Manager']) ... @endanyrole
         */
        Blade::if('anyrole', function (array $roles, ?string $guard = null) {
            return RolePermission::hasAnyRole($roles, $guard);
        });

        /**
         * @allroles directive - Check if user has all of the specified roles
         * Usage: @allroles(['Admin', 'Manager']) ... @endallroles
         */
        Blade::if('allroles', function (array $roles, ?string $guard = null) {
            return RolePermission::hasAllRoles($roles, $guard);
        });

        /**
         * @permission directive - Check if user has a specific permission
         * Usage: @permission('edit-employees') ... @endpermission
         */
        Blade::if('permission', function (string $permission, ?string $guard = null) {
            return RolePermission::hasPermission($permission, $guard);
        });

        /**
         * @anypermission directive - Check if user has any of the specified permissions
         * Usage: @anypermission(['edit-employees', 'view-employees']) ... @endanypermission
         */
        Blade::if('anypermission', function (array $permissions, ?string $guard = null) {
            return RolePermission::hasAnyPermission($permissions, $guard);
        });

        /**
         * @allpermissions directive - Check if user has all of the specified permissions
         * Usage: @allpermissions(['edit-employees', 'view-employees']) ... @endallpermissions
         */
        Blade::if('allpermissions', function (array $permissions, ?string $guard = null) {
            return RolePermission::hasAllPermissions($permissions, $guard);
        });

        /**
         * @superadmin directive - Check if user is a super admin
         * Usage: @superadmin ... @endsuperadmin
         */
        Blade::if('superadmin', function (?string $guard = null) {
            return RolePermission::isSuperAdmin($guard);
        });

        /**
         * @admin directive - Check if user is an admin
         * Usage: @admin ... @endadmin
         */
        Blade::if('admin', function (?string $guard = null) {
            return RolePermission::isAdmin($guard);
        });

        /**
         * @manager directive - Check if user is a manager
         * Usage: @manager ... @endmanager
         */
        Blade::if('manager', function (?string $guard = null) {
            return RolePermission::isManager($guard);
        });

        /**
         * @supervisor directive - Check if user is a supervisor
         * Usage: @supervisor ... @endsupervisor
         */
        Blade::if('supervisor', function (?string $guard = null) {
            return RolePermission::isSupervisor($guard);
        });

        /**
         * @staff directive - Check if user is staff
         * Usage: @staff ... @endstaff
         */
        Blade::if('staff', function (?string $guard = null) {
            return RolePermission::isStaff($guard);
        });

        /**
         * @rolelevel directive - Check if user has minimum role level
         * Usage: @rolelevel(4) ... @endrolelevel
         */
        Blade::if('rolelevel', function (int $minLevel, ?string $guard = null) {
            return RolePermission::hasRoleLevel($minLevel, $guard);
        });

        /**
         * @canmanage directive - Check if user can manage another user
         * Usage: @canmanage($user) ... @endcanmanage
         */
        Blade::if('canmanage', function ($targetUser, ?string $guard = null) {
            return RolePermission::canManageUser($targetUser, $guard);
        });

        /**
         * @module directive - Check if user can access a module
         * Usage: @module('production') ... @endmodule
         */
        Blade::if('module', function (string $module, ?string $guard = null) {
            return RolePermission::canAccessModule($module, $guard);
        });

        /**
         * @unlessrole directive - Inverse of @role
         * Usage: @unlessrole('Admin') ... @endunlessrole
         */
        Blade::if('unlessrole', function (string $role, ?string $guard = null) {
            return ! RolePermission::hasRole($role, $guard);
        });

        /**
         * @unlesspermission directive - Inverse of @permission
         * Usage: @unlesspermission('edit-employees') ... @endunlesspermission
         */
        Blade::if('unlesspermission', function (string $permission, ?string $guard = null) {
            return ! RolePermission::hasPermission($permission, $guard);
        });
    }
}
