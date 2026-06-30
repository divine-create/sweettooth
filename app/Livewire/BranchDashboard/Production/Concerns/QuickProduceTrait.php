<?php

namespace App\Livewire\BranchDashboard\Production\Concerns;

use App\Models\CustomerOrder;
use App\Models\Department;
use App\Models\Item;
use App\Models\ProductDispatch;
use App\Models\ProductionStore;
use App\Models\ProductionStoreMovement;
use App\Models\ProductionStoreStock;
use App\Models\Recipe;
use App\Models\Shift;
use App\Services\CustomerOrderService;
use App\Services\ProductionStoreService;
use Illuminate\Support\Facades\DB;

trait QuickProduceTrait
{
    public ?Department $department = null;

    public ?Recipe $selectedRecipe = null;

    public ?string $selectedRecipeId = null;

    public $quantity = 1;

    public $yieldOutput = 0;

    // Quantity to dispatch from the just-produced batch (defaults to the full
    // approved output; the cashier may send only part and keep the rest in
    // finished goods to dispatch later).
    public $dispatchQuantity = 0;

    public $approvedQuantity = 0;

    public $rejectedQuantity = 0;

    public ?string $rejectionReason = null;

    public array $rejectionReasonOptions = [
        'burnt' => 'Burnt in Oven',
        'dropped' => 'Dropped/Damaged',
        'quality' => 'Quality Failure',
        'testing' => 'Testing/Sampling',
        'expired' => 'Expired during production',
        'other' => 'Other',
    ];

    public array $ingredients = [];

    public array $ingredientStock = [];

    public bool $hasInsufficientStock = false;

    public array $insufficientItems = [];

    public bool $showDispatchModal = false;

    public string $dispatchType = '';

    public ?int $selectedOrderId = null;

    public ?int $selectedSalesDepartmentId = null;

    public ?ProductionStore $productionStore = null;

    public array $pendingOrders = [];

    public ?int $lastProductionRecordId = null;

    public bool $isRedispatch = false;

    abstract public function getBranchId(): ?string;

    abstract public function getRecipes();

    public function mount($deptSlug)
    {
        $this->dept_slug = $deptSlug;
        $this->b_id = request()->query('b_id');

        $this->department = Department::where('slug', $deptSlug)->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        $this->productionStore = ProductionStore::where('branch_id', $this->getBranchId())
            ->where('department_id', $this->department->id)
            ->where('status', 'active')
            ->first();

        $this->loadPendingOrders();
    }

    protected function getCurrentShift(): ?Shift
    {
        $actor = current_actor();

        if (!$actor) {
            return null;
        }

        return Shift::where('employee_id', $actor->id)
            ->where('status', 'active')
            ->first();
    }

    public function updatedSelectedRecipeId(): void
    {
        $this->selectRecipe($this->selectedRecipeId);
    }

    public function selectRecipe($recipeId)
    {
        if (empty($recipeId)) {
            $this->selectedRecipe = null;
            $this->ingredients = [];
            $this->ingredientStock = [];

            return;
        }

        $this->selectedRecipe = Recipe::with(['ingredients.item', 'ingredients.unitOfMeasure', 'productType', 'unitOfMeasure', 'product'])
            ->find($recipeId);

        $this->quantity = 1;
        $this->calculateYield();
        $this->resolveIngredients();
        $this->checkStock();
        $this->loadPendingOrders();
    }

    public function updatedQuantity()
    {
        if ($this->quantity < 0) {
            $this->quantity = 1;
        }

        $this->calculateYield();
        $this->resolveIngredients();
        $this->checkStock();
    }

    protected function calculateYield()
    {
        if (!$this->selectedRecipe) {
            $this->yieldOutput = 0;
            $this->approvedQuantity = 0;
            $this->rejectedQuantity = 0;

            return;
        }

        $yieldQty = (float) $this->selectedRecipe->yield_quantity;
        $this->yieldOutput = (float) $this->quantity * $yieldQty;
        
        // Sync approved/rejected
        $this->approvedQuantity = (float) $this->yieldOutput - (float) $this->rejectedQuantity;
    }

    public function updatedApprovedQuantity()
    {
        $this->rejectedQuantity = max(0, (float) $this->yieldOutput - (float) $this->approvedQuantity);
    }

    public function updatedRejectedQuantity()
    {
        $this->approvedQuantity = max(0, (float) $this->yieldOutput - (float) $this->rejectedQuantity);
    }

