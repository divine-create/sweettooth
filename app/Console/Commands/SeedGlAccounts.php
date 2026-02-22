<?php

namespace App\Console\Commands;

use Database\Seeders\GlAccountSeeder;
use Illuminate\Console\Command;

class SeedGlAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:seed-gl-accounts {--force : Force seeding without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the GL accounts chart of accounts. WARNING: This will truncate existing GL accounts.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This command will clear all existing GL accounts.');
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Seeding cancelled.');
                return 0;
            }
        }

        try {
            $this->info('Seeding GL accounts from chart of accounts...');
            
            $seeder = new GlAccountSeeder();
            $count = $seeder->run();

            $this->info('✅ GL accounts seeded successfully!');
            $this->info('Total accounts created: ' . $count);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            return 1;
        }
    }
}
