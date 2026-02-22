<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class PurgeLegacyRoles extends Command
{
    protected $signature = 'roles:purge-legacy
                            {--dry-run : Show what would be changed without making changes}
                            {--skip-migrate : Do not run users:migrate-roles before deleting}
                            {--force : Delete roles even if users are still assigned (will detach)}';

    protected $description = 'Remove all roles not in the approved simplified role set';

    public function handle(): int
    {
        $allowedRoles = [
            // Executive/Admin
            'Super Admin',
            'MD',
            'Managing Director',
            'Admin',

            // Managers
            'Head of Production',
            'Sales Manager',
            'HR Manager',
            'Inventory Manager',
            'Accounting Manager',

            // Supervisors/Officers
            'Production Supervisor',
            'Sales Supervisor',
            'Inventory Supervisor',
            'HR Officer',
            'Accountant',

            // Staff
            'Production Staff',
            'Sales Staff',
            'Inventory Staff',
        ];

        $isDryRun = (bool) $this->option('dry-run');
        $skipMigrate = (bool) $this->option('skip-migrate');
        $force = (bool) $this->option('force');

        if (! $skipMigrate) {
            $this->info('Running users:migrate-roles before purging legacy roles...');
            $code = Artisan::call('users:migrate-roles', $isDryRun ? ['--dry-run' => true] : []);
            $this->line(Artisan::output());
            if ($code !== 0) {
                $this->error('users:migrate-roles failed. Aborting purge.');
                return Command::FAILURE;
            }
        }

        $roles = Role::whereNotIn('name', $allowedRoles)->get();

        if ($roles->isEmpty()) {
            $this->info('No legacy roles found.');
            return Command::SUCCESS;
        }

        $this->table(
            ['Role (to delete)', 'Users Assigned'],
            $roles->map(fn ($role) => [$role->name, $role->users()->count()])->toArray()
        );

        if ($isDryRun) {
            $this->warn('DRY RUN: No changes were made.');
            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm('Delete the legacy roles listed above?')) {
            $this->info('Aborted.');
            return Command::SUCCESS;
        }

        foreach ($roles as $role) {
            $userCount = $role->users()->count();

            if ($userCount > 0 && ! $force) {
                $this->warn("Skipping {$role->name} (still assigned to {$userCount} user(s)). Use --force to detach and delete.");
                continue;
            }

            if ($userCount > 0 && $force) {
                $role->users()->detach();
            }

            $role->delete();
            $this->info("Deleted role: {$role->name}");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Legacy roles purge complete.');

        return Command::SUCCESS;
    }
}