    protected function resolveIngredients()
    {
        $this->ingredients = [];
        $this->ingredientStock = [];

        if (!$this->selectedRecipe) {
            return;
        }

        $wipService = app(\App\Services\WipRecipeService::class);

        // `$this->quantity` is the number of BATCHES (see the "Quantity to Produce
        // (batches)" field). WipRecipeService::resolveIngredients() expects the
        // total OUTPUT quantity in units and internally divides by the recipe
        // yield to get the batch factor. Passing batches here scaled every
        // ingredient down by the yield (e.g. 40g onions -> 1g for a yield of 40),
        // so pass the produced unit total instead.
        $outputQty = (float) $this->quantity * (float) $this->selectedRecipe->yield_quantity;
        $resolved = $wipService->resolveIngredients($this->selectedRecipe, $outputQty, false);

        $this->ingredients = $resolved->map(function ($ing) {
            $model = $ing->item;

            return [
                'item_id' => $ing->item_id,
                'item_name' => $ing->item_name,
                'quantity' => round($ing->quantity_for_production, 2),
                'uom_symbol' => $model?->unitOfMeasure?->symbol ?? 'N/A',
                'cost_per_unit' => $ing->cost_per_unit,
                'is_wip' => $ing->resolved_from_wip ?? false,
                'wip_recipe_name' => $ing->wip_recipe_name ?? null,
                'item_type' => $model ? get_class($model) : null,
            ];
        })->toArray();
    }

    protected function checkStock()
    {
        $this->hasInsufficientStock = false;
        $this->insufficientItems = [];

        $store = $this->productionStore;

        if (!$store) {
            $this->hasInsufficientStock = true;
            $this->insufficientItems[] = [
                'item_name' => 'Production Store',
                'message' => 'No production store found',
            ];

            return;
        }

        $service = app(ProductionStoreService::class);

        foreach ($this->ingredients as $ing) {
            $modelClass = $ing['item_type'] ?? \App\Models\Item::class;
            $item = $modelClass::find($ing['item_id']);
            
            if (!$item) {
                continue;
            }

            $available = $service->getAvailableStock($store, $item);
            $needed = $ing['quantity'];

            $this->ingredientStock[$ing['item_id']] = [
                'available' => $available,
                'status' => $available >= $needed ? 'ok' : 'insufficient',
            ];

            if ($available < $needed) {
                $this->hasInsufficientStock = true;
                $this->insufficientItems[] = [
                    'item_name' => $ing['item_name'],
                    'needed' => $needed,
                    'available' => $available,
                    'shortage' => $needed - $available,
                ];
            }
        }
    }

