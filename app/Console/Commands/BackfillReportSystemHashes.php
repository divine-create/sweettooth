<?php

namespace App\Console\Commands;

use App\Models\DepartmentReport;
use Illuminate\Console\Command;

class BackfillReportSystemHashes extends Command
{
    protected $signature = 'reports:backfill-system-hash
                            {--dry-run : Show how many reports would be updated without saving}
                            {--no-lock : Do not set system_data_locked_at when missing}';

    protected $description = 'Backfill system data hashes for existing department reports';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipLock = (bool) $this->option('no-lock');

        $query = DepartmentReport::query()
            ->whereNull('system_data_hash')
            ->orderBy('created_at');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No reports found without system data hash.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} report(s) without system data hash.");

        if ($dryRun) {
            $this->info('Dry run enabled. No changes were saved.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $query->chunk(200, function ($reports) use (&$updated, &$skipped, $skipLock) {
            foreach ($reports as $report) {
                try {
                    $hash = DepartmentReport::computeSystemDataHash(
                        $report->report_data ?? [],
                        $report->summary_metrics ?? [],
                        $report->charts_data ?? []
                    );

                    $attributes = [
                        'system_data_hash' => $hash,
                        'system_data_version' => $report->system_data_version ?: 1,
                    ];

                    if (!$skipLock && !$report->system_data_locked_at) {
                        $attributes['system_data_locked_at'] = now();
                    }

                    $report->forceFill($attributes)->save();
                    $updated++;
                } catch (\Exception $e) {
                    $skipped++;
                }
            }
        });

        $this->info("Updated {$updated} report(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
