<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'pending_days',
        'remaining_days',
        'carried_forward',
    ];

    protected $casts = [
        'total_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carried_forward' => 'decimal:2',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Methods
    public function updateBalance()
    {
        $this->remaining_days = $this->total_days + $this->carried_forward - $this->used_days - $this->pending_days;
        $this->save();
    }

    public static function initializeForEmployee($employeeId, $year = null)
    {
        $year = $year ?? now()->year;

        // Check if employee has custom allocations
        $allocations = EmployeeLeaveAllocation::forEmployee($employeeId)
            ->forYear($year)
            ->active()
            ->get();

        if ($allocations->isNotEmpty()) {
            // Use custom allocations
            foreach ($allocations as $allocation) {
                $allocation->syncLeaveBalance();
            }
        } else {
            // Use default leave types
            $leaveTypes = LeaveType::active()->get();

            foreach ($leaveTypes as $leaveType) {
                self::firstOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                    ],
                    [
                        'total_days' => $leaveType->default_days_per_year,
                        'used_days' => 0,
                        'pending_days' => 0,
                        'remaining_days' => $leaveType->default_days_per_year,
                        'carried_forward' => 0,
                    ]
                );
            }
        }
    }

    public function allocation()
    {
        return $this->hasOne(EmployeeLeaveAllocation::class, 'employee_id', 'employee_id')
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->year);
    }
}