    public function produce()
    {
        if (! $this->selectedRecipe) {
            $this->toast()->error('Please select a recipe first.')->send();

            return;
        }

        if ($this->hasInsufficientStock) {
            $items = implode(', ', array_column($this->insufficientItems, 'item_name'));
            $this->toast()->error("Insufficient stock for: {$items}")->send();

            return;
        }
        
        if ($this->rejectedQuantity > 0 && empty($this->rejectionReason)) {
            $this->toast()->error('Please select a reason for rejected items.')->send();
            return;
        }

        $store = $this->productionStore;
        $actor = current_actor();

        if (! $store) {
            $this->toast()->error('No production store found.')->send();

            return;
        }

        try {
            DB::transaction(function () use ($store, $actor) {
                // Ensure active shift exists
                $shift = $this->getCurrentShift();
                if (!$shift) {
                    throw new \Exception('No active shift found. Please clock in to record production.');
                }

                // 1. Ensure DailyProduce exists for this recipe and shift
                $dailyProduce = \App\Models\DailyProduce::firstOrCreate([
                    'shift_id' => $shift->id,
                    'recipe_id' => $this->selectedRecipe->id,
                ], [
                    'produce_date' => $shift->shift_date,
                    'shift_type' => $shift->shift_type,
                    'opening_quantity' => \App\Models\DailyProduce::getOpeningQuantityFromPreviousShift(
                        $this->selectedRecipe->id,
                        $shift->branch_id,
                        $shift->shift_date,
                        $shift->shift_type
                    ),
                    'status' => 'in_progress',
                    'requested_quantity' => 0,
                ]);

                // 2. Consume ingredients for the TOTAL batch produced (Approved + Rejected)
                foreach ($this->ingredients as $ing) {
                    $modelClass = $ing['item_type'] ?? \App\Models\Item::class;
                    $item = $modelClass::find($ing['item_id']);
                    
                    if (!$item) {
                        continue;
                    }

                    $neededQty = $ing['quantity'];

                    $stock = ProductionStoreStock::where('store_id', $store->id)
                        ->where('item_id', $item->id)
                        ->first();

                    if ($stock) {
                        $oldQty = (float) $stock->quantity_available;
                        $newQty = $oldQty - $neededQty;
                        $stock->quantity_available = $newQty;
                        $stock->save();

                        ProductionStoreMovement::create([
                            'store_id' => $store->id,
                            'stock_id' => $stock->id,
                            'item_id' => $item->id,
                            'quantity' => $neededQty,
                            'quantity_before' => $oldQty,
                            'quantity_after' => $newQty,
                            'type' => 'out',
                            'reference_type' => Recipe::class,
                            'reference_id' => $this->selectedRecipe->id,
                            'created_by_id' => $actor?->id,
                            'created_by_type' => get_class($actor),
                            'notes' => "Quick Production: {$this->selectedRecipe->product_name} x {$this->quantity}",
                        ]);
                    }
                }

                // 3. Capture ProductionRecord
                $batchNumber = 'BATCH-' . now()->format('YmdHis');
                $record = \App\Models\ProductionRecord::create([
                    'daily_produce_id' => $dailyProduce->id,
                    'recipe_id' => $this->selectedRecipe->id,
                    'batch_number' => $batchNumber,
                    'produced_by_id' => $actor->id,
                    'produced_by_type' => get_class($actor),
                    'quantity_produced' => $this->yieldOutput,
                    'quantity_approved' => $this->approvedQuantity,
                    'quantity_rejected' => $this->rejectedQuantity,
                    'quantity_remaining' => $this->approvedQuantity,
                    'production_time' => now(),
                    'quality_status' => $this->rejectedQuantity > 0 ? 'acceptable' : 'good',
                    'dispatch_status' => 'available',
                    'rejection_reason' => $this->rejectionReason,
                    'notes' => 'Quick Produce Batch',
                ]);

                $this->lastProductionRecordId = $record->id;

                // 4. Update DailyProduce totals
                $dailyProduce->produced_quantity += $this->approvedQuantity;
                $dailyProduce->save();

                // 5. Audit Log
                \App\Services\ProductionAuditService::logBatchProduced($actor, $record);

                // 5b. Track raw material utilization for reporting
                $this->trackIngredientUtilization($shift, (float) $this->yieldOutput);

                // 6. Add APPROVED quantity to the production store as held stock.
                //    WIP products are intermediate stock consumed by other recipes;
                //    finished goods are held in production until dispatched to sales.
                //    Persisting both means the produced quantity not yet sent out
                //    remains as the closing balance (= next shift's opening).
                if ($this->approvedQuantity > 0 && $this->selectedRecipe->product_id) {
                    $producedProduct = \App\Models\Product::find($this->selectedRecipe->product_id);

                    if ($producedProduct) {
                        app(ProductionStoreService::class)->recordProduction(
                            $store,
                            $producedProduct,
                            (float) $this->approvedQuantity,
                            $this->selectedRecipe,
                            $actor,
                            (bool) $this->selectedRecipe->is_wip
                        );
                    }
                } elseif ($this->selectedRecipe->is_wip && ! $this->selectedRecipe->product_id) {
                    throw new \Exception('WIP recipe must be linked to a product for auto-stocking.');
                }
                
                // If there's rejected quantity, log it for audit/reports
                if ($this->rejectedQuantity > 0) {
                    \App\Services\ProductionAuditService::logWaste(
                        $actor,
                        $dailyProduce, 
                        $this->rejectedQuantity,
                        $this->rejectionReasonOptions[$this->rejectionReason] ?? 'Rejected during quick produce'
                    );
                }
            });

            // Post the completed batch to the general ledger (approved output ->
            // Finished Goods / WIP, rejects -> write-off, materials consumed).
            if ($this->lastProductionRecordId) {
                $postedRecord = \App\Models\ProductionRecord::with('recipe')->find($this->lastProductionRecordId);
                if ($postedRecord) {
                    \App\Events\ProductionCompleted::dispatch($postedRecord);
                }
            }

            if ($this->selectedRecipe->is_wip) {
                $this->toast()->success("WIP produced: {$this->approvedQuantity} {$this->selectedRecipe->uomSymbol} added to stock.")->send();
                $this->resetProduction();
            } else {
                $this->toast()->success("Produced {$this->approvedQuantity} {$this->selectedRecipe->uomSymbol} of {$this->selectedRecipe->product_name}")->send();
                // Set quantity for dispatch to only the APPROVED amount; default
                // the dispatch quantity to the full batch (cashier may reduce it
                // or keep the batch in finished goods).
                $this->yieldOutput = $this->approvedQuantity;
                $this->dispatchQuantity = $this->approvedQuantity;
                $this->isRedispatch = false;
                $this->showDispatchModal = true;
            }
        } catch (\Exception $e) {
            $this->toast()->error("Production failed: " . $e->getMessage())->send();
        }
    }

