<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Auto-provisions accounting periods so GL posting never stalls at month rollover.
 * Ensures an OPEN period exists for the current month and a configurable number of
 * months ahead, per branch. Idempotent — safe to run on a schedule.
 */
class EnsureAccountingPeriods extends Command
{
    protected $signature = 'accounting:ensure-periods {--months=2 : Number of months to ensure, including the current one}';

    protected $description = 'Ensure an open accounting period exists for the current and upcoming months (per branch).';

    public function handle(): int
    {
        $monthsAhead = max(1, (int) $this->option('months'));
        $branches = Branch::query()->get(['id']);

        if ($branches->isEmpty()) {
            $this->warn('No branches found; nothing to do.');
            return self::SUCCESS;
        }

        $created = 0;
        foreach ($branches as $branch) {
            for ($i = 0; $i < $monthsAhead; $i++) {
                $month = Carbon::now()->startOfMonth()->addMonths($i);

                $period = AccountingPeriod::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'year' => (int) $month->year,
                        'month' => (int) $month->month,
                    ],
                    [
                        'period_start' => $month->copy()->startOfMonth(),
                        'period_end' => $month->copy()->endOfMonth(),
                        'status' => 'open',
                    ]
                );

                if ($period->wasRecentlyCreated) {
                    $created++;
                    $this->info("Created open period {$month->format('M Y')} for branch {$branch->id}");
                }
            }
        }

        $this->info("Done. {$created} period(s) created.");
        return self::SUCCESS;
    }
}
