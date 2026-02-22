<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;

class FixUserDepartmentAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-departments {--dry-run : Show what would be fixed without making changes} {--default-dept= : Default department ID to assign}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix users with missing departments by assigning them to a default department.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $defaultDeptId = $this->option('default-dept');

        $this->info('Scanning for users without departments...');

        // Get users without departments (excluding super admins)
        $usersWithoutDept = User::whereNull('department_id')
            ->orWhere('department_id', '')
            ->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'MD', 'Managing Director']);
            })
            ->get();

        if ($usersWithoutDept->isEmpty()) {
            $this->info('✓ No users found without departments!');

            return 0;
        }

        $count = $usersWithoutDept->count();
        $this->warn("Found {$count} user(s) without departments:");
        $this->newLine();

        // Display current state
        $this->table(
            ['ID', 'Name', 'Email', 'Branch ID', 'Current Department'],
            $usersWithoutDept->map(fn ($user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->branch_id ?? 'None',
                $user->department_id ?? 'NULL',
            ])->toArray()
        );

        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made.');
            $this->newLine();

            return 0;
        }

        // Ask which department to assign
        if (! $defaultDeptId) {
            $this->newLine();
            $this->info('Available departments:');

            $departments = Department::orderBy('name')->get();
            foreach ($departments as $dept) {
                $this->line("  [{$dept->id}] {$dept->name} (Branch: {$dept->branch_id})");
            }

            $this->newLine();
            $defaultDeptId = $this->ask('Enter department ID to assign to these users');
        }

        // Validate department exists
        $department = Department::find($defaultDeptId);
        if (! $department) {
            $this->error("Department with ID '{$defaultDeptId}' not found!");

            return 1;
        }

        $this->newLine();
        if (! $this->confirm("Assign {$count} user(s) to department: {$department->name} ({$department->id})?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Assign department to all users
        $updated = 0;
        foreach ($usersWithoutDept as $user) {
            $user->update(['department_id' => $defaultDeptId]);
            $updated++;
            $this->line("✓ Updated {$user->name} → {$department->name}");
        }

        $this->newLine();
        $this->info("✓ Successfully updated {$updated} user(s)!");

        return 0;
    }
}
