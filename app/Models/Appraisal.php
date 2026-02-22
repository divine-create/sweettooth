<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appraisal extends Model
{
    protected $fillable = [
        'employee_id',
        'manager_id',
        'appraisal_cycle_id',
        'status',
        'self_assessment_data',
        'manager_assessment_data',
        'hr_assessment_data',
        'final_rating',
        'final_comments',
        'development_plan',
        'submitted_at',
        'completed_at'
    ];

    protected $casts = [
        'self_assessment_data' => 'array',
        'manager_assessment_data' => 'array',
        'hr_assessment_data' => 'array',
        'development_plan' => 'array',
        'final_rating' => 'decimal:2',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function appraisalCycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'appraisal_cycle_id');
    }

    public function performanceGoals(): HasMany
    {
        return $this->hasMany(PerformanceGoal::class, 'appraisal_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(AppraisalFeedback::class, 'appraisal_id');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
                    ->whereHas('appraisalCycle', function ($q) {
                        $q->where('due_date', '<', now());
                    });
    }
}
