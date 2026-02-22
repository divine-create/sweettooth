<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Services\LeaveAuditService;

class LeaveApplication extends Model
{
    protected $fillable = [
        'application_number',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'emergency_contact',
        'supporting_document',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'approval_notes',
        'rejected_by_id',
        'rejected_by_type',
        'rejected_at',
        'rejection_reason',
        'cancelled_by_id',
        'cancelled_by_type',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
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

    public function approvedBy(): MorphTo
    {
        return $this->morphTo(
            'approved_by',
            'approved_by_type',
            'approved_by_id'
        );
    }

    public function rejectedBy(): MorphTo
    {
        return $this->morphTo(
            'rejected_by',
            'rejected_by_type',
            'rejected_by_id'
        );
    }

    public function cancelledBy(): MorphTo
    {
        return $this->morphTo(
            'cancelled_by',
            'cancelled_by_type',
            'cancelled_by_id'
        );
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Methods
    public static function generateApplicationNumber()
    {
        $year = now()->year;
        $lastApplication = self::where('application_number', 'like', "LA-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastApplication) {
            return "LA-{$year}-001";
        }

        $lastNumber = (int) substr($lastApplication->application_number, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return "LA-{$year}-{$newNumber}";
    }

    public function calculateWorkingDays()
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        $workingDays = 0;

        while ($start->lte($end)) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($start->dayOfWeek !== Carbon::SATURDAY && $start->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }

    public function approve($approverId, $approverType, $notes = null)
    {
        $this->status = 'approved';
        $this->approved_by_id = $approverId;
        $this->approved_by_type = $approverType;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance('approve');

        // Log the approval
        $approver = $this->approvedBy;
        if ($approver) {
            LeaveAuditService::logLeaveApproval($this, $approver, $notes);
        }
    }

    public function reject($rejecterId, $rejecterType, $reason)
    {
        $this->status = 'rejected';
        $this->rejected_by_id = $rejecterId;
        $this->rejected_by_type = $rejecterType;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance('reject');

        // Log the rejection
        $rejector = $this->rejectedBy;
        if ($rejector) {
            LeaveAuditService::logLeaveRejection($this, $rejector, $reason);
        }
    }

    public function cancel($cancellerId, $cancellerType, $reason)
    {
        $this->status = 'cancelled';
        $this->cancelled_by_type = $cancellerType;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance('cancel');
    }

    protected function updateLeaveBalance($action)
    {
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', Carbon::parse($this->start_date)->year)
            ->first();

        if (! $balance) {
            return;
        }

        switch ($action) {
            case 'approve':
                $balance->pending_days -= $this->total_days;
                $balance->used_days += $this->total_days;
                break;
            case 'reject':
            case 'cancel':
                $balance->pending_days -= $this->total_days;
                break;
        }

        $balance->updateBalance();
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }
}
