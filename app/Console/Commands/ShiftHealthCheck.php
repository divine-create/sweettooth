<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShiftHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:health-check
                          {--alert : Send alerts for critical issues}';

    protected $description = 'Monitor shift system health and send alerts';

    protected array $healthStatus = [
        'database' => false,
        'active_shifts' => false,
        'expired_shifts' => false,
        'notifications' => false,
        'auto_clock_out' => false,
    ];

    public function handle()
    {
        $this->info('🔍 Running shift system health check...');

        $this->checkDatabaseHealth();
        $this->checkActiveShiftsHealth();
        $this->checkExpiredShiftsHealth();
        $this->checkNotificationsHealth();
        $this->checkAutoClockOutHealth();

        $this->displayHealthReport();

        if ($this->option('alert')) {
            $this->sendHealthAlerts();
        }

        return $this->getOverallHealth() ? 0 : 1;
    }

    protected function checkDatabaseHealth()
    {
        try {
            $shiftCount = \App\Models\Shift::count();
            $configCount = \App\Models\ShiftConfiguration::count();

            $this->healthStatus['database'] = $shiftCount >= 0 && $configCount >= 0;

            $this->info("✅ Database: {$shiftCount} shifts, {$configCount} configurations");

        } catch (\Exception $e) {
            $this->healthStatus['database'] = false;
            $this->error("❌ Database: " . $e->getMessage());
        }
    }

    protected function checkActiveShiftsHealth()
    {
        try {
            $activeCount = \App\Models\Shift::where('status', 'active')->count();
            $maxAllowed = config('shift-monitoring.max_active_shifts', 1000);

            $this->healthStatus['active_shifts'] = $activeCount <= $maxAllowed;

            if ($activeCount > $maxAllowed) {
                $this->error("❌ Active shifts: {$activeCount} (exceeds limit of {$maxAllowed})");
            } else {
                $this->info("✅ Active shifts: {$activeCount}");
            }

        } catch (\Exception $e) {
            $this->healthStatus['active_shifts'] = false;
            $this->error("❌ Active shifts check failed: " . $e->getMessage());
        }
    }

    protected function checkExpiredShiftsHealth()
    {
        try {
            $expiredCount = \App\Models\Shift::where('status', 'active')
                ->where('shift_date', '<', now()->subDay())
                ->count();

            $maxAllowed = config('shift-monitoring.max_expired_shifts', 50);

            $this->healthStatus['expired_shifts'] = $expiredCount <= $maxAllowed;

            if ($expiredCount > $maxAllowed) {
                $this->error("❌ Expired shifts: {$expiredCount} (exceeds limit of {$maxAllowed})");
            } else {
                $this->info("✅ Expired shifts: {$expiredCount}");
            }

        } catch (\Exception $e) {
            $this->healthStatus['expired_shifts'] = false;
            $this->error("❌ Expired shifts check failed: " . $e->getMessage());
        }
    }

    protected function checkNotificationsHealth()
    {
        try {
            $failedNotifications = \DB::table('failed_jobs')
                ->where('payload', 'like', '%notification%')
                ->where('failed_at', '>', now()->subHours(24))
                ->count();

            $this->healthStatus['notifications'] = $failedNotifications < 10;

            if ($failedNotifications >= 10) {
                $this->error("❌ Notifications: {$failedNotifications} failed in last 24 hours");
            } else {
                $this->info("✅ Notifications: {$failedNotifications} failed in last 24 hours");
            }

        } catch (\Exception $e) {
            $this->healthStatus['notifications'] = false;
            $this->error("❌ Notifications check failed: " . $e->getMessage());
        }
    }

    protected function checkAutoClockOutHealth()
    {
        try {
            $lastRun = \Cache::get('auto_clock_out_last_run');

            if (!$lastRun) {
                $this->healthStatus['auto_clock_out'] = false;
                $this->error("❌ Auto clock out: Never run");
                return;
            }

            $lastRunTime = \Carbon\Carbon::parse($lastRun);
            $minutesSince = $lastRunTime->diffInMinutes(now());

            $this->healthStatus['auto_clock_out'] = $minutesSince <= 10; // Should run every 5 minutes

            if ($minutesSince > 10) {
                $this->error("❌ Auto clock out: Last run {$minutesSince} minutes ago");
            } else {
                $this->info("✅ Auto clock out: Last run {$minutesSince} minutes ago");
            }

        } catch (\Exception $e) {
            $this->healthStatus['auto_clock_out'] = false;
            $this->error("❌ Auto clock out check failed: " . $e->getMessage());
        }
    }

    protected function displayHealthReport()
    {
        $this->newLine();
        $this->line('📊 Health Check Summary');
        $this->line('─' . str_repeat('─', 40));

        $overallHealth = $this->getOverallHealth();
        $status = $overallHealth ? '🟢 HEALTHY' : '🔴 UNHEALTHY';

        $this->info("Overall Status: {$status}");
        $this->newLine();

        foreach ($this->healthStatus as $component => $healthy) {
            $icon = $healthy ? '✅' : '❌';
            $name = ucwords(str_replace('_', ' ', $component));
            $this->line("{$icon} {$name}");
        }
    }

    protected function getOverallHealth(): bool
    {
        return !in_array(false, $this->healthStatus, true);
    }

    protected function sendHealthAlerts()
    {
        if ($this->getOverallHealth()) {
            return; // All good, no alerts needed
        }

        $criticalIssues = array_filter($this->healthStatus, fn($healthy) => !$healthy);

        try {
            $alertManager = app(\App\Services\AlertManager::class);

            foreach ($criticalIssues as $component => $status) {
                $alertManager->triggerAlert('system_health', [
                    'component' => $component,
                    'status' => 'critical',
                    'timestamp' => now()->toIso8601String()
                ]);
            }

            $this->info("🚨 Health alerts sent for " . count($criticalIssues) . " critical issues");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send health alerts: " . $e->getMessage());
        }
    }
}
