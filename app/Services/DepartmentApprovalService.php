<?php

namespace App\Services;

use App\Models\ApprovalAuditRequest;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentApprovalService
{
    /**
     * Create a pending department creation request
     */
    public static function requestCreate(array $departmentData, string $reason): ApprovalAuditRequest
    {
        // Validate required fields
        if (empty($departmentData['name'])) {
            throw new \Exception('Department name is required');
        }

        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => $departmentData['branch_id'] ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department:create',
            'description' => $reason,
            'payload' => $departmentData,
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'department:create_requested',
            $request,
            "Requested department creation approval for '{$departmentData['name']}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department creation after approval
     */
    public static function executeCreate(ApprovalAuditRequest $request, $approver = null): Department
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;

            // Generate slug if not provided
            if (empty($payload['slug'])) {
                $payload['slug'] = Str::slug($payload['name']);
            }

            // Create the department
            $department = Department::create($payload);

            // Log the creation
            AuditService::log(
                $approver,
                'department:created',
                $department,
                "Department '{$department->name}' created after approval",
                'completed'
            );

            return $department;
        });
    }

    /**
     * Create a pending department update request
     */
    public static function requestUpdate(Department $department, array $changes, string $reason): ApprovalAuditRequest
    {
        if (!$department->exists) {
            throw new \Exception('Department does not exist');
        }

        $requester = current_actor();

        $payload = array_merge($changes, [
            'id' => $department->id,
            'original_values' => $department->getOriginal(),
        ]);

        $request = ApprovalAuditRequest::create([
            'branch_id' => $department->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department:update:' . $department->id,
            'description' => $reason,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Log the approval request
        $changedFields = array_keys($changes);
        AuditService::log(
            $requester,
            'department:update_requested',
            $department,
            "Requested department update approval for '{$department->name}'. Changes: " . implode(', ', $changedFields) . ". Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department update after approval
     */
    public static function executeUpdate(ApprovalAuditRequest $request, $approver = null): Department
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $departmentId = $payload['id'] ?? null;

            if (!$departmentId) {
                throw new \Exception('Invalid department ID in approval payload');
            }

            $department = Department::findOrFail($departmentId);

            // Remove non-fillable fields
            unset($payload['id'], $payload['original_values']);

            // Update slug if name changed
            if (isset($payload['name']) && empty($payload['slug'])) {
                $payload['slug'] = Str::slug($payload['name']);
            }

            // Update the department
            $department->update($payload);

            // Log the update
            AuditService::log(
                $approver,
                'department:updated',
                $department,
                "Department '{$department->name}' updated after approval",
                'completed'
            );

            return $department;
        });
    }

    /**
     * Create a pending department deletion request
     */
    public static function requestDelete(Department $department, string $reason): ApprovalAuditRequest
    {
        if (!$department->exists) {
            throw new \Exception('Department does not exist');
        }

        // Check if department has employees
        if ($department->employees()->count() > 0) {
            throw new \Exception('Cannot delete department with assigned employees. Reassign employees first.');
        }

        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => $department->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department:delete:' . $department->id,
            'description' => $reason,
            'payload' => [
                'id' => $department->id,
                'name' => $department->name,
                'branch_id' => $department->branch_id,
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'department:delete_requested',
            $department,
            "Requested department deletion approval for '{$department->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department deletion after approval
     */
    public static function executeDelete(ApprovalAuditRequest $request, $approver = null): bool
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $departmentId = $payload['id'] ?? null;

            if (!$departmentId) {
                throw new \Exception('Invalid department ID in approval payload');
            }

            $department = Department::findOrFail($departmentId);
            $departmentName = $department->name;

            // Delete the department
            $department->delete();

            // Log the deletion
            AuditService::log(
                $approver,
                'department:deleted',
                null,
                "Department '{$departmentName}' deleted after approval",
                'completed',
                null,
                ['deleted_department' => $departmentName]
            );

            return true;
        });
    }

    /**
     * Reject a department-related request
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

        AuditService::log(
            $approver,
            'department:request_rejected',
            $request,
            "Request rejected: {$rejectionReason}",
            'completed'
        );
    }
}
