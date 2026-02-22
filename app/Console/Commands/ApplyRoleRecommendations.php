<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ApplyRoleRecommendations extends Command
{
    protected $signature = 'roles:apply-recommendations {--assign-users : Assign roles to existing users without roles}';
    protected $description = 'Apply role recommendations (permissions + roles + permissions sync)';

    public function handle(): int
    {
        $this->info('Applying role recommendations...');

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\AssignPermissionsToRolesSeeder',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        if ($this->option('assign-users')) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\AssignRolesToExistingUsersSeeder',
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        $this->info('Role recommendations applied.');

        return self::SUCCESS;
    }
}
