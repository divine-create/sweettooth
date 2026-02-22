<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StandardizeRoles extends Command
{
    protected $signature = 'roles:standardize';
    protected $description = 'Standardize all role names to hyphenated format and remove duplicates';

    protected $roleMappings = [
        'Super Admin' => 'super-admin',
        'Admin' => 'admin',
        'Managing Director' => 'managing-director',
        'MD' => 'md',
        'Sales Manager' => 'sales-manager',
        'HR Manager' => 'hr-manager',
        'Inventory Manager' => 'inventory-manager',
        'Head of Production' => 'head-of-production',
        'Head of Gelato' => 'head-of-gelato',
        'Confectionaries Manager' => 'confectionaries-manager',
        'Till Supervisor' => 'till-supervisor',
        'Corner Store Manager' => 'corner-store-manager',
        'Kitchen Staff' => 'kitchen-staff',
        'Gelato Production Staff' => 'gelato-production-staff',
        'Confectionaries Production Staff' => 'confectionaries-production-staff',
        'Cashier' => 'cashier',
        'Corner Store Staff' => 'corner-store-staff',
        'Confectionaries Sales Staff' => 'confectionaries-sales-staff',
        'Stock Controller' => 'stock-controller',
        'Store Keeper' => 'store-keeper',
        'HR Officer' => 'hr-officer',
        'Chef' => 'chef',
    ];

    public function handle()
    {
        $this->info('🔄 Standardizing role names to hyphenated format...');

        // First, remove duplicate roles
        $this->removeDuplicateRoles();

        // Then rename roles to hyphenated format
        $this->renameRolesToHyphenated();

        // Update all code references (this will be done by the user)
        $this->info('✅ Role standardization complete!');
        $this->info('');
        $this->info('📝 Next steps:');
        $this->info('1. Update AuthorizationHelper.php to use new role names');
        $this->info('2. Update all code references to use hyphenated role names');
        $this->info('3. Test the authentication system');
    }

    protected function removeDuplicateRoles()
    {
        $this->info('🧹 Removing duplicate roles...');

        $roleCounts = Role::selectRaw('name, COUNT(*) as count')
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        foreach ($roleCounts as $roleCount) {
            $duplicates = Role::where('name', $roleCount->name)->get();

            // Keep the first one, delete the rest
            for ($i = 1; $i < $duplicates->count(); $i++) {
                $this->warn("Removing duplicate role: {$roleCount->name}");
                $duplicates[$i]->delete();
            }
        }

        $this->info('✅ Duplicate roles removed');
    }

    protected function renameRolesToHyphenated()
    {
        $this->info('🏷️  Renaming roles to hyphenated format...');

        foreach ($this->roleMappings as $oldName => $newName) {
            $role = Role::where('name', $oldName)->first();

            if ($role) {
                // Check if new name already exists
                if (Role::where('name', $newName)->exists()) {
                    $this->warn("Role '{$newName}' already exists, skipping rename of '{$oldName}'");
                    continue;
                }

                $this->info("Renaming '{$oldName}' → '{$newName}'");
                $role->update(['name' => $newName]);
            }
        }

        $this->info('✅ Roles renamed to hyphenated format');
    }
}