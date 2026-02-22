<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;

class PurchaseAuditService
{
    /**
     * Log purchase creation
     */
    public static function logPurchaseCreated(
        Purchase $purchase,
        $actor
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'purchase:created',
            $purchase,
            "Purchase #{$purchase->purchase_number} created from '{$supplierName}'. Total: " . number_format($purchase->landing_cost ?? 0, 2),
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'supplier_name' => $supplierName,
                'total_amount' => $purchase->landing_cost,
                'items_count' => $purchase->items?->count() ?? 0,
            ]
        );
    }

    /**
     * Log purchase approval
     */
    public static function logPurchaseApproved(
        Purchase $purchase,
        $actor,
        ?string $approvalNotes = null
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        $description = "Purchase #{$purchase->purchase_number} approved from '{$supplierName}'";
        if ($approvalNotes) {
            $description .= ". Notes: {$approvalNotes}";
        }

        return AuditService::log(
            $actor,
            'purchase:approved',
            $purchase,
            $description,
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'approval_notes' => $approvalNotes,
            ]
        );
    }

    /**
     * Log purchase rejection
     */
    public static function logPurchaseRejected(
        Purchase $purchase,
        $actor,
        string $rejectionReason
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'purchase:rejected',
            $purchase,
            "Purchase #{$purchase->purchase_number} rejected from '{$supplierName}'. Reason: {$rejectionReason}",
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'rejection_reason' => $rejectionReason,
            ]
        );
    }

    /**
     * Log purchase received
     */
    public static function logPurchaseReceived(
        Purchase $purchase,
        $actor,
        ?array $receivedItems = null
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';
        $itemsCount = $receivedItems ? count($receivedItems) : ($purchase->items?->count() ?? 0);

        return AuditService::log(
            $actor,
            'purchase:received',
            $purchase,
            "Purchase #{$purchase->purchase_number} received from '{$supplierName}'. Items: {$itemsCount}",
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'items_received' => $itemsCount,
                'received_items' => $receivedItems,
            ]
        );
    }

    /**
     * Log purchase partial receipt
     */
    public static function logPartialReceipt(
        Purchase $purchase,
        $actor,
        array $receivedItems,
        array $pendingItems
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'purchase:partial_receipt',
            $purchase,
            "Partial receipt for purchase #{$purchase->purchase_number} from '{$supplierName}'. Received: " . count($receivedItems) . ", Pending: " . count($pendingItems),
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'received_items' => $receivedItems,
                'pending_items' => $pendingItems,
            ]
        );
    }

    /**
     * Log purchase cancellation
     */
    public static function logPurchaseCancelled(
        Purchase $purchase,
        $actor,
        string $cancellationReason
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'purchase:cancelled',
            $purchase,
            "Purchase #{$purchase->purchase_number} cancelled from '{$supplierName}'. Reason: {$cancellationReason}",
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'cancellation_reason' => $cancellationReason,
            ]
        );
    }

    /**
     * Log purchase update
     */
    public static function logPurchaseUpdated(
        Purchase $purchase,
        array $changes,
        $actor
    ): AuditLog {
        $changedFields = array_keys($changes);

        return AuditService::log(
            $actor,
            'purchase:updated',
            $purchase,
            "Purchase #{$purchase->purchase_number} updated. Changed: " . implode(', ', $changedFields),
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'changes' => $changes,
            ]
        );
    }

    /**
     * Log purchase deletion
     */
    public static function logPurchaseDeleted(
        Purchase $purchase,
        $actor,
        string $reason
    ): AuditLog {
        $supplierName = $purchase->supplier?->name ?? $purchase->supplier_name ?? 'Unknown';

        return AuditService::log(
            $actor,
            'purchase:deleted',
            $purchase,
            "Purchase #{$purchase->purchase_number} deleted from '{$supplierName}'. Reason: {$reason}",
            'completed',
            null,
            [
                'purchase_number' => $purchase->purchase_number,
                'supplier_name' => $supplierName,
                'total_amount' => $purchase->landing_cost,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Log stock update from purchase
     */
    public static function logStockUpdatedFromPurchase(
        Purchase $purchase,
        $actor,
        array $stockUpdates
    ): AuditLog {
        return AuditService::log(
            $actor,
            'purchase:stock_updated',
            $purchase,
            "Stock updated from purchase #{$purchase->purchase_number}. Items affected: " . count($stockUpdates),
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'stock_updates' => $stockUpdates,
            ]
        );
    }

    /**
     * Log payment for purchase
     */
    public static function logPurchasePayment(
        Purchase $purchase,
        float $amount,
        string $paymentMethod,
        $actor
    ): AuditLog {
        return AuditService::log(
            $actor,
            'purchase:payment',
            $purchase,
            "Payment of " . number_format($amount, 2) . " made for purchase #{$purchase->purchase_number} via {$paymentMethod}",
            'completed',
            null,
            [
                'purchase_id' => $purchase->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]
        );
    }
}
