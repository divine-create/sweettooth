<?php

namespace App\Livewire\BranchDashboard\Production\Callbacks;

use App\Livewire\BaseComponent;
use App\Models\ProductionCallback;
use App\Models\Shift;
use App\Models\DailyProduce;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * CreateInventoryCallback Component
 * 
 * Manages creation of production callbacks for damaged/defective items.
 * Handles two callback types:
 *   1. Raw materials - Items from stock that are damaged, expired, etc.
 *   2. Finished products - Products produced in current shift with quality issues
 * 
 * Features:
 *   - Separate modal forms for raw materials and finished products
 *   - Automatic quantity validation against produced/dispatched quantities
 *   - Shift-based filtering and selection
 *   - Polymorphic actor tracking (Employee or User records callback)
 *   - Lists available items/products from current shift
 *   - Reason codes specific to callback type
 * 
 * Workflow:
 *   1. User selects production shift
 *   2. System displays available raw materials and finished products
 *   3. User clicks item/product to create callback
 *   4. Modal opens with quantity and reason fields
 *   5. Callback is created and awaits inventory approval
 * 
 * @property string|null $b_id Branch ID for filtering
 * @property int|null $currentShiftId Active shift for current employee
 * @property int|null $selectedShiftId Shift selected for viewing callbacks
 * @property string $callbackType Type of callback ('raw_material' or 'finished_product')
 * @property int|null $selectedItemId Raw material item being reported
 * @property int|null $selectedProductId Finished product being reported
 * @property decimal $callbackQuantity Quantity of damaged/defective items
 * @property string $callbackReason Reason for callback
 */
