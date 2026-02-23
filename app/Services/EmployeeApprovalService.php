<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Models\ApprovalAuditRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeApprovalService
{
    /**
     * Create a pending employee creation request
     */
    public static function requestCreate(array $employeeData, string $reason): ?ApprovalAuditRequest
    {
        // Validate required fields
        if (! self::validateEmployeeData($employeeData)) {
            return null;
        }

        $requester = current_actor();

        // Validate requester
        if (!$requester) {
            CleanError::show('Invalid requester or requester not authenticated.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $employeeData['branch_id'] ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'employee:create',
            'description' => $reason,
            'payload' => $employeeData,
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'employee:create_requested',
            $request,
            "Requested employee creation approval for '{$employeeData['name']}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending employee creation after approval
     */
    public static function executeCreate(ApprovalAuditRequest $request, $approver = null): ?Employee
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $approver = $approver ?? current_actor();
                $payload = $request->payload;

            if (empty($payload['user_type'])) {
                $payload['user_type'] = 'employee';
            }

            // Ensure password is hashed if provided as plaintext
            if (isset($payload['password']) && !str_starts_with($payload['password'], '$2y$')) {
                $payload['password'] = Hash::make($payload['password']);
            }

            // Remove non-fillable fields from payload
            $selectedRoleIds = $payload['selectedRoles'] ?? [];
            unset($payload['selectedRoles']);

            // Create the employee
            $employee = Employee::create($payload);

            // Convert role IDs to role names if provided
            $selectedRoles = [];
            if (!empty($selectedRoleIds)) {
                $selectedRoles = \Spatie\Permission\Models\Role::whereIn('id', $selectedRoleIds)
                    ->pluck('name')
                    ->toArray();
            }

            // Sync roles if provided
            if (!empty($selectedRoles)) {
                $employee->syncRoles($selectedRoles);
            }

            // Log the creation
            EmployeeAuditService::logEmployeeCreation($employee, $approver);

                return $employee;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to create employee.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending employee update request
     */
    public static function requestUpdate(Employee $employee, array $changes, string $reason, array $roleChanges = []): ?ApprovalAuditRequest
    {
        // Validate employee exists
        if (!$employee->exists) {
            CleanError::show('Employee does not exist.');
            return null;
        }

        $requester = current_actor();

        // Validate requester
        if (!$requester) {
            CleanError::show('Invalid requester or requester not authenticated.');
            return null;
        }

        // Build payload with employee ID and changes
        $payload = array_merge($changes, [
            'id' => $employee->id,
            'selectedRoles' => $roleChanges['roles'] ?? [],
            'original_values' => $employee->getOriginal(),
        ]);

        $request = ApprovalAuditRequest::create([
            'branch_id' => $employee->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'employee:update:' . $employee->id,
            'description' => $reason,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Log the approval request
        $changedFields = array_keys(array_diff_assoc($changes, $employee->getOriginal()));
        AuditService::log(
            $requester,
            'employee:update_requested',
            $employee,
            "Requested employee update approval for '{$employee->name}'. Changes: " . implode(', ', $changedFields) . ". Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending employee update after approval
     */
    public static function executeUpdate(ApprovalAuditRequest $request, $approver = null): ?Employee
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $approver = $approver ?? current_actor();
                $payload = $request->payload;
                $employeeId = $payload['id'] ?? null;

                if (!$employeeId) {
                    CleanError::show('Invalid employee ID in approval payload.');
                    return null;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    CleanError::show('Employee not found. Please refresh and try again.');
                    return null;
                }

            // Extract roles and original values from payload
            $selectedRoleIds = $payload['selectedRoles'] ?? [];
            $originalValues = $payload['original_values'] ?? [];
            unset($payload['selectedRoles'], $payload['original_values'], $payload['id']);

            // Get changed fields for audit
            $changedFields = [];
            foreach ($payload as $key => $value) {
                if (isset($originalValues[$key]) && $originalValues[$key] != $value) {
                    $changedFields[$key] = [
                        'old' => $originalValues[$key],
                        'new' => $value,
                    ];
                }
            }

            // Update the employee
            $employee->update($payload);

            // Convert role IDs to role names if provided
            $selectedRoles = [];
            if (!empty($selectedRoleIds)) {
                $selectedRoles = \Spatie\Permission\Models\Role::whereIn('id', $selectedRoleIds)
                    ->pluck('name')
                    ->toArray();
            }

            // Sync roles if provided
            if (!empty($selectedRoles)) {
                $oldRoles = $employee->roles->pluck('name')->toArray();
                $employee->syncRoles($selectedRoles);

                // Log role changes if any
                if ($oldRoles != $selectedRoles) {
                    EmployeeAuditService::logRoleChange(
                        $employee,
                        $oldRoles,
                        $selectedRoles,
                        'Updated during employee edit',
                        $approver
                    );
                }
            }

            // Log the update
            EmployeeAuditService::logEmployeeUpdate($employee, $changedFields, $approver);

                return $employee;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to update employee.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending employee deletion request
     */
    public static function requestDelete(Employee $employee, string $reason): ?ApprovalAuditRequest
    {
        // Validate employee exists
        if (!$employee->exists) {
            CleanError::show('Employee does not exist.');
            return null;
        }

        // Check if employee has subordinates
        if ($employee->subordinates()->count() > 0) {
            CleanError::show('Cannot delete employee with active subordinates. Reassign subordinates first.');
            return null;
        }

        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => $employee->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'employee:delete:' . $employee->id,
            'description' => $reason,
            'payload' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'employee_number' => $employee->employee_number,
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'employee:delete_requested',
            $employee,
            "Requested employee deletion approval for '{$employee->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending employee deletion after approval
     */
    public static function executeDelete(ApprovalAuditRequest $request, $approver = null): bool
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $approver = $approver ?? current_actor();
                $payload = $request->payload;
                $employeeId = $payload['id'] ?? null;

                if (!$employeeId) {
                    CleanError::show('Invalid employee ID in approval payload.');
                    return false;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    CleanError::show('Employee not found. Please refresh and try again.');
                    return false;
                }
                $employeeName = $employee->name;

            // Revoke all roles before deletion
            $employee->syncRoles([]);

            // Soft delete the employee
            $employee->delete();

            // Log the deletion
            EmployeeAuditService::logEmployeeDeactivation($employee, $approver, 'Employee deleted after approval');

                return true;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to delete employee.', $e, [
                'request_id' => $request->id,
            ]);
            return false;
        }
    }

    /**
     * Create a pending role sync request
     */
    public static function requestRoleSync(Employee $employee, array $newRoleIds, string $reason): ?ApprovalAuditRequest
    {
        // Validate employee exists
        if (!$employee->exists) {
            CleanError::show('Employee does not exist.');
            return null;
        }

        $requester = current_actor();
        $oldRoles = $employee->roles->pluck('name')->toArray();

        // Convert new role IDs to role names
        $newRoles = [];
        if (!empty($newRoleIds)) {
            $newRoles = \Spatie\Permission\Models\Role::whereIn('id', $newRoleIds)
                ->pluck('name')
                ->toArray();
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $employee->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'employee:sync_roles:' . $employee->id,
            'description' => $reason,
            'payload' => [
                'id' => $employee->id,
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        $added = array_diff($newRoles, $oldRoles);
        $removed = array_diff($oldRoles, $newRoles);
        $changeDescription = '';
        if (!empty($added)) {
            $changeDescription .= 'Adding: ' . implode(', ', $added) . '. ';
        }
        if (!empty($removed)) {
            $changeDescription .= 'Removing: ' . implode(', ', $removed) . '.';
        }

        AuditService::log(
            $requester,
            'employee:role_sync_requested',
            $employee,
            "Requested role sync approval for '{$employee->name}'. {$changeDescription} Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending role sync after approval
     */
    public static function executeRoleSync(ApprovalAuditRequest $request, $approver = null): ?Employee
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $approver = $approver ?? current_actor();
                $payload = $request->payload;
                $employeeId = $payload['id'] ?? null;

                if (!$employeeId) {
                    CleanError::show('Invalid employee ID in approval payload.');
                    return null;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    CleanError::show('Employee not found. Please refresh and try again.');
                    return null;
                }
                $oldRoles = $payload['old_roles'] ?? [];
                $newRoles = $payload['new_roles'] ?? [];

            // Sync roles
            $employee->syncRoles($newRoles);

            // Log the role change
            EmployeeAuditService::logRoleChange(
                $employee,
                $oldRoles,
                $newRoles,
                $request->description,
                $approver
            );

                return $employee;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to sync roles.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending permission sync request
     */
    public static function requestPermissionSync(Employee $employee, array $newPermissions, string $reason): ?ApprovalAuditRequest
    {
        // Validate employee exists
        if (!$employee->exists) {
            CleanError::show('Employee does not exist.');
            return null;
        }

        $requester = current_actor();
        $oldPermissions = $employee->getDirectPermissions()->pluck('name')->toArray();

        $request = ApprovalAuditRequest::create([
            'branch_id' => $employee->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'employee:sync_permissions:' . $employee->id,
            'description' => $reason,
            'payload' => [
                'id' => $employee->id,
                'old_permissions' => $oldPermissions,
                'new_permissions' => $newPermissions,
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'employee:permission_sync_requested',
            $employee,
            "Requested permission sync approval for '{$employee->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending permission sync after approval
     */
    public static function executePermissionSync(ApprovalAuditRequest $request, $approver = null): ?Employee
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $approver = $approver ?? current_actor();
                $payload = $request->payload;
                $employeeId = $payload['id'] ?? null;

                if (!$employeeId) {
                    CleanError::show('Invalid employee ID in approval payload.');
                    return null;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    CleanError::show('Employee not found. Please refresh and try again.');
                    return null;
                }
                $oldPermissions = $payload['old_permissions'] ?? [];
                $newPermissions = $payload['new_permissions'] ?? [];

            // Sync permissions
            $employee->syncPermissions($newPermissions);

            // Log permission changes
            $granted = array_diff($newPermissions, $oldPermissions);
            $revoked = array_diff($oldPermissions, $newPermissions);

            foreach ($granted as $permission) {
                EmployeeAuditService::logPermissionChange($employee, $permission, 'granted', $request->description, $approver);
            }

            foreach ($revoked as $permission) {
                EmployeeAuditService::logPermissionChange($employee, $permission, 'revoked', $request->description, $approver);
            }

                return $employee;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to sync permissions.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Reject an employee-related request
     */
    public static function rejectRequest(ApprovalAuditRequest $request, $approver, string $rejectionReason): void
    {
        $request->update([
            'approver_id' => $approver->id,
            'approver_type' => get_class($approver),
            'status' => 'rejected',
            'rejection_comment' => $rejectionReason,
            'denied_at' => now(),
        ]);

        // Log the rejection
        AuditService::log(
            $approver,
            'employee:request_rejected',
            $request,
            "Request rejected: {$rejectionReason}",
            'completed'
        );
    }

    /**
     * Validate employee data for creation/update
     */
    private static function validateEmployeeData(array $data): bool
    {
        $requiredFields = ['name', 'email'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                CleanError::show("The {$field} field is required for employee creation.");
                return false;
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            CleanError::show('Invalid email format.');
            return false;
        }

        // Check email uniqueness (excluding current employee for updates)
        $existingEmployee = Employee::where('email', $data['email']);
        if (isset($data['id'])) {
            $existingEmployee->where('id', '!=', $data['id']);
        }
        if ($existingEmployee->exists()) {
            CleanError::show('An employee with this email already exists.');
            return false;
        }

        return true;
    }
}
