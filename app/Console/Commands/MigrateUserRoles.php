<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MigrateUserRoles extends Command
{
    protected $signature = 'users:migrate-roles {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Migrate existing users from legacy roles to department-independent roles';

    protected array $roleMapping = [
        // Old roles -> New roles
        'Super Admin' => 'Super Admin',
        'MD' => 'MD',
        'Managing Director' => 'Managing Director',
        'Admin' => 'Admin',

        // Department heads/managers
        'Head of Production' => 'Head of Production',
        'Sales Manager' => 'Sales Manager',
        'HR Manager' => 'HR Manager',
        'Inventory Manager' => 'Inventory Manager',
        'Accounting Manager' => 'Accounting Manager',

        // Supervisors
        'Sales Supervisor' => 'Sales Supervisor',
        'Till Supervisor' => 'Sales Supervisor',

        // Production specialists -> Production Supervisor
        'Chef' => 'Production Supervisor',
        'Head of Gelato' => 'Production Supervisor',
        'Confectionaries Manager' => 'Production Supervisor',

        // Production Staff
        'Kitchen Staff' => 'Production Staff',
        'Gelato Production Staff' => 'Production Staff',
        'Confectionaries Production Staff' => 'Production Staff',

        // Sales Staff
        'Cashier' => 'Sales Staff',
        'Sales Associate' => 'Sales Staff',
        'Junior Cashier' => 'Sales Staff',
        'Corner Store Staff' => 'Sales Staff',
        'Corner Store Manager' => 'Sales Staff',
        'Confectionaries Sales Staff' => 'Sales Staff',

        // Inventory Staff
        'Stock Controller' => 'Inventory Supervisor',
        'Store Keeper' => 'Inventory Staff',
        'Inventory Clerk' => 'Inventory Staff',
        'Warehouse Manager' => 'Inventory Supervisor',
        'Store Supervisor' => 'Inventory Supervisor',
        'Store Manager' => 'Inventory Supervisor',

        // HR / Accounting
        'HR Officer' => 'HR Officer',
        'Accountant' => 'Accountant',

        // Generic
        'Employee' => 'Sales Staff',
        'Viewer' => 'Sales Staff',
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $users = User::with('roles')->get();

        $this->info($isDryRun ? 'DRY RUN: Showing role migration changes' : 'Migrating user roles to simplified system...');
        $this->newLine();

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $changes = [];

        foreach ($users as $user) {
            $oldRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $this->mapRoles($oldRoles, $user);

            if ($this->rolesChanged($oldRoles, $newRoles)) {
                $changes[] = [
                    'user' => $user->name . ' (' . $user->email . ')',
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoles,
                ];

                if (!$isDryRun) {
                    // Remove old roles
                    $user->roles()->detach();

                    // Assign new roles
                    foreach ($newRoles as $newRole) {
                        if ($role = Role::where('name', $newRole)->first()) {
                            $user->assignRole($role);
                        }
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (empty($changes)) {
            $this->info('✅ No role changes needed.');
            return;
        }

        // Display summary
        $this->table(
            ['User', 'Old Roles', 'New Roles'],
            array_map(function ($change) {
                return [
                    $change['user'],
                    implode(', ', $change['old_roles']),
                    implode(', ', $change['new_roles']),
                ];
            }, $changes)
        );

        $this->newLine();
        $this->info($isDryRun
            ? "DRY RUN: Would migrate " . count($changes) . " users."
            : "✅ Successfully migrated " . count($changes) . " users to simplified roles."
        );
    }

    private function mapRoles(array $oldRoles, User $user): array
    {
        $newRoles = [];

        foreach ($oldRoles as $oldRole) {
            if (isset($this->roleMapping[$oldRole])) {
                $newRole = $this->roleMapping[$oldRole];
                if (!in_array($newRole, $newRoles)) {
                    $newRoles[] = $newRole;
                }
                continue;
            }

            if ($oldRole === 'Supervisor') {
                $newRoles[] = $this->mapSupervisorByDepartment($user);
            }

            if ($oldRole === 'Manager') {
                $newRoles[] = $this->mapManagerByDepartment($user);
            }
        }

        $newRoles = array_filter(array_unique($newRoles));

        if (empty($newRoles)) {
            $newRoles[] = $this->defaultRoleForUser($user);
        }

        return array_unique($newRoles);
    }

    private function rolesChanged(array $oldRoles, array $newRoles): bool
    {
        sort($oldRoles);
        sort($newRoles);

        // Map old roles to new for comparison
        $mappedOldRoles = array_map(function ($role) {
            return $this->roleMapping[$role] ?? $role;
        }, $oldRoles);

        sort($mappedOldRoles);

        return $mappedOldRoles !== $newRoles;
    }

    private function mapSupervisorByDepartment(User $user): string
    {
        $deptName = $user->department?->name ?? '';
        $category = $user->department?->category?->name ?? '';

        if ($category === 'Production') return 'Production Supervisor';
        if ($category === 'Sales') return 'Sales Supervisor';
        if ($category === 'Inventory') return 'Inventory Supervisor';
        if ($category === 'Support' && str_contains($deptName, 'Account')) return 'Accountant';
        if ($category === 'Support' && str_contains($deptName, 'HR')) return 'HR Officer';

        return 'Sales Supervisor';
    }

    private function mapManagerByDepartment(User $user): string
    {
        $deptName = $user->department?->name ?? '';
        $category = $user->department?->category?->name ?? '';

        if ($category === 'Production') return 'Head of Production';
        if ($category === 'Sales') return 'Sales Manager';
        if ($category === 'Inventory') return 'Inventory Manager';
        if ($category === 'Support' && str_contains($deptName, 'Account')) return 'Accounting Manager';
        if ($category === 'Support' && str_contains($deptName, 'HR')) return 'HR Manager';

        return 'Sales Manager';
    }

    private function defaultRoleForUser(User $user): string
    {
        $category = $user->department?->category?->name ?? '';

        return match ($category) {
            'Production' => 'Production Staff',
            'Sales' => 'Sales Staff',
            'Inventory' => 'Inventory Staff',
            default => 'Sales Staff',
        };
    }
}