#[Layout('components.layouts.app.branch-dashboard')]
class CreateInventoryCallback extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;

    public ?int $currentShiftId = null;
    public ?int $selectedShiftId = null;
    public $availableShifts = [];
    public $shiftDate;

    // Callback form
    public $showCallbackModal = false;
    public $callbackType; // raw_material or finished_product - initialized in mount to preserve state
    public $selectedRawDispatchId = null;
    public $selectedItemId = null;
    public $selectedProductId = null;
    public $callbackQuantity = 0;
    public $callbackUom = '';
    public $callbackReason = '';
    public $callbackNotes = '';

    // Reason options
    public array $rawMaterialReasonOptions = [
        'damaged' => 'Damaged',
        'expired' => 'Expired',
        'quality_issue' => 'Quality Issue',
        'wrong_batch' => 'Wrong Batch',
        'contamination' => 'Contamination',
        'other' => 'Other',
    ];

    public array $finishedProductReasonOptions = [
        'damaged' => 'Damaged',
        'quality_issue' => 'Quality Issue',
        'contamination' => 'Contamination',
        'other' => 'Other',
    ];

    // Table headers for raw materials
    public array $rawMaterialHeaders = [
        ['index' => 'item', 'label' => 'Raw Material'],
        ['index' => 'sku', 'label' => 'SKU'],
        ['index' => 'dispatched_qty', 'label' => 'Dispatched Qty'],
        ['index' => 'uom', 'label' => 'UOM'],
        ['index' => 'dispatch_time', 'label' => 'Dispatch Time'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    // Table headers for finished products
    public array $finishedProductHeaders = [
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'sku', 'label' => 'SKU'],
        ['index' => 'produced_qty', 'label' => 'Produced Qty'],
        ['index' => 'uom', 'label' => 'UOM'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    protected function getModelClass(): string
    {
        return ProductionCallback::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $shiftId = $this->selectedShiftId ?? $this->currentShiftId;

        if (!$shiftId) {
            return ProductionCallback::query()->whereRaw('1=0');
        }

        return ProductionCallback::query()
            ->where('shift_id', $shiftId);
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->shiftDate = \Carbon\Carbon::today()->format('Y-m-d');
        $this->loadAvailableShifts();
        $this->loadCurrentShift();

        // Set selected shift to current shift if available
        if ($this->currentShiftId) {
            $this->selectedShiftId = $this->currentShiftId;
        } elseif (!empty($this->availableShifts)) {
            // If no active shift, select the most recent one
            $this->selectedShiftId = $this->availableShifts[0]->id;
        }
    }

    protected function loadAvailableShifts()
    {
        $branchId = $this->getBranchId();
        $employee = auth()->user();

        // Get production shifts from last 30 days
        $this->availableShifts = Shift::where('branch_id', $branchId)
            ->where('shift_date', '>=', now()->subDays(30))
            ->whereHas('department.category', function ($q) {
                $q->where('name', 'Production');
            })
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    protected function loadCurrentShift()
    {
        $employee = auth()->user();

        // Find active production shift for this employee
        $activeShift = Shift::where('branch_id', $this->getBranchId())
            ->where('shift_date', \Carbon\Carbon::today())
            ->where('status', 'active')
            ->where('employee_id', $employee->id)
            ->whereHas('department.category', function ($q) {
                $q->where('name', 'Production');
            })
            ->first();

        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
        }
    }

    public function updatedSelectedShiftId($shiftId = null)
    {
        // Refresh data when shift selection changes
        $this->resetPage();
    }

    /**
     * Get callback-eligible raw materials.
     */
    public function getRawMaterialsProperty()
    {
        $query = $this->getEligibleRawMaterialDispatchesQuery();
        if (! $query) {
            return collect([]);
        }

        return $query->orderBy('dispatch_time', 'desc')->paginate($this->quantity);
    }

    /**
     * Get finished products from today's production
     */
    public function getFinishedProductsProperty()
    {
        $shiftId = $this->selectedShiftId ?? $this->currentShiftId;

        if (!$shiftId) {
            return collect([]);
        }

        $query = DailyProduce::with([
            'recipe.product',
            'productionRecords:id,daily_produce_id,quantity_rejected',
        ])
            ->where('shift_id', $shiftId)
            ->where('produced_quantity', '>', 0);

        // Search filter
        if ($this->search) {
            $query->whereHas('recipe.product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('updated_at', 'desc')->paginate($this->quantity);
    }

    public function openRawMaterialCallbackModal($dispatchId)
    {
        $query = $this->getEligibleRawMaterialDispatchesQuery();
        $dispatch = $query
            ? $query->whereKey($dispatchId)->first()
            : null;

        if (!$dispatch) {
            $this->toast()->error('Dispatch not available for callback. It may already be in production or outside this branch.')->send();
            return;
        }

        $this->callbackType = 'raw_material';
        $this->selectedRawDispatchId = $dispatch->id;
        $this->selectedItemId = $dispatch->item_id;
        $this->selectedProductId = null;
        $this->callbackQuantity = 0;
        $this->callbackUom = $dispatch->uom
            ?? $dispatch->item->unitOfMeasure?->symbol
            ?? $dispatch->item->uom
            ?? 'units';
        $this->callbackReason = '';
        $this->callbackNotes = '';
        $this->showCallbackModal = true;
    }

    public function openFinishedProductCallbackModal($dailyProduceId)
    {
        $dailyProduce = DailyProduce::with([
            'recipe.product',
            'productionRecords:id,daily_produce_id,quantity_rejected',
        ])->find($dailyProduceId);

        if (!$dailyProduce) {
            $this->toast()->error('Daily produce record not found.')->send();
            return;
        }

        $callbackPool = $this->getFinishedProductCallbackPool($dailyProduce);
        if ($callbackPool <= 0) {
            $this->toast()->error('No damaged/rejected quantity is available for callback on this product.')->send();
            return;
        }

        $usedQty = ProductionCallback::where('shift_id', $dailyProduce->shift_id)
            ->where('product_id', $dailyProduce->recipe->product_id)
            ->where('source_type', 'finished_product_reject')
            ->where('status', '!=', 'rejected')
            ->sum('quantity');

        $remaining = (float) $callbackPool - (float) $usedQty;

        if ($remaining <= 0) {
            $this->toast()->error('Callback quantity already fully used for this product and shift.')->send();
            return;
        }

        $this->callbackType = 'finished_product';
        $this->selectedItemId = null;
        $this->selectedProductId = $dailyProduce->recipe->product_id;
        $this->callbackQuantity = 0;
        $this->callbackUom = $dailyProduce->recipe->product->unitOfMeasure?->symbol
            ?? $dailyProduce->recipe->product->uom
            ?? 'units';
        $this->callbackReason = '';
        $this->callbackNotes = '';
        $this->showCallbackModal = true;
    }

    public function closeCallbackModal()
    {
        $this->showCallbackModal = false;
        // Don't reset callbackType - preserve the user's selection
        $this->selectedRawDispatchId = null;
        $this->selectedItemId = null;
        $this->selectedProductId = null;
        $this->callbackQuantity = 0;
        $this->callbackUom = '';
        $this->callbackReason = '';
        $this->callbackNotes = '';
    }

    public function submitCallback()
    {
        // Validate based on callback type
        $reasonOptions = $this->callbackType === 'raw_material'
            ? array_keys($this->rawMaterialReasonOptions)
            : array_keys($this->finishedProductReasonOptions);

        $rules = [
            'callbackQuantity' => 'required|numeric|min:0.01',
            'callbackReason' => 'required|in:' . implode(',', $reasonOptions),
            'callbackUom' => 'required|string',
            'callbackNotes' => $this->callbackType === 'raw_material'
                ? 'required|string|min:5|max:1000'
                : 'nullable|string|max:1000',
        ];

        $this->validate($rules, [
            'callbackQuantity.required' => 'Callback quantity is required',
            'callbackQuantity.min' => 'Callback quantity must be greater than 0',
            'callbackReason.required' => 'Please select a callback reason',
            'callbackUom.required' => 'Unit of measure is required',
            'callbackNotes.required' => 'Please add notes for raw material callbacks.',
            'callbackNotes.min' => 'Notes must be at least 5 characters.',
        ]);

        try {
            DB::beginTransaction();
            $shiftId = $this->selectedShiftId ?? $this->currentShiftId;
            if (! $shiftId) {
                throw new \Exception('Please select a production shift before submitting callback.');
            }

            // Validate quantity based on type
            if ($this->callbackType === 'raw_material') {
                $query = $this->getEligibleRawMaterialDispatchesQuery();
                $dispatch = $query
                    ? $query->lockForUpdate()->find($this->selectedRawDispatchId)
                    : null;

                if (! $dispatch) {
                    throw new \Exception('Selected raw material dispatch is no longer eligible for callback. It may already be in production.');
                }

                if ($this->callbackQuantity > (float) $dispatch->quantity) {
                    $this->toast()->error("Callback quantity cannot exceed dispatched quantity ({$dispatch->quantity}).")->send();
                    return;
                }

                // Enforce source identity from selected dispatch (prevents tampering).
                $this->selectedItemId = $dispatch->item_id;
                $this->callbackUom = $dispatch->uom
                    ?? $dispatch->item?->unitOfMeasure?->symbol
                    ?? $dispatch->item?->uom
                    ?? 'units';
            } else {
                // For finished products, check daily produce
                $dailyProduce = DailyProduce::where('shift_id', $shiftId)
                    ->whereHas('recipe', function ($q) {
                        $q->where('product_id', $this->selectedProductId);
                    })
                    ->with('productionRecords:id,daily_produce_id,quantity_rejected')
                    ->first();

                if (!$dailyProduce) {
                    throw new \Exception('Daily produce record not found');
                }

                $callbackPool = $this->getFinishedProductCallbackPool($dailyProduce);
                if ($callbackPool <= 0) {
                    $this->toast()->error('No damaged/rejected quantity is available for callback on this product.')->send();
                    return;
                }

                $usedQty = ProductionCallback::where('shift_id', $shiftId)
                    ->where('product_id', $this->selectedProductId)
                    ->where('source_type', 'finished_product_reject')
                    ->where('status', '!=', 'rejected')
                    ->sum('quantity');

                $remaining = (float) $callbackPool - (float) $usedQty;

                if ($remaining <= 0) {
                    $this->toast()->error('Callback quantity already fully used for this product and shift.')->send();
                    return;
                }

                if ($this->callbackQuantity > $remaining) {
                    $this->toast()->error("Callback quantity cannot exceed recorded damaged quantity ({$remaining}).")->send();
                    return;
                }

                if ($this->callbackQuantity > $dailyProduce->produced_quantity) {
                    $this->toast()->error("Callback quantity cannot exceed produced quantity ({$dailyProduce->produced_quantity}).")->send();
                    return;
                }
            }

            $actor = current_actor();
            if (!$actor) {
                throw new \Exception('No authenticated actor found');
            }

            // Create callback record
            $sourceType = $this->callbackType === 'raw_material'
                ? 'raw_material_from_stock'
                : 'finished_product_reject';

            ProductionCallback::create([
                'shift_id' => $shiftId,
                'source_type' => $sourceType,
                'item_id' => $this->selectedItemId,
                'product_id' => $this->selectedProductId,
                'recorded_by_id' => $actor->id,
                'recorded_by_type' => get_class($actor),
                'quantity' => $this->callbackQuantity,
                'uom' => $this->callbackUom,
                'reason' => $this->callbackReason,
                'status' => 'pending',
                'notes' => trim((string) $this->callbackNotes),
                'callback_time' => now(),
            ]);

            DB::commit();

            $callbackTypeName = $this->callbackType === 'raw_material' ? 'raw material' : 'finished product';
            $this->toast()->success("Production callback for {$callbackTypeName} created successfully! Awaiting inventory approval.")->send();
            $this->closeCallbackModal();
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error creating callback: ' . $e->getMessage())->send();
        }
    }

    /**
     * Query raw material dispatches that can still be called back.
     * Business rule: once any production starts for a linked request, callbacks are blocked.
     */
    private function getEligibleRawMaterialDispatchesQuery()
    {
        $branchId = $this->getBranchId();
        if (! $branchId) {
            return null;
        }

        $query = \App\Models\ItemDispatch::query()
            ->with(['item', 'itemRequest', 'itemRequest.requestedBy'])
            ->where('branch_id', $branchId)
            ->whereNotNull('dispatch_time')
            // Only material dispatches tied to production requests.
            ->whereHas('itemRequest.productionRequests')
            // Allow cross-shift + no-shift requests only while nothing has been produced.
            ->whereDoesntHave('itemRequest.productionRequests.dailyProduces', function ($dailyProduceQuery) {
                $dailyProduceQuery->where('produced_quantity', '>', 0);
            })
            ->whereDoesntHave('itemRequest.productionRequests.productionRecords');

        if ($this->search) {
            $query->whereHas('item', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function render()
    {
        $shiftId = $this->selectedShiftId ?? $this->currentShiftId;

        // Enhance finished products with callback allowance info
        if ($this->callbackType === 'finished_product') {
            $finished = $this->finishedProducts;
            if ($finished instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $finished->getCollection()->transform(function ($row) {
                    $callbackQty = $this->getFinishedProductCallbackPool($row);
                    $usedQty = ProductionCallback::where('shift_id', $row->shift_id)
                        ->where('product_id', $row->recipe->product_id)
                        ->where('source_type', 'finished_product_reject')
                        ->where('status', '!=', 'rejected')
                        ->sum('quantity');
                    $row->callback_remaining = max(0, $callbackQty - (float) $usedQty);
                    $row->can_callback = $row->callback_remaining > 0;
                    return $row;
                });
            }
        }

        return view('livewire.branch-dashboard.production.callbacks.create-inventory-callback', [
            'currentShift' => $this->currentShiftId ? Shift::find($this->currentShiftId) : null,
            'selectedShift' => $shiftId ? Shift::find($shiftId) : null,
            'rawMaterials' => $this->rawMaterials,
            'finishedProducts' => $this->finishedProducts,
        ]);
    }

    /**
     * Total finished-product quantity allowed for callback on a daily produce row.
     * Uses the larger of manually tracked callback quantity and batch rejected quantity.
     */
    private function getFinishedProductCallbackPool(DailyProduce $dailyProduce): float
    {
        $manualDamaged = (float) ($dailyProduce->callback_quantity ?? 0);
        $batchRejected = $dailyProduce->relationLoaded('productionRecords')
            ? (float) $dailyProduce->productionRecords->sum('quantity_rejected')
            : (float) $dailyProduce->productionRecords()->sum('quantity_rejected');

        return max($manualDamaged, $batchRejected);
    }
}
