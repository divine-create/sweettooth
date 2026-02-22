<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AssignPermissionsToRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            FixRolePermissionsSeeder::class,
        ]);
    }
}
