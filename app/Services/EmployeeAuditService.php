<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

class EmployeeAuditService
{
    /**
     * Log employee creation
     */
    public static function logEmployeeCreation(
        Employee $employee,
        $actor,
        string $status = 'completed'
    ): AuditLog {
        return AuditService::log(
            $actor,
            'employee:created',
            $employee,
            "Employee '{$employee->name}' (#{$employee->employee_number}) created",
            $status,
            null,
            [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_email' => $employee->email,
                'department_id' => $employee->department_id,
                'branch_id' => $employee->branch_id,
            ]
        );
    }

    /**
     * Log employee update
     */
    public static function logEmployeeUpdate(
        Employee $employee,
        array $changes,
        $actor,
        string $status = 'completed'
    ): AuditLog {
        $changedFieldsList = [];
        foreach ($changes as $field => $change) {
            if (is_array($change) && isset($change['old'], $change['new'])) {
                $changedFieldsList[] = "{$field}: {$change['old']} -> {$change['new']}";
            } else {
                $changedFieldsList[] = $field;
            }
        }

        $description = "Employee '{$employee->name}' updated";
        if (!empty($changedFieldsList)) {
            $description .= ": " . implode(', ', array_slice($changedFieldsList, 0, 5));
            if (count($changedFieldsList) > 5) {
                $description .= " and " . (count($changedFieldsList) - 5) . " more fields";
            }
        }

        return AuditService::log(
            $actor,
            'employee:updated',
            $employee,
            $description,
            $status,
            null,
            ['changed_fields' => $changes]
        );
    }

