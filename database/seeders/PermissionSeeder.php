<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive permissions for all system modules and features.
     * All permissions follow the pattern: verb-noun (e.g., view-employees, create-roles)
     */
    public function run(): void
    {
        $guard = 'web';

        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing permissions for this guard
        Permission::where('guard_name', $guard)->delete();

        $permissions = [
            // ===== SYSTEM/ADMIN PERMISSIONS =====
            ['name' => 'manage-system', 'description' => 'Full system management', 'category' => 'system'],
            ['name' => 'manage-roles', 'description' => 'Manage roles', 'category' => 'system'],
            ['name' => 'manage-permissions', 'description' => 'Manage permissions', 'category' => 'system'],
            ['name' => 'manage-branches', 'description' => 'Manage branches', 'category' => 'system'],
            ['name' => 'manage-settings', 'description' => 'Manage system settings', 'category' => 'system'],
            ['name' => 'view-audit-logs', 'description' => 'View audit logs', 'category' => 'system'],

            // ===== ORGANIZATION/HR PERMISSIONS =====
            ['name' => 'manage-organization', 'description' => 'Manage organization data', 'category' => 'organization'],
            ['name' => 'view-employees', 'description' => 'View employees', 'category' => 'hr'],
            ['name' => 'manage-employees', 'description' => 'Create/update employees', 'category' => 'hr'],
            ['name' => 'view-departments', 'description' => 'View departments', 'category' => 'hr'],
            ['name' => 'manage-departments', 'description' => 'Create/update departments', 'category' => 'hr'],
            ['name' => 'manage-roles-assignments', 'description' => 'Assign roles to users', 'category' => 'hr'],
            ['name' => 'manage-staff-schedule', 'description' => 'Manage staff schedules', 'category' => 'hr'],
            ['name' => 'manage-leave', 'description' => 'Manage leave', 'category' => 'hr'],
            ['name' => 'manage-payroll', 'description' => 'Manage payroll', 'category' => 'hr'],
            ['name' => 'view-hr-reports', 'description' => 'View HR reports', 'category' => 'hr'],

            // ===== PRODUCTION PERMISSIONS =====
            ['name' => 'view-production', 'description' => 'View production module', 'category' => 'production'],
            ['name' => 'manage-production', 'description' => 'Manage production operations', 'category' => 'production'],
            ['name' => 'manage-recipes', 'description' => 'Manage recipes', 'category' => 'production'],
            ['name' => 'manage-quality', 'description' => 'Manage quality control', 'category' => 'production'],
            ['name' => 'view-production-reports', 'description' => 'View production reports', 'category' => 'production'],

            // ===== SALES PERMISSIONS =====
            ['name' => 'view-sales', 'description' => 'View sales module', 'category' => 'sales'],
            ['name' => 'process-sales', 'description' => 'Process sales', 'category' => 'sales'],
            ['name' => 'manage-sales', 'description' => 'Manage sales operations', 'category' => 'sales'],
            ['name' => 'manage-refunds', 'description' => 'Manage refunds', 'category' => 'sales'],
            ['name' => 'manage-discounts', 'description' => 'Manage sales discounts', 'category' => 'sales'],
            ['name' => 'view-sales-reports', 'description' => 'View sales reports', 'category' => 'sales'],

            // ===== INVENTORY PERMISSIONS =====
            ['name' => 'view-inventory', 'description' => 'View inventory module', 'category' => 'inventory'],
            ['name' => 'manage-inventory', 'description' => 'Manage inventory operations', 'category' => 'inventory'],
            ['name' => 'view-suppliers',   'description' => 'View supplier list and details', 'category' => 'inventory'],
            ['name' => 'create-suppliers', 'description' => 'Create new suppliers',           'category' => 'inventory'],
            ['name' => 'manage-suppliers', 'description' => 'Manage suppliers',               'category' => 'inventory'],
            ['name' => 'manage-purchases', 'description' => 'Manage purchases', 'category' => 'inventory'],
            ['name' => 'manage-stock-takes', 'description' => 'Manage stock takes', 'category' => 'inventory'],
            ['name' => 'view-inventory-reports', 'description' => 'View inventory reports', 'category' => 'inventory'],

            // ===== ACCOUNTING PERMISSIONS =====
            ['name' => 'view-accounting', 'description' => 'View accounting module', 'category' => 'accounting'],
            ['name' => 'manage-accounting', 'description' => 'Manage accounting operations', 'category' => 'accounting'],
            ['name' => 'reconcile-accounts', 'description' => 'Reconcile accounts', 'category' => 'accounting'],
            ['name' => 'manage-bank-accounts', 'description' => 'Manage bank accounts', 'category' => 'accounting'],
            ['name' => 'manage-accounting-periods', 'description' => 'Manage accounting periods', 'category' => 'accounting'],
            ['name' => 'view-financial-reports', 'description' => 'View financial reports', 'category' => 'accounting'],

            // ===== REPORTING/ANALYTICS PERMISSIONS =====
            ['name' => 'view-reports', 'description' => 'View reports', 'category' => 'reporting'],
            ['name' => 'export-reports', 'description' => 'Export reports', 'category' => 'reporting'],
            ['name' => 'view-analytics', 'description' => 'View analytics', 'category' => 'reporting'],
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'guard_name' => $guard,
                'description' => $permission['description'] ?? '',
                'category' => $permission['category'] ?? 'general',
            ]);
        }

        echo '✅ '.count($permissions)." permissions created successfully.\n";
    }
}
