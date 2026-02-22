<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Migrate users from old role system to simplified 5-role system
 *
 * Usage:
 *   php artisan roles:migrate-to-simplified          # Run migration
 *   php artisan roles:migrate-to-simplified --dry-run # Preview changes
 *   php artisan roles:migrate-to-simplified --cleanup  # Remove old roles after migration
 */
class MigrateToSimplifiedRoles extends Command
{
    protected $signature = 'roles:migrate-to-simplified
                            {--dry-run : Preview changes without applying them}
                            {--cleanup : Remove old roles after migration}';

    protected $description = 'Migrate users from old roles to simplified 5-role system';

    /**
     * Old role -> New role mapping
     */
    private array $roleMapping = [
        // Level 5 - Super Admin
        'Super Admin' => 'Super Admin',
        'MD' => 'Super Admin',
        'Managing Director' => 'Super Admin',

        // Level 4 - Admin
        'Admin' => 'Admin',

        // Level 3 - Manager
        'Head of Production' => 'Manager',
        'Chef' => 'Manager',
        'Head of Gelato' => 'Manager',
        'Confectionaries Manager' => 'Manager',
        'Sales Manager' => 'Manager',
        'HR Manager' => 'Manager',
        'Inventory Manager' => 'Manager',
        'Corner Store Manager' => 'Manager',

        // Level 2 - Supervisor
        'Till Supervisor' => 'Supervisor',
        'Sales Supervisor' => 'Supervisor',
        'Stock Controller' => 'Supervisor',
        'Supervisor' => 'Supervisor',

        // Level 1 - Staff (everything else)
        'Kitchen Staff' => 'Staff',
        'Gelato Production Staff' => 'Staff',
        'Confectionaries Production Staff' => 'Staff',
        'Cashier' => 'Staff',
        'Junior Cashier' => 'Staff',
        'Corner Store Staff' => 'Staff',
        'Sales Associate' => 'Staff',
        'Store Keeper' => 'Staff',
        'HR Officer' => 'Staff',
        'Employee' => 'Staff',
        'Viewer' => 'Staff',
        'Operator' => 'Staff',
    ];

    /**
     * Old role -> Default department mapping (if user has no department)
     */
    private array $departmentMapping = [
        'Chef' => 'Kitchen',
        'Kitchen Staff' => 'Kitchen',
        'Head of Gelato' => 'Gelato Production',
        'Gelato Production Staff' => 'Gelato Production',
        'Confectionaries Manager' => 'Confectionaries Production',
        'Confectionaries Production Staff' => 'Confectionaries Production',
        'Sales Manager' => 'Till',
        'Till Supervisor' => 'Till',
        'Cashier' => 'Till',
        'Junior Cashier' => 'Till',
        'Sales Associate' => 'Till',
        'Corner Store Manager' => 'Corner Store',
        'Corner Store Staff' => 'Corner Store',
        'HR Manager' => 'HR',
        'HR Officer' => 'HR',
        'Inventory Manager' => 'Inventory/Store',
        'Stock Controller' => 'Inventory/Store',
        'Store Keeper' => 'Inventory/Store',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $cleanup = $this->option('cleanup');

        $this->info('=== Simplified Roles Migration ===');
        $this->info($dryRun ? '(DRY RUN - No changes will be made)' : '');
        $this->newLine();

        // Ensure simplified roles exist
        $this->ensureSimplifiedRolesExist($dryRun);

        // Migrate users
        $this->migrateUsers($dryRun);

        // Cleanup old roles if requested
        if ($cleanup && !$dryRun) {
            $this->cleanupOldRoles();
        }

        $this->newLine();
        $this->info('=== Migration Complete ===');

        return Command::SUCCESS;
    }

    /**
     * Ensure the 5 simplified roles exist
     */
    private function ensureSimplifiedRolesExist(bool $dryRun): void
    {
        $this->info('Checking simplified roles...');

        $roles = [
            ['name' => 'Super Admin', 'level' => 5],
            ['name' => 'Admin', 'level' => 4],
            ['name' => 'Manager', 'level' => 3],
            ['name' => 'Supervisor', 'level' => 2],
            ['name' => 'Staff', 'level' => 1],
        ];

        foreach ($roles as $roleData) {
            $exists = Role::where('name', $roleData['name'])->exists();

            if ($exists) {
                $this->line("  [EXISTS] {$roleData['name']}");
                if (!$dryRun) {
                    Role::where('name', $roleData['name'])->update(['level' => $roleData['level']]);
                }
            } else {
                $this->line("  [CREATE] {$roleData['name']}");
                if (!$dryRun) {
                    Role::create([
                        'name' => $roleData['name'],
                        'guard_name' => 'web',
                        'level' => $roleData['level'],
                    ]);
                }
            }
        }

        $this->newLine();
    }

    /**
     * Migrate users to new roles
     */
    private function migrateUsers(bool $dryRun): void
    {
        $this->info('Migrating users...');
        $this->newLine();

        $stats = [
            'total' => 0,
            'migrated' => 0,
            'skipped' => 0,
            'department_assigned' => 0,
        ];

        User::with(['roles', 'department'])->chunk(50, function ($users) use ($dryRun, &$stats) {
            foreach ($users as $user) {
                $stats['total']++;

                $oldRole = $user->roles->first()?->name;
                $newRole = $this->roleMapping[$oldRole] ?? 'Staff';

                // Skip if already on new system
                if (in_array($oldRole, ['Super Admin', 'Admin', 'Manager', 'Supervisor', 'Staff'])) {
                    $this->line("  [SKIP] {$user->email} - Already on new role: {$oldRole}");
                    $stats['skipped']++;
                    continue;
                }

                // Determine department
                $department = $user->department;
                $needsDepartment = false;

                if (!$department && isset($this->departmentMapping[$oldRole])) {
                    $deptName = $this->departmentMapping[$oldRole];
                    $department = Department::where('name', $deptName)->first();
                    $needsDepartment = true;
                }

                // Log the change
                $this->line("  [MIGRATE] {$user->email}");
                $this->line("            Role: {$oldRole} -> {$newRole}");
                if ($needsDepartment && $department) {
                    $this->line("            Dept: (none) -> {$department->name}");
                }

                if (!$dryRun) {
                    // Update role
                    $user->syncRoles([$newRole]);

                    // Update department if needed
                    if ($needsDepartment && $department) {
                        $user->update(['department_id' => $department->id]);
                        $stats['department_assigned']++;
                    }

                    // Log the migration
                    DB::table('role_migration_log')->insert([
                        'user_id' => $user->id,
                        'old_role' => $oldRole ?? 'none',
                        'new_role' => $newRole,
                        'migrated_at' => now(),
                    ]);
                }

                $stats['migrated']++;
            }
        });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users', $stats['total']],
                ['Migrated', $stats['migrated']],
                ['Skipped (already new)', $stats['skipped']],
                ['Departments Assigned', $stats['department_assigned']],
            ]
        );
    }

    /**
     * Remove old roles that are no longer needed
     */
    private function cleanupOldRoles(): void
    {
        $this->newLine();
        $this->info('Cleaning up old roles...');

        $keepRoles = ['Super Admin', 'Admin', 'Manager', 'Supervisor', 'Staff'];

        $oldRoles = Role::whereNotIn('name', $keepRoles)->get();

        foreach ($oldRoles as $role) {
            $userCount = $role->users()->count();

            if ($userCount > 0) {
                $this->warn("  [KEEP] {$role->name} - Still has {$userCount} users");
            } else {
                $this->line("  [DELETE] {$role->name}");
                $role->delete();
            }
        }
    }
}
