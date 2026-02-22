<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportAllProductionData extends Command
{
    protected $signature = 'import:all-production-data
        {--branch-id= : Branch UUID to assign all data to}
        {--produced-by-id= : User UUID to set as produced_by for production records}
        {--created-by-id= : User UUID to set as created_by for recipes}
        {--create-departments : Create departments if missing}
        {--create-shifts : Create shifts if missing}
        {--department-category-id= : Category ID for auto-created departments}
        {--start-date= : Only import records from this date onwards (YYYY-MM-DD)}
        {--end-date= : Only import movements up to this date (YYYY-MM-DD)}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import all production data in correct sequence: UOMs → Items → Products → Recipes → Production Records → Stock Movements';

    public function handle(): int
    {
        $this->info("========================================");
        $this->info("  Sweettooth Production Data Import");
        $this->info("========================================");
        $this->newLine();

        $branchId = $this->option('branch-id');
        $dryRun = $this->option('dry-run') ? '--dry-run' : '';
        $createDepartments = $this->option('create-departments') ? '--create-departments' : '';
        $createShifts = $this->option('create-shifts') ? '--create-shifts' : '';
        $departmentCategoryId = $this->option('department-category-id') ? "--department-category-id={$this->option('department-category-id')}" : '';
        $startDate = $this->option('start-date') ? "--start-date={$this->option('start-date')}" : '';
        $endDate = $this->option('end-date') ? "--end-date={$this->option('end-date')}" : '';
        
        $branchOption = $branchId ? "--branch-id={$branchId}" : '';
        $producedByOption = $this->option('produced-by-id') ? "--produced-by-id={$this->option('produced-by-id')}" : '';
        $createdByOption = $this->option('created-by-id') ? "--created-by-id={$this->option('created-by-id')}" : '';

        // Step 1: Seed UOMs
        $this->info("[Step 1/6] Seeding Units of Measure...");
        $this->newLine();
        Artisan::call('uoms:seed');
        $this->line(Artisan::output());
        $this->newLine();

        // Step 2: Import Items
        $this->info("[Step 2/6] Importing Items (Ingredients)...");
        $this->newLine();
        Artisan::call('import:production-items-new', [
            '--branch-id' => $branchId,
            '--dry-run' => $dryRun,
        ]);
        $this->line(Artisan::output());
        $this->newLine();

        // Step 3: Import Products
        $this->info("[Step 3/6] Importing Products...");
        $this->newLine();
        Artisan::call('import:production-products-new', [
            '--branch-id' => $branchId,
            '--dry-run' => $dryRun,
        ]);
        $this->line(Artisan::output());
        $this->newLine();

        // Step 4: Import Recipes
        $this->info("[Step 4/6] Importing Recipes...");
        $this->newLine();
        Artisan::call('import:production-recipes-new', [
            '--branch-id' => $branchId,
            '--created-by-id' => $this->option('created-by-id'),
            '--dry-run' => $dryRun,
        ]);
        $this->line(Artisan::output());
        $this->newLine();

        // Step 5: Import Production Records
        $this->info("[Step 5/6] Importing Production Records...");
        $this->newLine();
        Artisan::call('import:production-records-real', [
            '--branch-id' => $branchId,
            '--produced-by-id' => $this->option('produced-by-id'),
            '--create-shifts' => $this->option('create-shifts'),
            '--create-departments' => $this->option('create-departments'),
            '--department-category-id' => $this->option('department-category-id'),
            '--start-date' => $this->option('start-date'),
            '--end-date' => $this->option('end-date'),
            '--dry-run' => $dryRun,
        ]);
        $this->line(Artisan::output());
        $this->newLine();

        // Step 6: Import Stock Movements
        $this->info("[Step 6/6] Importing Stock Movements...");
        $this->newLine();
        Artisan::call('import:production-stock-movements-real', [
            '--branch-id' => $branchId,
            '--create-departments' => $this->option('create-departments'),
            '--department-category-id' => $this->option('department-category-id'),
            '--start-date' => $this->option('start-date'),
            '--end-date' => $this->option('end-date'),
            '--dry-run' => $dryRun,
        ]);
        $this->line(Artisan::output());
        $this->newLine();

        $this->info("========================================");
        $this->info("  Import Complete!");
        $this->info("========================================");
        
        if ($dryRun) {
            $this->warn("DRY RUN: No data was written to the database.");
            $this->warn("Remove --dry-run to actually import data.");
        }

        return 0;
    }
}
