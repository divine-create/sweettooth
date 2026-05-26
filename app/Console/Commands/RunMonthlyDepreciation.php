<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Services\AssetDepreciationService;
use Illuminate\Console\Command;

class RunMonthlyDepreciation extends Command
{
    protected $signature = 'accounting:run-depreciation {--period= : Accounting period ID (defaults to current open period)}';

    protected $description = 'Generate monthly fixed asset depreciation entries for the current accounting period';

    public function __construct(private AssetDepreciationService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodId = $this->option('period');

        $period = $periodId
            ? AccountingPeriod::find($periodId)
            : AccountingPeriod::currentPeriod()->first();

        if (! $period) {
            $this->error('No open accounting period found. Open a period first.');
            return self::FAILURE;
        }

        $this->info("Running depreciation for period: {$period->period_start->format('M Y')}");

        $entries = $this->service->generateForPeriod($period);

        if ($entries->isEmpty()) {
            $this->line('No new depreciation entries generated (already run or no active assets).');
            return self::SUCCESS;
        }

        $this->info("Generated {$entries->count()} depreciation entries.");

        $posted  = $entries->where('gl_posting_status', 'posted')->count();
        $failed  = $entries->where('gl_posting_status', 'failed')->count();
        $pending = $entries->where('gl_posting_status', 'pending')->count();

        $this->table(['Status', 'Count'], [
            ['Posted', $posted],
            ['Failed', $failed],
            ['Pending', $pending],
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
