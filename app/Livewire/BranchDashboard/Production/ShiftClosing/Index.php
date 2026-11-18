<?php

namespace App\Livewire\BranchDashboard\Production\ShiftClosing;

use App\Models\Branch;
use App\Models\DailyProduce;
use App\Models\Department;
use App\Models\Item;
use App\Models\ItemRequest;
use App\Models\Product;
use App\Models\ProductionRecord;
use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\ProductionStoreStock;
use App\Models\Shift;
use App\Services\ProductionStockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public ?string $deptSlug = null;

    public ?int $departmentId = null;
    public ?Department $department = null;
    public ?ProductionStore $store = null;
    public ?Shift $currentShift = null;
    public ?string $currentShiftId = null;

    public string $departmentName = '';
    public string $branchName = '';
    public $shiftDate;
    public $shiftType = 'morning';

    // Tabs: finished, wip, raw
    public string $activeTab = 'finished';
    public string $search = '';

    // Stock items categorized
    public array $stockItems = [
        'finished' => [],
        'wip' => [],
        'raw' => [],
    ];
    public array $closingCounts = [];
    public array $itemNotes = [];

    // Closing state
    public bool $isClosed = false;
    public ?string $closedAt = null;
    public string $notes = '';

    // Production metrics
    public array $productionSummary = [];
    public array $rawMaterialUsage = [];
    public array $materialVariances = [];
    public $totalMaterialEfficiency = 0;

    public function mount($deptSlug = null)
    {
        $this->deptSlug = $deptSlug ?? request()->route('deptSlug') ?? $this->deptSlug;
        $this->b_id = request()->query('b_id', $this->b_id) ?? session('selected_branch_id');
        $this->shiftDate = Carbon::today()->format('Y-m-d');

        $this->loadDepartmentAndStore();
        $this->loadCurrentShift();
        $this->loadClosingStockItems();
        $this->loadProductionClosingData();
    }

    protected function loadDepartmentAndStore(): void
    {
        $branchId = $this->b_id ?? current_branch_id();
        if ($branchId) {
            $branch = Branch::find($branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        $query = Department::query();
        if ($this->deptSlug) {
            $query->where('slug', $this->deptSlug);
        } else {
            $query->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['production']));
        }

        $this->department = $query->first();

        if (!$this->department) {
            abort(404, 'Production Department not found.');
        }

        $this->departmentId = $this->department->id;
        $this->departmentName = $this->department->name;
        $this->deptSlug = $this->department->slug;
        $this->store = ProductionStore::getOrCreateForDepartment($this->departmentId);
    }

    protected function loadCurrentShift(): void
    {
        $employee = Auth::user();
        $today = Carbon::today()->toDateString();

        // 1. Check for active shift for this department
        $activeShift = Shift::where('department_id', $this->departmentId)
            ->where('shift_date', $today)
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        // 2. Fallback to employee's active shift
        if (!$activeShift && $employee) {
            $activeShift = Shift::where('employee_id', $employee->id)
                ->where('shift_date', $today)
                ->where('status', 'active')
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();
        }

        // 3. Fallback to today's closed shift (to view closing records)
        if (!$activeShift) {
            $activeShift = Shift::where('department_id', $this->departmentId)
                ->where('shift_date', $today)
                ->where('status', 'closed')
                ->latest('clock_out')
                ->first();
        }

        if ($activeShift) {
            $this->currentShift = $activeShift;
            $this->currentShiftId = (string) $activeShift->id;
            $this->shiftType = $activeShift->shift_type ?? 'morning';

            if ($activeShift->status === 'closed') {
                $this->isClosed = true;
                $this->closedAt = $activeShift->shift_closed_at
                    ? $activeShift->shift_closed_at->format('M d, Y h:i A')
                    : ($activeShift->clock_out ? $activeShift->clock_out->format('M d, Y h:i A') : 'Completed');
            }
        }
    }

    public function loadClosingStockItems(): void
    {
        if (!$this->store || !$this->department) {
            return;
        }

        $stockService = app(ProductionStockService::class);

        // Ensure shift daily stocks exist
        if ($this->currentShift && !$this->isClosed) {
            $stockService->initializeShiftDailyStock($this->store, $this->department, $this->currentShift);
        }

        $categories = $stockService->getTrackableItemsForStore($this->store, $this->department);
        $today = Carbon::today()->toDateString();
        $shiftId = $this->currentShift?->id;

        $dailyRecords = ProductionStoreDailyStock::where('store_id', $this->store->id)
            ->where('stock_date', $today)
            ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId))
            ->get()
            ->keyBy('item_id');

        $this->stockItems = [
            'finished' => [],
            'wip' => [],
            'raw' => [],
        ];

        foreach (['finished', 'wip', 'raw'] as $cat) {
            foreach ($categories[$cat] as $entity) {
                $itemId = (string) $entity->id;
                $daily = $dailyRecords->get($itemId);

                $opening = $daily ? (float) $daily->opening_quantity : 0.0;
                $added = $daily ? (float) $daily->addition_quantity : 0.0;
                $used = $daily ? (float) $daily->quantity_used : 0.0;
                $theoretical = max(0, $opening + $added - $used);

                $actualClosing = $daily && $daily->workflow_step === 'closing_completed'
                    ? (float) $daily->closing_quantity
                    : $theoretical;

                $this->stockItems[$cat][$itemId] = [
                    'id' => $itemId,
                    'daily_id' => $daily?->id,
                    'name' => $entity->name,
                    'sku' => $entity->sku ?? 'N/A',
                    'uom' => $entity->unitOfMeasure?->symbol ?? ($cat === 'raw' ? ($entity->uom ?? 'units') : 'pcs'),
                    'opening' => $opening,
                    'added' => $added,
                    'used' => $used,
                    'theoretical' => $theoretical,
                    'verified' => $daily ? (bool) $daily->is_workflow_verified : false,
                ];

                if (!isset($this->closingCounts[$itemId])) {
                    $this->closingCounts[$itemId] = $actualClosing;
                }
                if (!isset($this->itemNotes[$itemId])) {
                    $this->itemNotes[$itemId] = $daily?->notes ?? '';
                }
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function matchAllTheoretical(): void
    {
        $currentItems = $this->stockItems[$this->activeTab] ?? [];
        foreach ($currentItems as $itemId => $item) {
            $this->closingCounts[$itemId] = $item['theoretical'];
        }
        $this->toast()->info('Populated all items in ' . strtoupper($this->activeTab) . ' with theoretical counts.')->send();
    }

    public function loadProductionClosingData(): void
    {
        if (!$this->currentShiftId) {
            $this->productionSummary = [];
            return;
        }

        $dailyProduces = DailyProduce::where('shift_id', $this->currentShiftId)
            ->with(['recipe', 'productionRecords'])
            ->get();

        $summary = [];
        foreach ($dailyProduces as $produce) {
            $totalProduced = $produce->productionRecords->sum('quantity_produced');
            $totalApproved = $produce->productionRecords->sum('quantity_approved');
            $totalRejected = $produce->productionRecords->sum('quantity_rejected');
            $totalSent = $produce->productionRecords->sum('quantity_sent_out');

            $summary[] = [
                'recipe_name' => $produce->recipe->name ?? 'N/A',
                'batches_planned' => $produce->batches_to_produce,
                'batches_produced' => $produce->productionRecords->count(),
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'total_sent' => $totalSent,
                'yield_percentage' => $totalProduced > 0 ? ($totalApproved / $totalProduced) * 100 : 0,
            ];
        }

        $this->productionSummary = $summary;
        $this->loadRawMaterialUsage();
    }

    public function loadRawMaterialUsage(): void
    {
        if (!$this->currentShiftId) {
            $this->rawMaterialUsage = [];
            $this->materialVariances = [];
            $this->totalMaterialEfficiency = 0;
            return;
        }

        $shift = Shift::find($this->currentShiftId);
        if (!$shift) return;

        $dailyProduces = DailyProduce::where('shift_id', $this->currentShiftId)
            ->with(['recipe.ingredients.item', 'productionRecords'])
            ->get();

        $materialUsageByItem = [];
        $totalPlannedCost = 0;
        $totalActualCost = 0;

        foreach ($dailyProduces as $produce) {
            $recipe = $produce->recipe;
            if (!$recipe) continue;

            $totalProduced = $produce->productionRecords->sum('quantity_produced');
            $batchesProduced = $totalProduced > 0 ? ceil($totalProduced / ($recipe->yield_quantity ?: 1)) : 0;

            foreach ($recipe->ingredients as $ingredient) {
                $item = $ingredient->item;
                if (!$item) continue;

                $itemId = $item->id;
                $plannedQuantity = $ingredient->getQuantityForBatchSize($batchesProduced);
                $plannedCost = $plannedQuantity * $ingredient->cost_per_unit;

                if (!isset($materialUsageByItem[$itemId])) {
                    $materialUsageByItem[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $item->name,
                        'item_code' => $item->item_code ?? 'N/A',
                        'uom' => $ingredient->unitOfMeasure?->symbol ?? 'N/A',
                        'planned_quantity' => 0,
                        'actual_quantity' => 0,
                        'variance' => 0,
                        'planned_cost' => 0,
                        'actual_cost' => 0,
                        'cost_variance' => 0,
                        'efficiency_percentage' => 0,
                        'recipes_used_in' => [],
                    ];
                }

                $materialUsageByItem[$itemId]['planned_quantity'] += $plannedQuantity;
                $materialUsageByItem[$itemId]['planned_cost'] += $plannedCost;
                $materialUsageByItem[$itemId]['recipes_used_in'][] = $recipe->product_name;

                $totalPlannedCost += $plannedCost;
            }
        }

        $itemRequests = ItemRequest::where('department_id', $this->departmentId)
            ->where('shift', $this->shiftType)
            ->whereDate('request_date', $this->shiftDate)
            ->where('status', 'dispatched')
            ->with(['requestDetails.item', 'itemDispatches.dispatchDetails'])
            ->get();

        foreach ($itemRequests as $request) {
            foreach ($request->requestDetails as $detail) {
                $item = $detail->item;
                if (!$item) continue;

                $itemId = $item->id;
                $actualQuantity = $detail->quantity_dispatched ?? 0;
                $actualCost = $actualQuantity * ((float) ($item->unit_price ?? $detail->cost_per_unit ?? 0));

                if (isset($materialUsageByItem[$itemId])) {
                    $materialUsageByItem[$itemId]['actual_quantity'] += $actualQuantity;
                    $materialUsageByItem[$itemId]['actual_cost'] += $actualCost;
                    $totalActualCost += $actualCost;
                } else {
                    $materialUsageByItem[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $item->name,
                        'item_code' => $item->item_code ?? 'N/A',
                        'uom' => $detail->unitOfMeasure?->symbol ?? 'N/A',
                        'planned_quantity' => 0,
                        'actual_quantity' => $actualQuantity,
                        'variance' => $actualQuantity,
                        'planned_cost' => 0,
                        'actual_cost' => $actualCost,
                        'cost_variance' => $actualCost,
                        'efficiency_percentage' => 0,
                        'recipes_used_in' => ['Unplanned Usage'],
                    ];
                    $totalActualCost += $actualCost;
                }
            }
        }

        $significantVariances = [];
        foreach ($materialUsageByItem as $itemId => &$usage) {
            $usage['variance'] = $usage['actual_quantity'] - $usage['planned_quantity'];
            $usage['cost_variance'] = $usage['actual_cost'] - $usage['planned_cost'];

            if ($usage['planned_quantity'] > 0) {
                $usage['efficiency_percentage'] = ($usage['actual_quantity'] / $usage['planned_quantity']) * 100;
            } else {
                $usage['efficiency_percentage'] = $usage['actual_quantity'] > 0 ? 999 : 100;
            }

            $variancePercentage = $usage['planned_quantity'] > 0
                ? abs($usage['variance'] / $usage['planned_quantity']) * 100
                : 0;

            if ($variancePercentage > 5 || abs($usage['cost_variance']) > 50) {
                $significantVariances[] = [
                    'item_name' => $usage['item_name'],
                    'item_code' => $usage['item_code'],
                    'variance_quantity' => $usage['variance'],
                    'variance_percentage' => round($variancePercentage, 2),
                    'cost_variance' => $usage['cost_variance'],
                    'severity' => abs($usage['cost_variance']) > 100 ? 'high' : 'medium',
                    'type' => $usage['variance'] > 0 ? 'overuse' : 'underuse',
                ];
            }

            $usage['variance'] = round($usage['variance'], 2);
            $usage['efficiency_percentage'] = round($usage['efficiency_percentage'], 2);
            $usage['planned_quantity'] = round($usage['planned_quantity'], 2);
            $usage['actual_quantity'] = round($usage['actual_quantity'], 2);
            $usage['recipes_used_in'] = array_unique($usage['recipes_used_in']);
        }

        $this->totalMaterialEfficiency = $totalPlannedCost > 0
            ? round(($totalActualCost / $totalPlannedCost) * 100, 2)
            : 100;

        $this->rawMaterialUsage = array_values($materialUsageByItem);
        $this->materialVariances = $significantVariances;
    }

    public function saveShiftClosing(): void
    {
        if (!$this->currentShift) {
            $this->toast()->error('No active shift found. Please clock in first.')->send();
            return;
        }

        if ($this->isClosed) {
            $this->toast()->warning('This shift is already closed.')->send();
            return;
        }

        $this->validate([
            'closingCounts.*' => 'required|numeric|min:0',
            'itemNotes.*' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ], [
            'closingCounts.*.required' => 'Please enter a count for each item.',
            'closingCounts.*.numeric' => 'Count must be a valid number.',
            'closingCounts.*.min' => 'Count cannot be negative.',
        ]);

        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $userId = Auth::id();
            $today = Carbon::today()->toDateString();

            // 1. Update closing counts and variances on daily stocks
            foreach ($this->closingCounts as $itemId => $count) {
                $actualClosing = (float) $count;
                $note = $this->itemNotes[$itemId] ?? null;

                $dailyStock = ProductionStoreDailyStock::firstOrNew([
                    'store_id' => $this->store->id,
                    'item_id' => (string) $itemId,
                    'stock_date' => $today,
                    'shift_id' => $this->currentShift->id,
                ]);

                $theoretical = (float) $dailyStock->opening_quantity + (float) $dailyStock->addition_quantity - (float) $dailyStock->quantity_used;
                $dailyStock->closing_quantity = $actualClosing;
                $dailyStock->variance = $actualClosing - $theoretical;
                $dailyStock->workflow_step = 'closing_completed';
                $dailyStock->is_workflow_verified = true;
                $dailyStock->verified_at = $now;
                $dailyStock->verified_by = $userId;
                $dailyStock->notes = $note;
                $dailyStock->save();

                // 2. Synchronize master ProductionStoreStock balance to the verified physical closing count
                ProductionStoreStock::updateOrCreate(
                    [
                        'store_id' => $this->store->id,
                        'item_id' => (string) $itemId,
                    ],
                    [
                        'quantity_available' => $actualClosing,
                    ]
                );
            }

            // 3. Prepare summary metadata
            $shiftSummary = [
                'production_summary' => $this->productionSummary,
                'raw_material_usage' => $this->rawMaterialUsage,
                'material_variances' => $this->materialVariances,
                'material_efficiency' => $this->totalMaterialEfficiency,
                'user_notes' => $this->notes,
                'closed_at' => $now->toDateTimeString(),
                'closed_by' => $userId,
            ];

            // 4. Close the shift
            $this->currentShift->status = 'closed';
            $this->currentShift->clock_out = $now;
            $this->currentShift->shift_closed_at = $now;
            $this->currentShift->workflow_state = 'completed';
            $this->currentShift->notes = $this->notes ?: json_encode($shiftSummary);
            $this->currentShift->save();

            // 5. Finalize DailyProduce and lock ProductionRecords
            ProductionRecord::whereHas('dailyProduce', function ($q) {
                $q->where('shift_id', $this->currentShift->id);
            })->update(['is_locked' => true]);

            DailyProduce::where('shift_id', $this->currentShift->id)
                ->update(['status' => 'finalized']);

            // 6. Update ItemRequest status to completed
            ItemRequest::where('department_id', $this->departmentId)
                ->where('shift', $this->shiftType)
                ->whereDate('request_date', $this->shiftDate)
                ->where('status', 'dispatched')
                ->update(['status' => 'completed']);

            DB::commit();

            $this->isClosed = true;
            $this->closedAt = $now->format('M d, Y h:i A');

            $this->toast()->success('Production shift closed and final physical stock synchronized successfully!')->send();

            // Refresh data
            $this->loadClosingStockItems();
            $this->loadProductionClosingData();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error closing production shift: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Error closing shift: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        $filteredItems = [];
        $search = trim($this->search);
        $currentTabItems = $this->stockItems[$this->activeTab] ?? [];

        foreach ($currentTabItems as $itemId => $item) {
            if ($search === '' || stripos($item['name'], $search) !== false || stripos($item['sku'], $search) !== false) {
                $filteredItems[$itemId] = $item;
            }
        }

        return view('livewire.branch-dashboard.production.shift-closing.index', [
            'filteredItems' => $filteredItems,
            'finishedCount' => count($this->stockItems['finished'] ?? []),
            'wipCount' => count($this->stockItems['wip'] ?? []),
            'rawCount' => count($this->stockItems['raw'] ?? []),
        ]);
    }
}
