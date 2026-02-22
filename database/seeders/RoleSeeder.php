<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles for this guard to avoid conflicts
        Role::where('guard_name', $guard)->delete();

        // ===== EXECUTIVE =====
        $superAdmin = Role::create([
            'name' => 'Super Admin',
            'guard_name' => $guard,
            'description' => 'Full system access with all permissions',
            'display_order' => 1,
            'level' => 5,
            'is_protected' => true,
        ]);

        $managingDirector = Role::create([
            'name' => 'Managing Director',
            'guard_name' => $guard,
            'description' => 'Managing Director - Executive level',
            'display_order' => 2,
            'level' => 5,
            'is_protected' => true,
        ]);

        $admin = Role::create([
            'name' => 'Admin',
            'guard_name' => $guard,
            'description' => 'Administrative access',
            'display_order' => 4,
            'level' => 4,
            'is_protected' => true,
        ]);

        // Give executive roles ALL permissions
        $allPermissions = Permission::where('guard_name', $guard)->get();
        foreach ([$superAdmin, $managingDirector, $admin] as $role) {
            $role->syncPermissions($allPermissions);
        }

        // ===== DEPARTMENT MANAGERS =====
        $productionManager = Role::create([
            'name' => 'Production Manager',
            'guard_name' => $guard,
            'description' => 'Production department manager',
            'display_order' => 10,
            'level' => 3,
        ]);
        $productionManager->syncPermissions([
            'view-production', 'manage-production', 'manage-recipes', 'manage-quality',
            'view-production-reports', 'view-inventory',
            'view-reports', 'view-analytics',
        ]);

        $salesManager = Role::create([
            'name' => 'Sales Manager',
            'guard_name' => $guard,
            'description' => 'Sales department manager',
            'display_order' => 11,
            'level' => 3,
        ]);
        $salesManager->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales', 'manage-refunds', 'manage-discounts',
            'view-sales-reports', 'view-inventory',
            'view-reports', 'view-analytics',
        ]);

        $hrManager = Role::create([
            'name' => 'HR Manager',
            'guard_name' => $guard,
            'description' => 'Human Resources manager',
            'display_order' => 12,
            'level' => 3,
        ]);
        $hrManager->syncPermissions([
            'manage-organization', 'view-employees', 'manage-employees',
            'view-departments', 'manage-departments',
            'manage-roles-assignments', 'manage-staff-schedule',
            'manage-leave', 'manage-payroll', 'view-hr-reports',
            'view-reports', 'view-analytics',
        ]);

        $inventoryManager = Role::create([
            'name' => 'Inventory Manager',
            'guard_name' => $guard,
            'description' => 'Inventory and stock management',
            'display_order' => 13,
            'level' => 3,
        ]);
        $inventoryManager->syncPermissions([
            'view-inventory', 'manage-inventory', 'manage-suppliers',
            'manage-purchases', 'manage-stock-takes', 'view-inventory-reports',
            'view-reports', 'view-analytics',
        ]);

        $accountingManager = Role::create([
            'name' => 'Accounting Manager',
            'guard_name' => $guard,
            'description' => 'Accounting and financial management',
            'display_order' => 14,
            'level' => 4,
        ]);
        $accountingManager->syncPermissions([
            'view-accounting', 'manage-accounting', 'reconcile-accounts',
            'manage-bank-accounts', 'manage-accounting-periods', 'view-financial-reports',
            'view-reports', 'view-analytics',
        ]);

        $headChef = Role::create([
            'name' => 'Head Chef',
            'guard_name' => $guard,
            'description' => 'Head chef for pastry/kitchen operations',
            'display_order' => 15,
            'level' => 3,
        ]);
        $headChef->syncPermissions([
            'view-production', 'manage-production', 'manage-recipes',
            'view-production-reports', 'view-inventory',
        ]);

        $gelatoChef = Role::create([
            'name' => 'Gelato Chef',
            'guard_name' => $guard,
            'description' => 'Gelato production manager',
            'display_order' => 16,
            'level' => 3,
        ]);
        $gelatoChef->syncPermissions([
            'view-production', 'manage-production',
            'view-production-reports', 'view-inventory',
        ]);

        $floorManager = Role::create([
            'name' => 'Floor Manager',
            'guard_name' => $guard,
            'description' => 'Sales floor manager',
            'display_order' => 17,
            'level' => 3,
        ]);
        $floorManager->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales',
            'view-sales-reports', 'view-inventory',
            'view-reports', 'view-analytics',
        ]);

        // ===== SUPERVISORS =====
        $hrOfficer = Role::create([
            'name' => 'HR Officer',
            'guard_name' => $guard,
            'description' => 'HR officer',
            'display_order' => 23,
            'level' => 2,
        ]);
        $hrOfficer->syncPermissions([
            'view-employees', 'manage-employees', 'manage-leave', 'view-hr-reports',
        ]);

        $accountant = Role::create([
            'name' => 'Accountant',
            'guard_name' => $guard,
            'description' => 'Accounting operations',
            'display_order' => 24,
            'level' => 2,
        ]);
        $accountant->syncPermissions([
            'view-accounting', 'reconcile-accounts', 'view-financial-reports', 'view-reports',
        ]);

        $costAccountant = Role::create([
            'name' => 'Cost Accountant',
            'guard_name' => $guard,
            'description' => 'Cost accounting and valuation',
            'display_order' => 25,
            'level' => 2,
        ]);
        $costAccountant->syncPermissions([
            'view-accounting', 'view-financial-reports', 'view-production', 'view-inventory',
        ]);

        $tillSupervisor = Role::create([
            'name' => 'Till Supervisor',
            'guard_name' => $guard,
            'description' => 'Till operations supervisor',
            'display_order' => 26,
            'level' => 2,
        ]);
        $tillSupervisor->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales', 'view-sales-reports', 'view-inventory',
        ]);

        $cornerstoreSupervisor = Role::create([
            'name' => 'Cornerstore Supervisor',
            'guard_name' => $guard,
            'description' => 'Cornerstore supervisor',
            'display_order' => 27,
            'level' => 2,
        ]);
        $cornerstoreSupervisor->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales', 'view-inventory',
        ]);

        $consessionSupervisor = Role::create([
            'name' => 'Consession Supervisor',
            'guard_name' => $guard,
            'description' => 'Concession supervisor',
            'display_order' => 28,
            'level' => 2,
        ]);
        $consessionSupervisor->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales',
        ]);

        $coffeeBaristaTrainer = Role::create([
            'name' => 'Coffee Barista Trainer',
            'guard_name' => $guard,
            'description' => 'Barista trainer and supervisor',
            'display_order' => 29,
            'level' => 2,
        ]);
        $coffeeBaristaTrainer->syncPermissions([
            'view-sales', 'process-sales', 'manage-employees',
        ]);

        $lobbyHostSupervisor = Role::create([
            'name' => 'Lobby Host Supervisor',
            'guard_name' => $guard,
            'description' => 'Lobby host supervisor',
            'display_order' => 30,
            'level' => 2,
        ]);
        $lobbyHostSupervisor->syncPermissions([
            'view-sales', 'manage-employees',
        ]);

        $kitchenAssistantSupervisor = Role::create([
            'name' => 'Kitchen Assistant Supervisor',
            'guard_name' => $guard,
            'description' => 'Kitchen assistants supervisor',
            'display_order' => 31,
            'level' => 2,
        ]);
        $kitchenAssistantSupervisor->syncPermissions([
            'view-production', 'view-production-reports', 'manage-employees',
        ]);

        $hotKitchenChef = Role::create([
            'name' => 'Hot Kitchen Chef',
            'guard_name' => $guard,
            'description' => 'Hot kitchen supervisor',
            'display_order' => 32,
            'level' => 2,
        ]);
        $hotKitchenChef->syncPermissions([
            'view-production', 'manage-production', 'view-production-reports', 'view-inventory',
        ]);

        $pastryChef = Role::create([
            'name' => 'Pastry Chef',
            'guard_name' => $guard,
            'description' => 'Pastry supervisor',
            'display_order' => 33,
            'level' => 2,
        ]);
        $pastryChef->syncPermissions([
            'view-production', 'manage-production', 'manage-recipes', 'view-production-reports', 'view-inventory',
        ]);

        $assistantShopFloorManager = Role::create([
            'name' => 'Assistant Shop Floor Manager',
            'guard_name' => $guard,
            'description' => 'Assistant shop floor manager',
            'display_order' => 34,
            'level' => 2,
        ]);
        $assistantShopFloorManager->syncPermissions([
            'view-sales', 'process-sales', 'manage-sales', 'view-inventory',
        ]);

        $inventoryTeamLead = Role::create([
            'name' => 'Inventory Team Lead',
            'guard_name' => $guard,
            'description' => 'Inventory team lead',
            'display_order' => 35,
            'level' => 2,
        ]);
        $inventoryTeamLead->syncPermissions([
            'view-inventory', 'manage-inventory', 'view-inventory-reports', 'manage-employees',
        ]);

        $procurementOfficer = Role::create([
            'name' => 'Procurement Officer',
            'guard_name' => $guard,
            'description' => 'Procurement officer',
            'display_order' => 36,
            'level' => 2,
        ]);
        $procurementOfficer->syncPermissions([
            'view-inventory', 'manage-suppliers', 'manage-purchases',
        ]);

        $facilityOfficer = Role::create([
            'name' => 'Facility Officer',
            'guard_name' => $guard,
            'description' => 'Facilities officer',
            'display_order' => 37,
            'level' => 2,
        ]);
        $facilityOfficer->syncPermissions([
            'view-employees', 'manage-employees',
        ]);

        $cleanersSupervisor = Role::create([
            'name' => 'Cleaners Supervisor',
            'guard_name' => $guard,
            'description' => 'Cleaners supervisor',
            'display_order' => 38,
            'level' => 2,
        ]);
        $cleanersSupervisor->syncPermissions([
            'view-employees', 'manage-employees',
        ]);

        $chiefSecurityOfficer = Role::create([
            'name' => 'Chief Security Officer',
            'guard_name' => $guard,
            'description' => 'Chief security officer',
            'display_order' => 39,
            'level' => 2,
        ]);
        $chiefSecurityOfficer->syncPermissions([
            'view-employees', 'manage-employees',
        ]);

        $socialMediaManager = Role::create([
            'name' => 'Social Media Manager',
            'guard_name' => $guard,
            'description' => 'Social media manager',
            'display_order' => 40,
            'level' => 2,
        ]);
        $socialMediaManager->syncPermissions([
            'view-reports', 'view-analytics', 'view-sales-reports',
        ]);

        // ===== STAFF =====
        $productionStaff = Role::create([
            'name' => 'Production Staff',
            'guard_name' => $guard,
            'description' => 'Production staff',
            'display_order' => 60,
            'level' => 1,
        ]);
        $productionStaff->syncPermissions([
            'view-production', 'view-production-reports',
        ]);

        $salesStaff = Role::create([
            'name' => 'Sales Staff',
            'guard_name' => $guard,
            'description' => 'Sales staff',
            'display_order' => 61,
            'level' => 1,
        ]);
        $salesStaff->syncPermissions([
            'view-sales', 'process-sales',
        ]);

        $inventoryStaff = Role::create([
            'name' => 'Inventory Staff',
            'guard_name' => $guard,
            'description' => 'Inventory staff',
            'display_order' => 62,
            'level' => 1,
        ]);
        $inventoryStaff->syncPermissions([
            'view-inventory',
        ]);

        $kitchenAssistant = Role::create([
            'name' => 'Kitchen Assistant',
            'guard_name' => $guard,
            'description' => 'Kitchen assistant',
            'display_order' => 63,
            'level' => 1,
        ]);
        $kitchenAssistant->syncPermissions([
            'view-production',
        ]);

        $dataProcessor = Role::create([
            'name' => 'Data Processor',
            'guard_name' => $guard,
            'description' => 'Production data processor',
            'display_order' => 64,
            'level' => 1,
        ]);
        $dataProcessor->syncPermissions([
            'view-production', 'view-reports', 'export-reports',
        ]);

        $coffeeBarista = Role::create([
            'name' => 'Coffee Barista',
            'guard_name' => $guard,
            'description' => 'Coffee barista',
            'display_order' => 65,
            'level' => 1,
        ]);
        $coffeeBarista->syncPermissions([
            'view-sales', 'process-sales',
        ]);

        $cashier = Role::create([
            'name' => 'Cashier',
            'guard_name' => $guard,
            'description' => 'Cashier',
            'display_order' => 66,
            'level' => 1,
        ]);
        $cashier->syncPermissions([
            'view-sales', 'process-sales',
        ]);

        $waitStaff = Role::create([
            'name' => 'Wait Staff',
            'guard_name' => $guard,
            'description' => 'Wait staff',
            'display_order' => 67,
            'level' => 1,
        ]);
        $waitStaff->syncPermissions([
            'view-sales', 'process-sales',
        ]);

        $lobbyHost = Role::create([
            'name' => 'Lobby Host',
            'guard_name' => $guard,
            'description' => 'Lobby host',
            'display_order' => 68,
            'level' => 1,
        ]);
        $lobbyHost->syncPermissions([
            'view-sales',
        ]);

        $consessionAttendant = Role::create([
            'name' => 'Consession Attendant',
            'guard_name' => $guard,
            'description' => 'Concession attendant',
            'display_order' => 69,
            'level' => 1,
        ]);
        $consessionAttendant->syncPermissions([
            'view-sales', 'process-sales',
        ]);

        $storeKeeper = Role::create([
            'name' => 'Store Keeper',
            'guard_name' => $guard,
            'description' => 'Store keeper',
            'display_order' => 70,
            'level' => 1,
        ]);
        $storeKeeper->syncPermissions([
            'view-inventory', 'manage-inventory',
        ]);

        $securityOfficer = Role::create([
            'name' => 'Security Officer',
            'guard_name' => $guard,
            'description' => 'Security officer',
            'display_order' => 71,
            'level' => 1,
        ]);
        $securityOfficer->syncPermissions([
            'view-employees',
        ]);

        $driver = Role::create([
            'name' => 'Driver',
            'guard_name' => $guard,
            'description' => 'Driver',
            'display_order' => 72,
            'level' => 1,
        ]);
        $driver->syncPermissions([
            'view-inventory',
        ]);

        echo '✅ '.Role::where('guard_name', $guard)->count()." roles created successfully with permissions assigned.\n";
    }
}
