<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'manage-opening-stock',
            'guard_name' => 'web',
        ]);

        foreach (['HR', 'HR Manager', 'HR Officer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['HR', 'HR Manager', 'HR Officer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $role->hasPermissionTo('manage-opening-stock')) {
                $role->revokePermissionTo('manage-opening-stock');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
