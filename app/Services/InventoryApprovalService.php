<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Models\ApprovalAuditRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class InventoryApprovalService
{
    /**
     * Create a pending stock adjustment request
     */
    public static function requestStockAdjustment(Employee|User $requester, int $stockId, array $adjustments, string $reason): ?ApprovalAuditRequest
    {
        // Validate stock exists
        if (!$stockId) {
            CleanError::show('Stock ID is required for adjustment request.');
            return null;
        }

        $stock = Stock::with('item')->find($stockId);
        if (!$stock) {
            CleanError::show('Stock not found. Please refresh and try again.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'stock_adjustment:' . $stockId,
            'description' => $reason,
            'payload' => [
                'stock_item_id' => $stockId,
                'quantity_available' => (float) ($adjustments['quantity_available'] ?? 0),
                'quantity_reserved' => (float) ($adjustments['quantity_reserved'] ?? 0),
                'quantity_damaged' => (float) ($adjustments['quantity_damaged'] ?? 0),
                'average_cost' => (float) ($adjustments['average_cost'] ?? 0),
                'health_status' => $adjustments['health_status'] ?? 'good',
                'expiry_date' => $adjustments['expiry_date'] ?? null,
                'reorder_level' => (float) ($adjustments['reorder_level'] ?? 0),
                'max_stock_level' => (float) ($adjustments['max_stock_level'] ?? 0),
                'notes' => $adjustments['notes'] ?? '',
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        $avgCost = $adjustments['average_cost'] ?? 0;
        $health = $adjustments['health_status'] ?? 'good';
        $reorder = $adjustments['reorder_level'] ?? 0;
        $maxStock = $adjustments['max_stock_level'] ?? 0;
        
        AuditService::log(
            $requester,
            'create',
            $request,
            "Requested stock adjustment approval for item '{$stock->item->name}'. " .
            "Proposed: Available={$adjustments['quantity_available']}, Reserved={$adjustments['quantity_reserved']}, " .
            "Damaged={$adjustments['quantity_damaged']}, Cost={$avgCost}, " .
            "Health={$health}, Reorder={$reorder}, Max={$maxStock}. Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending stock adjustment after approval
     */
    public static function executeStockAdjustment(ApprovalAuditRequest $request): ?Stock
    {
        try {
            return DB::transaction(function () use ($request) {
                $approver =  current_actor();
                $payload = $request->payload;
                $stockItemId = $payload['stock_item_id'] ?? null;

                if (!$stockItemId) {
                    CleanError::show('Invalid stock item ID in approval payload.');
                    return null;
                }

                $stock = Stock::where('id', $stockItemId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if (!$stock) {
                    CleanError::show('Stock not found for approval request.');
                    return null;
                }

            $oldQuantity = (float) $stock->quantity_available;
            $newQuantity = (float) ($payload['quantity_available'] ?? 0);
            $oldDamaged = (float) $stock->quantity_damaged;
            $newDamaged = (float) ($payload['quantity_damaged'] ?? 0);
            $unitCost = (float) ($payload['average_cost'] ?? $stock->average_cost ?? 0);

            // Update stock
            $stock->update([
                'quantity_available' => $newQuantity,
                'quantity_reserved' => (float) ($payload['quantity_reserved'] ?? 0),
                'quantity_damaged' => (float) ($payload['quantity_damaged'] ?? 0),
                'average_cost' => (float) ($payload['average_cost'] ?? 0),
                'health_status' => $payload['health_status'] ?? 'good',
                'expiry_date' => $payload['expiry_date'] ?? null,
                'last_stock_take_date' => now(),
            ]);

            // Update item reorder and max stock levels if provided
            if (isset($payload['reorder_level']) || isset($payload['max_stock_level'])) {
                $stock->item->update([
                    'reorder_level' => (float) ($payload['reorder_level'] ?? $stock->item->reorder_level),
                    'max_stock_level' => (float) ($payload['max_stock_level'] ?? $stock->item->max_stock_level),
                ]);
            }

            $quantityDiff = (float) ($newQuantity - $oldQuantity);
            if ($quantityDiff != 0.0) {
                StockMovement::create([
                    'stock_id' => $stock->id,
                    'type' => 'adjustment',
                    'adjustment_reason' => 'adjustment',
                    'quantity' => (float) abs($quantityDiff),
                    'quantity_before' => $oldQuantity,
                    'quantity_after' => $newQuantity,
                    'unit_cost' => $unitCost,
                    'cost_impact' => (float) abs($quantityDiff) * $unitCost,
                    'reference_type' => ApprovalAuditRequest::class,
                    'reference_id' => $request->id,
                    'moved_by_id' => $approver->id,
                    'moved_by_type' => get_class($approver),
                    'approved_by_id' => $approver->id,
                    'approved_by_type' => get_class($approver),
                    'approved_at' => now(),
                    'notes' => 'Approved adjustment: ' . ($payload['notes'] ?? ''),
                    'movement_date' => now(),
                ]);
            }

            $damagedDiff = (float) ($newDamaged - $oldDamaged);
            if ($damagedDiff != 0.0) {
                StockMovement::create([
                    'stock_id' => $stock->id,
                    'type' => 'damaged',
                    'adjustment_reason' => 'damage',
                    'quantity' => (float) abs($damagedDiff),
                    'quantity_before' => $oldDamaged,
                    'quantity_after' => $newDamaged,
                    'unit_cost' => $unitCost,
                    'cost_impact' => (float) abs($damagedDiff) * $unitCost,
                    'reference_type' => ApprovalAuditRequest::class,
                    'reference_id' => $request->id,
                    'moved_by_id' => $approver->id,
                    'moved_by_type' => get_class($approver),
                    'approved_by_id' => $approver->id,
                    'approved_by_type' => get_class($approver),
                    'approved_at' => now(),
                    'notes' => 'Approved damage adjustment: ' . ($payload['notes'] ?? ''),
                    'movement_date' => now(),
                ]);
            }

                return $stock;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to execute stock adjustment.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Reject a pending stock adjustment request
     */
    public static function rejectStockAdjustment(ApprovalAuditRequest $request, Employee|User $approver, string $comment): void
    {
        $request->update([
            'approver_id' => $approver->id,
            'approver_type' => get_class($approver),
            'status' => 'rejected',
            'comment' => $comment,
            'denied_at' => now(),
        ]);
    }

    /**
     * Create a pending item creation request
     */
    public static function requestItemCreation(Employee|User $requester, array $itemData, string $reason): ?ApprovalAuditRequest
    {
        // Validate required fields
        if (empty($itemData['name']) || empty($itemData['sku'])) {
            CleanError::show('Item name and SKU are required for creation request.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'create_item',
            'description' => $reason,
            'payload' => [
                'name' => $itemData['name'],
                'sku' => $itemData['sku'],
                'category' => $itemData['category'] ?? '',
                'uom_id' => $itemData['uom_id'] ?? null,
                'reorder_level' => (float) ($itemData['reorder_level'] ?? 0),
                'max_stock_level' => (float) ($itemData['max_stock_level'] ?? 0),
                'status' => $itemData['status'] ?? 'active',
            ],
            'status' => 'pending',
        ]);

        // Log the approval request
        $logMessage = "Requested item creation approval. Item: '{$itemData['name']}' (SKU: {$itemData['sku']}) " .
            "Category: " . ($itemData['category'] ?? '') . ", UOM: " . ($itemData['uom'] ?? '') . ", " .
            "Reorder: " . ($itemData['reorder_level'] ?? 0) . ", Max: " . ($itemData['max_stock_level'] ?? 0) . ". " .
            "Reason: {$reason}";
        
        AuditService::log(
            $requester,
            'create',
            $request,
            $logMessage,
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending item creation after approval
     */
    public static function executeItemCreation(ApprovalAuditRequest $request): ?Item
    {
        try {
            return DB::transaction(function () use ($request) {

                $payload = $request->payload;

            $item = Item::create([
                'branch_id' => $request->branch_id,
                'name' => $payload['name'],
                'sku' => $payload['sku'],
                'category' => $payload['category'],
                'uom_id' => $payload['uom_id'],
                'reorder_level' => (float) $payload['reorder_level'],
                'max_stock_level' => (float) $payload['max_stock_level'],
                'status' => $payload['status'],
            ]);

            // Create initial stock record
            Stock::create([
                'branch_id' => $item->branch_id,
                'item_id' => $item->id,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'average_cost' => 0,
                'last_stock_take_date' => now(),
                'health_status' => 'good',
            ]);

                return $item;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to create item.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending item update request
     */
    public static function requestItemUpdate(Employee|User $requester, int $itemId, array $changes, string $reason): ?ApprovalAuditRequest
    {
        // Validate item exists
        if (!$itemId) {
            CleanError::show('Item ID is required for update request.');
            return null;
        }

        $item = Item::find($itemId);
        if (!$item) {
            CleanError::show('Item not found. Please refresh and try again.');
            return null;
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'update_item:' . $itemId,
            'description' => $reason,
            'payload' => array_merge(['item_id' => $itemId], $changes),
            'status' => 'pending',
        ]);

        // Log the approval request
        $changesList = [];
        foreach ($changes as $key => $value) {
            $changesList[] = "{$key}: {$value}";
        }
        AuditService::log(
            $requester,
            'create',
            $request,
            "Requested item update approval for '{$item->name}' (SKU: {$item->sku}). " .
            "Changes: " . implode(', ', $changesList) . ". " .
            "Reason: {$reason}",
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending item update after approval
     */
    public static function executeItemUpdate(ApprovalAuditRequest $request): ?Item
    {
        try {
            return DB::transaction(function () use ($request) {
                $payload = $request->payload;
                $itemId = $payload['item_id'] ?? null;

                if (!$itemId) {
                    CleanError::show('Invalid item ID in approval payload.');
                    return null;
                }

                $item = Item::where('id', $itemId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if (!$item) {
                    CleanError::show('Item not found for approval request.');
                    return null;
                }

            // Remove item_id from payload for update
            $updateData = array_filter($payload, fn($key) => $key !== 'item_id', ARRAY_FILTER_USE_KEY);

            // Cast numeric values to float
            if (isset($updateData['reorder_level'])) {
                $updateData['reorder_level'] = (float) $updateData['reorder_level'];
            }
            if (isset($updateData['max_stock_level'])) {
                $updateData['max_stock_level'] = (float) $updateData['max_stock_level'];
            }

            $item->update($updateData);

                return $item;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to update item.', $e, [
                'request_id' => $request->id,
            ]);
            return null;
        }
    }

    /**
     * Create a pending item deletion request
     */
    public static function requestItemDeletion(Employee|User $requester, int $itemId, string $reason, array $deletionData = []): ?ApprovalAuditRequest
    {
        $payload = ['item_id' => $itemId];

        // Include related data if provided
        if (!empty($deletionData['related_data'])) {
            $payload['related_data'] = $deletionData['related_data'];
        }

        $request = ApprovalAuditRequest::create([
            'branch_id' => $requester instanceof User ? current_branch_id() : $requester->branch_id,
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'delete_item:' . $itemId,
            'description' => $reason,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Log the approval request
        $item = Item::find($itemId);
        if (!$item) {
            CleanError::show('Item not found. Please refresh and try again.');
            return null;
        }
        
        // Build log message with related data summary
        $logMessage = "Requested item deletion approval for '{$item->name}' (SKU: {$item->sku}). ";
        if (!empty($deletionData['related_data'])) {
            $relatedData = $deletionData['related_data'];
            $relatedSummary = [];
            if (!empty($relatedData['recipes'])) {
                $relatedSummary[] = $relatedData['recipes']['count'] . " recipe(s)";
            }
            if (!empty($relatedData['stocks'])) {
                $relatedSummary[] = $relatedData['stocks']['count'] . " stock record(s)";
            }
            if (!empty($relatedData['purchases'])) {
                $relatedSummary[] = $relatedData['purchases']['count'] . " purchase order(s)";
            }
            if (!empty($relatedData['stock_movements'])) {
                $relatedSummary[] = $relatedData['stock_movements']['count'] . " stock movement(s)";
            }
            if (!empty($relatedSummary)) {
                $logMessage .= "Related data to delete: " . implode(", ", $relatedSummary) . ". ";
            }
        }
        $logMessage .= "Reason: {$reason}";
        
        AuditService::log(
            $requester,
            'create',
            $request,
            $logMessage,
            'pending'
        );

        return $request;
    }

    /**
     * Execute a pending item deletion after approval
     *
     * This intelligently handles the deletion cascade:
     * - RecipeIngredients using this item are deleted
     * - Recipes that lose all ingredients can be marked as incomplete/disabled
     * - Products are NOT deleted, only their recipes are affected
     * - Stock and purchase data are cascaded as configured in models
     * - Item request details are also deleted
     */
    public static function executeItemDeletion(ApprovalAuditRequest $request, Employee|User $approver): bool
    {
        try {
            return DB::transaction(function () use ($request, $approver) {
                $payload = $request->payload;
                $itemId = $payload['item_id'] ?? null;

                if (!$itemId) {
                    CleanError::show('Invalid item ID in approval payload.');
                    return false;
                }

                $item = Item::where('id', $itemId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if (!$item) {
                    CleanError::show('Item not found for approval request.');
                    return false;
                }

            $itemName = $item->name;
            $itemSku = $item->sku;

            // Step 1: Get all recipes using this item BEFORE deletion
            $affectedRecipeIds = \App\Models\RecipeIngredient::where('item_id', $itemId)
                ->pluck('recipe_id')
                ->unique()
                ->toArray();

            // Step 2: Delete all recipe ingredients using this item
            \App\Models\RecipeIngredient::where('item_id', $itemId)->delete();

            // Step 3: Check affected recipes for integrity
            foreach ($affectedRecipeIds as $recipeId) {
                $recipe = \App\Models\Recipe::find($recipeId);
                if ($recipe && $recipe->ingredients()->count() === 0) {
                    AuditService::log(
                        $approver,
                        'update',
                        $recipe,
                        "Recipe marked incomplete - ingredient '{$itemName}' was deleted. Recipe now has no ingredients and requires review.",
                        'completed'
                    );
                }
            }

            // Step 4: Delete item request details referencing this item
            \App\Models\ItemRequestDetail::where('item_id', $itemId)->delete();

            // Step 5: Delete item dispatches referencing this item
            if (class_exists(\App\Models\ItemDispatch::class)) {
                \App\Models\ItemDispatch::where('item_id', $itemId)->delete();
            }

            // Step 6: Delete stock take details referencing this item
            if (class_exists(\App\Models\StockTakeDetail::class)) {
                \App\Models\StockTakeDetail::where('item_id', $itemId)->delete();
            }

            // Step 7: Delete the item itself
            // This will cascade delete stocks, stock movements, purchase items, etc.
            $item->delete();

            // Step 5: Log the complete deletion action with audit trail
            AuditService::log(
                $approver,
                'delete',
                $item,
                "Deleted item '{$itemName}' (SKU: {$itemSku}). Related data: " .
                count($affectedRecipeIds) . " recipe(s) affected. " .
                "RecipeIngredients, Stocks, StockMovements, and PurchaseItems were cascaded deleted.",
                'completed'
            );

            // Mark request as approved
                $request->update([
                'approver_id' => $approver->id,
                'approver_type' => Employee::class,
                'status' => 'approved',
                'approved_at' => now(),
            ]);
                return true;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to delete item.', $e, [
                'request_id' => $request->id,
            ]);
            return false;
        }
    }

    /**
     * Create a pending purchase creation request
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
    public static function executePurchaseCreation(ApprovalAuditRequest $request, Employee|User $approver)
    {
        // Implementation will be similar to purchase creation in Purchases.php
        // This is a placeholder - the actual logic should be in the service
            $request->update([
                'approver_id' => $approver->id,
                'approver_type' => get_class($approver),
                'status' => 'approved',
                'approved_at' => now(),
            ]);

        return $request;
    }

    /**
     * Create a pending purchase deletion request
     */
    public static function requestPurchaseDeletion(Employee|User $requester, int $purchaseId, string $reason): ?ApprovalAuditRequest
    {
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
        $purchase = Purchase::find($purchaseId);
        if (!$purchase) {
            CleanError::show('Purchase not found. Please refresh and try again.');
            return null;
        }
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

                $purchase->delete();

                // Mark request as approved
                $request->update([
                    'approver_id' => $approver->id,
                    'approver_type' => Employee::class,
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            CleanError::show('Failed to delete purchase.', $e, [
                'request_id' => $request->id,
            ]);
            return false;
        }
    }
}
