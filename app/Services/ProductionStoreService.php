<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Item;
use App\Models\ItemDispatch;
use App\Models\ItemRequest;
use App\Models\ProductDispatch;
use App\Models\ProductionStore;
use App\Models\ProductionStoreMovement;
use App\Models\ProductionStoreStock;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionStoreService
{
    /**
     * Stock the store from an ItemRequest dispatch.
     * This is called when inventory dispatches items to production department.
     */
    public function stockFromDispatch(ItemRequest $request, ItemDispatch $dispatch): void
    {
        $store = ProductionStore::getOrCreateForDepartment($request->department_id);

        $item = $dispatch->item;
        $quantity = (float) $dispatch->quantity;
        $unitCost = (float) ($item->unit_price ?? 0);

        DB::transaction(function () use ($store, $item, $quantity, $unitCost, $request, $dispatch) {
            $stock = ProductionStoreStock::firstOrNew([
                'store_id' => $store->id,
                'item_id' => $item->id,
            ]);

            $existingQty = (float) ($stock->quantity_available ?? 0);
            $existingCost = (float) ($stock->average_cost ?? 0);

            if ($existingQty + $quantity > 0) {
                $stock->average_cost = (($existingQty * $existingCost) + ($quantity * $unitCost)) / ($existingQty + $quantity);
            } else {
                $stock->average_cost = $unitCost;
            }

            $stock->quantity_available = $existingQty + $quantity;
            $stock->save();

            $this->recordIn($store, $item, $quantity, $request, $dispatch);
        });
    }

    /**
     * Record produced output into the store as held stock.
     *
     * Used by the Produce flow for BOTH:
     *  - WIP products (intermediate stock consumed by other recipes), and
     *  - finished goods (held in production until dispatched to sales).
     *
     * Adds the approved quantity to the product's store balance and writes a
     * correctly-attributed `in` movement (quantity_before/after + actor), so the
     * produced quantity not yet sent out remains as the closing balance.
     */
    public function recordProduction(
        ProductionStore $store,
        $product,
        float $quantity,
        ?Recipe $recipe = null,
        $actor = null,
        bool $isWip = false
    ): ProductionStoreStock {
        $productId = $product instanceof \Illuminate\Database\Eloquent\Model ? $product->id : $product;
        $unitCost = $product instanceof \App\Models\Product ? (float) ($product->cost ?? 0) : 0.0;
        $actor = $actor ?? current_actor();

        $stock = ProductionStoreStock::firstOrNew([
            'store_id' => $store->id,
            'item_id' => $productId,
        ]);

        $before = (float) ($stock->quantity_available ?? 0);
        $stock->quantity_available = $before + $quantity;
        if (! $stock->average_cost && $unitCost > 0) {
            $stock->average_cost = $unitCost;
        }
        $stock->save();

        ProductionStoreMovement::create([
            'store_id' => $store->id,
            'item_id' => $productId,
            'type' => 'in',
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $before + $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $recipe ? Recipe::class : null,
            'reference_id' => $recipe?->id,
            'moved_by_id' => $actor?->id,
            'moved_by_type' => $actor ? get_class($actor) : null,
            'movement_date' => now(),
            'notes' => ($isWip ? 'WIP' : 'Finished Goods')
                . ' Production (Approved): '
                . ($recipe?->product_name ?? ($product->name ?? '')),
        ]);

        return $stock;
    }

    /**
     * Draw down held finished-goods stock when it is dispatched out of production
     * (to a sales department or a customer order). The goods have physically left
     * production, so the held balance is reduced and an out/transfer movement is
     * recorded. The balance is allowed to go negative on purpose: a negative held
     * balance flags "dispatched more than was produced/held" for reconciliation.
     */
    public function recordDispatch(
        ProductionStore $store,
        $product,
        float $quantity,
        string $type = 'transfer',
        $reference = null,
        $actor = null,
        ?string $note = null
    ): ProductionStoreStock {
        $productId = $product instanceof \Illuminate\Database\Eloquent\Model ? $product->id : $product;
        $unitCost = $product instanceof \App\Models\Product ? (float) ($product->cost ?? 0) : 0.0;
        $actor = $actor ?? current_actor();
        $type = in_array($type, ['out', 'transfer'], true) ? $type : 'transfer';

        $stock = ProductionStoreStock::firstOrNew([
            'store_id' => $store->id,
            'item_id' => $productId,
        ]);

        $before = (float) ($stock->quantity_available ?? 0);
        $after = $before - $quantity;
        $stock->quantity_available = $after;
        $stock->save();

        ProductionStoreMovement::create([
            'store_id' => $store->id,
            'item_id' => $productId,
            'type' => $type,
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'unit_cost' => $unitCost,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'moved_by_id' => $actor?->id,
            'moved_by_type' => $actor ? get_class($actor) : null,
            'movement_date' => now(),
            'notes' => $note ?? 'Dispatched out of production',
        ]);

        return $stock;
    }

    /**
     * Reconcile production dispatches against what sales actually received, for a
     * day — the "kitchen sent vs shop received" check. Returns only flagged rows:
     * those still awaiting verification, or where the received quantity differs
     * from what was dispatched (a transit shortfall/over-receipt to investigate).
     *
     * Pass $productIds to scope to a production department's finished goods.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getDispatchVariances(string $branchId, $date, ?array $productIds = null): array
    {
        $query = ProductDispatch::query()
            ->where('branch_id', $branchId)
            ->whereDate('dispatch_date', $date);

        if (! empty($productIds)) {
            $query->whereIn('product_id', $productIds);
        }

        $rows = [];
        foreach ($query->with('product')->get() as $d) {
            $dispatched = (float) $d->quantity;
            $received = $d->received_quantity !== null ? (float) $d->received_quantity : null;
            $variance = $received === null ? null : $received - $dispatched;

            // Flag if not yet confirmed received, or received differs from sent.
            $flagged = $d->status !== 'received'
                ? true
                : ($variance !== null && abs($variance) > 0.001);

            if (! $flagged) {
                continue;
            }

            $rows[] = [
                'dispatch_id' => $d->id,
                'product_id' => $d->product_id,
                'product_name' => $d->product?->name ?? 'Unknown',
                'sales_department_id' => $d->sales_department_id,
                'dispatched' => $dispatched,
                'received' => $received,
                'variance' => $variance,
                'status' => $d->status,
            ];
        }

        return $rows;
    }

    /**
     * Consume ingredients from store for production.
     * Returns consumption result with details.
     */
    public function consumeForProduction(Recipe $recipe, float $productionQty): ConsumptionResult
    {
        $store = ProductionStore::forBranch(current_branch_id())
            ->forDepartment($recipe->department_id)
            ->active()
            ->first();

        if (!$store) {
            return new ConsumptionResult(
                consumed: [],
                shortages: [],
                fullySatisfied: false,
                error: 'No production store found for this department.'
            );
        }

        $wipService = app(WipRecipeService::class);
        $ingredients = $wipService->resolveIngredients($recipe, $productionQty, false);

        $consumed = [];
        $shortages = [];
        $totalCost = 0;
        $fullySatisfied = true;

        foreach ($ingredients as $ingredient) {
            $item = $ingredient->item; // Item or Product
            $neededQty = $ingredient->quantity_for_production;

            $available = $this->getAvailableStock($store, $item);

            if ($available >= $neededQty) {
                $this->recordOut($store, $item, $neededQty, $recipe);
                $consumed[$ingredient->item_id] = [
                    'quantity' => $neededQty,
                    'cost' => $neededQty * $ingredient->cost_per_unit,
                ];
                $totalCost += $neededQty * $ingredient->cost_per_unit;
            } else {
                if ($available > 0) {
                    $this->recordOut($store, $item, $available, $recipe);
                    $consumed[$ingredient->item_id] = [
                        'quantity' => $available,
                        'cost' => $available * $ingredient->cost_per_unit,
                    ];
                    $totalCost += $available * $ingredient->cost_per_unit;
                }

                $shortages[] = [
                    'item' => $item,
                    'item_id' => $ingredient->item_id,
                    'needed' => $neededQty,
                    'available' => $available,
                    'shortage' => $neededQty - $available,
                ];
                $fullySatisfied = false;
            }
        }

        return new ConsumptionResult(
            consumed: $consumed,
            shortages: $shortages,
            fullySatisfied: $fullySatisfied,
            totalCost: $totalCost
        );
    }

    /**
     * Get available stock for an item in the store.
     */
    public function getAvailableStock(ProductionStore $store, $item): float
    {
        $itemId = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : $item;

        $stock = $store->stocks()
            ->where('item_id', $itemId)
            ->first();

        return (float) ($stock?->quantity_available ?? 0);
    }

    /**
     * Get all stock items in a store.
     */
    public function getAllStock(ProductionStore $store): Collection
    {
        return $store->stocks()
            ->where('quantity_available', '>', 0)
            ->get();
    }

    /**
     * Record IN movement (stock added to store).
     */
    public function recordIn(
        ProductionStore $store,
        $item,
        float $quantity,
        $reference,
        $dispatch = null
    ): ProductionStoreMovement {
        $itemId = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : $item;
        $unitPrice = $item instanceof \App\Models\Item ? $item->unit_price : ($item instanceof \App\Models\Product ? $item->cost : 0);

        $stock = $store->stocks()->where('item_id', $itemId)->first();
        $before = (float) ($stock?->quantity_available ?? 0);

        $movement = ProductionStoreMovement::create([
            'store_id' => $store->id,
            'item_id' => $itemId,
            'type' => 'in',
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $before + $quantity,
            'unit_cost' => $unitPrice,
            'reference_type' => $dispatch ? ItemDispatch::class : ($reference ? get_class($reference) : null),
            'reference_id' => $dispatch?->id ?? $reference?->id,
            'moved_by_id' => current_actor()?->id,
            'moved_by_type' => get_class(current_actor()),
            'movement_date' => now(),
            'notes' => $dispatch ? "Stocked from dispatch" : "Stocked in",
        ]);

        return $movement;
    }

    /**
     * Record OUT movement (stock consumed from store).
     */
    public function recordOut(
        ProductionStore $store,
        $item,
        float $quantity,
        $reference
    ): ProductionStoreMovement {
        $itemId = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : $item;
        $unitPrice = $item instanceof \App\Models\Item ? $item->unit_price : ($item instanceof \App\Models\Product ? $item->cost : 0);

        $stock = $store->stocks()->where('item_id', $itemId)->first();
        $before = (float) ($stock?->quantity_available ?? 0);

        if ($stock) {
            $stock->quantity_available = $before - $quantity;
            $stock->save();
        }

        $movement = ProductionStoreMovement::create([
            'store_id' => $store->id,
            'item_id' => $itemId,
            'type' => 'out',
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $before - $quantity,
            'unit_cost' => $unitPrice,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->id,
            'moved_by_id' => current_actor()?->id,
            'moved_by_type' => get_class(current_actor()),
            'movement_date' => now(),
            'notes' => 'Consumed for production',
        ]);

        return $movement;
    }

    /**
     * Check if store has sufficient stock for a recipe.
     */
    public function checkStockAvailability(Recipe $recipe, float $productionQty): StockCheckResult
    {
        $store = ProductionStore::forBranch(current_branch_id())
            ->forDepartment($recipe->department_id)
            ->active()
            ->first();

        if (!$store) {
            return new StockCheckResult(
                canProduce: false,
                shortages: [],
                message: 'No production store found for this department.'
            );
        }

        $wipService = app(WipRecipeService::class);
        $ingredients = $wipService->resolveIngredients($recipe, $productionQty, false);

        $shortages = [];

        foreach ($ingredients as $ingredient) {
            $available = $this->getAvailableStock($store, $ingredient->item);

            if ($available < $ingredient->quantity_for_production) {
                $shortages[] = [
                    'item' => $ingredient->item,
                    'needed' => $ingredient->quantity_for_production,
                    'available' => $available,
                    'shortage' => $ingredient->quantity_for_production - $available,
                ];
            }
        }

        $canProduce = count($shortages) === 0;

        return new StockCheckResult(
            canProduce: $canProduce,
            shortages: $shortages,
            message: $canProduce ? 'Sufficient stock available' : 'Insufficient stock in production store'
        );
    }

    /**
     * Transfer items from store back to main inventory.
     */
    public function transferToMainInventory(
        ProductionStore $store,
        $item,
        float $quantity
    ): void {
        $itemId = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : $item;
        $unitPrice = $item instanceof \App\Models\Item ? $item->unit_price : ($item instanceof \App\Models\Product ? $item->cost : 0);

        $stock = $store->stocks()
            ->where('item_id', $itemId)
            ->first();

        if (!$stock || $stock->quantity_available < $quantity) {
            $available = $stock?->quantity_available ?? 0;
            throw new \InvalidArgumentException(
                "Insufficient stock in store. Available: {$available}"
            );
        }

        DB::transaction(function () use ($store, $itemId, $quantity, $stock, $unitPrice) {
            $before = $stock->quantity_available;
            $stock->quantity_available = $before - $quantity;
            $stock->save();

            ProductionStoreMovement::create([
                'store_id' => $store->id,
                'item_id' => $itemId,
                'type' => 'transfer',
                'quantity' => -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $stock->quantity_available,
                'unit_cost' => $unitPrice,
                'moved_by_id' => current_actor()?->id,
                'moved_by_type' => get_class(current_actor()),
                'movement_date' => now(),
                'notes' => 'Transferred to main inventory',
            ]);
        });
    }
}

/**
 * Value object for consumption result.
 */
class ConsumptionResult
{
    public function __construct(
        public array $consumed,
        public array $shortages,
        public bool $fullySatisfied,
        public float $totalCost = 0,
        public ?string $error = null
    ) {}
}

/**
 * Value object for stock check result.
 */
class StockCheckResult
{
    public function __construct(
        public bool $canProduce,
        public array $shortages,
        public string $message
    ) {}
}