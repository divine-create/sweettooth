<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceGoal extends Model
{
    protected $fillable = [
        'employee_id',
        'appraisal_id',
        'title',
        'description',
        'category',
        'type',
        'priority',
        'target_value',
        'current_value',
        'unit',
        'start_date',
        'target_date',
        'status',
        'last_updated_by'
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'start_date' => 'date',
        'target_date' => 'date',
        'employee_id' => 'uuid',
        'last_updated_by' => 'uuid'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }

    public function lastUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeOverdue($query)
    {
        return $query->where('target_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        return round(($this->current_value / $this->target_value) * 100, 2);
    }

    public function getIsOverdueAttribute()
    {
        return $this->target_date < now() && $this->status !== 'completed';
    }
}
