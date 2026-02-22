<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Notifications\ShiftEndingWarning;
use App\Notifications\ShiftAutoClockOutImminent;
use App\Notifications\ShiftReminder;
use App\Notifications\ShiftCompleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;

class ShiftNotificationService
{
    /**
     * Send shift ending warning notification
     */
    public function sendShiftEndingWarning(Shift $shift, int $minutesRemaining): void
    {
        try {
            $user = $shift->employee;

            // Prevent duplicate notifications within short time
            $cacheKey = "shift_warning_{$shift->id}_{$minutesRemaining}";
            if (Cache::has($cacheKey)) {
                return;
            }

            $user->notify(new ShiftEndingWarning($shift, $minutesRemaining));

            // Cache for 5 minutes to prevent spam
            Cache::put($cacheKey, true, 300);

            Log::info('Shift ending warning sent', [
                'shift_id' => $shift->id,
                'employee_id' => $user->id,
                'minutes_remaining' => $minutesRemaining
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shift ending warning', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send auto clock out imminent warning
     */
    public function sendAutoClockOutWarning(Shift $shift, int $minutesUntilAutoClockOut): void
    {
        try {
            $user = $shift->employee;

            // Prevent duplicate notifications
            $cacheKey = "auto_clock_warning_{$shift->id}_{$minutesUntilAutoClockOut}";
            if (Cache::has($cacheKey)) {
                return;
            }

            $user->notify(new ShiftAutoClockOutImminent($shift, $minutesUntilAutoClockOut));

            // Cache for 10 minutes
            Cache::put($cacheKey, true, 600);

            Log::info('Auto clock out warning sent', [
                'shift_id' => $shift->id,
                'employee_id' => $user->id,
                'minutes_until_auto_clock' => $minutesUntilAutoClockOut
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send auto clock out warning', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send shift reminder for upcoming shifts
     */
    public function sendShiftReminder(User $user, string $shiftType, Carbon $scheduledTime): void
    {
        try {
            // Prevent duplicate reminders within 24 hours
            $cacheKey = "shift_reminder_{$user->id}_{$shiftType}_" . $scheduledTime->format('Y-m-d');
            if (Cache::has($cacheKey)) {
                return;
            }

            $user->notify(new ShiftReminder($shiftType, $scheduledTime));

            // Cache for 24 hours
            Cache::put($cacheKey, true, 86400);

            Log::info('Shift reminder sent', [
                'employee_id' => $user->id,
                'shift_type' => $shiftType,
                'scheduled_time' => $scheduledTime->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shift reminder', [
                'employee_id' => $user->id,
                'shift_type' => $shiftType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send shift completed notification
     */
    public function sendShiftCompletedNotification(Shift $shift, string $completionReason = 'manual'): void
    {
        try {
            $user = $shift->employee;

            $user->notify(new ShiftCompleted($shift, $completionReason));

            Log::info('Shift completed notification sent', [
                'shift_id' => $shift->id,
                'employee_id' => $user->id,
                'completion_reason' => $completionReason,
                'total_hours' => $shift->clock_in ? $shift->clock_in->diffInHours($shift->clock_out) : 0
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shift completed notification', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check and send notifications for active shifts
     */
    public function checkAndSendNotifications(): array
    {
        $notificationsSent = [
            'warnings' => 0,
            'auto_clock_warnings' => 0,
            'reminders' => 0
        ];

        try {
            // Get all active shifts
            $activeShifts = Shift::with(['employee', 'configuration'])
                ->where('status', 'active')
                ->where('shift_date', Carbon::today())
                ->get();

            foreach ($activeShifts as $shift) {
                // Check for shift ending warnings
                $warningMinutes = $this->checkShiftEndingWarnings($shift);
                if ($warningMinutes) {
                    $this->sendShiftEndingWarning($shift, $warningMinutes);
                    $notificationsSent['warnings']++;
                }

                // Check for auto clock out warnings
                $autoClockMinutes = $this->checkAutoClockOutWarnings($shift);
                if ($autoClockMinutes) {
                    $this->sendAutoClockOutWarning($shift, $autoClockMinutes);
                    $notificationsSent['auto_clock_warnings']++;
                }
            }

            // Send shift reminders (for tomorrow's shifts)
            $reminderCount = $this->sendDailyReminders();
            $notificationsSent['reminders'] = $reminderCount;

        } catch (\Exception $e) {
            Log::error('Error in checkAndSendNotifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $notificationsSent;
    }

    /**
     * Check if shift needs ending warning
     */
    private function checkShiftEndingWarnings(Shift $shift): ?int
    {
        if (!$shift->clock_in || !$shift->configuration) {
            return null;
        }

        $now = Carbon::now();
        $expectedEnd = $this->resolveShiftEndTime($shift);
        if (! $expectedEnd) {
            return null;
        }

        // Check warning thresholds
        $warningThresholds = [30, 25, 15]; // minutes before end

        foreach ($warningThresholds as $threshold) {
            $warningTime = $expectedEnd->copy()->subMinutes($threshold);

            if ($now >= $warningTime && $now < $expectedEnd) {
                return $threshold;
            }
        }

        return null;
    }

    /**
     * Check if shift needs auto clock out warning
     */
    private function checkAutoClockOutWarnings(Shift $shift): ?int
    {
        if (!$shift->clock_in || !$shift->configuration) {
            return null;
        }

        $now = Carbon::now();
        $expectedEnd = $this->resolveShiftEndTime($shift);
        if (! $expectedEnd) {
            return null;
        }

        $autoClockOutTime = $expectedEnd->copy()->addMinutes((int) $shift->configuration->auto_clock_out_minutes);

        // Check warning thresholds
        $warningThresholds = [30, 25, 15]; // minutes before auto clock out

        foreach ($warningThresholds as $threshold) {
            $warningTime = $autoClockOutTime->copy()->subMinutes($threshold);

            if ($now >= $warningTime && $now < $autoClockOutTime) {
                return $threshold;
            }
        }

        return null;
    }

    /**
     * Send daily reminders for upcoming shifts
     */
    private function sendDailyReminders(): int
    {
        $remindersSent = 0;

        try {
            // Get users who have shifts tomorrow but no active shift today
            $tomorrow = Carbon::tomorrow();

            // This would typically query a schedule system
            // For now, we'll implement a basic version
            // In a real system, this would integrate with employee schedules

            Log::info('Daily shift reminders processed', ['reminders_sent' => $remindersSent]);

        } catch (\Exception $e) {
            Log::error('Error sending daily reminders', ['error' => $e->getMessage()]);
        }

        return $remindersSent;
    }

    /**
     * Get notification status for a user
     */
    public function getUserNotificationStatus(User $user): array
    {
        $activeShift = Shift::where('employee_id', $user->id)
            ->where('status', 'active')
            ->where('shift_date', Carbon::today())
            ->with('configuration')
            ->first();

        if (!$activeShift || !$activeShift->configuration) {
            return ['status' => 'no_active_shift'];
        }

        $config = $activeShift->configuration;
        $now = Carbon::now();

        // Calculate times
        $expectedEnd = $this->resolveShiftEndTime($activeShift);
        if (! $expectedEnd) {
            return ['status' => 'no_active_shift'];
        }
        $autoClockOutTime = $expectedEnd->copy()->addMinutes($config->auto_clock_out_minutes);

        $minutesToEnd = $now->diffInMinutes($expectedEnd, false);
        $minutesToAutoClock = $now->diffInMinutes($autoClockOutTime, false);

        return [
            'status' => 'active_shift',
            'shift_type' => $activeShift->shift_type,
            'minutes_to_end' => max(0, $minutesToEnd),
            'minutes_to_auto_clock' => max(0, $minutesToAutoClock),
            'warnings_needed' => $this->checkShiftEndingWarnings($activeShift) !== null,
            'auto_clock_warning_needed' => $this->checkAutoClockOutWarnings($activeShift) !== null
        ];
    }

    private function resolveShiftEndTime(Shift $shift): ?Carbon
    {
        if (! $shift->configuration) {
            return null;
        }

        $shiftDate = $shift->shift_date instanceof Carbon
            ? $shift->shift_date->toDateString()
            : Carbon::parse($shift->shift_date)->toDateString();

        $startTimeRaw = $shift->configuration->start_time instanceof Carbon
            ? $shift->configuration->start_time->format('H:i:s')
            : (string) $shift->configuration->start_time;
        $endTimeRaw = $shift->configuration->end_time instanceof Carbon
            ? $shift->configuration->end_time->format('H:i:s')
            : (string) $shift->configuration->end_time;

        try {
            $start = Carbon::parse("{$shiftDate} {$startTimeRaw}");
            $end = Carbon::parse("{$shiftDate} {$endTimeRaw}");

            if ($end <= $start) {
                $end->addDay();
            }

            return $end;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