    protected function resolveDailyProduceForAudit()
    {
        $shift = $this->getCurrentShift();
        if (!$shift) return null;
        
        return \App\Models\DailyProduce::where('shift_id', $shift->id)
            ->where('recipe_id', $this->selectedRecipe->id)
            ->first();
    }

    /**
     * Close the dispatch modal without sending. The approved output is already
     * held as finished-goods stock (recorded during produce), so it simply stays
     * in the production store to be dispatched later from the Finished Goods sheet
     * or the pending-batches list.
     */
    public function keepInFinishedGoods(): void
    {
        $this->toast()->success('Kept in finished goods. You can send it to sales later.')->send();
        $this->resetProduction();
    }

    /**
     * Resolve and validate how much of the current batch to dispatch. Returns
     * null (with a toast) when the requested quantity is invalid.
     */
    protected function resolveDispatchQuantity(): ?float
    {
        $max = (float) $this->yieldOutput;
        $qty = (float) ($this->dispatchQuantity > 0 ? $this->dispatchQuantity : $max);

        if ($qty <= 0) {
            $this->toast()->error('Enter a quantity to send.')->send();

            return null;
        }

        if ($qty > $max + 0.0001) {
            $this->toast()->error('Cannot send more than was produced (max ' . rtrim(rtrim(number_format($max, 2), '0'), '.') . ').')->send();

            return null;
        }

        return $qty;
    }

    /**
     * Translate a produced quantity (in the recipe's base/production UOM, e.g.
     * grams) into what the sales department will actually receive, expressed in
     * the product's SALES UOM. Production stocks and dispatches in the base unit;
     * the till sells in the sales unit, so this is the bridge the operator needs
     * to see at produce/dispatch time ("40 g produced -> 1 portion to sell").
     *
     * Falls back to the base unit when the product has no distinct sales UOM
     * configured, so the readout is always meaningful (shows "40 g" until a sales
     * unit is set, then automatically switches to "1 portion").
     *
     * @return array{qty: float, symbol: string, converted: bool}
     */
    public function salesReceivesFor(float $baseQty): array
    {
        $product = $this->selectedRecipe?->product;

        if (! $product) {
            return [
                'qty' => $baseQty,
                'symbol' => $this->selectedRecipe?->uomSymbol ?? '',
                'converted' => false,
            ];
        }

        return [
            'qty' => $product->convertBaseToSalesQuantity($baseQty),
            'symbol' => $product->effectiveSalesUomSymbol,
            'converted' => $product->hasSalesUomConversion(),
        ];
    }

