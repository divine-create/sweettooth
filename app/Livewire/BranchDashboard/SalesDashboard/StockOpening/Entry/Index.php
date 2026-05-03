<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Entry;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, SalesDepartmentContext;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public array $stockOpenings = [];

    public ?string $currentShiftId = null;

    public string $shiftType = 'morning';

    public $stockDate;

    public array $availableShifts = [];

    public bool $isVerified = false;

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->initializeDepartmentContext();
        $this->stockDate = Carbon::today()->format('Y-m-d');
        $this->loadAvailableShifts();
        $this->loadStockOpeningData();
    }

    /**
     * Get equivalent sales department IDs for the current context
     */
    protected function resolveEquivalentSalesDepartmentIds(): array
    {
        if (! $this->departmentId && ! $this->salesDeptSlug) {
            return [];
        }

        $branchId = $this->getBranchId();

        // First try branch-specific department
        $department = Department::where('slug', $this->salesDeptSlug)
            ->where('branch_id', $branchId)
            ->first();

        // Then try global department
        if (! $department) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->whereNull('branch_id')
                ->first();
        }

        if (! $department) {
            return [];
        }

        return [$department->id];
    }

    protected function loadAvailableShifts(): void
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds)) {
            return;
        }

        $shifts = Shift::where('branch_id', $this->b_id)
            ->where('shift_date', $this->stockDate)
            ->whereIn('department_id', $salesDepartmentIds)
            ->orderBy('clock_in', 'desc')
            ->get(['id', 'shift_number', 'shift_type', 'clock_in']);

        $this->availableShifts = $shifts->toArray();

        $activeShift = $shifts->firstWhere('status', 'active');
        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
            $this->shiftType = $activeShift->shift_type ?? 'morning';
        }
    }

    public function loadStockOpeningData(): void
    {
        $selectedProductIds = session()->get('stock_opening_selected_products', []);

        if (empty($selectedProductIds)) {
            $this->stockOpenings = [];

            return;
        }

        $stockDate = Carbon::parse($this->stockDate)->toDateString();
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds)) {
            $this->stockOpenings = [];

            return;
        }

        // Get products
        $products = Product::query()
            ->whereIn('id', $selectedProductIds)
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->select(['id', 'name', 'sku', 'uom_id', 'product_type_id', 'shelf_life_days'])
            ->with(['unitOfMeasure:id,symbol'])
            ->orderBy('name')
            ->get();

        // Get today's existing stock records
        $todayStocks = ProductStock::where('stock_date', $stockDate)
            ->whereIn('product_id', $selectedProductIds)
            ->get()
            ->keyBy('product_id');

        // Get previous shift's closing - the most recent closing before current shift
        // For morning: get yesterday's closing
        // For afternoon/other: get today's morning closing (or most recent before this shift)
        $previousClosingQuery = ProductStock::query()
            ->whereIn('product_id', $selectedProductIds)
            ->whereIn('department_id', $salesDepartmentIds)
            ->whereNotNull('closing_quantity')
            ->where('closing_quantity', '>', 0);

        if ($this->shiftType === 'morning') {
            // Morning shift: load yesterday's closing
            $previousClosingQuery->where('stock_date', '<', $stockDate);
        } else {
            // Afternoon/other shift: load today's closing from earlier shift(s)
            $previousClosingQuery->where('stock_date', '<=', $stockDate);
        }

        $previousStocks = $previousClosingQuery
            ->orderByDesc('stock_date')
            ->orderByDesc('id')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $stockOpenings = [];
        foreach ($products as $product) {
            $productId = (string) $product->id;
            $todayStock = $todayStocks[$productId] ?? null;
            $previousStock = $previousStocks[$productId] ?? null;

            $previousClosing = $previousStock ? (float) $previousStock->closing_quantity : 0;
            $expectedOpening = $previousClosing;
            $actualOpening = $todayStock ? (float) $todayStock->opening_quantity : $expectedOpening;
            $variance = $actualOpening - $expectedOpening;

            $stockOpenings[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_uom' => $product->unitOfMeasure?->symbol,
                'yesterday_closing' => $previousClosing,
                'expected_opening' => $expectedOpening,
                'actual_opening' => $actualOpening,
                'variance' => $variance,
                'production_date' => $todayStock?->production_date?->format('Y-m-d') ?? Carbon::today()->format('Y-m-d'),
                'expiry_date' => $todayStock?->expiry_date?->format('Y-m-d') ?? null,
                'shelf_life_days' => $product->shelf_life_days,
                'notes' => $todayStock?->notes ?? '',
                'is_saved' => $todayStock !== null,
            ];
        }

        $this->stockOpenings = $stockOpenings;
    }

    public function updatedStockOpenings()
    {
        // Variance is recalculated automatically via wire:model binding in the view
    }

    public function saveAll()
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds) || empty($this->stockOpenings)) {
            return;
        }

        $stockDate = Carbon::parse($this->stockDate)->toDateString();
        $primaryDeptId = reset($salesDepartmentIds);

        foreach ($this->stockOpenings as $entry) {
            $existingStock = ProductStock::where('stock_date', $stockDate)
                ->where('product_id', $entry['product_id'])
                ->first();

            if ($existingStock) {
                $existingStock->update([
                    'opening_quantity' => $entry['actual_opening'],
                    'production_date' => ! empty($entry['production_date']) ? Carbon::parse($entry['production_date']) : null,
                    'expiry_date' => ! empty($entry['expiry_date']) ? Carbon::parse($entry['expiry_date']) : null,
                    'notes' => $entry['notes'] ?? '',
                    'shift_type' => $this->shiftType,
                    'is_workflow_verified' => true,
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            } else {
                ProductStock::create([
                    'branch_id' => $this->b_id,
                    'department_id' => $primaryDeptId,
                    'product_id' => $entry['product_id'],
                    'stock_date' => $stockDate,
                    'opening_quantity' => $entry['actual_opening'],
                    'quantity_available' => $entry['actual_opening'],
                    'quantity_sold' => 0,
                    'closing_quantity' => $entry['actual_opening'],
                    'production_date' => ! empty($entry['production_date']) ? Carbon::parse($entry['production_date']) : null,
                    'expiry_date' => ! empty($entry['expiry_date']) ? Carbon::parse($entry['expiry_date']) : null,
                    'notes' => $entry['notes'] ?? '',
                    'shift_type' => $this->shiftType,
                    'workflow_step' => 'opening_verified',
                    'is_workflow_verified' => true,
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            }

            // Mark as saved
            $entry['is_saved'] = true;
        }

        $shiftId = $this->currentShiftId;

        if (!$shiftId) {
            $currentShift = \App\Models\Shift::where('employee_id', auth()->id())
                ->where('shift_date', $this->stockDate)
                ->where('status', 'active')
                ->first();
            $shiftId = $currentShift?->id;
        }

        if ($shiftId) {
            app(\App\Services\SalesWorkflowService::class)->completeStep(auth()->id(), $shiftId, 'stock_opening');
        }

        session()->forget('stock_opening_selected_products');

        $this->toast()->success('Stock opening saved! Redirecting to POS...')->send();

        $posUrl = route('branch-dashboard.sales-dashboard.pos.index', [
            'salesDeptSlug' => $this->salesDeptSlug,
            'b_id' => $this->b_id,
        ]);

        $this->dispatch('navigate-to-pos', url: $posUrl);
    }

    protected function updateWorkflowState(string $state): void
    {
        $shift = \App\Models\Shift::where('employee_id', auth()->id())
            ->where('shift_date', Carbon::today()->format('Y-m-d'))
            ->where('status', 'active')
            ->first();

        if ($shift) {
            $metadata = $shift->metadata ?? [];
            $metadata['stock_opening_completed'] = true;
            $metadata['stock_opening_completed_at'] = now()->toIso8601String();
            $shift->metadata = $metadata;
            $shift->workflow_state = $state;
            $shift->save();
        }
    }

    public function addMoreProducts()
    {
        return $this->redirect(route('branch-dashboard.sales-dashboard.stock-opening.select.index', [
            'salesDeptSlug' => $this->salesDeptSlug,
            'b_id' => $this->b_id,
        ]));
    }

    public function clearAndStartOver()
    {
        session()->forget('stock_opening_selected_products');
        $this->stockOpenings = [];

        return $this->redirect(route('branch-dashboard.sales-dashboard.stock-opening.select.index', [
            'salesDeptSlug' => $this->salesDeptSlug,
            'b_id' => $this->b_id,
        ]));
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.stock-opening.entry.index', [
            'stockOpenings' => $this->stockOpenings,
            'savedCount' => count(array_filter($this->stockOpenings, fn ($e) => $e['is_saved'] ?? false)),
            'totalCount' => count($this->stockOpenings),
            'salesDeptSlug' => $this->salesDeptSlug,
        ]);
    }

    protected function getModelClass(): string
    {
        return ProductStock::class;
    }

    protected function getAllSelectableIds(): array
    {
        return array_column($this->stockOpenings, 'product_id');
    }
}
