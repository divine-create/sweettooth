<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add level column to roles table if not exists
        if (!Schema::hasColumn('roles', 'level')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->tinyInteger('level')->default(1)->after('guard_name');
            });
        }

        // Step 2: Update existing roles with levels and ensure simplified roles exist
        $this->setupSimplifiedRoles();
    }

    /**
     * Set up the 5 simplified roles
     */
    private function setupSimplifiedRoles(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'level' => 5,
                'description' => 'Full system access, all branches, all departments',
            ],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'level' => 4,
                'description' => 'Branch-wide access, manages all departments in branch',
            ],
            [
                'name' => 'Manager',
                'guard_name' => 'web',
                'level' => 3,
                'description' => 'Department manager, full control of their department',
            ],
            [
                'name' => 'Supervisor',
                'guard_name' => 'web',
                'level' => 2,
                'description' => 'Department supervisor, limited management',
            ],
            [
                'name' => 'Staff',
                'guard_name' => 'web',
                'level' => 1,
                'description' => 'Department worker, basic operational access',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                $roleData
            );
        }

        // Update levels for any existing roles that match our simplified ones
        Role::where('name', 'Super Admin')->update(['level' => 5]);
        Role::where('name', 'Admin')->update(['level' => 4]);
        Role::where('name', 'Manager')->update(['level' => 3]);
        Role::where('name', 'Supervisor')->update(['level' => 2]);
        Role::where('name', 'Staff')->update(['level' => 1]);

        // Set level for old roles (so they still work during transition)
        $managerRoles = [
            'Head of Production', 'Chef', 'Head of Gelato', 'Confectionaries Manager',
            'Sales Manager', 'HR Manager', 'Inventory Manager', 'Corner Store Manager',
            'Managing Director', 'Production Manager', 'Accounting Manager', 'Head Chef',
            'Gelato Chef', 'Floor Manager',
        ];
        Role::whereIn('name', $managerRoles)->update(['level' => 3]);

        $supervisorRoles = ['Till Supervisor', 'Sales Supervisor', 'Stock Controller'];
        Role::whereIn('name', $supervisorRoles)->update(['level' => 2]);

        // All other roles default to level 1 (Staff)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
