<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportRealData extends Command
{
    protected $signature = 'real-data:import {--with-roles : Apply role recommendations before import} {--assign-users : Assign roles to users after import}';
    protected $description = 'Import real data from real_data directory (Port Harcourt seed)';

    public function handle(): int
    {
        if ($this->option('with-roles')) {
            $this->info('Applying roles and permissions before import...');
            Artisan::call('roles:apply-recommendations', [
                '--assign-users' => false,
            ]);
            $this->line(Artisan::output());
        }

        $this->info('Importing real data...');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PortHarcourtRealDataSeeder',
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

        $this->info('Real data import complete.');

        return self::SUCCESS;
    }
}
