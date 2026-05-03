<?php

namespace App\Livewire\BranchDashboard\Production\Concerns;

use App\Models\Department;
use App\Models\Item;
use App\Models\ProductDispatch;
use App\Models\ProductionStore;
use App\Models\ProductionStoreMovement;
use App\Models\ProductionStoreStock;
use App\Models\Recipe;
use App\Models\Shift;
use App\Services\ProductionStoreService;
use Illuminate\Support\Facades\DB;

trait QuickProduceTrait
{
    public ?Department $department = null;

    public ?Recipe $selectedRecipe = null;

    public $quantity = 1;

    public $yieldOutput = 0;

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

    public string $dispatchType = 'sales';

    public ?int $selectedOrderId = null;

    public ?int $selectedSalesDepartmentId = null;

    public ?ProductionStore $productionStore = null;

    public array $pendingOrders = [];

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
        $resolved = $wipService->resolveIngredients($this->selectedRecipe, $this->quantity, false);

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
                // Scenario A: Consume ingredients for the TOTAL batch produced (Approved + Rejected)
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

                // Only add APPROVED quantity to WIP stock if it's a WIP
                if ($this->selectedRecipe->is_wip) {
                    if (!$this->selectedRecipe->product_id) {
                        throw new \Exception('WIP recipe must be linked to a product for auto-stocking.');
                    }

                    if ($this->approvedQuantity > 0) {
                        $existingStock = ProductionStoreStock::where('store_id', $store->id)
                            ->where('item_id', $this->selectedRecipe->product_id)
                            ->first();

                        if ($existingStock) {
                            $existingStock->quantity_available = $existingStock->quantity_available + $this->approvedQuantity;
                            $existingStock->save();
                            $stock = $existingStock;
                        } else {
                            $stock = ProductionStoreStock::create([
                                'store_id' => $store->id,
                                'item_id' => $this->selectedRecipe->product_id,
                                'quantity_available' => $this->approvedQuantity,
                                'quantity_reserved' => 0,
                                'quantity_minimum' => 0,
                            ]);
                        }

                        ProductionStoreMovement::create([
                            'store_id' => $store->id,
                            'stock_id' => $stock->id,
                            'item_id' => $this->selectedRecipe->product_id,
                            'quantity' => $this->approvedQuantity,
                            'type' => 'in',
                            'reference_type' => Recipe::class,
                            'reference_id' => $this->selectedRecipe->id,
                            'created_by_id' => $actor?->id,
                            'created_by_type' => get_class($actor),
                            'notes' => "WIP Production (Approved): {$this->selectedRecipe->product_name}",
                        ]);
                    }
                }
                
                // If there's rejected quantity, log it for audit/reports
                if ($this->rejectedQuantity > 0) {
                    \App\Services\ProductionAuditService::logWaste(
                        $actor,
                        // We don't have a DailyProduce record here, so we might need to adjust logWaste 
                        // or just use a generic AuditService log. 
                        // For now, let's see if we can find a related DailyProduce or skip if not in shift.
                        $this->resolveDailyProduceForAudit() ?? new \App\Models\DailyProduce(), 
                        $this->rejectedQuantity,
                        $this->rejectionReasonOptions[$this->rejectionReason] ?? 'Rejected during quick produce'
                    );
                }
            });

            if ($this->selectedRecipe->is_wip) {
                $this->toast()->success("WIP produced: {$this->approvedQuantity} units added to stock.")->send();
                $this->resetProduction();
            } else {
                $this->toast()->success("Produced {$this->approvedQuantity} units of {$this->selectedRecipe->product_name}")->send();
                // Set quantity for dispatch to only the APPROVED amount
                $this->yieldOutput = $this->approvedQuantity;
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

        ProductDispatch::create([
            'branch_id' => $this->getBranchId(),
            'department_id' => $this->department->id,
            'production_shift_id' => $shift?->id,
            'shift_type' => $shift?->shift_type,
            'recipe_id' => $this->selectedRecipe->id,
            'product_id' => $this->selectedRecipe->product_id,
            'sales_department_id' => $this->selectedSalesDepartmentId,
            'quantity' => $this->yieldOutput,
            'uom' => $this->selectedRecipe->unitOfMeasure->symbol ?? 'units',
            'status' => 'pending_verification',
            'dispatched_by_id' => $actor?->id,
            'dispatched_by_type' => get_class($actor),
            'dispatch_date' => now()->toDateString(),
            'notes' => 'Quick Produce Dispatch',
        ]);

        $this->toast()->success('Dispatched to Sales! Pending confirmation.')->send();
        $this->resetProduction();
    }

    public function dispatchToOrder($orderId)
    {
        if (! $this->selectedRecipe || ! $orderId) {
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

        ProductDispatch::create([
            'branch_id' => $this->getBranchId(),
            'department_id' => $this->department->id,
            'production_shift_id' => $shift?->id,
            'shift_type' => $shift?->shift_type,
            'recipe_id' => $this->selectedRecipe->id,
            'product_id' => $this->selectedRecipe->product_id,
            'quantity' => $this->yieldOutput,
            'uom' => $this->selectedRecipe->unitOfMeasure->symbol ?? 'units',
            'status' => 'pending_verification',
            'dispatched_by_id' => $actor?->id,
            'dispatched_by_type' => get_class($actor),
            'dispatch_date' => now()->toDateString(),
            'notes' => "Quick Produce Dispatch - Order #{$orderId}",
        ]);

        $this->toast()->success("Dispatched to Order #{$orderId}: {$this->selectedRecipe->product_name}")->send();
        $this->resetProduction();
    }

    protected function resetProduction()
    {
        $this->showDispatchModal = false;
        $this->selectedRecipe = null;
        $this->selectedSalesDepartmentId = null;
        $this->quantity = 1;
        $this->ingredients = [];
        $this->ingredientStock = [];
        $this->hasInsufficientStock = false;
        $this->insufficientItems = [];
    }

    protected function loadPendingOrders()
    {
        $this->pendingOrders = [];
    }

    public function getSalesDepartments()
    {
        return Department::with('category')
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->where(function ($q) {
                $q->where('branch_id', $this->getBranchId())
                  ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
