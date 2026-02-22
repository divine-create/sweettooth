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
        $currentTime = $requestedTime ?? Carbon::now();

        // Get shift configuration with STRICT time windows
        $config = $this->getStrictShiftConfiguration($shiftType, $branchId);

        if (!$config) {
            return ValidationResult::invalid("Shift configuration not found for {$shiftType}");
        }

        // STRICT VALIDATION: Must be within exact clock-in window
        if (!$config->isWithinStrictClockInWindow($currentTime)) {
            $violationDetails = $this->getStrictViolationDetails($config, $currentTime);

            // Log security violation
            Log::warning('STRICT SHIFT TIME VIOLATION', [
                'shift_type' => $shiftType,
                'requested_time' => $currentTime->format('Y-m-d H:i:s'),
                'allowed_window' => $violationDetails['window'],
                'violation_type' => $violationDetails['type'],
                'branch_id' => $branchId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return ValidationResult::invalid($violationDetails['message']);
        }

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
        $currentTime = $requestedTime ?? Carbon::now();

        // Check for active shifts today
        $activeShift = \App\Models\Shift::where('employee_id', $employeeId)
            ->where('shift_date', $currentTime->toDateString())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();

        if ($activeShift) {
            return ValidationResult::invalid(
                "You already have an active {$activeShift->shift_type} shift today. Please clock out first."
            );
        }

        // Check for shifts that would overlap.
        // Only consider open/active shifts; closed shifts should not block a new clock-in.
        $config = ShiftConfiguration::forBranchAndType($branchId, $shiftType)->first();

        if ($config) {
            $shiftEnd = Carbon::createFromTimeString($config->end_time)->setDateFrom($currentTime);

            $overlappingShift = \App\Models\Shift::where('employee_id', $employeeId)
                ->where('shift_date', $currentTime->toDateString())
                ->where('status', 'active')
                ->where(function ($query) use ($currentTime, $shiftEnd) {
                    $query->whereBetween('clock_in', [$currentTime, $shiftEnd])
                          ->orWhereBetween('clock_out', [$currentTime, $shiftEnd])
                          ->orWhere(function ($q) use ($currentTime, $shiftEnd) {
                              $q->where('clock_in', '<=', $currentTime)
                                ->where('clock_out', '>=', $shiftEnd);
                          });
                })
                ->first();

            if ($overlappingShift) {
                return ValidationResult::invalid(
                    "This shift would overlap with your existing shift today."
                );
            }
        }

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
