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

        $roles = ['MD', 'Operations Manager', 'Production Manager', 'Sales Manager', 'Inventory Manager'];

        foreach ($roles as $roleName) {
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

        $permission = Permission::where('name', 'manage-opening-stock')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
