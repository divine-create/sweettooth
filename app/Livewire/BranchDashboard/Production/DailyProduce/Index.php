<?php

namespace App\Livewire\BranchDashboard\Production\DailyProduce;

use App\Enums\ProductionRequestSourceType;
use App\Models\DailyProduce;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductionRequest;
use App\Models\SalesProductionItemMaterialRequest;
use App\Models\SalesProductionRequestItem;
use App\Models\Shift;
use App\Services\ProductionAuditService;
use App\Services\ProductionStoreService;
use App\Services\SalesProductionDispatchService;
use App\Services\WipRecipeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;
    public $salesDepartments = []; // Available sales departments for dispatch

    public $selectedShiftId = null;
    public $availableShifts = [];
    public $dailyProduces = [];
    public $currentShift = null;
    public $showRecordModal = false;
    public $recordingProduceId = null;
    public $showHelpModal = false;
    public int $noShiftRequestsCount = 0;

    // For editing quantities
    public $editingQuantities = [];
    public $damagedQuantities = [];

    // For batch-level dispatch management
    public $batchQuantities = []; // Stores sent_out and order quantities for each batch
    public $batchSalesDepartments = []; // Stores sales_department_id for each batch (legacy)
    public $batchDispatches = []; // Stores multiple dispatch allocations per batch: [[sales_dept_id, quantity], ...]
    public $batchDispatchLocks = []; // batch_id => true when dispatch rows already exist (immutable allocations)
    public $batchAllowedSalesDepartments = []; // batch_id => allowed sales_department_id (if restricted)
    public $batchSalesDepartmentOptions = []; // batch_id => options allowed for UI selection
    public $batchProductAllowedSalesDepartmentIds = []; // batch_id => enforceable allowed IDs by product ownership
    public $salesRequestLimits = []; // daily_produce_id => requested_units (if sales request)
    private array $salesDemandContextCache = [];
    private array $productSalesDepartmentIdsCache = [];

    // For recording production batches
    public $batchesProduced = 1; // Number of batches made
    public $batchQuantityProduced = 0; // Auto-calculated from batches × yield
    public $batchQuantityApproved = 0;
    public $batchQuantityRejected = 0;
    public $batchQualityStatus = 'good';
    public $batchRejectionReason = '';
    public $batchNotes = '';
    public $recordingProduce = null;
    public $isSavingBatch = [];

    public function mount($deptSlug)
    {
        $this->dept_slug = $deptSlug;
        $this->department = Department::where('slug', $deptSlug)->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        $this->loadAvailableShifts();
        $this->loadCurrentShift();
        $this->loadSalesDepartments();
    }

    /**
     * Load available shifts for past/future shift viewing
     */
    /**
     * Load sales departments (departments that can sell products)
     */
    public function loadSalesDepartments()
    {
        $branchId = $this->getBranchId();

        // Load all Sales-category departments for this branch/global scope.
        $this->salesDepartments = Department::query()
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%sales%']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->toArray();
    }

    public function loadAvailableShifts()
    {
        $branchId = $this->getBranchId();

        // Get shifts from last 90 days for this department (increased from 30)
        $this->availableShifts = Shift::where('branch_id', $branchId)
            ->where('department_id', $this->department->id)
            ->where('shift_date', '>=', now()->subDays(90))
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    /**
     * When user selects a different shift to view/edit
     */
    public function updatedSelectedShiftId($shiftId = null)
    {
        // Clear editing state when switching shifts
        $this->editingQuantities = [];

        if ($shiftId) {
            $this->currentShift = Shift::find($shiftId);
        }

        $this->loadDailyProduces();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function loadCurrentShift()
    {
        $branchId = $this->getBranchId();

        // Get today's shift for this department
        $this->currentShift = Shift::where('branch_id', $branchId)
            ->where('department_id', $this->department->id)
            ->where('shift_date', today())
            ->orderBy('shift_type')
            ->first();

        if ($this->currentShift) {
            $this->selectedShiftId = $this->currentShift->id;
            $this->loadDailyProduces();
        } else {
            // Check if there are production requests without a shift (created by super admins)
            $hasNoShiftRequests = ProductionRequest::where('production_department_id', $this->department->id)
                ->whereDate('created_at', today())
                ->exists();

            if ($hasNoShiftRequests) {
                // Show the "no shift" production requests
                $this->selectedShiftId = 'no-shift';
                $this->loadDailyProduces();
            } else {
                // If no shift found for today, try to get the most recent shift
                $this->currentShift = Shift::where('branch_id', $branchId)
                    ->where('department_id', $this->department->id)
                    ->orderBy('shift_date', 'desc')
                    ->orderBy('shift_type', 'desc')
                    ->first();

                if ($this->currentShift) {
                    $this->selectedShiftId = $this->currentShift->id;
                    $this->loadDailyProduces();
                } else {
                    // If no shifts exist at all, try to create a shift for today
                    $this->createTodaysShift($branchId);
                }
            }
        }
    }

    /**
     * Create a shift for today if none exists
     */
    private function createTodaysShift($branchId)
    {
        // Check if shift creation is needed - only for production departments
        $existingRequests = ProductionRequest::forBranch($branchId)
            ->where('production_department_id', $this->department->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($existingRequests) {
            // Create a default shift for today if production requests exist
            $shift = Shift::create([
                'branch_id' => $branchId,
                'department_id' => $this->department->id,
                'shift_date' => today(),
                'shift_type' => 'morning', // Default shift type
                'status' => 'active',
                'shift_number' => Shift::generateShiftNumber(
                    (string) ($this->department->slug ?? $this->department->id),
                    (string) Auth::id(),
                    now(),
                    'morning'
                ),
            ]);

            $this->currentShift = $shift;
            $this->selectedShiftId = $shift->id;
            $this->loadDailyProduces();
        }
    }

    public function loadDailyProduces()
    {
        if (!$this->selectedShiftId) {
            return;
        }

        $this->salesRequestLimits = [];
        $this->batchDispatchLocks = [];
        $this->batchAllowedSalesDepartments = [];
        $this->batchSalesDepartmentOptions = [];
        $this->batchProductAllowedSalesDepartmentIds = [];
        $this->productSalesDepartmentIdsCache = [];

        // Handle "no-shift" production requests (created by super admins)
        if ($this->selectedShiftId === 'no-shift') {
            // Load production requests without a shift
            $productionRequests = ProductionRequest::where('production_department_id', $this->department->id)
                ->whereDate('created_at', today())
                ->with(['recipe', 'itemRequest.requestDetails.item'])
                ->get();
            
            $this->dailyProduces = $productionRequests->map(function ($prodRequest) {
                $recipe = $prodRequest->recipe;
                $itemRequest = $prodRequest->itemRequest;
                $salesDemandContext = $this->resolveSalesDemandContext($prodRequest);
                $isSalesRequest = $salesDemandContext['is_sales_request'];
                $isStoreRequest = (bool) $prodRequest->item_request_id;
                $requestedUnits = $salesDemandContext['requested_units'];
                $salesUomSymbol = $salesDemandContext['sales_uom_symbol'];

                $sourceLabel = $isSalesRequest ? 'Sales Demand' : ($isStoreRequest ? 'Store Request' : 'Direct Request');
                $sourceClass = $isSalesRequest
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
                    : ($isStoreRequest
                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                        : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200');
                
                $produced = 0;
                $damaged = 0;
                $netAvailable = $produced - $damaged;
                $producability = $this->calculateProducableFromRequest($prodRequest);
                $canProduce = ($producability['producable_quantity'] ?? 0) > 0;
                $canProduceFull = (bool) ($producability['can_produce_full_batch'] ?? false);
                $computedStatus = $canProduceFull ? 'Ready to Produce' : ($canProduce ? 'Partially Ready' : 'Not Started - Awaiting Ingredients');
                $statusBadge = $canProduceFull
                    ? 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200'
                    : ($canProduce
                        ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200');
                
                $requestedBatches = (int) ($prodRequest->batches_requested
                    ?? ($recipe && $recipe->yield_quantity > 0
                        ? ceil(((float) $prodRequest->planned_production_quantity) / (float) $recipe->yield_quantity)
                        : 0));
                $producedBatches = (int) ($prodRequest->batches_produced ?? 0);
                $pendingBatches = max(0, (int) ($prodRequest->batches_pending ?? ($requestedBatches - $producedBatches)));

                return [
                    'id' => $prodRequest->id,
                    'recipe_id' => $recipe?->id,
                    'recipe_name' => $recipe?->product_name ?? 'Unknown Product',
                    'sales_department_id' => $salesDemandContext['sales_department_id'],
                    'sales_department_name' => $salesDemandContext['sales_department_name'],
                    'requested_units' => $requestedUnits,
                    'sales_uom' => $salesUomSymbol,
                    'production_request_number' => $prodRequest->id ? 'PR-' . $prodRequest->id : null,
                    'item_request_number' => $itemRequest->request_number ?? null,
                    'requested_quantity' => $prodRequest->planned_production_quantity,
                    'produced_quantity' => $produced,
                    'opening_quantity' => 0,
                    'closing_quantity' => 0,
                    'callback_quantity' => $damaged,
                    'damaged_quantity' => $damaged,
                    'sent_out_quantity' => 0,
                    'order_quantity' => 0,
                    'net_available' => $netAvailable,
                    'expected_closing' => 0,
                    'variance' => 0,
                    'variance_quantity' => 0,
                    'variance_percentage' => 0,
                    'uom' => $recipe?->unitOfMeasure?->symbol ?? 'unit',
                    'status' => 'not_started',
                    'computed_status' => $computedStatus,
                    'status_badge_color' => $statusBadge,
                    'production_records_count' => 0,
                    'requested_batches' => $requestedBatches,
                    'produced_batches' => $producedBatches,
                    'pending_batches' => $pendingBatches,
                    'manual_status' => null,
                    'is_no_shift' => true,
                    'request_source_label' => $sourceLabel,
                    'request_source_class' => $sourceClass,
                    'is_sales_request' => $isSalesRequest,
                    'is_store_request' => $isStoreRequest,
                    'excess_to_stock' => $requestedUnits !== null ? max(0, (float) $prodRequest->planned_production_quantity - $requestedUnits) : 0,
                    'has_variance_issue' => false,
                    'producability' => $producability,
                ];
            })->toArray();
            
            return;
        }

        $shift = Shift::find($this->selectedShiftId);

        if (!$shift) {
            return;
        }

        // Attach any unassigned production requests for today to this shift
        ProductionRequest::whereNull('shift_id')
            ->where('production_department_id', $this->department->id)
            ->whereDate('created_at', $shift->shift_date)
            ->update(['shift_id' => $shift->id]);

        // Auto-create DailyProduce records from ProductionRequests if they don't exist
        DailyProduce::autoCreateFromProductionRequests($shift);
    

        // Load all daily produces for this shift with all related data
        $produces = DailyProduce::with([
            'recipe:id,product_id,product_name,uom_id,yield_quantity,sku',
            'recipe.unitOfMeasure:id,symbol,name',
            'shift:id,shift_date,shift_type',
            'productionRecords:id,daily_produce_id,recipe_id,batch_number,quantity_produced,quantity_approved,quantity_rejected,quantity_sent_out,quantity_for_order,quantity_remaining,dispatch_status,quality_status,production_time,produced_by_id,produced_by_type,rejection_reason,notes',
            'productionRecords.producedBy:id,name,email'
        ])
        ->where('shift_id', $this->selectedShiftId)
        ->orderBy('production_request_id')
        ->orderBy('recipe_id')
        ->get();
        

        $this->dailyProduces = $produces->map(function ($produce) use ($shift) {
            $productionRequest = $produce->production_request_id
                ? ProductionRequest::with(['itemRequest.requestDetails.item', 'itemRequest'])->find($produce->production_request_id)
                : ProductionRequest::where('shift_id', $shift->id)
                    ->where('recipe_id', $produce->recipe_id)
                    ->with(['itemRequest.requestDetails.item', 'itemRequest'])
                    ->first();
            $salesDemandContext = $this->resolveSalesDemandContext($productionRequest);
            $allowedSalesDeptId = $salesDemandContext['sales_department_id'];
            $requestedUnits = $salesDemandContext['requested_units'];
            $salesUomSymbol = $salesDemandContext['sales_uom_symbol'];
            $recipeProductId = $this->resolveProductIdForRecipe($produce->recipe);
            $productAllowedDeptIds = $this->resolveAllowedSalesDepartmentIdsForProduct($recipeProductId);
            if ($allowedSalesDeptId && ! in_array((int) $allowedSalesDeptId, $productAllowedDeptIds, true)) {
                $productAllowedDeptIds[] = (int) $allowedSalesDeptId;
            }
            $productAllowedDeptOptions = $this->filterSalesDepartmentsByIds($productAllowedDeptIds);
            if ($allowedSalesDeptId) {
                $this->salesRequestLimits[$produce->id] = $requestedUnits;
            }

            $requestedBatches = (int) ($productionRequest?->batches_requested ?? 0);
            $producedBatches = (int) ($productionRequest?->batches_produced ?? 0);
            $pendingBatches = (int) ($productionRequest?->batches_pending ?? 0);

            // Get item request status from ItemRequest model (not ProductionRequest)
            $itemRequestStatus = 'N/A';
            $itemRequestNumber = 'N/A';

            if ($productionRequest && $productionRequest->itemRequest) {
                $itemRequestNumber = $productionRequest->itemRequest->request_number;
                // Use the ItemRequest status field directly
                $itemRequestStatus = $productionRequest->itemRequest->status ?? 'pending';
            }
            $isSalesRequest = $salesDemandContext['is_sales_request'];
            $isStoreRequest = (bool) ($productionRequest?->item_request_id);
            $sourceLabel = $isSalesRequest ? 'Sales Demand' : ($isStoreRequest ? 'Store Request' : 'Production Request');
            $sourceClass = $isSalesRequest
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
                : ($isStoreRequest
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                    : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200');

            // Get dispatched items for this request
            $dispatchedItems = [];
            if ($productionRequest && $productionRequest->itemRequest) {
                foreach ($productionRequest->itemRequest->requestDetails as $detail) {
                    $dispatchedItems[] = [
                        'item_name' => $detail->item->name ?? 'N/A',
                        'requested' => (float) $detail->quantity_requested,
                        'approved' => (float) $detail->quantity_approved,
                        'dispatched' => (float) $detail->quantity_dispatched,
                        'uom' => $detail->unitOfMeasure?->symbol ?? $detail->item->unitOfMeasure?->symbol ?? '',
                    ];
                }
            }

            // Auto-calculate produced quantity from production records
            $actualProducedQty = $produce->getTotalProducedFromRecords();

            // Update the model if produced quantity changed
            if ($produce->produced_quantity != $actualProducedQty) {
                $produce->produced_quantity = $actualProducedQty;
                $produce->updateCalculations();
            }

            // Get production records (batches) with details
            $batchesData = $produce->productionRecords->map(function ($batch) use ($allowedSalesDeptId, $productAllowedDeptIds, $productAllowedDeptOptions) {
                // Load existing dispatches for this batch
                $existingDispatches = \App\Models\ProductDispatch::where('production_record_id', $batch->id)
                    ->with('salesDepartment')
                    ->get()
                    ->map(function ($dispatch) {
                        return [
                            'id' => $dispatch->id,
                            'sales_department_id' => $dispatch->sales_department_id,
                            'sales_department_name' => $dispatch->salesDepartment->name ?? 'Unknown',
                            'quantity' => (float) $dispatch->quantity,
                            'status' => $dispatch->status,
                        ];
                    })->toArray();
                $isDispatchLocked = count($existingDispatches) > 0;

                $this->batchDispatchLocks[$batch->id] = $isDispatchLocked;
                $this->batchAllowedSalesDepartments[$batch->id] = $allowedSalesDeptId;
                $this->batchProductAllowedSalesDepartmentIds[$batch->id] = $productAllowedDeptIds;

                if ($allowedSalesDeptId) {
                    $this->batchSalesDepartmentOptions[$batch->id] = array_values(array_filter(
                        $productAllowedDeptOptions,
                        fn (array $dept): bool => (int) ($dept['id'] ?? 0) === (int) $allowedSalesDeptId
                    ));
                } else {
                    $this->batchSalesDepartmentOptions[$batch->id] = $productAllowedDeptOptions;
                }

                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_produced' => (float) $batch->quantity_produced,
                    'quantity_approved' => (float) $batch->quantity_approved,
                    'quantity_rejected' => (float) $batch->quantity_rejected,
                    'quantity_sent_out' => (float) $batch->quantity_sent_out,
                    'quantity_for_order' => (float) $batch->quantity_for_order,
                    'quantity_remaining' => (float) $batch->quantity_remaining,
                    'dispatch_status' => $batch->dispatch_status,
                    'quality_status' => $batch->quality_status,
                    'production_time' => $batch->production_time->format('M d, h:i A'),
                    'produced_by' => $batch->producedBy->name ?? 'N/A',
                    'dispatches' => $existingDispatches,
                    'dispatch_locked' => $isDispatchLocked,
                    'allowed_sales_department_id' => $allowedSalesDeptId,
                    'product_allowed_sales_department_ids' => $productAllowedDeptIds,
                ];
            })->toArray();
            $totalRejectedQty = (float) $produce->productionRecords->sum('quantity_rejected');

            return [
                'id' => $produce->id,
                'recipe_id' => $produce->recipe_id,
                'recipe_name' => $produce->recipe->product_name ?? 'N/A',
                'uom' => $produce->recipe->unitOfMeasure?->symbol ?? '',
                'opening_quantity' => (float) $produce->opening_quantity,
                'requested_quantity' => (float) $produce->requested_quantity,
                'requested_units' => $requestedUnits,
                'sales_uom' => $salesUomSymbol,
                'excess_to_stock' => $requestedUnits !== null ? max(0, (float) $produce->requested_quantity - $requestedUnits) : 0,
                'produced_quantity' => (float) $actualProducedQty,
                'rejected_quantity' => $totalRejectedQty,
                'net_available' => (float) $produce->getNetAvailable(),
                'sent_out_quantity' => (float) $produce->sent_out_quantity,
                'order_quantity' => (float) $produce->order_quantity,
                'callback_quantity' => (float) $produce->callback_quantity,
                'closing_quantity' => (float) $produce->closing_quantity,
                'expected_closing' => (float) $produce->expected_closing,
                'variance' => (float) $produce->variance,
                'has_variance_issue' => $produce->hasVarianceIssue(),
                'variance_percentage' => $produce->getVariancePercentage(),
                'production_records_count' => $produce->productionRecords->count(),
                'requested_batches' => $requestedBatches,
                'produced_batches' => $producedBatches,
                'pending_batches' => $pendingBatches,

                // Computed status based on ItemRequest and production progress
                'computed_status' => $produce->getComputedProductionStatus(),
                'status_badge_color' => $produce->getStatusBadgeColor(),
                'can_start_production' => $produce->canStartProduction(),
                'manual_status' => $produce->status ?? 'in_progress',

                // Producable quantity calculation (CRITICAL BUSINESS LOGIC)
                'producability' => $produce->calculateProducableQuantity(),

                // Batch-level data
                'batches' => $batchesData,

                // Linked data from production request
                'item_request_status' => $itemRequestStatus,
                'item_request_number' => $itemRequestNumber,
                'dispatched_items' => $dispatchedItems,
                'production_request_id' => $productionRequest?->id,
                'sales_department_id' => $allowedSalesDeptId,
                'sales_department_name' => $salesDemandContext['sales_department_name'],
                'production_request_number' => $productionRequest?->id ? 'PR-' . $productionRequest->id : null,
                'request_source_label' => $sourceLabel,
                'request_source_class' => $sourceClass,
                'is_sales_request' => $isSalesRequest,
                'is_store_request' => $isStoreRequest,
            ];
        })->toArray();

        // Initialize editing quantities if not set (ONLY editable fields)
        if (empty($this->editingQuantities)) {
            foreach ($this->dailyProduces as $produce) {
                $this->editingQuantities[$produce['id']] = [
                    // produced_quantity is AUTO-CALCULATED from production records
                    'sent_out_quantity' => $produce['sent_out_quantity'],
                    'order_quantity' => $produce['order_quantity'],
                    'callback_quantity' => $produce['callback_quantity'],
                    'closing_quantity' => $produce['closing_quantity'],
                ];

                // Initialize batch quantities for batch-level management (preserve existing values)
                foreach ($produce['batches'] as $batch) {
                    if (!isset($this->batchQuantities[$batch['id']])) {
                        $this->batchQuantities[$batch['id']] = [
                            'quantity_sent_out' => $batch['quantity_sent_out'],
                            'quantity_for_order' => $batch['quantity_for_order'],
                        ];
                    }

                    // Initialize sales department selection (default to first sales dept if available)
                    if (!isset($this->batchSalesDepartments[$batch['id']])) {
                        $restrictedDeptId = (int) ($batch['allowed_sales_department_id'] ?? 0);
                        if ($restrictedDeptId > 0) {
                            $this->batchSalesDepartments[$batch['id']] = $restrictedDeptId;
                        } else {
                            $allowedDeptIds = $this->getBatchAllowedDepartmentIds((int) $batch['id']);
                            $this->batchSalesDepartments[$batch['id']] = $allowedDeptIds[0] ?? null;
                        }
                    }

                    // Initialize batch dispatches (multiple allocations per batch)
                    if (!isset($this->batchDispatches[$batch['id']])) {
                        // If there are existing dispatches, use them
                        if (!empty($batch['dispatches'])) {
                            $this->batchDispatches[$batch['id']] = $batch['dispatches'];
                        } else {
                            // Otherwise, create empty array (user can add dispatches)
                            $this->batchDispatches[$batch['id']] = [];
                        }
                    }

                    $restrictedDeptId = (int) ($batch['allowed_sales_department_id'] ?? 0);
                    if ($restrictedDeptId > 0) {
                        // Sales-request flow uses dispatch allocations; normalize any legacy for_order/sent_out values.
                        $this->normalizeLockedSalesBatchAllocations($batch['id'], $restrictedDeptId);
                    }
                }
            }
        }
    }

    /**
     * Calculate producable quantity for no-shift production requests
     */
    private function calculateProducableFromRequest(ProductionRequest $productionRequest): array
    {
        $recipe = $productionRequest->recipe;
        if (! $recipe) {
            return [
                'producable_quantity' => 0,
                'requested_quantity' => (float) $productionRequest->planned_production_quantity,
                'shortage' => (float) $productionRequest->planned_production_quantity,
                'shortage_percentage' => 100,
                'limiting_ingredient' => 'No recipe found',
                'ingredient_analysis' => [],
                'can_produce_full_batch' => false,
            ];
        }

        $requestedQty = (float) $productionRequest->planned_production_quantity;
        $recipeYield = (float) $recipe->yield_quantity;
        if ($recipeYield <= 0) {
            return [
                'producable_quantity' => 0,
                'requested_quantity' => $requestedQty,
                'shortage' => $requestedQty,
                'shortage_percentage' => 100,
                'limiting_ingredient' => 'Invalid recipe yield',
                'ingredient_analysis' => [],
                'can_produce_full_batch' => false,
            ];
        }

        $itemRequest = $productionRequest->itemRequest;
        if (! $itemRequest) {
            return [
                'producable_quantity' => 0,
                'requested_quantity' => $requestedQty,
                'shortage' => $requestedQty,
                'shortage_percentage' => 100,
                'limiting_ingredient' => 'No item request',
                'ingredient_analysis' => [],
                'can_produce_full_batch' => false,
            ];
        }

        $requestedBatches = $requestedQty / $recipeYield;
        $ingredientAnalysis = [];
        $minProducableBatches = PHP_FLOAT_MAX;
        $limitingIngredient = null;
        $hasLimitingIngredient = false;

        foreach ($recipe->ingredients as $recipeIngredient) {
            $itemId = $recipeIngredient->item_id;
            $qtyNeededPerBatch = (float) $recipeIngredient->quantity;

            $requestDetail = $itemRequest->requestDetails->firstWhere('item_id', $itemId);
            $requested = $requestDetail ? (float) $requestDetail->quantity_requested : 0;
            $approved = $requestDetail ? (float) $requestDetail->quantity_approved : 0;
            $dispatched = $requestDetail ? (float) $requestDetail->quantity_dispatched : 0;

            $producableBatchesFromThisIngredient = 0;
            $neededForRequestedBatches = $qtyNeededPerBatch * $requestedBatches;

            if ($qtyNeededPerBatch > 0) {
                if ($dispatched >= $neededForRequestedBatches) {
                    $producableBatchesFromThisIngredient = $requestedBatches;
                } else {
                    $producableBatchesFromThisIngredient = $dispatched / $qtyNeededPerBatch;
                }
            }

            $isLimiting = $producableBatchesFromThisIngredient < $requestedBatches;

            $ingredientAnalysis[] = [
                'item_name' => $recipeIngredient->item->name ?? 'Unknown',
                'quantity_per_batch' => $qtyNeededPerBatch,
                'total_needed_for_request' => $neededForRequestedBatches,
                'quantity_requested' => $requested,
                'quantity_approved' => $approved,
                'quantity_dispatched' => $dispatched,
                'quantity_used' => min($dispatched, $neededForRequestedBatches),
                'quantity_remaining' => max(0, $dispatched - $neededForRequestedBatches),
                'producable_batches' => $producableBatchesFromThisIngredient,
                'is_limiting' => $isLimiting,
                'shortage' => max(0, $neededForRequestedBatches - $dispatched),
                'uom' => $recipeIngredient->item->unitOfMeasure?->symbol ?? '',
            ];

            if ($producableBatchesFromThisIngredient < $minProducableBatches) {
                $minProducableBatches = $producableBatchesFromThisIngredient;
                $limitingIngredient = $recipeIngredient->item->name ?? 'Unknown';
                $hasLimitingIngredient = true;
            }
        }

        if ($minProducableBatches === PHP_FLOAT_MAX) {
            $minProducableBatches = 0;
        }

        $minProducableBatches = min($minProducableBatches, $requestedBatches);
        $producableUnits = $minProducableBatches * $recipeYield;

        if (! $hasLimitingIngredient || $minProducableBatches >= $requestedBatches) {
            foreach ($ingredientAnalysis as &$analysis) {
                $analysis['is_limiting'] = false;
            }
        }

        $shortage = max(0, $requestedQty - $producableUnits);
        $shortagePercentage = $requestedQty > 0 ? ($shortage / $requestedQty) * 100 : 0;

        return [
            'producable_quantity' => $producableUnits,
            'requested_quantity' => $requestedQty,
            'shortage' => $shortage,
            'shortage_percentage' => round($shortagePercentage, 2),
            'limiting_ingredient' => $limitingIngredient,
            'ingredient_analysis' => $ingredientAnalysis,
            'can_produce_full_batch' => $producableUnits >= $requestedQty,
        ];
    }


    public function updateQuantity($produceId, $field)
    {
        try {
            $produce = DailyProduce::with(['recipe', 'shift', 'productionRecords:id,daily_produce_id'])->find($produceId);

            if (!$produce) {
                $this->toast()->error('Daily produce record not found.')->send();
                return;
            }

            $newValue = (float) ($this->editingQuantities[$produceId][$field] ?? 0);
            $oldValue = (float) $produce->$field;

            // CRITICAL VALIDATION: Prevent sending out more than available
            if ($field === 'sent_out_quantity') {
                $hasLockedBatchDispatches = ProductDispatch::query()
                    ->whereHas('productionRecord', function ($query) use ($produce) {
                        $query->where('daily_produce_id', $produce->id);
                    })
                    ->exists();

                if ($hasLockedBatchDispatches && abs($newValue - $oldValue) > 0.0001) {
                    $this->toast()->error('Sent Out is locked after batch dispatch. Use callbacks to reverse dispatched stock.')->send();
                    $this->editingQuantities[$produceId][$field] = $oldValue;
                    return;
                }

                $netAvailable = $produce->getNetAvailable();
                $requestedQty = (float) $produce->requested_quantity;
                $salesRequestedUnits = $this->getSalesRequestedUnitsLimit($produce);
                if ($salesRequestedUnits !== null) {
                    $requestedQty = $salesRequestedUnits;
                }

                // Check 1: Cannot send out more than requested
                if ($newValue > $requestedQty) {
                    $this->toast()->error("Cannot send out {$newValue} - only {$requestedQty} was requested!")->send();
                    // Reset to old value
                    $this->editingQuantities[$produceId][$field] = $oldValue;
                    return;
                }

                // Check 2: Cannot send out more than net available (produced - callback)
                if ($newValue > $netAvailable) {
                    $this->toast()->error("Cannot send out {$newValue} - only {$netAvailable} available (after callbacks)!")->send();
                    // Reset to old value
                    $this->editingQuantities[$produceId][$field] = $oldValue;
                    return;
                }

                // If sent_out_quantity is being updated, create dispatch records
                if ($newValue > $oldValue) {
                    $quantityToDispatch = $newValue - $oldValue;
                    try {
                        $this->createProductDispatch($produce, $quantityToDispatch);
                    } catch (\Exception $e) {
                        // Log error but don't fail the update
                        logger()->error('Failed to create product dispatch: ' . $e->getMessage());
                    }
                }
            }

            // Update the field from editing quantities array
            $produce->$field = $newValue;

            // Auto-calculate closing quantity for flow fields, but respect manual physical count edits.
            if ($field !== 'closing_quantity') {
                $produce->closing_quantity = $produce->opening_quantity +
                                            $produce->getNetAvailable() -
                                            $produce->sent_out_quantity -
                                            $produce->order_quantity;
            }

            // Recalculate expected closing and variance
            $produce->updateCalculations();

            $this->toast()->success('Updated successfully.')->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error updating: ' . $e->getMessage())->send();
        }
    }

    public function saveAllQuantities()
    {
        try {
            // Get actor before transaction for audit logging
            $actor = current_actor();
            
            DB::transaction(function () use ($actor) {
                foreach ($this->editingQuantities as $produceId => $quantities) {
                    $produce = DailyProduce::find($produceId);

                    if (!$produce) {
                        continue;
                    }

                    $newSentOut = (float) ($quantities['sent_out_quantity'] ?? 0);
                    $oldSentOutCurrent = (float) $produce->sent_out_quantity;
                    $hasLockedBatchDispatches = ProductDispatch::query()
                        ->whereHas('productionRecord', function ($query) use ($produce) {
                            $query->where('daily_produce_id', $produce->id);
                        })
                        ->exists();

                    if ($hasLockedBatchDispatches && abs($newSentOut - $oldSentOutCurrent) > 0.0001) {
                        throw new \RuntimeException('Sent Out is locked after batch dispatch. Use callbacks to reverse dispatched stock.');
                    }

                    // Store old values for audit trail
                    $oldSentOut = $produce->sent_out_quantity;
                    $oldOrder = $produce->order_quantity;
                    $oldCallback = $produce->callback_quantity;
                    $oldClosing = (float) $produce->closing_quantity;

                    // Update ONLY editable quantities (produced is auto-calculated)
                    $produce->sent_out_quantity = (float) ($quantities['sent_out_quantity'] ?? 0);
                    $produce->order_quantity = (float) ($quantities['order_quantity'] ?? 0);
                    $produce->callback_quantity = (float) ($quantities['callback_quantity'] ?? 0);

                    // Auto-update produced quantity from production records
                    $produce->produced_quantity = $produce->getTotalProducedFromRecords();

                    $newClosing = (float) ($quantities['closing_quantity'] ?? $oldClosing);
                    $closingWasEdited = abs($newClosing - $oldClosing) > 0.0001;

                    // If closing was edited, respect physical count. Otherwise keep auto-calculated closing.
                    if ($closingWasEdited) {
                        $produce->closing_quantity = $newClosing;
                    } else {
                        $produce->closing_quantity = $produce->opening_quantity +
                                                    $produce->getNetAvailable() -
                                                    $produce->sent_out_quantity -
                                                    $produce->order_quantity;
                    }

                    // Recalculate expected closing and variance
                    $produce->updateCalculations();

                    // Log significant quantity changes using actor pattern
                    if ($oldSentOut != $produce->sent_out_quantity || 
                        $oldOrder != $produce->order_quantity || 
                        $oldCallback != $produce->callback_quantity) {
                        ProductionAuditService::logVariance(
                            $actor,
                            $produce,
                            $oldSentOut + $oldOrder + $oldCallback,
                            $produce->sent_out_quantity + $produce->order_quantity + $produce->callback_quantity,
                            "Updated quantities - Sent out: {$oldSentOut}→{$produce->sent_out_quantity}, Order: {$oldOrder}→{$produce->order_quantity}, Callback: {$oldCallback}→{$produce->callback_quantity}"
                        );
                    }
                }
            });

            $this->toast()->success('All quantities saved successfully.')->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error saving: ' . $e->getMessage())->send();
        }
    }

    public function openRecordModal($produceId)
    {
        if ($this->selectedShiftId === 'no-shift') {
            $productionRequest = ProductionRequest::with('recipe')->find($produceId);
            if (! $productionRequest) {
                $this->toast()->error('Production request not found.')->send();
                return;
            }

            $produce = $this->ensureDailyProduceFromRequest($productionRequest);
            if (! $produce) {
                return;
            }

            $produce->loadMissing('shift');
            $this->currentShift = $produce->shift;
            $this->selectedShiftId = $produce->shift_id;
            $produceId = $produce->id;
        }

        $this->recordingProduceId = $produceId;

        // Load the produce record with recipe details
        $this->recordingProduce = DailyProduce::with('recipe')->find($produceId);

        if (! $this->recordingProduce) {
            $this->toast()->error('Daily produce record not found.')->send();
            return;
        }

        // Reset form fields with smart defaults based on requested quantity and recipe yield
        $requestedQty = (float) ($this->recordingProduce->requested_quantity ?? 0);
        $yieldPerBatch = (float) ($this->recordingProduce->recipe->yield_quantity ?? 0);
        if ($requestedQty > 0 && $yieldPerBatch > 0) {
            $this->batchesProduced = max(1, (int) ceil($requestedQty / $yieldPerBatch));
            $this->batchQuantityProduced = $requestedQty;
            $this->batchQuantityApproved = $requestedQty;
            $this->batchQuantityRejected = 0;
        } else {
            $this->batchesProduced = 1;
            $this->batchQuantityProduced = 0;
            $this->batchQuantityApproved = 0;
            $this->batchQuantityRejected = 0;
        }
        $this->batchQualityStatus = 'good';
        $this->batchRejectionReason = '';
        $this->batchNotes = '';

        // Calculate initial quantity based on batch count (keeps yield in sync)
        $this->calculateQuantityFromBatches();

        $this->showRecordModal = true;
    }

    public function closeRecordModal()
    {
        $this->recordingProduceId = null;
        $this->recordingProduce = null;
        $this->showRecordModal = false;
        $this->loadDailyProduces(); // Reload to show new production records
    }

    public function updatedBatchesProduced()
    {
        // Recalculate quantity when number of batches changes
        $this->calculateQuantityFromBatches();
    }

    public function calculateQuantityFromBatches()
    {
        if (!$this->recordingProduce || !$this->recordingProduce->recipe) {
            return;
        }

        $yieldPerBatch = (float) $this->recordingProduce->recipe->yield_quantity;
        $this->batchQuantityProduced = (float) $this->batchesProduced * $yieldPerBatch;

        // Auto-set approved quantity to produced quantity by default
        if ($this->batchQuantityProduced > 0 && $this->batchQuantityApproved == 0 && $this->batchQuantityRejected == 0) {
            $this->batchQuantityApproved = $this->batchQuantityProduced;
        }
    }

    public function updatedBatchQuantityProduced()
    {
        if ($this->recordingProduce && $this->recordingProduce->recipe) {
            $yieldPerBatch = (float) $this->recordingProduce->recipe->yield_quantity;
            if ($yieldPerBatch > 0) {
                $this->batchesProduced = max(1, (int) round((float) $this->batchQuantityProduced / $yieldPerBatch));
            }
        }

        // Auto-set approved quantity to produced quantity by default
        if ($this->batchQuantityProduced > 0 && $this->batchQuantityApproved == 0 && $this->batchQuantityRejected == 0) {
            $this->batchQuantityApproved = $this->batchQuantityProduced;
        }
    }

    public function updatedBatchQuantityApproved()
    {
        // Auto-calculate rejected quantity
        if ($this->batchQuantityProduced > 0) {
            $this->batchQuantityRejected = max(0, $this->batchQuantityProduced - $this->batchQuantityApproved);
        }
    }

    public function updatedBatchQuantityRejected()
    {
        // Auto-calculate approved quantity
        if ($this->batchQuantityProduced > 0) {
            $this->batchQuantityApproved = max(0, $this->batchQuantityProduced - $this->batchQuantityRejected);
        }
    }

    public function recordBatch()
    {
        // Custom validation for rejection reason
        if ($this->batchQuantityRejected > 0 && empty($this->batchRejectionReason)) {
            $this->toast()->error('Please provide a reason for rejected items')->send();
            return;
        }

        $this->validate([
            'batchesProduced' => 'required|integer|min:1',
            'batchQuantityProduced' => 'required|numeric|min:0.01',
            'batchQuantityApproved' => 'required|numeric|min:0',
            'batchQuantityRejected' => 'required|numeric|min:0',
            'batchQualityStatus' => 'required|in:excellent,good,acceptable,rejected',
        ], [
            'batchesProduced.required' => 'Number of batches is required',
            'batchesProduced.min' => 'Number of batches must be greater than 0',
            'batchesProduced.integer' => 'Number of batches must be a whole number',
            'batchQuantityProduced.required' => 'Quantity produced is required',
            'batchQuantityProduced.min' => 'Quantity produced must be greater than 0',
        ]);

        try {
            // Validate that approved + rejected = produced
            $total = (float) $this->batchQuantityApproved + (float) $this->batchQuantityRejected;
            $produced = (float) $this->batchQuantityProduced;

            if (abs($total - $produced) > 0.01) {
                $this->toast()->error("Approved ({$this->batchQuantityApproved}) + Rejected ({$this->batchQuantityRejected}) must equal Produced ({$this->batchQuantityProduced})")->send();
                return;
            }

            $produce = DailyProduce::find($this->recordingProduceId);
            if (! $produce && $this->selectedShiftId === 'no-shift') {
                $produce = $this->resolveDailyProduceForAction($this->recordingProduceId);
                if ($produce) {
                    $this->recordingProduceId = $produce->id;
                }
            }

            if (!$produce) {
                $this->toast()->error('Daily produce record not found.')->send();
                return;
            }

            // CRITICAL: Prevent recording batches if status is completed (unless reopened)
            if ($produce->status === 'completed') {
                $this->toast()->error('This production is marked as completed. Please reopen it first to record more batches.')->send();
                return;
            }

            // VALIDATION: Check if total production would exceed requested quantity
            $currentProduced = $produce->getTotalProducedFromRecords();
            $newTotal = $currentProduced + $this->batchQuantityApproved;
            $requested = (float) $produce->requested_quantity;

            if ($newTotal > $requested) {
                $this->toast()->error("Cannot record batch: Total produced would be {$newTotal}, but only {$requested} was requested. You cannot produce more than requested!")->send();
                return;
            }

            // Generate batch number (e.g., "Batch 1", "Batch 2")
            $batchCount = $produce->productionRecords()->count() + 1;
            $batchNumber = "Batch {$batchCount}";

            // Check production store for ingredients and consume
            // Scenario A: Consume ingredients for the TOTAL batch produced (Approved + Rejected)
            $this->consumeFromProductionStore($produce, $this->batchQuantityProduced);

            // Get current actor for polymorphic relationship
            $actor = current_actor();

            // Create production record
            $productionRecord = new \App\Models\ProductionRecord();
            $productionRecord->daily_produce_id = $produce->id;
            $productionRecord->recipe_id = $produce->recipe_id;
            $productionRecord->batch_number = $batchNumber;
            // Use polymorphic pattern for actor tracking
            $productionRecord->produced_by_id = $actor->id;
            $productionRecord->produced_by_type = get_class($actor);
            $productionRecord->quantity_produced = $this->batchQuantityProduced;
            $productionRecord->quantity_approved = $this->batchQuantityApproved;
            $productionRecord->quantity_rejected = $this->batchQuantityRejected;
            $productionRecord->quantity_sent_out = 0; // Initially nothing sent out
            $productionRecord->quantity_for_order = 0; // Initially nothing for orders
            $productionRecord->quantity_remaining = $this->batchQuantityApproved; // All approved quantity is available
            $productionRecord->production_time = now();
            $productionRecord->quality_status = $this->batchQualityStatus;
            $productionRecord->dispatch_status = 'available';
            $productionRecord->rejection_reason = $this->batchQuantityRejected > 0 ? $this->batchRejectionReason : null;
            $productionRecord->notes = $this->batchNotes;
            $productionRecord->save();

            // Log batch production to audit trail
            ProductionAuditService::logBatchProduced(
                $actor,
                $productionRecord,
                $this->batchNotes
            );

            // If there's rejected quantity, log it as waste for Scenario A tracking
            if ($this->batchQuantityRejected > 0) {
                ProductionAuditService::logWaste(
                    $actor,
                    $produce,
                    $this->batchQuantityRejected,
                    $this->batchRejectionReason ?? 'Rejected during production'
                );
            }

            // Track raw material utilization for this batch using TOTAL produced
            $this->trackRawMaterialUtilization($produce, $this->batchQuantityProduced);

            // Refresh the produce model to ensure productionRecords relationship is fresh
            $produce->refresh();
            $produce->load('productionRecords');

            // Auto-update produced quantity from all production records
            $totalProduced = $produce->getTotalProducedFromRecords();
            $produce->produced_quantity = $totalProduced;

            // Auto-update closing quantity to match expected closing (prevents -100% variance)
            // Closing = Opening + Produced - Sent Out - Orders - Callbacks
            $produce->closing_quantity = $produce->opening_quantity +
                                        $produce->getNetAvailable() -
                                        $produce->sent_out_quantity -
                                        $produce->order_quantity;

            $produce->updateCalculations();
            $produce->syncBatchFulfillmentFields();

            $this->toast()->success("Production batch recorded successfully! Total produced: {$totalProduced}")->send();
            $this->closeRecordModal();

        } catch (\Exception $e) {
            $this->toast()->error('Error recording batch: ' . $e->getMessage())->send();
        }
    }

    public function markComplete($produceId)
    {
        try {
            $produce = $this->resolveDailyProduceForAction($produceId);

            if (!$produce) {
                $this->toast()->error('Daily produce record not found.')->send();
                return;
            }

            $oldStatus = $produce->status;
            $actor = current_actor();
            
            $produce->status = 'completed';
            $produce->save();

            // Log status change to audit trail using actor pattern
            ProductionAuditService::logVariance(
                $actor,
                $produce,
                $produce->expected_closing,
                $produce->closing_quantity,
                "Production marked as completed. Status changed from {$oldStatus} to completed."
            );

            $this->toast()->success('Marked as completed.')->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error marking as complete: ' . $e->getMessage())->send();
        }
    }

    public function markInProgress($produceId)
    {
        try {
            $produce = $this->resolveDailyProduceForAction($produceId);

            if (!$produce) {
                $this->toast()->error('Daily produce record not found.')->send();
                return;
            }

            $oldStatus = $produce->status;
            $actor = current_actor();
            
            $produce->status = 'in_progress';
            $produce->save();

            // Log status change to audit trail using actor pattern
            ProductionAuditService::logVariance(
                $actor,
                $produce,
                $produce->expected_closing,
                $produce->closing_quantity,
                "Production reopened. Status changed from {$oldStatus} to in_progress."
            );

            $this->toast()->success('Marked as in progress.')->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function toggleHelpModal()
    {
        $this->showHelpModal = !$this->showHelpModal;
    }

    /**
     * Add a new dispatch allocation to a batch
     */
    public function addBatchDispatch($batchId)
    {
        $batchId = (int) $batchId;
        if ($this->isBatchDispatchLocked($batchId, true)) {
            $this->toast()->error('Dispatch is locked for this batch. Use callbacks to reverse or adjust dispatched stock.')->send();

            return;
        }

        if (!isset($this->batchDispatches[$batchId])) {
            $this->batchDispatches[$batchId] = [];
        }

        $restrictedDeptId = $this->batchAllowedSalesDepartments[$batchId] ?? null;
        $allowedDeptIds = $this->getBatchAllowedDepartmentIds((int) $batchId);

        if ($restrictedDeptId && ! in_array((int) $restrictedDeptId, $allowedDeptIds, true)) {
            $this->toast()->error('This product is not assigned to the locked sales department. Update product sales department assignment first.')->send();

            return;
        }

        if (! $restrictedDeptId && empty($allowedDeptIds)) {
            $this->toast()->error('No sales department is assigned to this product. Assign a primary sales department on the product first.')->send();

            return;
        }

        // Add new empty dispatch entry
        $this->batchDispatches[$batchId][] = [
            'sales_department_id' => $restrictedDeptId ?? ($allowedDeptIds[0] ?? null),
            'quantity' => 0,
            'status' => 'pending',
        ];

        $this->updateBatchTotals($batchId);
    }

    /**
     * Remove a dispatch allocation from a batch
     */
    public function removeBatchDispatch($batchId, $dispatchIndex)
    {
        $batchId = (int) $batchId;
        if ($this->isBatchDispatchLocked($batchId, true)) {
            $this->toast()->error('Dispatch is locked for this batch. Use callbacks to reverse or adjust dispatched stock.')->send();

            return;
        }

        if (isset($this->batchDispatches[$batchId][$dispatchIndex])) {
            unset($this->batchDispatches[$batchId][$dispatchIndex]);
            // Reindex array
            $this->batchDispatches[$batchId] = array_values($this->batchDispatches[$batchId]);
        }

        $this->updateBatchTotals($batchId);
    }

    /**
     * Update batch dispatch details and recalculate totals
     */
    public function updateBatchDispatch($batchId, $dispatchIndex, $field, $value)
    {
        $batchId = (int) $batchId;
        if ($this->isBatchDispatchLocked($batchId, true)) {
            $this->toast()->error('Dispatch is locked for this batch. Use callbacks to reverse or adjust dispatched stock.')->send();
            $this->loadDailyProduces();

            return;
        }

        if (isset($this->batchDispatches[$batchId][$dispatchIndex])) {
            $this->batchDispatches[$batchId][$dispatchIndex][$field] = $value;
            $this->updateBatchTotals($batchId);
        }
    }

    /**
     * Listen for changes to batchDispatches and validate/update totals
     */
    public function updatedBatchDispatches($value, $key)
    {
        // Extract batchId from the key (format: "batchId.index.field")
        $parts = explode('.', $key);
        $batchId = isset($parts[0]) ? (int) $parts[0] : 0;
        if ($batchId <= 0) {
            return;
        }

        if ($this->isBatchDispatchLocked($batchId, true)) {
            $this->toast()->error('Dispatch is locked for this batch. Use callbacks to reverse or adjust dispatched stock.')->send();
            $this->loadDailyProduces();

            return;
        }

        // Validate dispatch allocations don't exceed approved quantity
        $this->validateBatchDispatches($batchId);

        $this->updateBatchTotals($batchId);
    }

    /**
     * Validate that dispatch allocations don't exceed approved quantity
     */
    private function validateBatchDispatches($batchId)
    {
        if ($this->isBatchDispatchLocked((int) $batchId)) {
            return;
        }

        $batch = \App\Models\ProductionRecord::find($batchId);
        if (!$batch) return;

        $approvedQuantity = (float) $batch->quantity_approved;
        $forOrder = 0;
        if (isset($this->batchQuantities[$batchId])) {
            $forOrder = (float) ($this->batchQuantities[$batchId]['quantity_for_order'] ?? 0);
        } else {
            $forOrder = (float) $batch->quantity_for_order;
        }

        $availableForDispatch = max(0, $approvedQuantity - $forOrder);
        $totalDispatched = 0;

        $restrictedDeptId = $this->batchAllowedSalesDepartments[$batchId] ?? null;
        $allowedDeptIds = $this->getBatchAllowedDepartmentIds((int) $batchId);

        // Note: production may dispatch beyond sales-requested quantity.
        // We only cap by approved batch quantity (after for-order allocations).

        if (isset($this->batchDispatches[$batchId])) {
            foreach ($this->batchDispatches[$batchId] as $index => $dispatch) {
                $selectedDeptId = (int) ($dispatch['sales_department_id'] ?? 0);

                if ($restrictedDeptId && $selectedDeptId !== (int) $restrictedDeptId) {
                    $this->batchDispatches[$batchId][$index]['sales_department_id'] = $restrictedDeptId;
                    $this->toast()->error('This production can only be dispatched to the requesting sales department.')->send();
                    $selectedDeptId = (int) $restrictedDeptId;
                }

                if (! $restrictedDeptId) {
                    if (empty($allowedDeptIds)) {
                        $this->batchDispatches[$batchId][$index]['sales_department_id'] = null;
                        $this->batchDispatches[$batchId][$index]['quantity'] = 0;
                        $this->toast()->error('No sales department is assigned to this product. Assign a primary sales department first.')->send();
                        continue;
                    }

                    if (! in_array($selectedDeptId, $allowedDeptIds, true)) {
                        $this->batchDispatches[$batchId][$index]['sales_department_id'] = $allowedDeptIds[0];
                        $selectedDeptId = (int) $allowedDeptIds[0];
                        $this->toast()->error('Selected sales department cannot receive this product.')->send();
                    }
                } elseif (! in_array((int) $restrictedDeptId, $allowedDeptIds, true)) {
                    $this->batchDispatches[$batchId][$index]['quantity'] = 0;
                    $this->toast()->error('Locked sales department is not assigned to this product. Update product assignment first.')->send();
                    continue;
                }

                $quantity = (float) ($dispatch['quantity'] ?? 0);

                // Validate individual dispatch doesn't exceed remaining
                $remainingForBatch = $availableForDispatch - ($totalDispatched - $quantity);
                if ($quantity > $remainingForBatch) {
                    // Reset to maximum allowed
                    $this->batchDispatches[$batchId][$index]['quantity'] = $remainingForBatch;
                    $this->toast()->error("Dispatch quantity cannot exceed available batch quantity ({$remainingForBatch})!")->send();
                }

                $totalDispatched += (float) $this->batchDispatches[$batchId][$index]['quantity'];
            }
        }

        // Check total doesn't exceed approved
        if ($totalDispatched > $availableForDispatch) {
            $this->toast()->error("Total dispatch allocations ({$totalDispatched}) cannot exceed available batch quantity ({$availableForDispatch})!")->send();

            // Reset all dispatches to zero to prevent invalid state
            foreach ($this->batchDispatches[$batchId] as $index => $dispatch) {
                $this->batchDispatches[$batchId][$index]['quantity'] = 0;
            }
        }
    }

    /**
     * Update batch totals based on current dispatches
     */
    private function updateBatchTotals($batchId)
    {
        $batch = \App\Models\ProductionRecord::find($batchId);
        if (!$batch) return;

        if ($this->isBatchDispatchLocked((int) $batchId, true)) {
            $totalSentOut = (float) ProductDispatch::query()
                ->where('production_record_id', $batch->id)
                ->sum('quantity');

            $this->batchQuantities[$batchId]['quantity_sent_out'] = $totalSentOut;
            $this->updateBatchQuantity($batchId, 'quantity_sent_out');

            return;
        }

        // Calculate total sent out from all dispatches
        $totalSentOut = 0;
        if (isset($this->batchDispatches[$batchId])) {
            foreach ($this->batchDispatches[$batchId] as $dispatch) {
                $totalSentOut += (float) ($dispatch['quantity'] ?? 0);
            }
        }

        // Update batch quantities (for backward compatibility)
        $this->batchQuantities[$batchId]['quantity_sent_out'] = $totalSentOut;

        // Update the batch record and daily produce aggregates
        $this->updateBatchQuantity($batchId, 'quantity_sent_out');
    }

    /**
     * Update batch-level dispatch quantities
     */
    public function updateBatchQuantity($batchId, $field)
    {
        try {
            $batch = \App\Models\ProductionRecord::find($batchId);

            if (!$batch) {
                $this->toast()->error('Batch not found.')->send();
                return;
            }

            $newValue = (float) ($this->batchQuantities[$batchId][$field] ?? 0);

            // Skip sales department validation for quantity_sent_out since dispatch allocations are now handled separately
            // through the batchDispatches array which supports multiple allocations per batch
            if ($field === 'quantity_sent_out') {
                if ($this->isBatchDispatchLocked((int) $batchId, true)) {
                    $newValue = (float) ProductDispatch::query()
                        ->where('production_record_id', $batch->id)
                        ->sum('quantity');
                    $this->batchQuantities[$batchId]['quantity_sent_out'] = $newValue;
                }

                // The quantity_sent_out is now calculated based on all dispatch allocations in batchDispatches
                // Validation happens in the dispatch allocation UI, not here
            }

            // Validate: sent_out + for_order cannot exceed approved quantity
            $sentOut = $field === 'quantity_sent_out' ? $newValue : (float) $batch->quantity_sent_out;
            $forOrder = $field === 'quantity_for_order' ? $newValue : (float) $batch->quantity_for_order;

            $total = $sentOut + $forOrder;
            $approved = (float) $batch->quantity_approved;

            if ($total > $approved) {
                $this->toast()->error("Cannot allocate {$total} from batch - only {$approved} approved!")->send();
                // Reset to old value
                $this->batchQuantities[$batchId][$field] = (float) $batch->$field;
                return;
            }

            if ($field === 'quantity_for_order') {
                $this->validateBatchDispatches($batchId);
            }

            // Update the field
            $batch->$field = $newValue;
            $batch->updateQuantityRemaining();

            // Update aggregate quantities in daily produce
            $dailyProduce = \App\Models\DailyProduce::find($batch->daily_produce_id);
            if ($dailyProduce) {
                // Calculate totals from all batch quantities (including the one just updated)
                $totalSentOut = 0;
                $totalForOrder = 0;

                foreach ($dailyProduce->productionRecords as $batchRecord) {
                    if (isset($this->batchQuantities[$batchRecord->id])) {
                        $totalSentOut += (float) ($this->batchQuantities[$batchRecord->id]['quantity_sent_out'] ?? 0);
                        $totalForOrder += (float) ($this->batchQuantities[$batchRecord->id]['quantity_for_order'] ?? 0);
                    } else {
                        $totalSentOut += (float) $batchRecord->quantity_sent_out;
                        $totalForOrder += (float) $batchRecord->quantity_for_order;
                    }
                }

                $dailyProduce->sent_out_quantity = $totalSentOut;
                $dailyProduce->order_quantity = $totalForOrder;

                // Auto-calculate closing quantity
                $dailyProduce->closing_quantity = $dailyProduce->opening_quantity +
                                                  $dailyProduce->getNetAvailable() -
                                                  $dailyProduce->sent_out_quantity -
                                                  $dailyProduce->order_quantity;

                $dailyProduce->updateCalculations();
            }

            // Create dispatch record if sending out
            // Dispatch records are created in saveBatchQuantities to prevent duplicates.

            $this->toast()->success("Batch updated: {$batch->quantity_remaining} remaining")->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error updating batch: ' . $e->getMessage())->send();
        }
    }

    /**
     * Save all batch quantities for a product
     */
    public function saveBatchQuantities($produceId)
    {
        if (!empty($this->isSavingBatch[$produceId])) {
            return;
        }

        $this->isSavingBatch[$produceId] = true;

        try {
            $produce = DailyProduce::with('productionRecords')->find($produceId);

            if (!$produce) {
                $this->toast()->error('Daily produce record not found.')->send();
                return;
            }

            $produce->loadMissing('recipe.unitOfMeasure');

            $salesRequestedUnits = $this->getSalesRequestedUnitsLimit($produce);

            DB::transaction(function () use ($produce, $salesRequestedUnits) {
                $productionRequest = $produce->production_request_id
                    ? ProductionRequest::find($produce->production_request_id)
                    : ProductionRequest::where('shift_id', $produce->shift_id)
                        ->where('recipe_id', $produce->recipe_id)
                        ->first();

                $salesDemandContext = $this->resolveSalesDemandContext($productionRequest);
                $linkedSalesItem = ! empty($salesDemandContext['sales_request_item_id'])
                    ? SalesProductionRequestItem::find((int) $salesDemandContext['sales_request_item_id'])
                    : null;
                /** @var SalesProductionDispatchService $dispatchService */
                $dispatchService = app(SalesProductionDispatchService::class);
                $hasLinkedDispatches = false;

                // Note: production may dispatch more than sales-requested quantity.

                foreach ($produce->productionRecords as $batch) {
                    $isDispatchLocked = $this->isBatchDispatchLocked((int) $batch->id, true);
                    $lockedDispatchTotal = 0.0;
                    if ($isDispatchLocked) {
                        $existingDispatches = ProductDispatch::query()
                            ->where('production_record_id', $batch->id)
                            ->get(['sales_department_id', 'quantity']);
                        $lockedDispatchRows = $existingDispatches->map(static function ($dispatch): array {
                            return [
                                'sales_department_id' => (int) $dispatch->sales_department_id,
                                'quantity' => (float) $dispatch->quantity,
                            ];
                        })->all();
                        $lockedDispatchTotal = (float) $existingDispatches->sum('quantity');

                        $incomingDispatchRows = $this->batchDispatches[$batch->id] ?? $lockedDispatchRows;
                        if ($this->normalizeDispatchAllocations($incomingDispatchRows) !== $this->normalizeDispatchAllocations($lockedDispatchRows)) {
                            throw new \RuntimeException("Batch {$batch->batch_number} dispatch is locked. Use callbacks to reverse or adjust dispatched stock.");
                        }

                        $this->batchDispatches[$batch->id] = $lockedDispatchRows;
                        $this->batchQuantities[$batch->id]['quantity_sent_out'] = $lockedDispatchTotal;
                    }

                    $restrictedDeptId = (int) ($this->batchAllowedSalesDepartments[$batch->id] ?? 0);
                    $productAllowedDeptIds = $this->getBatchAllowedDepartmentIds((int) $batch->id);
                    if ($restrictedDeptId > 0 && ! $isDispatchLocked) {
                        // Ensure sales-request batches use dispatch allocations, not for_order.
                        $this->normalizeLockedSalesBatchAllocations($batch->id, $restrictedDeptId);
                    }

                    // Update batch quantities (legacy support)
                    if (! isset($this->batchQuantities[$batch->id])) {
                        $this->batchQuantities[$batch->id] = [
                            'quantity_sent_out' => (float) $batch->quantity_sent_out,
                            'quantity_for_order' => (float) $batch->quantity_for_order,
                        ];
                    }

                    $batch->quantity_sent_out = $isDispatchLocked
                        ? $lockedDispatchTotal
                        : (float) ($this->batchQuantities[$batch->id]['quantity_sent_out'] ?? 0);
                    $batch->quantity_for_order = (float) ($this->batchQuantities[$batch->id]['quantity_for_order'] ?? 0);
                    $batch->updateQuantityRemaining();

                    // Create/update product dispatches from batchDispatches
                    if ($isDispatchLocked) {
                        continue;
                    }

                    if (isset($this->batchDispatches[$batch->id])) {
                        $approvedQuantity = (float) $batch->quantity_approved;
                        $forOrder = (float) ($this->batchQuantities[$batch->id]['quantity_for_order'] ?? $batch->quantity_for_order);
                        $availableForDispatch = max(0, $approvedQuantity - $forOrder);

                        $restrictedDeptId = $this->batchAllowedSalesDepartments[$batch->id] ?? null;
                        $productAllowedDeptIds = $this->getBatchAllowedDepartmentIds((int) $batch->id);

                        $totalDispatched = 0;
                        foreach ($this->batchDispatches[$batch->id] as $dispatchData) {
                            $targetSalesDeptId = (int) ($dispatchData['sales_department_id'] ?? 0);
                            if ($restrictedDeptId && $targetSalesDeptId !== (int) $restrictedDeptId) {
                                throw new \RuntimeException('This production can only be dispatched to the requesting sales department.');
                            }
                            if (empty($productAllowedDeptIds)) {
                                throw new \RuntimeException('No sales department is assigned to this product. Assign a primary sales department first.');
                            }
                            if (! in_array($targetSalesDeptId, $productAllowedDeptIds, true)) {
                                throw new \RuntimeException('Cannot dispatch: selected sales department is not assigned to this product.');
                            }
                            $totalDispatched += (float) ($dispatchData['quantity'] ?? 0);
                        }

                        if ($totalDispatched > $availableForDispatch) {
                            throw new \RuntimeException("Total dispatch allocations ({$totalDispatched}) cannot exceed available batch quantity ({$availableForDispatch}).");
                        }

                        // Delete existing dispatches for this batch
                        \App\Models\ProductDispatch::where('production_record_id', $batch->id)->delete();

                        // Create new dispatches
                        foreach ($this->batchDispatches[$batch->id] as $dispatchData) {
                            if ((float) ($dispatchData['quantity'] ?? 0) > 0) {
                                $targetSalesDeptId = (int) ($dispatchData['sales_department_id'] ?? 0);
                                $recipeProductId = $this->resolveProductIdForRecipe($produce->recipe);

                                \App\Models\ProductDispatch::create([
                                    'production_request_id' => $productionRequest?->id,
                                    'sales_production_request_item_id' => $linkedSalesItem?->id,
                                    'branch_id' => $this->getBranchId(),
                                    'daily_produce_id' => $produce->id,
                                    'production_record_id' => $batch->id,
                                    'production_shift_id' => $produce->shift_id,
                                    'sales_department_id' => $targetSalesDeptId,
                                    'product_id' => $recipeProductId,
                                    'dispatched_by_id' => auth()->id(),
                                    'dispatched_by_type' => 'user',
                                    'quantity' => (float) $dispatchData['quantity'],
                                    'uom' => $produce->recipe->unitOfMeasure?->symbol ?? 'units',
                                    'dispatch_time' => now(),
                                    'shift_type' => $produce->shift_type,
                                    'dispatch_date' => $produce->produce_date,
                                    'status' => 'pending_verification',
                                    'notes' => "Dispatched from batch {$batch->batch_number}",
                                ]);

                                if ($linkedSalesItem) {
                                    $hasLinkedDispatches = true;
                                }
                            }
                        }
                    }
                }

                if ($linkedSalesItem && $hasLinkedDispatches) {
                    $dispatchService->markItemAsDispatched($linkedSalesItem);
                }

                // Update aggregate quantities in daily produce (now based on actual dispatches)
                $totalSentOut = \App\Models\ProductDispatch::whereHas('productionRecord', function ($q) use ($produce) {
                    $q->where('daily_produce_id', $produce->id);
                })->sum('quantity');

                $totalForOrder = $produce->productionRecords()->sum('quantity_for_order');

                $produce->sent_out_quantity = $totalSentOut;
                $produce->order_quantity = $totalForOrder;

                // Auto-calculate closing quantity
                $produce->closing_quantity = $produce->opening_quantity +
                                              $produce->getNetAvailable() -
                                              $produce->sent_out_quantity -
                                              $produce->order_quantity;

                $produce->updateCalculations();
            });

            $this->toast()->success('All batch quantities saved successfully.')->send();
            $this->loadDailyProduces();

        } catch (\Exception $e) {
            $this->toast()->error('Error saving batch quantities: ' . $e->getMessage())->send();
        } finally {
            $this->isSavingBatch[$produceId] = false;
        }
    }

    /**
     * For locked sales-request batches, convert legacy values into dispatch allocations.
     * This prevents quantities from being stranded in "for order" with no ProductDispatch rows.
     */
    private function normalizeLockedSalesBatchAllocations(int $batchId, int $restrictedDeptId): void
    {
        if (! isset($this->batchQuantities[$batchId])) {
            $this->batchQuantities[$batchId] = [
                'quantity_sent_out' => 0,
                'quantity_for_order' => 0,
            ];
        }

        if (! isset($this->batchDispatches[$batchId])) {
            $this->batchDispatches[$batchId] = [];
        }

        $legacySentOut = (float) ($this->batchQuantities[$batchId]['quantity_sent_out'] ?? 0);
        $legacyForOrder = (float) ($this->batchQuantities[$batchId]['quantity_for_order'] ?? 0);

        // If old rows stored sent_out but no dispatch allocations, seed one allocation.
        if ($legacySentOut > 0 && count($this->batchDispatches[$batchId]) === 0) {
            $this->batchDispatches[$batchId][] = [
                'sales_department_id' => $restrictedDeptId,
                'quantity' => $legacySentOut,
                'status' => 'pending',
            ];
        }

        // Move legacy for_order allocation into dispatch allocation for the locked sales department.
        if ($legacyForOrder > 0) {
            $merged = false;
            foreach ($this->batchDispatches[$batchId] as $index => $dispatch) {
                if ((int) ($dispatch['sales_department_id'] ?? 0) === $restrictedDeptId) {
                    $currentQty = (float) ($dispatch['quantity'] ?? 0);
                    $this->batchDispatches[$batchId][$index]['quantity'] = $currentQty + $legacyForOrder;
                    $merged = true;
                    break;
                }
            }

            if (! $merged) {
                $this->batchDispatches[$batchId][] = [
                    'sales_department_id' => $restrictedDeptId,
                    'quantity' => $legacyForOrder,
                    'status' => 'pending',
                ];
            }

            $this->batchQuantities[$batchId]['quantity_for_order'] = 0;
        }

        // Enforce locked destination and sync sent_out from allocations.
        $totalSentOut = 0;
        foreach ($this->batchDispatches[$batchId] as $index => $dispatch) {
            $this->batchDispatches[$batchId][$index]['sales_department_id'] = $restrictedDeptId;
            $totalSentOut += (float) ($dispatch['quantity'] ?? 0);
        }

        $this->batchQuantities[$batchId]['quantity_sent_out'] = $totalSentOut;
        $this->batchQuantities[$batchId]['quantity_for_order'] = 0;
    }

    private function isBatchDispatchLocked(int $batchId, bool $fresh = false): bool
    {
        if (! $fresh && array_key_exists($batchId, $this->batchDispatchLocks)) {
            return (bool) $this->batchDispatchLocks[$batchId];
        }

        $isLocked = ProductDispatch::query()
            ->where('production_record_id', $batchId)
            ->exists();

        $this->batchDispatchLocks[$batchId] = $isLocked;

        return $isLocked;
    }

    /**
     * Normalize allocations to a deterministic [sales_department_id => quantity] map for immutable checks.
     *
     * @param  array<int, array<string, mixed>>  $dispatchRows
     * @return array<int, float>
     */
    private function normalizeDispatchAllocations(array $dispatchRows): array
    {
        $normalized = [];

        foreach ($dispatchRows as $dispatchRow) {
            $salesDepartmentId = (int) ($dispatchRow['sales_department_id'] ?? 0);
            $quantity = (float) ($dispatchRow['quantity'] ?? 0);

            if ($salesDepartmentId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$salesDepartmentId] = ($normalized[$salesDepartmentId] ?? 0.0) + $quantity;
        }

        ksort($normalized);

        foreach ($normalized as $salesDepartmentId => $quantity) {
            $normalized[$salesDepartmentId] = round((float) $quantity, 4);
        }

        return $normalized;
    }

    /**
     * Create a product dispatch record when products are sent out to sales
     *
     * @param DailyProduce $produce Daily produce record
     * @param float $quantity Quantity to dispatch
     * @param int $salesDepartmentId Sales department receiving the dispatch
     * @param int|null $productionRecordId Batch ID (optional)
     */
    private function createProductDispatch($produce, $quantity, $salesDepartmentId = null, $productionRecordId = null)
    {
        if ($quantity <= 0) {
            return;
        }

        $productionRequest = $produce->production_request_id
            ? ProductionRequest::find($produce->production_request_id)
            : ProductionRequest::where('shift_id', $produce->shift_id)
                ->where('recipe_id', $produce->recipe_id)
                ->first();
        $salesDemandContext = $this->resolveSalesDemandContext($productionRequest);
        $linkedSalesItem = ! empty($salesDemandContext['sales_request_item_id'])
            ? SalesProductionRequestItem::find((int) $salesDemandContext['sales_request_item_id'])
            : null;

        if (!$salesDepartmentId) {
            $salesDepartmentId = $salesDemandContext['sales_department_id'] ?? ($this->salesDepartments[0]['id'] ?? null);
        }

        if (! $salesDepartmentId) {
            return;
        }

        $productId = $this->resolveProductIdForRecipe($produce->recipe);

        $product = $productId ? Product::find($productId) : null;

        if (!$product) {
            // Log warning but don't fail - product might not be created yet
            logger()->warning("Product not found for recipe SKU: {$produce->recipe->sku}");
            return;
        }

        if (! $this->isSalesDepartmentAllowedForProduct((string) $product->id, (int) $salesDepartmentId)) {
            throw new \RuntimeException('Cannot dispatch: target sales department is not assigned to this product.');
        }

        // Get sales department name for notes
        $salesDept = Department::find($salesDepartmentId);
        $salesDeptName = $salesDept?->name ?? 'Unknown Department';

        ProductDispatch::create([
            'production_request_id' => $productionRequest?->id,
            'sales_production_request_item_id' => $linkedSalesItem?->id,
            'branch_id' => $this->getBranchId(),
            'daily_produce_id' => $produce->id,
            'production_record_id' => $productionRecordId,
            'production_shift_id' => $produce->shift_id,
            'sales_department_id' => $salesDepartmentId,
            'product_id' => $product->id,
            'dispatched_by_id' => Auth::guard('web')->id(),
            'dispatched_by_type' => 'user',
            'quantity' => $quantity,
            'uom' => $product->unitOfMeasure?->symbol ?? $produce->recipe->unitOfMeasure?->symbol ?? 'units',
            'dispatch_time' => now(),
            'shift_type' => $produce->shift_type,
            'dispatch_date' => $produce->produce_date,
            'status' => 'pending_verification',
            'notes' => "Dispatched from production to {$salesDeptName} - {$produce->recipe->product_name}",
        ]);

        if ($linkedSalesItem) {
            /** @var SalesProductionDispatchService $dispatchService */
            $dispatchService = app(SalesProductionDispatchService::class);
            $dispatchService->markItemAsDispatched($linkedSalesItem);
        }
    }

    private function trackRawMaterialUtilization($produce, $unitsProduced)
    {
        // Load recipe with ingredients
        $recipe = \App\Models\Recipe::with('ingredients.item')->find($produce->recipe_id);

        if (!$recipe || !$recipe->ingredients) {
            return;
        }

        // Get the production request to find dispatched quantities
        $productionRequest = $produce->production_request_id
            ? \App\Models\ProductionRequest::with('itemRequest.requestDetails')->find($produce->production_request_id)
            : \App\Models\ProductionRequest::where('shift_id', $produce->shift_id)
                ->where('recipe_id', $produce->recipe_id)
                ->with('itemRequest.requestDetails')
                ->first();

        if (!$productionRequest || !$productionRequest->itemRequest) {
            return;
        }

        foreach ($recipe->ingredients as $ingredient) {
            // Find the matching item request detail
            $requestDetail = $productionRequest->itemRequest->requestDetails
                ->firstWhere('item_id', $ingredient->item_id);

            if (!$requestDetail) {
                continue;
            }

            // Calculate expected usage based on recipe
            $quantityRequired = (float) $ingredient->quantity * $unitsProduced;

            // Actual usage is the dispatched quantity (what was actually used)
            $quantityUsed = (float) $requestDetail->quantity_dispatched;

            // Calculate variance
            $variance = $quantityUsed - $quantityRequired;

            // Determine variance type (5% tolerance)
            $tolerance = $quantityRequired * 0.05;
            $varianceType = 'within_tolerance';

            if ($variance > $tolerance) {
                $varianceType = 'over_used';
            } elseif ($variance < -$tolerance) {
                $varianceType = 'under_used';
            }

            // Calculate cost impact (variance * item cost)
            $itemCost = $ingredient->item->cost_per_unit ?? 0;
            $costImpact = $variance * $itemCost;

            // Create or update raw material utilization record
            \App\Models\RawMaterialUtilization::updateOrCreate([
                'shift_id' => $produce->shift_id,
                'recipe_id' => $produce->recipe_id,
                'item_id' => $ingredient->item_id,
            ], [
                'quantity_required' => $quantityRequired,
                'quantity_used' => $quantityUsed,
                'units_produced' => $unitsProduced,
                'variance' => $variance,
                'variance_type' => $varianceType,
                'cost_impact' => $costImpact,
                'notes' => "Auto-tracked from production batch",
            ]);
        }
    }

    /**
     * Consume ingredients from production store before creating production record.
     */
    private function consumeFromProductionStore(DailyProduce $produce, float $unitsProduced): void
    {
        $recipe = \App\Models\Recipe::with('ingredients.item')->find($produce->recipe_id);

        if (!$recipe) {
            return;
        }

        $result = app(ProductionStoreService::class)->consumeForProduction($recipe, $unitsProduced);

        if (!$result->fullySatisfied && !empty($result->shortages)) {
            $shortageDetails = collect($result->shortages)->map(function ($s) {
                return "{$s['item']->name}: need {$s['needed']}, have {$s['available']}";
            })->implode(', ');

            throw new \Exception("Insufficient stock in Production Store. Shortages: {$shortageDetails}. Please request more items from inventory.");
        }

        if ($result->error) {
            throw new \Exception($result->error);
        }
    }

    /**
     * Get sales-requested unit limit for a daily produce (if applicable).
     */
    private function getSalesRequestedUnitsLimit(DailyProduce $produce): ?float
    {
        $productionRequest = $produce->production_request_id
            ? ProductionRequest::find($produce->production_request_id)
            : ProductionRequest::where('shift_id', $produce->shift_id)
                ->where('recipe_id', $produce->recipe_id)
                ->first();

        $salesDemandContext = $this->resolveSalesDemandContext($productionRequest);

        return $salesDemandContext['requested_units'];
    }

    /**
     * Resolve enforceable allowed sales department IDs for a batch.
     *
     * @return array<int>
     */
    private function getBatchAllowedDepartmentIds(int $batchId): array
    {
        $ids = $this->batchProductAllowedSalesDepartmentIds[$batchId] ?? [];

        return array_values(array_unique(array_map(static fn ($id) => (int) $id, $ids)));
    }

    /**
     * Resolve sales departments that can receive a product dispatch.
     * Includes the primary sales_department_id and any departments assigned via the department_product relationship.
     *
     * @return array<int>
     */
    private function resolveAllowedSalesDepartmentIdsForProduct(?string $productId): array
    {
        if (! $productId) {
            return [];
        }

        $cacheKey = (string) $productId;
        if (array_key_exists($cacheKey, $this->productSalesDepartmentIdsCache)) {
            return $this->productSalesDepartmentIdsCache[$cacheKey];
        }

        $product = Product::query()
            ->select('id', 'sales_department_id')
            ->with(['departments:id'])
            ->find($productId);

        if (! $product) {
            return $this->productSalesDepartmentIdsCache[$cacheKey] = [];
        }

        $allowedIds = [];

        // 1. Include the primary sales department from direct column linkage
        if ($product->sales_department_id) {
            $allowedIds[] = (int) $product->sales_department_id;
        }

        // 2. Include all departments assigned via the department_product relationship
        if ($product->departments->isNotEmpty()) {
            foreach ($product->departments as $dept) {
                $allowedIds[] = (int) $dept->id;
            }
        }

        $allowedIds = array_values(array_unique($allowedIds));

        if (empty($allowedIds)) {
            return $this->productSalesDepartmentIdsCache[$cacheKey] = [];
        }

        // Validate that these departments actually exist
        $validIds = Department::query()
            ->whereIn('id', $allowedIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->toArray();

        // Self-heal primary linkage if it's invalid but present
        if ($product->sales_department_id && ! in_array((int) $product->sales_department_id, $validIds, true)) {
            try {
                $product->sales_department_id = null;
                $product->saveQuietly();
            } catch (\Throwable $e) {
                // Non-fatal: keep runtime behavior even if persistence fails.
            }
        }

        return $this->productSalesDepartmentIdsCache[$cacheKey] = $validIds;
    }

    /**
     * @param  array<int>  $departmentIds
     * @return array<int, array{id:int,name:string,slug:?string}>
     */
    private function filterSalesDepartmentsByIds(array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        $filtered = array_values(array_filter($this->salesDepartments, function (array $dept) use ($departmentIds): bool {
            return in_array((int) ($dept['id'] ?? 0), $departmentIds, true);
        }));

        $presentIds = array_values(array_map(static fn (array $dept): int => (int) ($dept['id'] ?? 0), $filtered));
        $missingIds = array_values(array_diff(
            array_values(array_unique(array_map(static fn ($id): int => (int) $id, $departmentIds))),
            $presentIds
        ));

        // ID-based fallback: include explicitly allowed department IDs even when
        // they are outside the preloaded "sales category" list for this branch context.
        if (! empty($missingIds)) {
            $fallback = Department::query()
                ->whereIn('id', $missingIds)
                ->get(['id', 'name', 'slug'])
                ->map(static fn ($dept): array => [
                    'id' => (int) $dept->id,
                    'name' => (string) $dept->name,
                    'slug' => $dept->slug,
                ])
                ->all();

            $filtered = array_values(array_merge($filtered, $fallback));
        }

        return $filtered;
    }

    private function isSalesDepartmentAllowedForProduct(string $productId, int $salesDepartmentId): bool
    {
        $allowedIds = $this->resolveAllowedSalesDepartmentIdsForProduct($productId);

        return in_array($salesDepartmentId, $allowedIds, true);
    }

    /**
     * Resolve a Product ID for a recipe, supporting legacy rows that may not have product_id set.
     */
    private function resolveProductIdForRecipe($recipe): ?string
    {
        if (! $recipe) {
            return null;
        }

        if (! empty($recipe->product_id)) {
            $existingProductId = Product::query()
                ->where('id', (string) $recipe->product_id)
                ->value('id');

            if ($existingProductId) {
                return (string) $existingProductId;
            }
        }

        $branchId = $this->getBranchId();
        $resolvedProductId = null;

        if (! empty($recipe->sku)) {
            $productId = Product::query()
                ->where('sku', trim((string) $recipe->sku))
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($scopeQuery) use ($branchId) {
                        $scopeQuery->whereNull('branch_id')
                            ->orWhere('branch_id', $branchId);
                    });
                })
                ->value('id');

            if ($productId) {
                $resolvedProductId = (string) $productId;
            }
        }

        if (! $resolvedProductId && ! empty($recipe->product_name)) {
            $normalizedName = strtolower(trim((string) $recipe->product_name));
            $productId = Product::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($scopeQuery) use ($branchId) {
                        $scopeQuery->whereNull('branch_id')
                            ->orWhere('branch_id', $branchId);
                    });
                })
                ->orderByDesc('is_active')
                ->value('id');

            if ($productId) {
                $resolvedProductId = (string) $productId;
            }
        }

        // Self-heal stale recipe linkage when we successfully resolve by SKU/name.
        if ($resolvedProductId && (string) ($recipe->product_id ?? '') !== $resolvedProductId) {
            try {
                $recipe->product_id = $resolvedProductId;
                $recipe->saveQuietly();
            } catch (\Throwable $e) {
                // Non-fatal; keep runtime resolution even if persistence fails.
            }
        }

        return $resolvedProductId;
    }

    /**
     * Resolve sales-demand context for a production request using source linkage.
     *
     * @return array{
     *   is_sales_request: bool,
     *   sales_request_item_id: int|null,
     *   sales_department_id: int|null,
     *   sales_department_name: string|null,
     *   requested_units: float|null
     *   sales_uom_symbol: string|null
     * }
     */
    private function resolveSalesDemandContext(?ProductionRequest $productionRequest): array
    {
        $default = [
            'is_sales_request' => false,
            'sales_request_item_id' => null,
            'sales_department_id' => null,
            'sales_department_name' => null,
            'requested_units' => null,
            'sales_uom_symbol' => null,
        ];

        if (! $productionRequest) {
            return $default;
        }

        if (
            $productionRequest->source_type === ProductionRequestSourceType::SALES_DEMAND->value
            && ! empty($productionRequest->source_id)
        ) {
            $cacheKey = (string) $productionRequest->source_id;
            if (array_key_exists($cacheKey, $this->salesDemandContextCache)) {
                return $this->salesDemandContextCache[$cacheKey];
            }

            $salesItem = SalesProductionRequestItem::query()
                ->with([
                    'request.salesDepartment:id,name',
                    'product.unitOfMeasure:id,symbol',
                    'product.salesUom:id,symbol',
                ])
                ->find((int) $productionRequest->source_id);

            if (! $salesItem || ! $salesItem->request) {
                $salesUomSymbol = $salesItem?->product?->salesUom?->symbol
                    ?? $salesItem?->product?->unitOfMeasure?->symbol;

                return $this->salesDemandContextCache[$cacheKey] = [
                    'is_sales_request' => true,
                    'sales_request_item_id' => null,
                    'sales_department_id' => null,
                    'sales_department_name' => null,
                    'requested_units' => (float) $salesItem?->quantity_requested ?: null,
                    'sales_uom_symbol' => $salesUomSymbol,
                ];
            }

            $salesUomSymbol = $salesItem->product?->salesUom?->symbol
                ?? $salesItem->product?->unitOfMeasure?->symbol;

            return $this->salesDemandContextCache[$cacheKey] = [
                'is_sales_request' => true,
                'sales_request_item_id' => (int) $salesItem->id,
                'sales_department_id' => $salesItem->request->sales_department_id
                    ? (int) $salesItem->request->sales_department_id
                    : null,
                'sales_department_name' => $salesItem->request->salesDepartment?->name,
                'requested_units' => (float) $salesItem->quantity_requested,
                'sales_uom_symbol' => $salesUomSymbol,
            ];
        }

        // Backward-compatible fallback: older execution rows may miss source_type/source_id
        // but are still linked to sales demand via item_request_id.
        if (! empty($productionRequest->item_request_id)) {
            $cacheKey = 'itemreq:' . (string) $productionRequest->item_request_id;
            if (array_key_exists($cacheKey, $this->salesDemandContextCache)) {
                return $this->salesDemandContextCache[$cacheKey];
            }

            $materialLink = SalesProductionItemMaterialRequest::query()
                ->with([
                    'salesItem.request.salesDepartment:id,name',
                    'salesItem.product.unitOfMeasure:id,symbol',
                    'salesItem.product.salesUom:id,symbol',
                ])
                ->where('item_request_id', (int) $productionRequest->item_request_id)
                ->latest('id')
                ->first();

            $salesItem = $materialLink?->salesItem;
            if ($salesItem && $salesItem->request) {
                $salesUomSymbol = $salesItem->product?->salesUom?->symbol
                    ?? $salesItem->product?->unitOfMeasure?->symbol;

                return $this->salesDemandContextCache[$cacheKey] = [
                    'is_sales_request' => true,
                    'sales_request_item_id' => (int) $salesItem->id,
                    'sales_department_id' => $salesItem->request->sales_department_id
                        ? (int) $salesItem->request->sales_department_id
                        : null,
                    'sales_department_name' => $salesItem->request->salesDepartment?->name,
                    'requested_units' => (float) $salesItem->quantity_requested,
                    'sales_uom_symbol' => $salesUomSymbol,
                ];
            }
        }

        // Legacy fallback: execution request created from sales demand but not linked by source/item_request.
        // We recover sales context from token in notes: [SPRI:<sales_item_id>]
        if (! empty($productionRequest->notes)) {
            if (preg_match('/\[SPRI:(\d+)\]/', (string) $productionRequest->notes, $matches) === 1) {
                $salesItemId = (int) ($matches[1] ?? 0);
                if ($salesItemId > 0) {
                    $cacheKey = 'spri:' . $salesItemId;
                    if (array_key_exists($cacheKey, $this->salesDemandContextCache)) {
                        return $this->salesDemandContextCache[$cacheKey];
                    }

                    $salesItem = SalesProductionRequestItem::query()
                        ->with([
                            'request.salesDepartment:id,name',
                            'product.unitOfMeasure:id,symbol',
                            'product.salesUom:id,symbol',
                        ])
                        ->find($salesItemId);

                    if ($salesItem && $salesItem->request) {
                        $salesUomSymbol = $salesItem->product?->salesUom?->symbol
                            ?? $salesItem->product?->unitOfMeasure?->symbol;

                        return $this->salesDemandContextCache[$cacheKey] = [
                            'is_sales_request' => true,
                            'sales_request_item_id' => (int) $salesItem->id,
                            'sales_department_id' => $salesItem->request->sales_department_id
                                ? (int) $salesItem->request->sales_department_id
                                : null,
                            'sales_department_name' => $salesItem->request->salesDepartment?->name,
                            'requested_units' => (float) $salesItem->quantity_requested,
                            'sales_uom_symbol' => $salesUomSymbol,
                        ];
                    }
                }
            }
        }

        return $default;
    }

    /**
     * Resolve daily produce from a no-shift production request, creating shift/produce if needed.
     */
    private function ensureDailyProduceFromRequest(ProductionRequest $productionRequest): ?DailyProduce
    {
        if (! $productionRequest->recipe_id) {
            $this->toast()->error('Cannot proceed: request has no recipe.')->send();
            return null;
        }

        $branchId = $this->getBranchId();
        $shift = $productionRequest->shift_id ? Shift::find($productionRequest->shift_id) : null;

        if (! $shift) {
            $shiftType = $this->currentShift?->shift_type ?? 'morning';
            $shift = Shift::firstOrCreate([
                'branch_id' => $branchId,
                'department_id' => $this->department->id,
                'shift_date' => today(),
                'shift_type' => $shiftType,
            ], [
                'employee_id' => Auth::id(),
                'shift_number' => Shift::generateShiftNumber(
                    (string) ($this->department->slug ?? $this->department->id),
                    (string) Auth::id(),
                    now(),
                    (string) $shiftType
                ),
                'status' => 'active',
            ]);
        }

        if (! $productionRequest->shift_id) {
            $productionRequest->update(['shift_id' => $shift->id]);
        }

        $openingQty = DailyProduce::getOpeningQuantityFromPreviousShift(
            $productionRequest->recipe_id,
            $shift->branch_id,
            $shift->shift_date,
            $shift->shift_type
        );

        return DailyProduce::firstOrCreate([
            'shift_id' => $shift->id,
            'recipe_id' => $productionRequest->recipe_id,
            'production_request_id' => $productionRequest->id,
        ], [
            'produce_date' => $shift->shift_date,
            'shift_type' => $shift->shift_type,
            'opening_quantity' => $openingQty,
            'requested_quantity' => $productionRequest->planned_production_quantity,
            'produced_quantity' => 0,
            'batches_produced_this_shift' => 0,
            'batches_remaining' => $productionRequest->batches_requested,
            'fulfillment_status' => 'pending',
            'sent_out_quantity' => 0,
            'order_quantity' => 0,
            'callback_quantity' => 0,
            'closing_quantity' => $openingQty,
            'expected_closing' => $openingQty,
            'variance' => 0,
        ]);
    }

    /**
     * Resolve a DailyProduce record for actions, supporting no-shift production requests.
     */
    private function resolveDailyProduceForAction($produceId): ?DailyProduce
    {
        if ($this->selectedShiftId !== 'no-shift') {
            return DailyProduce::whereHas('shift', fn ($q) => $q->where('branch_id', $this->getBranchId()))->find($produceId);
        }

        $productionRequest = ProductionRequest::with('recipe')->find($produceId);
        if (! $productionRequest) {
            $this->toast()->error('Production request not found.')->send();
            return null;
        }

        $produce = $this->ensureDailyProduceFromRequest($productionRequest);
        if (! $produce) {
            return null;
        }

        $produce->loadMissing('shift');
        $this->currentShift = $produce->shift;
        $this->selectedShiftId = $produce->shift_id;

        return $produce;
    }

    public function render()
    {
        $branchId = $this->getBranchId();
        $this->noShiftRequestsCount = ProductionRequest::where('production_department_id', $this->department->id)
            ->whereDate('created_at', today())
            ->count();

        // Get available shifts for this department - cache for performance
        $cacheKey = "available_shifts_{$branchId}_{$this->department->id}";
        $availableShifts = cache()->remember($cacheKey, 300, function() use ($branchId) { // Cache for 5 minutes
            return Shift::where('branch_id', $branchId)
                ->where('department_id', $this->department->id)
                ->orderBy('shift_date', 'desc')
                ->orderBy('shift_type')
                ->limit(30) // Show more shifts
                ->get();
        });


        // Debug info: count production requests for current shift
        $productionRequestsCount = 0;
        if ($this->selectedShiftId) {
            if ($this->selectedShiftId === 'no-shift') {
                // Count no-shift production requests
                $productionRequestsCount = ProductionRequest::forBranch($branchId)
                    ->whereNull('shift_id')
                    ->where('created_at', '>=', today())
                    ->count();
            } else {
                $productionRequestsCount = ProductionRequest::where('shift_id', $this->selectedShiftId)->count();
            }
        }

        return view('livewire.branch-dashboard.production.daily-produce.index', [
            'availableShifts' => $availableShifts,
            'productionRequestsCount' => $productionRequestsCount,
            'noShiftRequestsCount' => $this->noShiftRequestsCount,
        ]);
    }
}
