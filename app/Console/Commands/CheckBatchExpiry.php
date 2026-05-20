<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\StockBatchService;
use Illuminate\Console\Command;

class CheckBatchExpiry extends Command
{
    protected $signature = 'inventory:check-batch-expiry {--branch= : Branch UUID to process (defaults to all branches)}';

    protected $description = 'Mark expired stock batches and sync stock health status';

    public function handle(StockBatchService $service): int
    {
        $branchOption = $this->option('branch');

        if ($branchOption) {
            $branches = Branch::where('id', $branchOption)->pluck('id');
            if ($branches->isEmpty()) {
                $this->error("Branch {$branchOption} not found.");
                return self::FAILURE;
            }
        } else {
            $branches = Branch::pluck('id');
        }

        $total = 0;
        foreach ($branches as $branchId) {
            $count = $service->markExpiredBatches($branchId);
            if ($count > 0) {
                $this->info("Branch {$branchId}: {$count} batch(es) marked expired.");
            }
            $total += $count;
        }

        $this->info("Done. Total expired batches marked: {$total}");

        return self::SUCCESS;
    }
}
