<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmployeeStepout extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'shift_id',
        'reason',
        'reason_details',
        'request_time',
        'approved_time',
        'stepout_time',
        'return_time',
        'duration_minutes',
        'expected_duration_minutes',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approval_notes',
        'rejected_by_id',
        'rejected_by_type',
        'rejection_reason',
        'is_overdue',
        'overdue_minutes',
        'overdue_reason',
    ];

    protected $casts = [
        'request_time' => 'datetime',
        'approved_time' => 'datetime',
        'stepout_time' => 'datetime',
        'return_time' => 'datetime',
        'is_overdue' => 'boolean',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function shift()
    {
        return $this->belongsTo(\App\Models\Shift::class);
    }

    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    public function rejectedBy(): MorphTo
    {
        return $this->morphTo('rejected_by', 'rejected_by_type', 'rejected_by_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_overdue', true);
    }

    // Methods
    public function approve($approverId, $approverType, $notes = null)
    {
        $this->status = 'approved';
        $this->approved_by_id = $approverId;
        $this->approved_by_type = $approverType;
        $this->approved_time = now();
        $this->approval_notes = $notes;
        $this->save();
    }

    public function reject($rejecterId, $rejectorType, $reason)
    {
        $this->status = 'rejected';
        $this->rejected_by_id = $rejecterId;
        $this->rejected_by_type = $rejectorType;
        $this->rejection_reason = $reason;
        $this->save();
    }

    public function startStepout()
    {
        $this->status = 'in_progress';
        $this->stepout_time = now();
        $this->save();
    }

    public function completeStepout()
    {
        $this->return_time = now();
        $this->status = 'completed';

        if ($this->stepout_time) {
            $this->duration_minutes = $this->stepout_time->diffInMinutes($this->return_time);

            // Check if overdue
            if ($this->duration_minutes > $this->expected_duration_minutes) {
                $this->is_overdue = true;
                $this->overdue_minutes = $this->duration_minutes - $this->expected_duration_minutes;
            }
        }

        $this->save();
    }

    public function checkOverdue()
    {
        if ($this->status === 'in_progress' && $this->stepout_time) {
            $minutesElapsed = $this->stepout_time->diffInMinutes(now());

            if ($minutesElapsed > $this->expected_duration_minutes) {
                $this->status = 'overdue';
                $this->is_overdue = true;
                $this->overdue_minutes = $minutesElapsed - $this->expected_duration_minutes;
                $this->save();
            }
        }
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'in_progress' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'rejected' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }

    public function getReasonLabelAttribute()
    {
        return match ($this->reason) {
            'restroom' => 'Restroom Break',
            'water_break' => 'Water Break',
            'prayer' => 'Prayer',
            'emergency' => 'Emergency',
            'medical' => 'Medical',
            'other' => 'Other',
            default => ucfirst($this->reason),
        };
    }
}
