<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Models\ApprovalAuditRequest;
use App\Models\Employee;
use App\Models\Item;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockBatchService;
use Illuminate\Support\Facades\DB;

class PurchaseAuditApprovalService
{
    /**
     * Create a pending purchase creation request via the audit approval system
     */
    public static function requestPurchaseCreation(Employee|User $requester, array $purchaseData, string $reason): ?ApprovalAuditRequest
    {
        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'create_purchase',
            'description' => $reason,
            'payload' => $purchaseData,
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'create',
            $request,
            "Requested purchase creation approval from '{$purchaseData['supplier_name']}'. " .
            "Total: {$purchaseData['landing_cost']}. Items: " . count($purchaseData['items'] ?? []) . ". " .
            "Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending purchase creation after approval
     */
    public static function executePurchaseCreation(ApprovalAuditRequest $request, Employee|User $approver): ?Purchase
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $payload = $request->payload;

            // Create the purchase
            $purchase = Purchase::create([
                'branch_id' => $request->branch_id,
                'recorded_by_id' => $approver->id,
                'recorded_by_type' => get_class($approver),
                'supplier_name' => $payload['supplier_name'],
                'purchase_number' => $payload['purchase_number'] ?? Purchase::generatePurchaseNumber($request->branch_id),
                'purchase_date' => $payload['purchase_date'],
                'total_fob_fc' => (float) ($payload['total_fob_fc'] ?? 0),
                'total_fob_ngn' => (float) ($payload['total_fob_ngn'] ?? 0),
                'other_costs' => (float) ($payload['other_costs'] ?? 0),
                'landing_cost' => (float) $payload['landing_cost'],
                'payment_status' => $payload['payment_status'] ?? 'pending',
                'status' => 'approved',
            ]);

            // Create purchase items and stock movements if provided
            if (!empty($payload['items'])) {
                $itemsById = Item::with('unitOfMeasure')
                    ->whereIn('id', collect($payload['items'])->pluck('item_id')->filter()->unique())
                    ->get()
                    ->keyBy('id');

                foreach ($payload['items'] as $item) {
                    $quantity = (float) $item['quantity'];
                    $costPerUnit = (float) ($item['cost_per_unit'] ?? $item['unit_cost'] ?? 0);
                    $totalCost = $quantity * $costPerUnit;
                    $itemId = (int) ($item['item_id'] ?? 0);
                    $purchaseUom = (string) ($item['uom'] ?? '');
                    $itemModel = $itemsById->get($itemId);

                    if (! $itemModel) {
                        CleanError::show("Item #{$itemId} was not found for purchase approval.");
                        return null;
                    }

                    $baseQuantity = self::convertPurchaseQuantityToItemBase($itemModel, $quantity, $purchaseUom);
                    $baseCostPerUnit = $baseQuantity > 0 ? ($totalCost / $baseQuantity) : 0.0;

                    // Create purchase item
                    $purchaseItemModel = $purchase->purchaseItems()->create([
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'uom' => $purchaseUom,
                        'fob_fc' => (float) ($item['fob_fc'] ?? 0),
                        'fob_ngn' => (float) ($item['fob_ngn'] ?? 0),
                        'other_costs' => (float) ($item['other_costs'] ?? 0),
                        'total_cost' => $totalCost,
                        'cost_per_unit' => $costPerUnit,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                    ]);

                    // Create or get stock
                    $stock = Stock::firstOrCreate(
                        [
                            'branch_id' => $request->branch_id,
                            'item_id' => $itemId,
                        ],
                        [
                            'quantity_available' => 0,
                            'quantity_reserved' => 0,
                            'average_cost' => 0,
                        ]
                    );

                    // Update average cost
                    $stock->updateAverageCost($baseQuantity, $baseCostPerUnit);

                    // Record quantity before update
                    $quantity_before = (float) $stock->quantity_available;

                    // Update stock quantity
                    $stock->quantity_available = $quantity_before + $baseQuantity;
                    $stock->last_stock_take_date = now();
                    $stock->save();

                    // Create batch record and sync stock expiry
                    $purchaseItemModel->load('purchase');
                    app(StockBatchService::class)->createBatchFromPurchase(
                        $stock, $purchaseItemModel, $baseQuantity, $baseCostPerUnit
                    );

                    // Create stock movement record
                    StockMovement::create([
                        'stock_id' => $stock->id,
                        'type' => 'in',
                        'quantity_before' => $quantity_before,
                        'quantity_after' => $stock->quantity_available,
                        'quantity' => $baseQuantity,
                        'reference_type' => 'App\Models\Purchase',
                        'reference_id' => $purchase->id,
                        'moved_by_type' => get_class($approver),
                        'moved_by_id' => $approver->id,
                        'movement_date' => $purchase->purchase_date,
                        'notes' => 'Purchase: ' . $purchase->purchase_number,
                    ]);

                    if ($costPerUnit > 0) {
                        $itemModel->unit_price = $costPerUnit;
                        $itemModel->last_unit_price = $costPerUnit;
                        $itemModel->save();
                    }
                }
            }

            // Mark request as approved
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => get_class($approver),
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Log the completion
            AuditService::log(
                $approver,
                'create',
                $purchase,
                "Purchase #{$purchase->purchase_number} created via approval: " . count($payload['items'] ?? []) . " items. " .
                "Total FOB FC: {$payload['total_fob_fc']}, Total FOB NGN: {$payload['total_fob_ngn']}, Landing Cost: {$payload['landing_cost']}",
                'completed'
            );

                return $purchase;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to create purchase.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending purchase deletion request via the audit approval system
     */
    public static function requestPurchaseDeletion(Employee|User $requester, int $purchaseId, string $reason): ?ApprovalAuditRequest
    {
        $purchase = Purchase::find($purchaseId);
        if (!$purchase) {
            CleanError::show('Purchase not found. Please refresh and try again.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'delete_purchase:' . $purchaseId,
            'description' => $reason,
            'payload' => ['purchase_id' => $purchaseId],
            'status' => 'pending',
        ]);

        // Log the approval request
        AuditService::log(
            $requester,
            'create',
            $request,
            "Requested purchase deletion approval for #{$purchase->purchase_number} from {$purchase->supplier_name}. " .
            "Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending purchase deletion after approval
     */
    public static function executePurchaseDeletion(ApprovalAuditRequest $request, Employee|User $approver): bool
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $payload = $request->payload;
                $purchaseId = $payload['purchase_id'] ?? null;

                if (!$purchaseId) {
                    CleanError::show('Invalid purchase ID in approval payload.');
                    return false;
                }

                $purchase = Purchase::where('id', $purchaseId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if (!$purchase) {
                    CleanError::show('Purchase not found for approval request.');
                    return false;
                }

            $purchaseNumber = $purchase->purchase_number;
            $supplierName = $purchase->supplier_name;

            $purchase->delete();

            // Mark request as approved
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => get_class($approver),
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Log the deletion
            AuditService::log(
                $approver,
                'delete',
                $purchase,
                "Deleted purchase #{$purchaseNumber} from {$supplierName} via approval.",
                'completed'
            );
                return true;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to delete purchase.', $e, [
                'request_id' => $request->id,
            ]);
            return false;
        }
    }

