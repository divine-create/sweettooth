<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportSchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'department_id',
        'schedule_name',
        'report_type',
        'report_category',
        'frequency',
        'frequency_config',
        'generation_time',
        'auto_compile',
        'auto_send_md',
        'recipients',
        'notification_channels',
        'is_active',
        'last_generated_at',
        'next_generation_at',
    ];

    protected $casts = [
        'generation_time' => 'datetime',
        'auto_compile' => 'boolean',
        'auto_send_md' => 'boolean',
        'recipients' => 'array',
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the schedule.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department that owns the schedule.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schedules for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include schedules for a specific department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for schedules that need to be generated.
     */
    public function scopeDueForGeneration($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_generation_at')
                    ->orWhere('next_generation_at', '<=', now());
            });
    }

    /**
     * Update last generation timestamp and calculate next generation time.
     */
    public function markAsGenerated()
    {
        $this->update([
            'last_generated_at' => now(),
            'next_generation_at' => $this->calculateNextGenerationTime(),
        ]);
    }

    /**
     * Calculate next generation time based on frequency.
     */
    public function calculateNextGenerationTime()
    {
        $baseTime = now()->setTimeFromTimeString($this->generation_time);

        return match ($this->frequency) {
            'daily' => $baseTime->addDay(),
            'weekly' => $baseTime->addWeek(),
            'biweekly' => $baseTime->addWeeks(2),
            'monthly' => $baseTime->addMonth(),
            'quarterly' => $baseTime->addMonths(3),
            default => $baseTime->addDay(),
        };
    }

    /**
     * Activate the schedule.
     */
    public function activate()
    {
        $this->update([
            'is_active' => true,
            'next_generation_at' => $this->calculateNextGenerationTime(),
        ]);
    }

    /**
     * Deactivate the schedule.
     */
    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'next_generation_at' => null,
        ]);
    }
}