    /**
     * Log salary change
     */
    public static function logSalaryChange(
        Employee $employee,
        $oldSalary,
        $newSalary,
        string $reason,
        $actor,
        string $status = 'completed'
    ): AuditLog {
        $change = $oldSalary > 0 ? round((($newSalary - $oldSalary) / $oldSalary) * 100, 2) : 0;
        $changeDirection = $change >= 0 ? 'increased' : 'decreased';

        return AuditService::log(
            $actor,
            'employee:salary_changed',
            $employee,
            "Salary {$changeDirection} from " . number_format($oldSalary, 2) . " to " . number_format($newSalary, 2) . " ({$change}%): {$reason}",
            $status,
            null,
            [
                'old_salary' => $oldSalary,
                'new_salary' => $newSalary,
                'change_percentage' => $change,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log role change
     */
    public static function logRoleChange(
        Employee $employee,
        array $oldRoles,
        array $newRoles,
        string $reason,
        $actor,
        string $status = 'completed'
    ): AuditLog {
        $added = array_diff($newRoles, $oldRoles);
        $removed = array_diff($oldRoles, $newRoles);

        $description = "Roles changed for '{$employee->name}'";
        $changes = [];

        if (!empty($added)) {
            $changes[] = "Added: " . implode(', ', $added);
        }
        if (!empty($removed)) {
            $changes[] = "Removed: " . implode(', ', $removed);
        }

        if (!empty($changes)) {
            $description .= " - " . implode('; ', $changes);
        }

        if (!empty($reason)) {
            $description .= ". Reason: {$reason}";
        }

        return AuditService::log(
            $actor,
            'employee:roles_changed',
            $employee,
            $description,
            $status,
            null,
            [
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
                'added' => $added,
                'removed' => $removed,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log permission change
     */
    public static function logPermissionChange(
        Employee $employee,
        string $permission,
        string $action, // 'granted' or 'revoked'
        string $reason,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'employee:permission_' . $action,
            $employee,
            "Permission '{$permission}' {$action} for '{$employee->name}': {$reason}",
            'completed',
            null,
            [
                'permission' => $permission,
                'action' => $action,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log activation status change
     */
    public static function logActivationStatusChange(
        Employee $employee,
        bool $wasActive,
        bool $isActive,
        string $reason,
        $actor
    ): AuditLog {
        $action = $isActive ? 'activated' : 'deactivated';

        return AuditService::log(
            $actor,
            'employee:' . $action,
            $employee,
            "Employee '{$employee->name}' {$action}: {$reason}",
            'completed',
            null,
            [
                'was_active' => $wasActive,
                'is_active' => $isActive,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log employee deactivation (soft delete)
     */
    public static function logEmployeeDeactivation(
        Employee $employee,
        $actor,
        string $reason = 'Employee deactivated'
    ): AuditLog {
        return AuditService::log(
            $actor,
            'employee:deactivated',
            $employee,
            "Employee '{$employee->name}' (#{$employee->employee_number}) deactivated: {$reason}",
            'completed',
            null,
            [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log employee termination
     */
    public static function logEmployeeTermination(
        Employee $employee,
        string $terminationDate,
        string $reason,
        ?\Illuminate\Database\Eloquent\Model $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'employee:terminated',
            $employee,
            "Employee '{$employee->name}' terminated effective {$terminationDate}: {$reason}",
            'completed',
            null,
            [
                'employee_id' => $employee->id,
                'termination_date' => $terminationDate,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log department transfer
     */
    public static function logDepartmentTransfer(
        Employee $employee,
        $oldDepartment,
        $newDepartment,
        string $reason,
        $actor
    ): AuditLog {
        $oldDeptName = $oldDepartment->name ?? 'None';
        $newDeptName = $newDepartment->name ?? 'None';

        return AuditService::log(
            $actor,
            'employee:department_transferred',
            $employee,
            "Employee '{$employee->name}' transferred from '{$oldDeptName}' to '{$newDeptName}': {$reason}",
            'completed',
            null,
            [
                'old_department_id' => $oldDepartment->id ?? null,
                'old_department_name' => $oldDeptName,
                'new_department_id' => $newDepartment->id ?? null,
                'new_department_name' => $newDeptName,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log branch transfer
     */
    public static function logBranchTransfer(
        Employee $employee,
        $oldBranch,
        $newBranch,
        string $reason,
        $actor
    ): AuditLog {
        $oldBranchName = $oldBranch->name ?? 'None';
        $newBranchName = $newBranch->name ?? 'None';

        return AuditService::log(
            $actor,
            'employee:branch_transferred',
            $employee,
            "Employee '{$employee->name}' transferred from '{$oldBranchName}' to '{$newBranchName}': {$reason}",
            'completed',
            null,
            [
                'old_branch_id' => $oldBranch->id ?? null,
                'old_branch_name' => $oldBranchName,
                'new_branch_id' => $newBranch->id ?? null,
                'new_branch_name' => $newBranchName,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log manager assignment
     */
    public static function logManagerAssignment(
        Employee $employee,
        ?Employee $oldManager,
        ?Employee $newManager,
        string $reason,
        $actor
    ): AuditLog {
        $oldManagerName = $oldManager ? $oldManager->name : 'None';
        $newManagerName = $newManager ? $newManager->name : 'None';

        return AuditService::log(
            $actor,
            'employee:manager_assigned',
            $employee,
            "Manager for '{$employee->name}' changed from '{$oldManagerName}' to '{$newManagerName}': {$reason}",
            'completed',
            null,
            [
                'old_manager_id' => $oldManager?->id,
                'old_manager_name' => $oldManagerName,
                'new_manager_id' => $newManager?->id,
                'new_manager_name' => $newManagerName,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log probation status change
     */
    public static function logProbationStatusChange(
        Employee $employee,
        string $action, // 'started', 'extended', 'ended', 'passed', 'failed'
        ?string $endDate,
        string $reason,
        $actor
    ): AuditLog {
        $description = "Probation {$action} for '{$employee->name}'";
        if ($endDate) {
            $description .= " (ends: {$endDate})";
        }
        $description .= ": {$reason}";

        return AuditService::log(
            $actor,
            'employee:probation_' . $action,
            $employee,
            $description,
            'completed',
            null,
            [
                'probation_action' => $action,
                'probation_end_date' => $endDate,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log password reset
     */
    public static function logPasswordReset(
        Employee $employee,
        $actor,
        bool $byAdmin = false
    ): AuditLog {
        $description = $byAdmin
            ? "Password reset for '{$employee->name}' by admin"
            : "Password changed by '{$employee->name}'";

        return AuditService::log(
            $actor,
            'employee:password_reset',
            $employee,
            $description,
            'completed',
            null,
            ['by_admin' => $byAdmin]
        );
    }

    /**
     * Log profile photo update
     */
    public static function logProfilePhotoUpdate(
        Employee $employee,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'employee:profile_photo_updated',
            $employee,
            "Profile photo updated for '{$employee->name}'",
            'completed'
        );
    }

    /**
     * Generate audit report for an employee
     */
    public static function generateAuditReport(
        Employee $employee,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 100
    ): array {
        $query = AuditLog::where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id);

        if ($fromDate) {
            $query->where('logged_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('logged_at', '<=', $toDate);
        }

        $logs = $query->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();

        // Group by action type
        $grouped = $logs->groupBy('action');

        // Calculate statistics
        $stats = [
            'total_actions' => $logs->count(),
            'role_changes' => $grouped->get('employee:roles_changed')?->count() ?? 0,
            'updates' => $grouped->get('employee:updated')?->count() ?? 0,
            'salary_changes' => $grouped->get('employee:salary_changed')?->count() ?? 0,
        ];

        return [
            'employee' => $employee,
            'logs' => $logs,
            'stats' => $stats,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ];
    }
}
