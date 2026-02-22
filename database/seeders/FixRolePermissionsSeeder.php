<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::where('guard_name', $guard)->get();

        $rolePermissions = [
            'Super Admin' => ['*'],
            'Managing Director' => ['*'],
            'Admin' => ['*'],

            'Production Manager' => [
                'view-production', 'manage-production', 'manage-recipes', 'manage-quality',
                'view-production-reports', 'view-inventory',
                'view-reports', 'view-analytics',
            ],
            'Sales Manager' => [
                'view-sales', 'process-sales', 'manage-sales', 'manage-refunds', 'manage-discounts',
                'view-sales-reports', 'view-inventory',
                'view-reports', 'view-analytics',
            ],
            'HR Manager' => [
                'manage-organization', 'view-employees', 'manage-employees',
                'view-departments', 'manage-departments',
                'manage-roles-assignments', 'manage-staff-schedule',
                'manage-leave', 'manage-payroll', 'view-hr-reports',
                'view-reports', 'view-analytics',
            ],
            'Inventory Manager' => [
                'view-inventory', 'manage-inventory', 'manage-suppliers',
                'manage-purchases', 'manage-stock-takes', 'view-inventory-reports',
                'view-reports', 'view-analytics',
            ],
            'Accounting Manager' => [
                'view-accounting', 'manage-accounting', 'reconcile-accounts',
                'manage-bank-accounts', 'manage-accounting-periods', 'view-financial-reports',
                'view-reports', 'view-analytics',
            ],
            'Head Chef' => [
                'view-production', 'manage-production', 'manage-recipes',
                'view-production-reports', 'view-inventory',
            ],
            'Gelato Chef' => [
                'view-production', 'manage-production',
                'view-production-reports', 'view-inventory',
            ],
            'Floor Manager' => [
                'view-sales', 'process-sales', 'manage-sales',
                'view-sales-reports', 'view-inventory',
                'view-reports', 'view-analytics',
            ],

            'HR Officer' => [
                'view-employees', 'manage-employees', 'manage-leave', 'view-hr-reports',
            ],
            'Accountant' => [
                'view-accounting', 'reconcile-accounts', 'view-financial-reports', 'view-reports',
            ],
            'Cost Accountant' => [
                'view-accounting', 'view-financial-reports', 'view-production', 'view-inventory',
            ],
            'Till Supervisor' => [
                'view-sales', 'process-sales', 'manage-sales', 'view-sales-reports', 'view-inventory',
            ],
            'Cornerstore Supervisor' => [
                'view-sales', 'process-sales', 'manage-sales', 'view-inventory',
            ],
            'Consession Supervisor' => [
                'view-sales', 'process-sales', 'manage-sales',
            ],
            'Coffee Barista Trainer' => [
                'view-sales', 'process-sales', 'manage-employees',
            ],
            'Lobby Host Supervisor' => [
                'view-sales', 'manage-employees',
            ],
            'Kitchen Assistant Supervisor' => [
                'view-production', 'view-production-reports', 'manage-employees',
            ],
            'Hot Kitchen Chef' => [
                'view-production', 'manage-production', 'view-production-reports', 'view-inventory',
            ],
            'Pastry Chef' => [
                'view-production', 'manage-production', 'manage-recipes', 'view-production-reports', 'view-inventory',
            ],
            'Assistant Shop Floor Manager' => [
                'view-sales', 'process-sales', 'manage-sales', 'view-inventory',
            ],
            'Inventory Team Lead' => [
                'view-inventory', 'manage-inventory', 'view-inventory-reports', 'manage-employees',
            ],
            'Procurement Officer' => [
                'view-inventory', 'manage-suppliers', 'manage-purchases',
            ],
            'Facility Officer' => [
                'view-employees', 'manage-employees',
            ],
            'Cleaners Supervisor' => [
                'view-employees', 'manage-employees',
            ],
            'Chief Security Officer' => [
                'view-employees', 'manage-employees',
            ],
            'Social Media Manager' => [
                'view-reports', 'view-analytics', 'view-sales-reports',
            ],

            'Production Staff' => [
                'view-production', 'view-production-reports',
            ],
            'Sales Staff' => [
                'view-sales', 'process-sales',
            ],
            'Inventory Staff' => [
                'view-inventory',
            ],
            'Kitchen Assistant' => [
                'view-production',
            ],
            'Data Processor' => [
                'view-production', 'view-reports', 'export-reports',
            ],
            'Coffee Barista' => [
                'view-sales', 'process-sales',
            ],
            'Cashier' => [
                'view-sales', 'process-sales',
            ],
            'Wait Staff' => [
                'view-sales', 'process-sales',
            ],
            'Lobby Host' => [
                'view-sales',
            ],
            'Consession Attendant' => [
                'view-sales', 'process-sales',
            ],
            'Store Keeper' => [
                'view-inventory', 'manage-inventory',
            ],
            'Security Officer' => [
                'view-employees',
            ],
            'Driver' => [
                'view-inventory',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if (! $role) {
                continue;
            }

            if (in_array('*', $permissions, true)) {
                $role->syncPermissions($allPermissions);
            } else {
                $role->syncPermissions($permissions);
            }
        }

        $this->command->info('Roles permissions synchronized successfully!');
    }
}