    /**
     * Create a pending purchase approval request from draft
     */
    public static function requestPurchaseApproval(Employee|User $requester, int $purchaseId, string $reason): ?ApprovalAuditRequest
    {
        $purchase = Purchase::find($purchaseId);
        if (!$purchase) {
            CleanError::show('Purchase not found. Please refresh and try again.');
            return null;
        }

        if ($purchase->status !== 'draft') {
            CleanError::show('Purchase must be in draft status to request approval.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'approve_purchase:' . $purchaseId,
            'description' => $reason,
            'payload' => ['purchase_id' => $purchaseId],
            'status' => 'pending',
        ]);

        // Update purchase status
        $purchase->update(['status' => 'pending_approval']);

        // Log the approval request
        AuditService::log(
            $requester,
            'update',
            $purchase,
            "Requested approval for purchase draft #{$purchase->purchase_number} from {$purchase->supplier_name}. " .
            "Total: {$purchase->landing_cost}. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending purchase approval request
     */
    public static function approvePurchase(ApprovalAuditRequest $request, Employee|User $approver): ?Purchase
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $payload = $request->payload;
                $purchaseId = $payload['purchase_id'] ?? null;

                if (!$purchaseId) {
                    CleanError::show('Invalid purchase ID in approval payload.');
                    return null;
                }

                $purchase = Purchase::where('id', $purchaseId)
                    ->where('branch_id', $request->branch_id)
                    ->with('purchaseItems.item.unitOfMeasure')
                    ->first();
                if (!$purchase) {
                    CleanError::show('Purchase not found for approval request.');
                    return null;
                }

            // Update purchase status to approved
            $purchase->update(['status' => 'approved']);

            // Create stock movements for each purchase item
            foreach ($purchase->purchaseItems as $purchaseItem) {
                $itemModel = $purchaseItem->item;
                if (! $itemModel) {
                    CleanError::show("Item #{$purchaseItem->item_id} was not found for purchase approval.");
                    return null;
                }

                $stock = Stock::firstOrCreate(
                    [
                        'branch_id' => $request->branch_id,
                        'item_id' => $purchaseItem->item_id,
                    ],
                    [
                        'quantity_available' => 0,
                        'quantity_reserved' => 0,
                        'average_cost' => 0,
                    ]
                );

                $quantity = (float) $purchaseItem->quantity;
                $costPerUnit = $purchaseItem->cost_per_unit ?? 0;
                $totalCost = $quantity * (float) $costPerUnit;
                $purchaseUom = (string) ($purchaseItem->uom ?? '');
                $baseQuantity = self::convertPurchaseQuantityToItemBase($itemModel, $quantity, $purchaseUom);
                $baseCostPerUnit = $baseQuantity > 0 ? ($totalCost / $baseQuantity) : 0.0;

                // Update average cost
                $stock->updateAverageCost($baseQuantity, $baseCostPerUnit);

                // Record quantity before update
                $quantity_before = (float) $stock->quantity_available;

                // Update stock quantity
                $stock->quantity_available = $quantity_before + $baseQuantity;
                $stock->last_stock_take_date = now();
                $stock->save();

                // Create batch record and sync stock expiry
                $purchaseItem->load('purchase');
                app(StockBatchService::class)->createBatchFromPurchase(
                    $stock, $purchaseItem, $baseQuantity, $baseCostPerUnit
                );

                // Create stock movement record
                StockMovement::create([
                    'stock_id' => $stock->id,
                    'type' => 'in',
                    'quantity_before' => $quantity_before,
                    'quantity_after' => $stock->quantity_available,
                    'quantity' => $baseQuantity,
                    'reference_type' => 'App\Models\Purchase',
                    'reference_id' => $purchase->id,
                    'moved_by_type' => get_class($approver),
                    'moved_by_id' => $approver->id,
                    'movement_date' => $purchase->purchase_date,
                    'notes' => 'Purchase: ' . $purchase->purchase_number,
                ]);

                if ((float) $costPerUnit > 0) {
                    $itemModel->unit_price = (float) $costPerUnit;
                    $itemModel->last_unit_price = (float) $costPerUnit;
                    $itemModel->save();
                }
            }

            // Mark request as approved
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => Employee::class,
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Log the approval
            AuditService::log(
                $approver,
                'update',
                $purchase,
                "Approved purchase #{$purchase->purchase_number} from {$purchase->supplier_name}. " .
                "Created stock movements for " . count($purchase->purchaseItems) . " items.",
                'completed'
            );

                return $purchase;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to approve purchase.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    private static function convertPurchaseQuantityToItemBase(Item $item, float $quantity, ?string $purchaseUom): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        if (! $item->uom_id) {
            return $quantity;
        }

        $purchaseUom = trim((string) $purchaseUom);
        if ($purchaseUom === '') {
            CleanError::show("Purchase UOM is required for item '{$item->name}'.");
            return 0.0;
        }

        $converted = $item->convertToBaseUom($quantity, $purchaseUom);
        if ($converted === null) {
            $baseUom = $item->unitOfMeasure?->symbol ?? 'item base UOM';
            CleanError::show(
                "No UOM conversion found for item '{$item->name}' from {$purchaseUom} to {$baseUom}."
            );
            return 0.0;
        }

        $baseQuantity = (float) $converted;
        if ($baseQuantity <= 0) {
            CleanError::show(
                "Invalid converted quantity for item '{$item->name}' using {$purchaseUom}."
            );
            return 0.0;
        }

        return $baseQuantity;
    }

    /**
     * Reject a pending purchase approval request
     */
    public static function rejectPurchase(ApprovalAuditRequest $request, Employee|User $approver, string $comment): ?Purchase
    {
        try {
            return DB::transaction(function () use ($request, $approver, $comment) {
                $payload = $request->payload;
                $purchaseId = $payload['purchase_id'] ?? null;

                if (!$purchaseId) {
                    CleanError::show('Invalid purchase ID in approval payload.');
                    return null;
                }

                $purchase = Purchase::where('id', $purchaseId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if (!$purchase) {
                    CleanError::show('Purchase not found for approval request.');
                    return null;
                }

            // Update purchase status back to draft
            $purchase->update(['status' => 'draft']);

            // Mark request as rejected
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => get_class($approver),
                'status' => 'rejected',
                'comment' => $comment,
                'denied_at' => now(),
            ]);

            // Log the rejection
            AuditService::log(
                $approver,
                'update',
                $purchase,
                "Rejected purchase #{$purchase->purchase_number} from {$purchase->supplier_name}. Reason: {$comment}",
                'completed'
            );

                return $purchase;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to reject purchase.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }
}