    public function dispatchToSales()
    {
        if (! $this->selectedRecipe) {
            return;
        }

        if (!$this->selectedRecipe->product_id) {
            $this->toast()->error('Recipe not linked to a product. Please assign via Product Assignments.')->send();

            return;
        }

        if (!$this->selectedSalesDepartmentId) {
            $this->toast()->error('Please select a sales department.')->send();

            return;
        }

        if ($this->selectedRecipe->is_wip) {
            $this->toast()->error('WIP items cannot be dispatched to sales.')->send();

            return;
        }

        $actor = current_actor();
        $shift = $this->getCurrentShift();
        $isSuperAdmin = $actor && $actor->hasRole('super-admin');

        if (!$shift && !$isSuperAdmin) {
            $this->toast()->error('No active shift found. Please clock in first.')->send();

            return;
        }

        $qty = $this->resolveDispatchQuantity();
        if ($qty === null) {
            return;
        }

        $record = \App\Models\ProductionRecord::find($this->lastProductionRecordId);
        $store = $this->productionStore;

        DB::transaction(function () use ($record, $store, $actor, $shift, $qty) {
            $dispatch = ProductDispatch::create([
                'branch_id' => $this->getBranchId(),
                'department_id' => $this->department->id,
                'production_record_id' => $this->lastProductionRecordId,
                'daily_produce_id' => $record?->daily_produce_id,
                'production_shift_id' => $shift?->id,
                'shift_type' => $shift?->shift_type,
                'recipe_id' => $this->selectedRecipe->id,
                'product_id' => $this->selectedRecipe->product_id,
                'sales_department_id' => $this->selectedSalesDepartmentId,
                'quantity' => $qty,
                'uom' => $this->selectedRecipe->unitOfMeasure->symbol ?? 'units',
                'status' => 'pending_verification',
                'dispatched_by_id' => $actor?->id,
                'dispatched_by_type' => get_class($actor),
                'dispatch_date' => now()->toDateString(),
                'notes' => 'Quick Produce Dispatch',
            ]);

            // Update production record remaining quantity if linked
            if ($record) {
                $record->quantity_sent_out += $qty;
                $record->updateQuantityRemaining();
            }

            // Draw down the held finished-goods balance — the goods have left
            // production for sales (the shop confirms receipt separately).
            if ($store) {
                $product = \App\Models\Product::find($this->selectedRecipe->product_id);
                if ($product) {
                    app(ProductionStoreService::class)->recordDispatch(
                        $store,
                        $product,
                        (float) $qty,
                        'transfer',
                        $dispatch,
                        $actor,
                        'Dispatched to sales (pending verification)'
                    );
                }
            }
        });

        $kept = (float) $this->yieldOutput - $qty;
        $this->toast()->success(
            $kept > 0.0001
                ? 'Sent ' . rtrim(rtrim(number_format($qty, 2), '0'), '.') . ' to sales; ' . rtrim(rtrim(number_format($kept, 2), '0'), '.') . ' kept in finished goods.'
                : 'Dispatched to Sales! Pending confirmation.'
        )->send();
        $this->resetProduction();
    }

    public function dispatchToOrder()
    {
        $orderId = $this->selectedOrderId;

        if (! $this->selectedRecipe || ! $orderId) {
            $this->toast()->error('Please select a customer order.')->send();

            return;
        }

        if (!$this->selectedRecipe->product_id) {
            $this->toast()->error('Recipe not linked to a product. Please assign via Product Assignments.')->send();

            return;
        }

        if ($this->selectedRecipe->is_wip) {
            $this->toast()->error('WIP items cannot be dispatched.')->send();

            return;
        }

        $actor = current_actor();
        $shift = $this->getCurrentShift();
        $isSuperAdmin = $actor && $actor->hasRole('super-admin');

        if (!$shift && !$isSuperAdmin) {
            $this->toast()->error('No active shift found. Please clock in first.')->send();

            return;
        }

        $order = CustomerOrder::find($orderId);

        if (! $order) {
            $this->toast()->error('Customer order not found.')->send();

            return;
        }

        $qty = $this->resolveDispatchQuantity();
        if ($qty === null) {
            return;
        }

        $record = \App\Models\ProductionRecord::find($this->lastProductionRecordId);

        $dispatch = ProductDispatch::create([
            'branch_id' => $this->getBranchId(),
            'department_id' => $this->department->id,
            'production_record_id' => $this->lastProductionRecordId,
            'daily_produce_id' => $record?->daily_produce_id,
            'production_shift_id' => $shift?->id,
            'shift_type' => $shift?->shift_type,
            'recipe_id' => $this->selectedRecipe->id,
            'product_id' => $this->selectedRecipe->product_id,
            'customer_order_id' => $order->id,
            'quantity' => $qty,
            'uom' => $this->selectedRecipe->unitOfMeasure->symbol ?? 'units',
            'status' => 'pending_verification',
            'dispatched_by_id' => $actor?->id,
            'dispatched_by_type' => get_class($actor),
            'dispatch_date' => now()->toDateString(),
            'notes' => "Customer Order {$order->order_number} — {$order->customer_name}",
        ]);

        // Update production record remaining quantity if linked
        if ($record) {
            $record->quantity_for_order += $qty;
            $record->updateQuantityRemaining();
        }

        // Draw down the held finished-goods balance — the goods have left
        // production to fulfil a customer order.
        $store = $this->productionStore;
        if ($store) {
            $orderProduct = \App\Models\Product::find($this->selectedRecipe->product_id);
            if ($orderProduct) {
                app(ProductionStoreService::class)->recordDispatch(
                    $store,
                    $orderProduct,
                    (float) $qty,
                    'out',
                    $dispatch,
                    $actor,
                    "Dispatched to customer order {$order->order_number}"
                );
            }
        }

        // Advance customer order status (IN_PRODUCTION or COMPLETED)
        app(CustomerOrderService::class)->recordDispatch($order, $dispatch);

        $kept = (float) $this->yieldOutput - $qty;
        $this->toast()->success(
            "Dispatched {$order->order_number} ({$order->customer_name})"
            . ($kept > 0.0001 ? '; ' . rtrim(rtrim(number_format($kept, 2), '0'), '.') . ' kept in finished goods.' : '')
        )->send();
        $this->resetProduction();
    }

