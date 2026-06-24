<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Creates the "Operations Manager" role — a cross-category oversight role
 * that covers BOTH Production and Sales.
 *
 * Level 4 is required (not 3): DepartmentScopeMiddleware locks a level-3
 * Manager to the single category of their own department, so a level-3 role
 * (or even a user holding both Production Manager + Sales Manager roles) could
 * not reach the other category's department-scoped dashboards. Level 4 grants
 * access to all departments within the branch, across categories.
 *
 * Permissions are the union of Production Manager + Sales Manager.
 *
 * Idempotent: safe to run against the live DB; does not touch other roles.
 */
return new class extends Migration
{
    private string $guard = 'web';

    private string $roleName = 'Operations Manager';

    private array $permissionNames = [
        // Production (from Production Manager)
        'view-production', 'manage-production', 'manage-recipes', 'manage-quality', 'view-production-reports',
        // Sales (from Sales Manager)
        'view-sales', 'process-sales', 'manage-sales', 'manage-refunds', 'manage-discounts', 'view-sales-reports',
        // Shared
        'view-inventory',
        'view-reports', 'view-analytics', 'send-reports-to-md',
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(
            ['name' => $this->roleName, 'guard_name' => $this->guard],
            [
                'description' => 'Oversees both Production and Sales operations across departments',
                'display_order' => 5,
                'level' => 4,
                'is_protected' => false,
            ]
        );

        // Ensure attributes are correct even if the role pre-existed.
        $role->forceFill([
            'description' => 'Oversees both Production and Sales operations across departments',
            'display_order' => 5,
            'level' => 4,
        ])->save();

        $permissions = Permission::where('guard_name', $this->guard)
            ->whereIn('name', $this->permissionNames)
            ->get();

        $role->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', $this->roleName)
            ->where('guard_name', $this->guard)
            ->first();

        if ($role) {
            $role->syncPermissions([]);
            $role->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
