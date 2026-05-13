<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AssignRolesToExistingUsersSeeder extends Seeder
{
    /**
     * Assign roles to existing users that don't have roles
     */
    public function run(): void
    {
        $guard = 'web';

        // Get roles
        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', $guard)->first();
        $adminRole = Role::where('name', 'Admin')->where('guard_name', $guard)->first();
        $employeeRole = Role::where('name', 'Employee')->where('guard_name', $guard)->first();
        $salesStaffRole = Role::where('name', 'Sales Staff')->where('guard_name', $guard)->first();
        $productionStaffRole = Role::where('name', 'Production Staff')->where('guard_name', $guard)->first();
        $inventoryStaffRole = Role::where('name', 'Inventory Staff')->where('guard_name', $guard)->first();

        if (!$superAdminRole || !$adminRole) {
            $this->command->error('Required admin roles not found. Please run AssignPermissionsToRolesSeeder first!');
            return;
        }

        $defaultStaffRole = $employeeRole ?? $salesStaffRole ?? $productionStaffRole ?? $inventoryStaffRole;
        if (!$defaultStaffRole) {
            $this->command->error('No staff role available (Employee/Sales Staff/Production Staff/Inventory Staff).');
            return;
        }

        // Assign Super Admin role to user with is_superadmin = 1 (column may not exist)
        $superAdmins = \Illuminate\Support\Facades\Schema::hasColumn('users', 'is_superadmin')
            ? Employee::where('is_superadmin', 1)->get()
            : collect();
        foreach ($superAdmins as $user) {
            if (!$user->hasRole('Super Admin')) {
                $user->assignRole($superAdminRole);
                $this->command->info("Assigned Super Admin role to: {$user->email}");
            }
        }

        // Assign Admin role to users with user_type = 'admin'
        $admins = Employee::where('user_type', 'admin')->get();
        foreach ($admins as $user) {
            if (!$user->hasAnyRole(['Super Admin', 'Admin', 'Managing Director'])) {
                $user->assignRole($adminRole);
                $this->command->info("Assigned Admin role to: {$user->email}");
            }
        }

        // Assign Employee role to all other users without roles
        $usersWithoutRoles = Employee::whereDoesntHave('roles')->get();
        foreach ($usersWithoutRoles as $user) {
            $user->assignRole($defaultStaffRole);
            $this->command->info("Assigned {$defaultStaffRole->name} role to: {$user->email}");
        }

        $this->command->info('✅ Role assignment completed for existing users.');
    }
}
