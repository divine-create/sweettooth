<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Restricts the "Operations Manager" role to Production + Sales only.
 *
 * The role was originally seeded with the union of Production Manager + Sales
 * Manager permissions, which included `view-inventory`. Per requirements, an
 * Operations Manager oversees Production and Sales but must NOT see Inventory,
 * Accounting or HR. Accounting/HR were never granted (their dashboards gate on
 * permissions this role does not hold), so only `view-inventory` is dropped here.
 *
 * Re-syncs to the exact intended set, so it is self-correcting regardless of the
 * role's current permissions, and runs after the create migration on fresh installs.
 *
 * Note: this does NOT remove the production material-request / store / raw-material
 * pages, which live under the `production` route group (gated by department.scope),
 * not by `view-inventory`. It only removes the standalone Inventory module + its
 * sidebar entry.
 */
return new class extends Migration
{
    private string $guard = 'web';

    private string $roleName = 'Operations Manager';

    /** Production + Sales + reporting only — no inventory, accounting or HR. */
    private array $permissionNames = [
        // Production
        'view-production', 'manage-production', 'manage-recipes', 'manage-quality', 'view-production-reports',
        // Sales
        'view-sales', 'process-sales', 'manage-sales', 'manage-refunds', 'manage-discounts', 'view-sales-reports',
        // Reporting
        'view-reports', 'view-analytics', 'send-reports-to-md',
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', $this->roleName)->where('guard_name', $this->guard)->first();

        if (! $role) {
            // Role not present (e.g. unexpected ordering) — nothing to adjust.
            return;
        }

        $permissions = Permission::where('guard_name', $this->guard)
            ->whereIn('name', $this->permissionNames)
            ->get();

        $role->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Re-add view-inventory to restore the previous (create-migration) state.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', $this->roleName)->where('guard_name', $this->guard)->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::where('guard_name', $this->guard)
            ->whereIn('name', array_merge($this->permissionNames, ['view-inventory']))
            ->get();

        $role->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
