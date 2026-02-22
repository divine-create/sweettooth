<?php

namespace App\Services;

use Carbon\Carbon;

class StrictShiftConfig
{
    public string $name;
    public string $clock_in_start;
    public string $clock_in_end;
    public string $branch_id;
    public string $timezone;

    public function __construct(array $config, string $branchId)
    {
        $this->name = $config['name'];
        $this->clock_in_start = $config['clock_in_start'];
        $this->clock_in_end = $config['clock_in_end'];
        $this->branch_id = $branchId;
        $this->timezone = $config['timezone'];
    }

    /**
     * STRICT TIME WINDOW CHECK
     * Must be EXACTLY within the allowed clock-in window
     */
    public function isWithinStrictClockInWindow(Carbon $currentTime): bool
    {
        // Convert to branch timezone if needed
        $branchTime = $currentTime->setTimezone($this->timezone);

        $start = Carbon::createFromTimeString($this->clock_in_start)
            ->setDateFrom($branchTime);

        $end = Carbon::createFromTimeString($this->clock_in_end)
            ->setDateFrom($branchTime);

        // STRICT: Must be at or after start AND before end
        return $branchTime >= $start && $branchTime < $end;
    }

    /**
     * Get remaining time in current window
     */
    public function getRemainingTimeInWindow(Carbon $currentTime): ?int
    {
        if (!$this->isWithinStrictClockInWindow($currentTime)) {
            return null;
        }

        $end = Carbon::createFromTimeString($this->clock_in_end)
            ->setDateFrom($currentTime);

        return $currentTime->diffInMinutes($end);
    }

    /**
     * Get time until window opens
     */
    public function getTimeUntilWindowOpens(Carbon $currentTime): ?int
    {
        $start = Carbon::createFromTimeString($this->clock_in_start)
            ->setDateFrom($currentTime);

        if ($currentTime >= $start) {
            return null; // Window is open or already passed
        }

        return $currentTime->diffInMinutes($start);
    }
}