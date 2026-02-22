<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Console\Command;

class CheckUserDepartmentRequirements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:check-departments {--fix : Attempt to assign missing departments based on roles} {--report : Generate detailed report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that all non-super-admin users have departments assigned. Optionally fix missing assignments.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking user department requirements...');
        $this->newLine();

        // Get users without departments
        $result = RolePermissionService::ensureAllUsersHaveDepartment();
        $usersWithoutDept = $result['users'];
        $totalMissing = $result['total_users_missing_dept'];

        if ($totalMissing === 0) {
            $this->info('✓ All non-super-admin users have departments assigned!');

            return 0;
        }

        $this->warn("⚠ Found {$totalMissing} user(s) without department assignment:");
        $this->newLine();

        // Display table
        $this->table(
            ['ID', 'Name', 'Email', 'Branch ID', 'Roles'],
            array_map(function ($user) {
                $userData = User::find($user['id']);
                $roles = $userData ? $userData->roles->pluck('name')->join(', ') : 'N/A';

                return [
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['branch_id'] ?? 'None',
                    $roles ?: 'None',
                ];
            }, $usersWithoutDept)
        );

        if ($this->option('fix')) {
            $this->newLine();
            $this->warn('--fix option would require manual department assignment.');
            $this->info('Please use the admin interface to assign departments to these users.');

            return 1;
        }

        if ($this->option('report')) {
            $this->newLine();
            $this->info('Detailed Report:');
            foreach ($usersWithoutDept as $user) {
                $userData = User::find($user['id']);
                $this->line("  • {$user['name']} ({$user['email']})");
                $this->line("    - Branch: {$user['branch_id']}");
                if ($userData) {
                    $roles = $userData->roles->pluck('name')->join(', ');
                    $roleDisplay = $roles ?: 'None';
                    $this->line("    - Roles: {$roleDisplay}");
                }
                $this->line("    - Status: Missing Department");
            }
        }

        $this->newLine();
        $this->warn('ACTION REQUIRED: Assign departments to these users before they can be assigned roles.');

        return 1;
    }
}
