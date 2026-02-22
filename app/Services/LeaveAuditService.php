<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveAllocation;
use App\Models\EmployeeLeaveBalance;
use Illuminate\Database\Eloquent\Model;

class LeaveAuditService
{
    /**
     * Log leave application submission
     */
    public static function logLeaveApplication(
        LeaveApplication $leave,
        $actor,
        string $status = 'pending'
    ): AuditLog {
        $leaveTypeName = $leave->leaveType?->name ?? 'Unknown';
        $employeeName = $leave->employee?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'leave:applied',
            $leave,
            "Leave application submitted by '{$employeeName}': {$leaveTypeName} from {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')} ({$leave->total_days} days)",
            $status,
            null,
            [
                'application_number' => $leave->application_number,
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'leave_type_name' => $leaveTypeName,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'total_days' => $leave->total_days,
                'reason' => $leave->reason,
            ]
        );
    }

    /**
     * Log leave approval
     */
    public static function logLeaveApproval(
        LeaveApplication $leave,
        $actor,
        ?string $approvalNotes = null
    ): AuditLog {
        $leaveTypeName = $leave->leaveType?->name ?? 'Unknown';
        $employeeName = $leave->employee?->name ?? 'Unknown';

        $description = "Leave approved for '{$employeeName}': {$leaveTypeName} ({$leave->total_days} days)";
        if ($approvalNotes) {
            $description .= ". Notes: {$approvalNotes}";
        }

        return AuditService::log(
            $actor,
            'leave:approved',
            $leave,
            $description,
            'completed',
            null,
            [
                'application_number' => $leave->application_number,
                'employee_id' => $leave->employee_id,
                'leave_type_name' => $leaveTypeName,
                'total_days' => $leave->total_days,
                'approval_notes' => $approvalNotes,
            ]
        );
    }

    /**
     * Log leave rejection
     */
    public static function logLeaveRejection(
        LeaveApplication $leave,
        $actor,
        string $rejectionReason
    ): AuditLog {
        $leaveTypeName = $leave->leaveType?->name ?? 'Unknown';
        $employeeName = $leave->employee?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'leave:rejected',
            $leave,
            "Leave rejected for '{$employeeName}': {$leaveTypeName} ({$leave->total_days} days). Reason: {$rejectionReason}",
            'completed',
            null,
            [
                'application_number' => $leave->application_number,
                'employee_id' => $leave->employee_id,
                'leave_type_name' => $leaveTypeName,
                'total_days' => $leave->total_days,
                'rejection_reason' => $rejectionReason,
            ]
        );
    }

    /**
     * Log leave cancellation
     */
    public static function logLeaveCancellation(
        LeaveApplication $leave,
        $actor,
        string $cancellationReason
    ): AuditLog {
        $leaveTypeName = $leave->leaveType?->name ?? 'Unknown';
        $employeeName = $leave->employee?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'leave:cancelled',
            $leave,
            "Leave cancelled for '{$employeeName}': {$leaveTypeName} ({$leave->total_days} days). Reason: {$cancellationReason}",
            'completed',
            null,
            [
                'application_number' => $leave->application_number,
                'employee_id' => $leave->employee_id,
                'leave_type_name' => $leaveTypeName,
                'total_days' => $leave->total_days,
                'cancellation_reason' => $cancellationReason,
            ]
        );
    }

    /**
     * Log leave allocation change
     */
    public static function logLeaveAllocationChange(
        EmployeeLeaveAllocation $allocation,
        float $oldBalance,
        float $newBalance,
        string $reason,
        $actor
    ): AuditLog {
        $employeeName = $allocation->employee?->name ?? 'Unknown';
        $leaveTypeName = $allocation->leaveType?->name ?? 'Unknown';
        $change = $newBalance - $oldBalance;
        $changeDirection = $change >= 0 ? 'increased' : 'decreased';

        return AuditService::log(
            $actor,
            'leave:allocation_changed',
            $allocation,
            "Leave allocation {$changeDirection} for '{$employeeName}' ({$leaveTypeName}): {$oldBalance} -> {$newBalance} days. Reason: {$reason}",
            'completed',
            null,
            [
                'employee_id' => $allocation->employee_id,
                'employee_name' => $employeeName,
                'leave_type_id' => $allocation->leave_type_id,
                'leave_type_name' => $leaveTypeName,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'change' => $change,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log leave balance adjustment
     */
    public static function logLeaveBalanceAdjustment(
        EmployeeLeaveBalance $balance,
        string $adjustmentType, // 'credit', 'debit', 'reset', 'carryover'
        float $amount,
        string $reason,
        $actor
    ): AuditLog {
        $employeeName = $balance->employee?->name ?? 'Unknown';
        $leaveTypeName = $balance->leaveType?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'leave:balance_adjusted',
            $balance,
            "Leave balance {$adjustmentType} for '{$employeeName}' ({$leaveTypeName}): {$amount} days. Reason: {$reason}",
            'completed',
            null,
            [
                'employee_id' => $balance->employee_id,
                'leave_type_id' => $balance->leave_type_id,
                'adjustment_type' => $adjustmentType,
                'amount' => $amount,
                'new_balance' => $balance->available_days,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log leave type creation
     */
    public static function logLeaveTypeCreation(
        LeaveType $leaveType,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'leave_type:created',
            $leaveType,
            "New leave type created: '{$leaveType->name}' (default: {$leaveType->default_days} days)",
            'completed',
            null,
            [
                'leave_type_id' => $leaveType->id,
                'name' => $leaveType->name,
                'default_days' => $leaveType->default_days ?? 0,
            ]
        );
    }

    /**
     * Log leave type update
     */
    public static function logLeaveTypeUpdate(
        LeaveType $leaveType,
        array $changes,
        $actor
    ): AuditLog {
        $changesDescription = [];
        foreach ($changes as $field => $value) {
            $changesDescription[] = "{$field}: {$value}";
        }

        return AuditService::log(
            $actor,
            'leave_type:updated',
            $leaveType,
            "Leave type '{$leaveType->name}' updated: " . implode(', ', $changesDescription),
            'completed',
            null,
            [
                'leave_type_id' => $leaveType->id,
                'changes' => $changes,
            ]
        );
    }

    /**
     * Log leave type deletion
     */
    public static function logLeaveTypeDeletion(
        LeaveType $leaveType,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'leave_type:deleted',
            $leaveType,
            "Leave type deleted: '{$leaveType->name}'",
            'completed',
            null,
            [
                'leave_type_id' => $leaveType->id,
                'name' => $leaveType->name,
            ]
        );
    }

    /**
     * Log annual leave reset
     */
    public static function logAnnualLeaveReset(
        int $year,
        int $employeesAffected,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'leave:annual_reset',
            null,
            "Annual leave balances reset for year {$year}. Employees affected: {$employeesAffected}",
            'completed',
            null,
            [
                'year' => $year,
                'employees_affected' => $employeesAffected,
            ]
        );
    }

    /**
     * Log leave carryover
     */
    public static function logLeaveCarryover(
        $employee,
        LeaveType $leaveType,
        float $carriedDays,
        int $fromYear,
        int $toYear,
        $actor
    ): AuditLog {
        $employeeName = $employee->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'leave:carryover',
            $employee,
            "Leave carryover for '{$employeeName}': {$carriedDays} days of {$leaveType->name} from {$fromYear} to {$toYear}",
            'completed',
            null,
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'carried_days' => $carriedDays,
                'from_year' => $fromYear,
                'to_year' => $toYear,
            ]
        );
    }

    /**
     * Log bulk leave allocation
     */
    public static function logBulkLeaveAllocation(
        LeaveType $leaveType,
        int $employeesAffected,
        float $daysAllocated,
        string $reason,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'leave:bulk_allocation',
            $leaveType,
            "Bulk leave allocation: {$daysAllocated} days of '{$leaveType->name}' allocated to {$employeesAffected} employees. Reason: {$reason}",
            'completed',
            null,
            [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'employees_affected' => $employeesAffected,
                'days_allocated' => $daysAllocated,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Generate leave audit report for an employee
     */
    public static function generateLeaveAuditReport(
        $employee,
        ?int $year = null,
        int $limit = 100
    ): array {
        $query = AuditLog::where(function ($q) {
            $q->where('action', 'like', 'leave:%');
        })->where(function ($q) use ($employee) {
            $q->where('auditable_id', $employee->id)
              ->orWhereJsonContains('details->employee_id', $employee->id);
        });

        if ($year) {
            $query->whereYear('logged_at', $year);
        }

        $logs = $query->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'employee' => $employee,
            'logs' => $logs,
            'total_actions' => $logs->count(),
            'year' => $year,
        ];
    }
}