    protected function resetProduction()
    {
        $this->showDispatchModal = false;
        $this->dispatchType = '';
        $this->selectedRecipe = null;
        $this->selectedSalesDepartmentId = null;
        $this->selectedOrderId = null;
        $this->lastProductionRecordId = null;
        $this->quantity = 1;
        $this->yieldOutput = 0;
        $this->dispatchQuantity = 0;
        $this->approvedQuantity = 0;
        $this->rejectedQuantity = 0;
        $this->rejectionReason = null;
        $this->ingredients = [];
        $this->ingredientStock = [];
        $this->hasInsufficientStock = false;
        $this->insufficientItems = [];
        $this->pendingOrders = [];
        $this->isRedispatch = false;
    }

    protected function loadPendingOrders()
    {
        $this->pendingOrders = [];
    }

    private function trackIngredientUtilization(\App\Models\Shift $shift, float $unitsProduced): void
    {
        foreach ($this->ingredients as $ing) {
            $consumed = (float) $ing['quantity'];

            if ($consumed <= 0) {
                continue;
            }

            // Only track true Raw Materials (Items), not intermediate WIP Products
            $itemType = $ing['item_type'] ?? \App\Models\Item::class;
            if ($itemType !== \App\Models\Item::class) {
                continue;
            }

            $existing = \App\Models\RawMaterialUtilization::where([
                'shift_id'  => $shift->id,
                'recipe_id' => $this->selectedRecipe->id,
                'item_id'   => $ing['item_id'],
            ])->first();

            if ($existing) {
                $existing->quantity_required = (float) $existing->quantity_required + $consumed;
                $existing->quantity_used     = (float) $existing->quantity_used + $consumed;
                $existing->units_produced    = (float) $existing->units_produced + $unitsProduced;
                $existing->save();
            } else {
                \App\Models\RawMaterialUtilization::create([
                    'shift_id'        => $shift->id,
                    'recipe_id'       => $this->selectedRecipe->id,
                    'item_id'         => $ing['item_id'],
                    'quantity_required' => $consumed,
                    'quantity_used'   => $consumed,
                    'units_produced'  => $unitsProduced,
                    'variance'        => 0,
                    'variance_type'   => 'within_tolerance',
                    'cost_impact'     => 0,
                    'notes'           => 'Quick Produce',
                ]);
            }
        }
    }

    public function getSalesDepartments()
    {
        $productId = $this->selectedRecipe?->product_id;

        $query = Department::with('category')
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->where(function ($q) {
                $q->where('branch_id', $this->getBranchId())
                  ->orWhereNull('branch_id');
            })
            ->where('is_active', true);

        if ($productId) {
            $product = \App\Models\Product::with('departments:id')->find($productId);
            if ($product) {
                $allowedIds = $product->departments->pluck('id')->toArray();
                if ($product->sales_department_id) {
                    $allowedIds[] = (int) $product->sales_department_id;
                }
                $allowedIds = array_unique($allowedIds);

                if (! empty($allowedIds)) {
                    $query->whereIn('id', $allowedIds);
                } else {
                    // Strictly return empty if product has no assignments
                    return collect();
                }
            }
        } else {
            // If recipe is not linked to a product, it shouldn't be dispatched to sales
            return collect();
        }

        return $query->orderBy('name')->get();
    }
}
