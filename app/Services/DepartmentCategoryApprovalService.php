<?php

namespace App\Services;

use App\Models\ApprovalAuditRequest;
use App\Models\DepartmentCategory;
use Illuminate\Support\Facades\DB;

class DepartmentCategoryApprovalService
{
    /**
     * Create a pending department category creation request
     */
    public static function requestCreate(array $categoryData, string $reason): ApprovalAuditRequest
    {
        if (empty($categoryData['name'])) {
            throw new \Exception('Category name is required');
        }

        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department_category:create',
            'description' => $reason,
            'payload' => $categoryData,
            'status' => 'pending',
        ]);

        AuditService::log(
            $requester,
            'department_category:create_requested',
            $request,
            "Requested department category creation approval for '{$categoryData['name']}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department category creation after approval
     */
    public static function executeCreate(ApprovalAuditRequest $request, $approver = null): DepartmentCategory
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;

            $category = DepartmentCategory::create($payload);

            AuditService::log(
                $approver,
                'department_category:created',
                $category,
                "Department category '{$category->name}' created after approval",
                'completed'
            );

            return $category;
        });
    }

    /**
     * Create a pending department category update request
     */
    public static function requestUpdate(DepartmentCategory $category, array $changes, string $reason): ApprovalAuditRequest
    {
        if (!$category->exists) {
            throw new \Exception('Department category does not exist');
        }

        $requester = current_actor();

        $payload = array_merge($changes, [
            'id' => $category->id,
            'original_values' => $category->getOriginal(),
        ]);

        $request = ApprovalAuditRequest::create([
            'branch_id' => current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department_category:update:' . $category->id,
            'description' => $reason,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        AuditService::log(
            $requester,
            'department_category:update_requested',
            $category,
            "Requested department category update approval for '{$category->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department category update after approval
     */
    public static function executeUpdate(ApprovalAuditRequest $request, $approver = null): DepartmentCategory
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $categoryId = $payload['id'] ?? null;

            if (!$categoryId) {
                throw new \Exception('Invalid category ID in approval payload');
            }

            $category = DepartmentCategory::findOrFail($categoryId);

            unset($payload['id'], $payload['original_values']);

            $category->update($payload);

            AuditService::log(
                $approver,
                'department_category:updated',
                $category,
                "Department category '{$category->name}' updated after approval",
                'completed'
            );

            return $category;
        });
    }

    /**
     * Create a pending department category deletion request
     */
    public static function requestDelete(DepartmentCategory $category, string $reason): ApprovalAuditRequest
    {
        if (!$category->exists) {
            throw new \Exception('Department category does not exist');
        }

        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'department_category:delete:' . $category->id,
            'description' => $reason,
            'payload' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'status' => 'pending',
        ]);

        AuditService::log(
            $requester,
            'department_category:delete_requested',
            $category,
            "Requested department category deletion approval for '{$category->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending department category deletion after approval
     */
    public static function executeDelete(ApprovalAuditRequest $request, $approver = null): bool
    {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $categoryId = $payload['id'] ?? null;

            if (!$categoryId) {
                throw new \Exception('Invalid category ID in approval payload');
            }

            $category = DepartmentCategory::findOrFail($categoryId);
            $categoryName = $category->name;

            $category->delete();

            AuditService::log(
                $approver,
                'department_category:deleted',
                null,
                "Department category '{$categoryName}' deleted after approval",
                'completed',
                null,
                ['deleted_category' => $categoryName]
            );

            return true;
        });
    }

    /**
     * Reject a department category request
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
            'department_category:request_rejected',
            $request,
            "Request rejected: {$rejectionReason}",
            'completed'
        );
    }
}
