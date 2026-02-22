<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ShiftConfiguration extends Model
{
    protected $fillable = [
        'branch_id', 'shift_type', 'name', 'start_time', 'end_time',
        'clock_in_start', 'clock_in_end', 'auto_clock_out_minutes',
        'is_active', 'max_overtime_hours', 'break_duration_minutes', 'timezone'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'clock_in_start' => 'datetime:H:i',
        'clock_in_end' => 'datetime:H:i',
        'is_active' => 'boolean',
        'max_overtime_hours' => 'decimal:2',
        'auto_clock_out_minutes' => 'integer',
        'break_duration_minutes' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        if ($end <= $start) {
            $end = $end->addDay(); // Handle overnight shifts
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Check if current time is within clock-in window (STRICT ENFORCEMENT)
     * Uses configured shift start/end times as the allowed window.
     */
    public function isWithinStrictClockInWindow(?Carbon $currentTime = null): bool
    {
        $now = $currentTime ?? Carbon::now($this->timezone);

        $startTimeRaw = $this->start_time instanceof Carbon
            ? $this->start_time->format('H:i:s')
            : (string) $this->start_time;
        $endTimeRaw = $this->end_time instanceof Carbon
            ? $this->end_time->format('H:i:s')
            : (string) $this->end_time;

        $start = Carbon::createFromTimeString($startTimeRaw)
            ->setDateFrom($now);
        $end = Carbon::createFromTimeString($endTimeRaw)
            ->setDateFrom($now);

        // Handle overnight shifts (end time next day)
        if ($end <= $start) {
            $end = $end->addDay();
        }

        // STRICT: Must be at or after start AND before end
        return $now >= $start && $now < $end;
    }

    /**
     * Get clock-in window status message
     */
    public function getClockInWindowMessage(): string
    {
        $startTimeRaw = $this->start_time instanceof Carbon
            ? $this->start_time->format('H:i:s')
            : (string) $this->start_time;
        $endTimeRaw = $this->end_time instanceof Carbon
            ? $this->end_time->format('H:i:s')
            : (string) $this->end_time;

        $start = Carbon::createFromTimeString($startTimeRaw)->format('g:i A');
        $end = Carbon::createFromTimeString($endTimeRaw)->format('g:i A');

        return "Clock-in window: {$start} - {$end}";
    }

    public function getClockInViolationMessage(?Carbon $currentTime = null): string
    {
        $now = $currentTime ?? Carbon::now($this->timezone);

        $startTimeRaw = $this->start_time instanceof Carbon
            ? $this->start_time->format('H:i:s')
            : (string) $this->start_time;
        $endTimeRaw = $this->end_time instanceof Carbon
            ? $this->end_time->format('H:i:s')
            : (string) $this->end_time;

        $start = Carbon::createFromTimeString($startTimeRaw)->setDateFrom($now);
        $end = Carbon::createFromTimeString($endTimeRaw)->setDateFrom($now);
        if ($end <= $start) {
            $end = $end->addDay();
        }

        $startLabel = $start->format('g:i A');
        $endLabel = $end->format('g:i A');
        $nowLabel = $now->format('g:i A');

        if ($now < $start) {
            return "Clock-in denied. Too early. Current time: {$nowLabel}. Allowed window: {$startLabel} - {$endLabel}.";
        }

        return "Clock-in denied. Too late. Current time: {$nowLabel}. Allowed window: {$startLabel} - {$endLabel}.";
    }

    /**
     * Check if shift should auto clock out
     */
    public function shouldAutoClockOut(Carbon $clockInTime, ?Carbon $currentTime = null): bool
    {
        $now = $currentTime ?? Carbon::now($this->timezone);

        $startTimeRaw = $this->start_time instanceof Carbon
            ? $this->start_time->format('H:i:s')
            : (string) $this->start_time;
        $endTimeRaw = $this->end_time instanceof Carbon
            ? $this->end_time->format('H:i:s')
            : (string) $this->end_time;

        $expectedStart = Carbon::createFromTimeString($startTimeRaw)
            ->setDateFrom($clockInTime);
        $expectedEnd = Carbon::createFromTimeString($endTimeRaw)
            ->setDateFrom($clockInTime);

        if ($expectedEnd <= $expectedStart) {
            $expectedEnd = $expectedEnd->addDay();
        }

        $gracePeriodEnd = $expectedEnd->addMinutes($this->auto_clock_out_minutes);

        return $now > $gracePeriodEnd;
    }

    /**
     * Scope for active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific branch and shift type
     */
    public function scopeForBranchAndType($query, $branchId, $shiftType)
    {
        return $query->where('branch_id', $branchId)
                    ->where('shift_type', $shiftType)
                    ->active();
    }
}
