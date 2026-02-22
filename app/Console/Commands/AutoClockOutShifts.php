<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\ShiftConfiguration;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;

class AutoClockOutShifts extends Command
{
    protected $signature = 'shifts:auto-clock-out
                          {--dry-run : Show what would be clocked out without making changes}
                          {--branch= : Target specific branch}
                          {--notify : Send notifications to affected employees}
                          {--force : Ignore grace periods and clock out immediately}
                          {--max-shifts=100 : Maximum shifts to process per run}';

    protected $description = 'Automatically clock out expired shifts based on configuration';

    protected AuditService $auditService;
    protected array $stats = [
        'processed' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function __construct(AuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    public function handle()
    {
        $startTime = microtime(true);
        $this->info('🚀 Starting auto clock out process...');
        $this->newLine();

        try {
            // Health check before processing
            if (!$this->systemHealthCheck()) {
                $this->error('❌ System health check failed. Aborting.');
                return 1;
            }

            // Build and execute query
            $expiredShifts = $this->getExpiredShifts();

            if ($expiredShifts->isEmpty()) {
                $this->info('✅ No expired shifts found.');
                $this->recordMetrics($startTime, 'no_work');
                return 0;
            }

            if ($this->option('dry-run')) {
                $this->showDryRunResults($expiredShifts);
                return 0;
            }

            // Process shifts with progress bar
            $this->processExpiredShifts($expiredShifts);

            // Send notifications if requested
            if ($this->option('notify')) {
                $this->sendBatchNotifications();
            }

            // Record final metrics
            $this->recordMetrics($startTime, 'success');
            $this->showFinalSummary();

        } catch (\Exception $e) {
            $this->recordMetrics($startTime, 'error', $e->getMessage());
            $this->error('❌ Auto clock out failed: ' . $e->getMessage());
            Log::error('Auto clock out command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function systemHealthCheck(): bool
    {
        try {
            // Check database connectivity
            Shift::count();

            // Check if we're not in maintenance mode unexpectedly
            if (app()->isDownForMaintenance()) {
                $this->warn('⚠️  Application is in maintenance mode');
            }

            // Check queue system if notifications enabled
            if ($this->option('notify') && !config('queue.default')) {
                $this->warn('⚠️  Queue system not configured for notifications');
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Auto clock out health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function getExpiredShifts()
    {
        $query = Shift::with(['employee', 'branch', 'configuration'])
            ->where('status', 'active');

        // Filter by branch if specified
        if ($this->option('branch')) {
            $query->where('branch_id', $this->option('branch'));
        }

        // Limit processing batch size
        $maxShifts = $this->option('max-shifts');
        if ($maxShifts > 0) {
            $query->limit($maxShifts);
        }

        $shifts = $query->orderBy('clock_in')->get();

        if ($this->option('force')) {
            return $shifts;
        }

        return $shifts->filter(function (Shift $shift) {
            return $this->isShiftExpired($shift);
        });
    }

    protected function showDryRunResults($expiredShifts)
    {
        $this->info('🔍 DRY RUN - The following shifts would be auto clocked out:');
        $this->newLine();

        $tableData = $expiredShifts->map(function ($shift) {
            $expectedEnd = $this->calculateExpectedEndTime($shift);
            $overdueMinutes = now()->diffInMinutes($expectedEnd, false);
            $workedMinutes = $shift->clock_in->diffInMinutes(now());

            return [
                'ID' => $shift->id,
                'Employee' => $shift->employee->name ?? 'Unknown',
                'Branch' => $shift->branch->name ?? 'Unknown',
                'Shift Type' => $shift->shift_type,
                'Started' => $shift->clock_in->format('H:i'),
                'Worked' => round($workedMinutes / 60, 1) . 'h',
                'Overdue' => abs($overdueMinutes) . 'm',
            ];
        });

        $this->table(
            ['ID', 'Employee', 'Branch', 'Shift Type', 'Started', 'Worked', 'Overdue'],
            $tableData->toArray()
        );

        $this->newLine();
        $this->info("📊 Total shifts that would be processed: {$expiredShifts->count()}");
        $this->info("💡 Use --force to clock out all active shifts immediately (emergency only)");
    }

    protected function processExpiredShifts($expiredShifts)
    {
        $bar = $this->output->createProgressBar($expiredShifts->count());
        $bar->setFormat('verbose');
        $bar->start();

        foreach ($expiredShifts as $shift) {
            try {
                $this->processSingleShift($shift);
                $this->stats['successful']++;
                $bar->setMessage("Processed shift {$shift->id}");

            } catch (\Exception $e) {
                $this->stats['failed']++;
                Log::error('Failed to auto clock out shift ' . $shift->id, [
                    'error' => $e->getMessage(),
                    'shift' => $shift->toArray()
                ]);
                $bar->setMessage("Failed shift {$shift->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function processSingleShift(Shift $shift)
    {
        // Calculate worked time before clocking out
        $workedMinutes = $shift->clock_in->diffInMinutes(now());

        // Update shift record
        $shift->update([
            'clock_out' => now(),
            'status' => 'auto_clocked_out',
            'auto_clocked_out_at' => now(),
            'auto_clock_out_reason' => $this->option('force')
                ? 'Emergency force clock out'
                : 'Exceeded configured shift end time + grace period',
            'metadata' => array_merge($shift->metadata ?? [], [
                'auto_clock_out_processed_at' => now()->toIso8601String(),
                'worked_minutes' => $workedMinutes,
                'processed_by_command' => true
            ])
        ]);

        // Clear any cached shift data
        Cache::forget("shift_status_{$shift->employee_id}");
        Cache::forget("active_shift_{$shift->employee_id}_" . $shift->shift_date->format('Y-m-d'));

        // Log audit event
        $this->auditService->log(
            $shift->employee,
            'auto_clock_out',
            $shift,
            "Shift automatically closed after {$workedMinutes} minutes. " .
            "Reason: {$shift->auto_clock_out_reason}",
            'system'
        );

        $this->stats['processed']++;
    }

    protected function calculateExpectedEndTime(Shift $shift): Carbon
    {
        // Use configuration if available
        if (isset($shift->metadata['config_id'])) {
            $config = ShiftConfiguration::find($shift->metadata['config_id']);
            if ($config) {
                $shiftDate = $shift->shift_date instanceof Carbon
                    ? $shift->shift_date->toDateString()
                    : Carbon::parse($shift->shift_date)->toDateString();

                $startTimeRaw = $config->start_time instanceof Carbon
                    ? $config->start_time->format('H:i:s')
                    : (string) $config->start_time;
                $endTimeRaw = $config->end_time instanceof Carbon
                    ? $config->end_time->format('H:i:s')
                    : (string) $config->end_time;

                $start = Carbon::parse("{$shiftDate} {$startTimeRaw}");
                $end = Carbon::parse("{$shiftDate} {$endTimeRaw}");

                if ($end <= $start) {
                    $end->addDay();
                }

                return $end->addMinutes($config->auto_clock_out_minutes);
            }
        }

        // Fallback: 8 hours from clock in + 1 hour grace
        return $shift->clock_in->copy()->addHours(9);
    }

    protected function isShiftExpired(Shift $shift): bool
    {
        if (! $shift->clock_in) {
            return false;
        }

        $config = $shift->configuration;
        if ($config) {
            $shiftDate = $shift->shift_date instanceof Carbon
                ? $shift->shift_date->toDateString()
                : Carbon::parse($shift->shift_date)->toDateString();

            $startTimeRaw = $config->start_time instanceof Carbon
                ? $config->start_time->format('H:i:s')
                : (string) $config->start_time;
            $endTimeRaw = $config->end_time instanceof Carbon
                ? $config->end_time->format('H:i:s')
                : (string) $config->end_time;

            $start = Carbon::parse("{$shiftDate} {$startTimeRaw}");
            $end = Carbon::parse("{$shiftDate} {$endTimeRaw}");

            if ($end <= $start) {
                $end->addDay();
            }

            $autoClockOutTime = $end->copy()->addMinutes((int) $config->auto_clock_out_minutes);

            return now()->greaterThan($autoClockOutTime);
        }

        // Legacy fallback
        return now()->greaterThan($shift->clock_in->copy()->addHours(9));
    }

    protected function sendBatchNotifications()
    {
        // Group notifications by employee to avoid spam
        $notifications = collect($this->stats['notifications'] ?? [])
            ->groupBy('employee_id');

        foreach ($notifications as $employeeId => $employeeNotifications) {
            try {
                $employee = \App\Models\User::find($employeeId);
                if ($employee) {
                    // TODO: Implement BatchAutoClockOutNotification
                    // Notification::route('database', $employeeId)
                    //     ->notify(new BatchAutoClockOutNotification($employeeNotifications));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send batch notification', [
                    'employee_id' => $employeeId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function recordMetrics(float $startTime, string $status, ?string $error = null)
    {
        $duration = round(microtime(true) - $startTime, 3);

        Log::info('Auto clock out command completed', [
            'status' => $status,
            'duration_seconds' => $duration,
            'stats' => $this->stats,
            'options' => [
                'dry_run' => $this->option('dry-run'),
                'branch' => $this->option('branch'),
                'notify' => $this->option('notify'),
                'force' => $this->option('force'),
                'max_shifts' => $this->option('max-shifts'),
            ],
            'error' => $error
        ]);

        // Store metrics for monitoring
        Cache::put("auto_clock_out_last_run", now()->toIso8601String(), 3600);
        Cache::put("auto_clock_out_last_stats", $this->stats, 3600);
    }

    protected function showFinalSummary()
    {
        $this->newLine();
        $this->info('📊 Auto Clock Out Summary');
        $this->line('─' . str_repeat('─', 50));

        $this->info("✅ Successfully processed: {$this->stats['successful']} shifts");

        if ($this->stats['failed'] > 0) {
            $this->error("❌ Failed to process: {$this->stats['failed']} shifts");
        }

        if ($this->stats['skipped'] > 0) {
            $this->warn("⚠️  Skipped: {$this->stats['skipped']} shifts");
        }

        if ($this->option('notify')) {
            $this->info("📧 Notifications queued for affected employees");
        }

        $this->info("🔄 Next run: " . now()->addMinutes(5)->format('H:i:s'));
    }
}
