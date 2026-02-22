<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MigrateEmployeesToUsers extends Command
{
    protected $signature = 'migrate:employees-to-users
                            {--dry-run : Run without making changes}
                            {--batch-size=50 : Number of records to process at once}
                            {--force : Skip all confirmations}
                            {--skip-validation : Skip pre-migration validation}';

    protected $description = 'Migrate employee data to users table with proper roles and unified authentication';

    protected $dryRun = false;
    protected $batchSize = 50;
    protected $migrationStats = [];

    // Role mapping based on existing system roles
    protected $roleMapping = [
        'manager' => 'Admin',
        'supervisor' => 'Sales Manager',
        'lead' => 'Sales Manager',
        'senior' => 'Sales Manager',
        'employee' => 'Sales Staff',
        'staff' => 'Sales Staff',
        'admin' => 'Admin',
        'administrator' => 'Admin',
        'default' => 'Sales Staff', // Default role
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');

        if ($this->dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Validate system state
        if (!$this->option('skip-validation') && !$this->validateSystemState()) {
            return Command::FAILURE;
        }

        // Get migration statistics
        $this->migrationStats = $this->getMigrationStats();
        $this->displayStats();

        if (!$this->option('force') && !$this->confirm('Proceed with migration?')) {
            return Command::SUCCESS;
        }

        // Execute migration
        $this->executeMigration();

        // Verify migration
        if ($this->verifyMigration()) {
            $this->info('✅ Migration completed successfully!');

            if (!$this->dryRun) {
                $this->info('🔄 Next steps:');
                $this->info('1. Run: php artisan migrate:seed-roles-permissions');
                $this->info('2. Update config/auth.php to remove employee guard');
                $this->info('3. Test authentication with new system');
                $this->info('4. Run: php artisan config:clear && php artisan cache:clear');
            }

            return Command::SUCCESS;
        } else {
            $this->error('❌ Migration verification failed');
            return Command::FAILURE;
        }
    }

    protected function validateSystemState(): bool
    {
        $this->info('🔍 Validating system state...');

        // Check if employees table exists
        if (!Schema::hasTable('employees')) {
            $this->error('❌ Employees table does not exist');
            return false;
        }

        // Check if users table has required columns
        $requiredColumns = ['branch_id', 'is_active'];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $this->error("❌ Users table missing required column: {$column}");
                return false;
            }
        }

        // Check for duplicate emails
        $duplicateEmails = DB::select("
            SELECT email, COUNT(*) as count FROM (
                SELECT email FROM users
                UNION ALL
                SELECT email FROM employees
            ) combined
            GROUP BY email
            HAVING count > 1
        ");

        if (!empty($duplicateEmails)) {
            $this->warn('⚠️  Found duplicate emails:');
            foreach ($duplicateEmails as $dup) {
                $this->warn("  - {$dup->email} (appears {$dup->count} times)");
            }

            if (!$this->confirm('Continue with duplicates? (existing users will be preserved)')) {
                return false;
            }
        }

        $this->info('✅ System validation passed');
        return true;
    }

    protected function getMigrationStats(): array
    {
        return [
            'total_employees' => DB::table('employees')->count(),
            'existing_users' => DB::table('users')->count(),
            'active_employees' => DB::table('employees')->where('status', 'active')->count(),
            'employees_with_branches' => DB::table('employees')->whereNotNull('branch_id')->count(),
            'orphaned_employees' => DB::table('employees')
                ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
                ->whereNotNull('employees.branch_id')
                ->whereNull('branches.id')
                ->count(),
        ];
    }

    protected function displayStats(): void
    {
        $stats = $this->migrationStats;

        $this->info('📊 Migration Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Employees', $stats['total_employees']],
                ['Existing Users', $stats['existing_users']],
                ['Active Employees', $stats['active_employees']],
                ['Employees with Branches', $stats['employees_with_branches']],
                ['Orphaned Employees', $stats['orphaned_employees']],
                ['Expected New Users', $stats['total_employees']],
            ]
        );
    }

    protected function executeMigration(): void
    {
        $progressBar = $this->output->createProgressBar($this->migrationStats['total_employees']);
        $progressBar->start();

        $processed = 0;
        $skipped = 0;
        $created = 0;

        DB::table('employees')
            ->orderBy('id')
            ->chunk($this->batchSize, function ($employees) use ($progressBar, &$processed, &$skipped, &$created) {
                foreach ($employees as $employee) {
                    $result = $this->migrateEmployee($employee);

                    if ($result === 'skipped') {
                        $skipped++;
                    } elseif ($result === 'created') {
                        $created++;
                    }

                    $processed++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();

        $this->info("📈 Migration Results:");
        $this->info("  - Processed: {$processed}");
        $this->info("  - Created: {$created}");
        $this->info("  - Skipped: {$skipped}");
    }

    protected function migrateEmployee($employee): string
    {
        // Check if user already exists
        $existingUser = User::where('email', $employee->email)->first();

        if ($existingUser) {
            if ($this->dryRun) {
                $this->info("[DRY RUN] Skipping existing user: {$employee->email}");
            }
            return 'skipped';
        }

        // Validate branch exists
        if ($employee->branch_id && !Branch::find($employee->branch_id)) {
            Log::warning("Employee has invalid branch_id, setting to null", [
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'branch_id' => $employee->branch_id
            ]);
            $employee->branch_id = null;
        }

        // Determine role based on employee data
        $role = $this->determineEmployeeRole($employee);

        $userData = [
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => $employee->password, // Keep existing hash
            'branch_id' => $employee->branch_id,
            'is_active' => ($employee->status ?? 'active') === 'active',
            'created_at' => $employee->created_at ?? now(),
            'updated_at' => $employee->updated_at ?? now(),
        ];

        if (!$this->dryRun) {
            $user = User::create($userData);

            // Assign role
            $user->assignRole($role);

            if ($this->output->isVerbose()) {
                $this->info("✅ Migrated: {$user->email} -> {$role}");
            }

            return 'created';
        } else {
            $this->info("[DRY RUN] Would migrate: {$employee->email} -> {$role}");
            return 'dry_run';
        }
    }

    protected function determineEmployeeRole($employee): string
    {
        // Check job title or position field
        if (isset($employee->job_title)) {
            $title = strtolower($employee->job_title);
            foreach ($this->roleMapping as $keyword => $role) {
                if (str_contains($title, $keyword)) {
                    return $role;
                }
            }
        }

        // Check position or role field
        if (isset($employee->position)) {
            $position = strtolower($employee->position);
            foreach ($this->roleMapping as $keyword => $role) {
                if (str_contains($position, $keyword)) {
                    return $role;
                }
            }
        }

        // Check department for clues
        if (isset($employee->department_id)) {
            $department = DB::table('departments')
                ->where('id', $employee->department_id)
                ->value('name');

            if ($department) {
                $deptName = strtolower($department);
                if (str_contains($deptName, 'management') || str_contains($deptName, 'admin')) {
                    return 'branch-manager';
                }
            }
        }

        // Check if employee has subordinates (indicates manager role)
        $hasSubordinates = DB::table('employees')
            ->where('manager_id', $employee->id)
            ->exists();

        if ($hasSubordinates) {
            return 'branch-manager';
        }

        // Default to employee role
        return 'employee';
    }

    protected function verifyMigration(): bool
    {
        $this->info('🔍 Verifying migration...');

        // Count checks
        $actualUsers = User::count();

        if ($actualUsers < $this->migrationStats['existing_users']) {
            $this->error("❌ User count decreased: {$actualUsers} < {$this->migrationStats['existing_users']}");
            return false;
        }

        // Role assignment check
        $usersWithRoles = User::whereHas('roles')->count();
        $totalUsers = User::count();

        if ($usersWithRoles < $totalUsers * 0.8) { // At least 80% should have roles
            $this->warn("⚠️  Only {$usersWithRoles}/{$totalUsers} users have roles assigned");
        }

        // Branch assignment check
        $usersWithBranches = User::whereNotNull('branch_id')->count();
        $expectedWithBranches = $this->migrationStats['employees_with_branches'];

        if ($usersWithBranches < $expectedWithBranches) {
            $this->warn("⚠️  Fewer users with branches than expected: {$usersWithBranches} < {$expectedWithBranches}");
        }

        $this->info("✅ Verification completed - {$actualUsers} total users");
        return true;
    }
}
