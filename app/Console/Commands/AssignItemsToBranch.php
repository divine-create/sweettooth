<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignItemsToBranch extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:assign-items-branch {branch_id : Branch UUID to assign} {--dry-run : Show counts without updating}';

    /**
     * The console command description.
     */
    protected $description = 'Assign all items and stocks to a single branch';

    public function handle(): int
    {
        $branchId = (string) $this->argument('branch_id');
        $dryRun = (bool) $this->option('dry-run');

        $itemCount = Item::query()->count();
        $stockCount = Stock::query()->count();

        $this->info("Items: {$itemCount}");
        $this->info("Stocks: {$stockCount}");
        $this->info("Target branch_id: {$branchId}");

        if ($dryRun) {
            $this->comment('Dry run only. No changes applied.');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($branchId) {
            Item::query()->update(['branch_id' => $branchId]);
            Stock::query()->update(['branch_id' => $branchId]);
        });

        $this->info('All items and stocks assigned to the branch.');

        return Command::SUCCESS;
    }
}
