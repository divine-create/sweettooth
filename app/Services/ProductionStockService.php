<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\ProductionStoreMovement;
use App\Models\ProductionStoreStock;
use App\Models\Recipe;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionStockService
{
    /**
     * Get all items trackable in a production department's store, categorized into:
     * - finished: Finished Goods (Cakes, Bread, Pastries, Gelato tubs)
     * - wip: Work-in-Progress (Sponges, Doughs, Pre-mixes)
     * - raw: Raw Materials & Ingredients (Flour, Sugar, Butter, Dairy, Flavors)
     */
    public function getTrackableItemsForStore(ProductionStore $store, Department $department): array
    {
        $branchId = $store->branch_id;
        $departmentId = $department->id;

        // 1. Identify WIP product IDs for this department
        $wipProductIds = Recipe::where('is_wip', true)
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereNull('department_id');
            })
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        // Also check ProductType code 'WIP'
        $wipFromType = Product::where('department_id', $departmentId)
            ->whereHas('productType', fn ($q) => $q->where('code', 'WIP'))
            ->pluck('id')
            ->toArray();
        $wipProductIds = array_unique(array_merge($wipProductIds, $wipFromType));

        // 2. Finished Goods: Active products belonging to this department that are not WIP
        $finishedGoods = Product::with('unitOfMeasure')
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereHas('departments', fn ($d) => $d->where('department_id', $departmentId));
            })
            ->where('is_active', true)
            ->when(!empty($wipProductIds), fn ($q) => $q->whereNotIn('id', $wipProductIds))
            ->orderBy('name')
            ->get();

        // 3. WIP Products
        $wipProducts = collect();
        if (!empty($wipProductIds)) {
            $wipProducts = Product::with('unitOfMeasure')
                ->whereIn('id', $wipProductIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // 4. Raw Materials:
        // Items currently in production store stock, plus items designated for this branch
        $storeRawItemIds = ProductionStoreStock::where('store_id', $store->id)
            ->whereRaw("item_id REGEXP '^[0-9]+$'")
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $rawMaterials = Item::with('unitOfMeasure')
            ->where('status', 'active')
            ->where(function ($q) use ($branchId, $storeRawItemIds) {
                $q->where('branch_id', $branchId);
                if (!empty($storeRawItemIds)) {
                    $q->orWhereIn('id', $storeRawItemIds);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'finished' => $finishedGoods,
            'wip' => $wipProducts,
            'raw' => $rawMaterials,
        ];
    }

    /**
     * Initialize daily stock entries for an active shift.
     * Guarantees every active finished good, WIP, and raw material has a record.
     */
    public function initializeShiftDailyStock(ProductionStore $store, Department $department, Shift $shift): Collection
    {
        return $this->ensureBaselineDailyStock($store, $department, $shift, $shift->shift_date ? Carbon::parse($shift->shift_date)->toDateString() : Carbon::today()->toDateString());
    }

    /**
     * Ensure baseline daily stock entries exist for a store and date (with or without an active shift).
     * Carries forward expected opening quantity from previous closing or master store balances.
     */
    public function ensureBaselineDailyStock(ProductionStore $store, Department $department, ?Shift $shift = null, ?string $stockDate = null): Collection
    {
        $targetDate = $stockDate ? Carbon::parse($stockDate)->toDateString() : ($shift?->shift_date ? Carbon::parse($shift->shift_date)->toDateString() : Carbon::today()->toDateString());
        $itemsByCategory = $this->getTrackableItemsForStore($store, $department);

        // If shift provided, link any previously unlinked records for this date
        if ($shift) {
            ProductionStoreDailyStock::where('store_id', $store->id)
                ->where('stock_date', $targetDate)
                ->whereNull('shift_id')
                ->update(['shift_id' => $shift->id]);
        }

        // Fetch existing entries for this store and date
        $existing = ProductionStoreDailyStock::where('store_id', $store->id)
            ->where('stock_date', $targetDate)
            ->when($shift, fn ($q) => $q->where('shift_id', $shift->id))
            ->get()
            ->keyBy('item_id');

        // Master store balances for fallback
        $masterBalances = ProductionStoreStock::where('store_id', $store->id)
            ->pluck('quantity_available', 'item_id');

        $records = collect();

        foreach (['finished', 'wip', 'raw'] as $cat) {
            foreach ($itemsByCategory[$cat] as $entity) {
                $itemId = (string) $entity->id;

                if ($existing->has($itemId)) {
                    $records->push($existing->get($itemId));
                    continue;
                }

                // Carry forward expected opening:
                // 1. Previous shift's closing quantity
                $prevClosing = ProductionStoreDailyStock::where('store_id', $store->id)
                    ->where('item_id', $itemId)
                    ->where('workflow_step', 'closing_completed')
                    ->latest('stock_date')
                    ->latest('id')
                    ->value('closing_quantity');

                $expectedOpening = $prevClosing !== null
                    ? (float) $prevClosing
                    : (float) ($masterBalances->get($itemId) ?? 0.0);

                $record = ProductionStoreDailyStock::create([
                    'store_id' => $store->id,
                    'item_id' => $itemId,
                    'stock_date' => $targetDate,
                    'shift_id' => $shift?->id,
                    'opening_quantity' => $expectedOpening,
                    'addition_quantity' => 0.0,
                    'quantity_used' => 0.0,
                    'closing_quantity' => $expectedOpening,
                    'variance' => 0.0,
                    'workflow_step' => 'opening_pending',
                    'is_workflow_verified' => false,
                ]);

                $records->push($record);
            }
        }

        return $records;
    }

    /**
     * Record a movement against the current active shift's daily stock record.
     */
    public function recordMovement(ProductionStoreMovement $movement): void
    {
        $store = $movement->store;
        if (!$store) {
            return;
        }

        $today = Carbon::today()->toDateString();
        $actor = $movement->actor ?? current_actor();

        // Resolve active shift for this department / actor
        $shift = null;
        if ($actor) {
            $shift = Shift::where('department_id', $store->department_id)
                ->where('shift_date', $today)
                ->where('status', 'active')
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();
        }

        $query = ProductionStoreDailyStock::where('store_id', $movement->store_id)
            ->where('item_id', (string) $movement->item_id)
            ->where('stock_date', $today);

        if ($shift) {
            $query->where('shift_id', $shift->id);
        }

        $dailyStock = $query->first();

        // If not found for current shift, fallback to any today record or create one
        if (!$dailyStock) {
            $dailyStock = ProductionStoreDailyStock::firstOrCreate(
                [
                    'store_id' => $movement->store_id,
                    'item_id' => (string) $movement->item_id,
                    'stock_date' => $today,
                    'shift_id' => $shift?->id,
                ],
                [
                    'opening_quantity' => (float) ($movement->quantity_before ?? 0.0),
                    'addition_quantity' => 0.0,
                    'quantity_used' => 0.0,
                    'closing_quantity' => (float) ($movement->quantity_after ?? 0.0),
                    'variance' => 0.0,
                    'workflow_step' => 'opening_pending',
                ]
            );
        }

        // Positive transfer or in -> addition
        if (in_array($movement->type, ['transfer', 'in']) && $movement->quantity > 0) {
            $dailyStock->addition_quantity += (float) $movement->quantity;
        }
        // Negative / out / consumption -> usage
        elseif (in_array($movement->type, ['consumption', 'out', 'damaged', 'return']) || ($movement->type === 'transfer' && $movement->quantity < 0)) {
            $dailyStock->quantity_used += abs((float) $movement->quantity);
        }

        $dailyStock->updateCalculatedFields();
        $dailyStock->save();
    }
}
