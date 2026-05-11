<?php

namespace App\Services;

use App\Models\ShiftConfiguration;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShiftTimingValidator
{
    /**
     * STRICT TIME WINDOW VALIDATION
     * Morning: 6:00 AM - 12:00 PM (no clock-in before 6 AM or after 12 PM)
     * Afternoon: 12:00 PM - 8:00 PM (no clock-in before 12 PM or after 8 PM)
     */
    public function validateStrictTimeWindows(
        string $shiftType,
        ?string $branchId = null,
        ?Carbon $requestedTime = null
    ): ValidationResult {
        // DISABLING STRICT TIME WINDOWS: Allow staff to clock in whenever they want
        return ValidationResult::valid();
    }

    /**
     * Get STRICT shift configuration with enforced time windows
     */
    private function getStrictShiftConfiguration(string $shiftType, ?string $branchId): ?StrictShiftConfig
    {
        $branchId = $branchId ?? current_branch_id();

        // STRICT TIME WINDOWS - No exceptions
        $strictWindows = [
            'morning' => [
                'name' => 'Morning Shift',
                'clock_in_start' => '06:00:00', // STRICT: Must be 6 AM or later
                'clock_in_end' => '12:00:00',   // STRICT: Must be before 12 PM
                'timezone' => 'UTC' // Will be converted to branch timezone
            ],
            'afternoon' => [
                'name' => 'Afternoon Shift',
                'clock_in_start' => '12:00:00', // STRICT: Must be 12 PM or later
                'clock_in_end' => '20:00:00',   // STRICT: Must be before 8 PM
                'timezone' => 'UTC'
            ],
            'full_time' => [
                'name' => 'Full Time',
                'clock_in_start' => '00:00:00', // No restrictions
                'clock_in_end' => '23:59:59',
                'timezone' => 'UTC'
            ]
        ];

        if (!isset($strictWindows[$shiftType])) {
            return null;
        }

        return new StrictShiftConfig($strictWindows[$shiftType], $branchId);
    }

    /**
     * Get detailed violation information for STRICT validation
     */
    private function getStrictViolationDetails(StrictShiftConfig $config, Carbon $currentTime): array
    {
        $startTime = Carbon::createFromTimeString($config->clock_in_start)
            ->setDateFrom($currentTime)
            ->format('g:i A');

        $endTime = Carbon::createFromTimeString($config->clock_in_end)
            ->setDateFrom($currentTime)
            ->format('g:i A');

        $currentTimeStr = $currentTime->format('g:i A');

        if ($currentTime < Carbon::createFromTimeString($config->clock_in_start)->setDateFrom($currentTime)) {
            return [
                'type' => 'too_early',
                'window' => "{$startTime} - {$endTime}",
                'message' => "❌ CLOCK-IN DENIED: Too early for {$config->name}!\n" .
                            "⏰ Current time: {$currentTimeStr}\n" .
                            "🕐 Allowed window: {$startTime} - {$endTime}\n" .
                            "⏳ Please wait until {$startTime} to clock in."
            ];
        } else {
            return [
                'type' => 'too_late',
                'window' => "{$startTime} - {$endTime}",
                'message' => "❌ CLOCK-IN DENIED: Too late for {$config->name}!\n" .
                            "⏰ Current time: {$currentTimeStr}\n" .
                            "🕐 Allowed window: {$startTime} - {$endTime}\n" .
                            "⏳ This shift window has ended. Please contact your supervisor."
            ];
        }
    }

    /**
     * Check for conflicting shifts
     */
    public function validateNoConflictingShifts(
        string $employeeId,
        string $shiftType,
        string $branchId,
        ?Carbon $requestedTime = null
    ): ValidationResult {
        // DISABLING CONFLICT VALIDATION: Allow multiple clock-ins and overlapping shift records
        return ValidationResult::valid();
    }

    /**
     * Get available shifts for current time
     */
    public function getAvailableShifts(?string $branchId = null): array
    {
        $branchId = $branchId ?? current_branch_id();
        $currentTime = Carbon::now();

        $availableConfigs = ShiftConfiguration::where('branch_id', $branchId)
            ->active()
            ->get()
            ->filter(function ($config) use ($currentTime) {
                return $config->isWithinStrictClockInWindow($currentTime);
            });

        return $availableConfigs->map(function ($config) {
            return [
                'type' => $config->shift_type,
                'name' => $config->name,
                'window' => $config->getClockInWindowMessage(),
                'duration_hours' => round($config->getDurationMinutes() / 60, 1)
            ];
        })->toArray();
    }
}
