<?php

namespace App\Services;

use App\Models\ApprovalAuditRequest;
use App\Models\ProductDispatchCallback;
use App\Models\ProductionCallback;
use Illuminate\Support\Facades\DB;

class CallbackApprovalService
{
    // ==========================================
    // INVENTORY CALLBACKS (ProductDispatchCallback)
    // ==========================================

    /**
     * Request approval for an inventory callback (product return from sales)
     */
    public static function requestInventoryCallback(
        ProductDispatchCallback $callback,
        string $reason
    ): ApprovalAuditRequest {
        $requester = current_actor();

        $request = ApprovalAuditRequest::create([
            'branch_id' => $callback->salesShift?->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'callback:inventory:' . $callback->id,
            'description' => $reason,
            'payload' => [
                'callback_id' => $callback->id,
                'callback_type' => 'inventory',
                'product_id' => $callback->product_id,
                'product_name' => $callback->product?->name,
                'quantity' => $callback->quantity,
                'uom' => $callback->uom,
                'reason' => $callback->reason,
            ],
            'status' => 'pending',
        ]);

        AuditService::log(
            $requester,
            'callback:inventory_requested',
            $callback,
            "Requested inventory callback approval for {$callback->quantity} {$callback->uom} of '{$callback->product?->name}'. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute an approved inventory callback
     */
    public static function executeInventoryCallback(
        ApprovalAuditRequest $request,
        $approver = null
    ): ProductDispatchCallback {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $callbackId = $payload['callback_id'] ?? null;

            if (!$callbackId) {
                throw new \Exception('Invalid callback ID in approval payload');
            }

            $callback = ProductDispatchCallback::findOrFail($callbackId);

            // Approve, receive, and complete the callback with stock updates
            if (!$callback->approveReceiveAndComplete($approver)) {
                throw new \Exception('Failed to process inventory callback');
            }

            AuditService::log(
                $approver,
                'callback:inventory_completed',
                $callback,
                "Inventory callback approved and completed for {$callback->quantity} {$callback->uom} of '{$callback->product?->name}'",
                'completed'
            );

            return $callback;
        });
    }

    // ==========================================
    // PRODUCTION CALLBACKS (ProductionCallback)
    // ==========================================

    /**
     * Request approval for a production callback (damaged items)
     */
    public static function requestProductionCallback(
        ProductionCallback $callback,
        string $reason
    ): ApprovalAuditRequest {
        $requester = current_actor();

        $itemName = $callback->isRawMaterial()
            ? $callback->item?->name
            : $callback->product?->name;

        $request = ApprovalAuditRequest::create([
            'branch_id' => $callback->shift?->branch_id ?? current_branch_id(),
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'callback:production:' . $callback->id,
            'description' => $reason,
            'payload' => [
                'callback_id' => $callback->id,
                'callback_type' => 'production',
                'source_type' => $callback->source_type,
                'item_id' => $callback->item_id,
                'product_id' => $callback->product_id,
                'item_name' => $itemName,
                'quantity' => $callback->quantity,
                'uom' => $callback->uom,
                'reason' => $callback->reason,
            ],
            'status' => 'pending',
        ]);

        AuditService::log(
            $requester,
            'callback:production_requested',
            $callback,
            "Requested production callback approval for {$callback->quantity} {$callback->uom} of '{$itemName}'. Type: {$callback->source_type}. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute an approved production callback
     */
    public static function executeProductionCallback(
        ApprovalAuditRequest $request,
        $approver = null
    ): ProductionCallback {
        return DB::transaction(function () use ($request, $approver) {
            $approver = $approver ?? current_actor();
            $payload = $request->payload;
            $callbackId = $payload['callback_id'] ?? null;

            if (!$callbackId) {
                throw new \Exception('Invalid callback ID in approval payload');
            }

            $callback = ProductionCallback::findOrFail($callbackId);

            // Approve the callback with automatic stock updates
            if (!$callback->approve($approver)) {
                throw new \Exception('Failed to approve production callback');
            }

            // Complete the callback
            $callback->complete();

            $itemName = $callback->isRawMaterial()
                ? $callback->item?->name
                : $callback->product?->name;

            AuditService::log(
                $approver,
                'callback:production_completed',
                $callback,
                "Production callback approved and completed for {$callback->quantity} {$callback->uom} of '{$itemName}'",
                'completed'
            );

            return $callback;
        });
    }

    /**
     * Reject a production callback
     */
    public static function rejectProductionCallback(
        ApprovalAuditRequest $request,
        $approver,
        string $rejectionReason
    ): ProductionCallback {
        return DB::transaction(function () use ($request, $approver, $rejectionReason) {
            $payload = $request->payload;
            $callbackId = $payload['callback_id'] ?? null;

            if (!$callbackId) {
                throw new \Exception('Invalid callback ID in approval payload');
            }

            $callback = ProductionCallback::findOrFail($callbackId);

            // Reject the callback
            $callback->reject($approver, $rejectionReason);

            // Update the approval request
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => get_class($approver),
                'status' => 'rejected',
                'rejection_comment' => $rejectionReason,
                'denied_at' => now(),
            ]);

            $itemName = $callback->isRawMaterial()
                ? $callback->item?->name
                : $callback->product?->name;

            AuditService::log(
                $approver,
                'callback:production_rejected',
                $callback,
                "Production callback rejected for '{$itemName}'. Reason: {$rejectionReason}",
                'completed'
            );

            return $callback;
        });
    }

    // ==========================================
    // COMMON METHODS
    // ==========================================

    /**
     * Reject any callback request
     */
    public static function rejectRequest(
        ApprovalAuditRequest $request,
        $approver,
        string $rejectionReason
    ): void {
        $request->update([
            'approver_id' => $approver->id,
            'approver_type' => get_class($approver),
            'status' => 'rejected',
            'rejection_comment' => $rejectionReason,
            'denied_at' => now(),
        ]);

        AuditService::log(
            $approver,
            'callback:request_rejected',
            $request,
            "Callback request rejected: {$rejectionReason}",
            'completed'
        );
    }

    /**
     * Log callback creation (for direct callback creation without approval)
     */
    public static function logCallbackCreated(
        $callback,
        string $type, // 'inventory' or 'production'
        $actor
    ): void {
        $itemName = $type === 'inventory'
            ? $callback->product?->name
            : ($callback->isRawMaterial() ? $callback->item?->name : $callback->product?->name);

        AuditService::log(
            $actor,
            "callback:{$type}_created",
            $callback,
            "Callback created for {$callback->quantity} {$callback->uom} of '{$itemName}'. Reason: {$callback->reason}",
            'completed'
        );
    }

    /**
     * Log callback status change
     */
    public static function logCallbackStatusChange(
        $callback,
        string $type,
        string $oldStatus,
        string $newStatus,
        $actor
    ): void {
        $itemName = $type === 'inventory'
            ? $callback->product?->name
            : ($callback->isRawMaterial() ? $callback->item?->name : $callback->product?->name);

        AuditService::log(
            $actor,
            "callback:{$type}_status_changed",
            $callback,
            "Callback status changed from '{$oldStatus}' to '{$newStatus}' for '{$itemName}'",
            'completed'
        );
    }
}
