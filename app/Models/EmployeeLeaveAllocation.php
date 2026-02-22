<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmployeeLeaveAllocation extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'notes',
        'allocated_by_id',
        'allocated_by_type',
        'allocated_at',
        'is_active',
    ];

    protected $casts = [
        'allocated_days' => 'decimal:2',
        'allocated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function allocatedBy(): MorphTo
    {
        return $this->morphTo(
            'allocated_by',
            'allocated_by_type',
            'allocated_by_id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Methods
    public static function allocateToEmployee($employeeId, $leaveTypeId, $year, $days, $allocatedBy = null, $notes = null)
    {
        return self::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'allocated_days' => $days,
                'notes' => $notes,
                'allocated_by' => $allocatedBy,
                'allocated_at' => now(),
                'is_active' => true,
            ]
        );
    }

    // Sync leave balance when allocation is created/updated
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($allocation) {
            $allocation->syncLeaveBalance();
        });
    }

    public function syncLeaveBalance()
    {
        $balance = EmployeeLeaveBalance::firstOrCreate(
            [
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $this->year,
            ],
            [
                'total_days' => 0,
                'used_days' => 0,
                'pending_days' => 0,
                'remaining_days' => 0,
                'carried_forward' => 0,
            ]
        );

        // Update total days based on allocation
        $balance->total_days = $this->allocated_days;
        $balance->updateBalance();
    }
}
