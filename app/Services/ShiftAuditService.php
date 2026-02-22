<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class ShiftAuditService
{
    /**
     * Log shift creation
     */
    public static function logShiftCreated(
        Model $shift,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);
        $branchName = $shift->branch?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'shift:created',
            $shift,
            "{$shiftType} #{$shift->id} created for branch '{$branchName}'",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'shift_type' => $shiftType,
                'branch_id' => $shift->branch_id ?? null,
                'branch_name' => $branchName,
            ]
        );
    }

    /**
     * Log shift opened
     */
    public static function logShiftOpened(
        Model $shift,
        $actor,
        ?float $openingBalance = null
    ): AuditLog {
        $shiftType = class_basename($shift);
        $description = "{$shiftType} #{$shift->id} opened";
        if ($openingBalance !== null) {
            $description .= " with opening balance: " . number_format($openingBalance, 2);
        }

        return AuditService::log(
            $actor,
            'shift:opened',
            $shift,
            $description,
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'opening_balance' => $openingBalance,
            ]
        );
    }

    /**
     * Log shift closed
     */
    public static function logShiftClosed(
        Model $shift,
        array $summary,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);

        $description = "{$shiftType} #{$shift->id} closed";
        if (isset($summary['total_sales'])) {
            $description .= ". Total sales: " . number_format($summary['total_sales'], 2);
        }
        if (isset($summary['closing_balance'])) {
            $description .= ". Closing balance: " . number_format($summary['closing_balance'], 2);
        }

        return AuditService::log(
            $actor,
            'shift:closed',
            $shift,
            $description,
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'summary' => $summary,
            ]
        );
    }

    /**
     * Log shift reopened
     */
    public static function logShiftReopened(
        Model $shift,
        string $reason,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);

        return AuditService::log(
            $actor,
            'shift:reopened',
            $shift,
            "{$shiftType} #{$shift->id} reopened. Reason: {$reason}",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log shift handover
     */
    public static function logShiftHandover(
        Model $shift,
        $fromEmployee,
        $toEmployee,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);
        $fromName = $fromEmployee?->name ?? 'Unknown';
        $toName = $toEmployee?->name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'shift:handover',
            $shift,
            "{$shiftType} #{$shift->id} handed over from '{$fromName}' to '{$toName}'",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'from_employee_id' => $fromEmployee?->id,
                'from_employee_name' => $fromName,
                'to_employee_id' => $toEmployee?->id,
                'to_employee_name' => $toName,
            ]
        );
    }

    /**
     * Log shift assignment change
     */
    public static function logShiftAssignmentChange(
        Model $shift,
        $oldEmployee,
        $newEmployee,
        string $reason,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);
        $oldName = $oldEmployee?->name ?? 'Unassigned';
        $newName = $newEmployee?->name ?? 'Unassigned';

        return AuditService::log(
            $actor,
            'shift:assignment_changed',
            $shift,
            "{$shiftType} #{$shift->id} reassigned from '{$oldName}' to '{$newName}'. Reason: {$reason}",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'old_employee_id' => $oldEmployee?->id,
                'new_employee_id' => $newEmployee?->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log cash adjustment during shift
     */
    public static function logCashAdjustment(
        Model $shift,
        float $amount,
        string $type, // 'add', 'remove', 'correction'
        string $reason,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);
        $direction = $type === 'remove' ? 'removed from' : 'added to';

        return AuditService::log(
            $actor,
            'shift:cash_adjustment',
            $shift,
            "Cash adjustment: " . number_format(abs($amount), 2) . " {$direction} {$shiftType} #{$shift->id}. Type: {$type}. Reason: {$reason}",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'amount' => $amount,
                'type' => $type,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log shift variance
     */
    public static function logShiftVariance(
        Model $shift,
        float $expectedAmount,
        float $actualAmount,
        $actor
    ): AuditLog {
        $shiftType = class_basename($shift);
        $variance = $actualAmount - $expectedAmount;
        $varianceType = $variance >= 0 ? 'overage' : 'shortage';

        return AuditService::log(
            $actor,
            'shift:variance_recorded',
            $shift,
            "{$shiftType} #{$shift->id} closed with {$varianceType} of " . number_format(abs($variance), 2) . ". Expected: " . number_format($expectedAmount, 2) . ", Actual: " . number_format($actualAmount, 2),
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'variance' => $variance,
                'variance_type' => $varianceType,
            ]
        );
    }

    /**
     * Log production shift summary
     */
    public static function logProductionShiftSummary(
        Model $shift,
        array $productionSummary,
        $actor
    ): AuditLog {
        $totalProduced = $productionSummary['total_produced'] ?? 0;
        $totalWaste = $productionSummary['total_waste'] ?? 0;

        return AuditService::log(
            $actor,
            'shift:production_summary',
            $shift,
            "Production shift #{$shift->id} summary: Produced: {$totalProduced}, Waste: {$totalWaste}",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'production_summary' => $productionSummary,
            ]
        );
    }

    /**
     * Log sales shift summary
     */
    public static function logSalesShiftSummary(
        Model $shift,
        array $salesSummary,
        $actor
    ): AuditLog {
        $totalSales = $salesSummary['total_sales'] ?? 0;
        $transactionCount = $salesSummary['transaction_count'] ?? 0;

        return AuditService::log(
            $actor,
            'shift:sales_summary',
            $shift,
            "Sales shift #{$shift->id} summary: Total sales: " . number_format($totalSales, 2) . ", Transactions: {$transactionCount}",
            'completed',
            null,
            [
                'shift_id' => $shift->id,
                'sales_summary' => $salesSummary,
            ]
        );
    }
}
