<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Entry;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Shift;
use App\Models\StockVariance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        // If we have a slug, resolve it first
        if ($this->salesDeptSlug) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $branchId)
                ->first();

            if (! $department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                return $this->withCombinedSalesPointIds([$department->id], (string) $department->slug, $branchId);
            }
        }

        // Fallback to explicit departmentId if provided
        if ($this->departmentId) {
            $department = Department::find($this->departmentId);

            return $this->withCombinedSalesPointIds(
                [(int) $this->departmentId],
                (string) ($department?->slug ?? ''),
                $branchId,
            );
        }

        return [];
    }

    /**
     * Merge in any departments combined into the same sales point (config/sales.php)
     * so the Till cashier's opening lists & counts member departments' products too.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    protected function withCombinedSalesPointIds(array $ids, string $slug, $branchId): array
    {
        if ($slug === '') {
            return array_values(array_unique(array_map('intval', $ids)));
        }

        $groupIds = \App\Support\CombinedSalesPoints::departmentIds($branchId, $slug);

        return array_values(array_unique(array_map('intval', array_merge($ids, $groupIds))));
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
            ->get(['id', 'shift_number', 'shift_type', 'status', 'clock_in']);

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
        $wipTypeIds = \DB::table('product_types')->where('code', 'WIP')->pluck('id');

        $products = Product::query()
            ->whereIn('id', $selectedProductIds)
            ->active()
            ->available()
            ->whereNotIn('product_type_id', $wipTypeIds)
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->select(['id', 'name', 'sku', 'uom_id', 'product_type_id', 'shelf_life_days', 'sales_department_id'])
            ->with(['unitOfMeasure:id,symbol'])
            ->orderBy('name')
            ->get();

        // Get today's existing stock records - scoped to this employee's shift
        $todayStocksQuery = ProductStock::where('stock_date', $stockDate)
            ->whereIn('product_id', $selectedProductIds)
            ->where('shift_type', $this->shiftType);

        if ($this->currentShiftId) {
            $todayStocksQuery->where('shift_id', (int) $this->currentShiftId);
        } elseif (! empty($salesDepartmentIds)) {
            $todayStocksQuery->whereIn('department_id', $salesDepartmentIds);
        }

        $todayStocks = $todayStocksQuery->get()->keyBy('product_id');

        $todayStockIds = $todayStocks->pluck('id')->filter()->all();

        // Get previous shift's closing - the most recent closing before current shift
        $primaryDeptId = ! empty($salesDepartmentIds) ? reset($salesDepartmentIds) : null;
        $previousClosingQuery = ProductStock::query()
            ->whereIn('product_id', $selectedProductIds)
            ->whereNotNull('closing_quantity');

        if (! empty($salesDepartmentIds)) {
            $previousClosingQuery->whereIn('department_id', $salesDepartmentIds);
            if ($primaryDeptId) {
                $previousClosingQuery->orderByRaw('department_id = ? DESC', [$primaryDeptId]);
            }
        }

        $previousClosingQuery->orderByDesc('id');

        if (! empty($todayStockIds)) {
            $minId = min($todayStockIds);
            $previousClosingQuery->where('id', '<', $minId);
        }

        $previousStocks = $previousClosingQuery
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        // Get today's dispatches (additions)
        $dispatchRows = \App\Models\ProductDispatch::query()
            ->whereIn('product_id', $selectedProductIds)
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->whereIn('status', ['pending_verification', 'accepted', 'received'])
            ->where(function ($query) use ($stockDate) {
                $query->whereDate('dispatch_date', $stockDate)
                    ->orWhereDate('dispatch_time', $stockDate)
                    ->orWhereDate('received_at', $stockDate);
            })
            ->get(['product_id', 'quantity', 'received_quantity', 'shift_type', 'created_at', 'received_at', 'dispatch_time']);

        $dispatchRowsByProduct = [];
        foreach ($dispatchRows as $row) {
            $dispatchRowsByProduct[(string) $row->product_id][] = $row;
        }

        $stockOpenings = [];
        foreach ($products as $product) {
            $productId = (string) $product->id;
            $todayStock = $todayStocks[$productId] ?? null;
            $previousStock = $previousStocks[$productId] ?? null;

            $todayAdditions = 0;
            $productDispatches = $dispatchRowsByProduct[$productId] ?? [];

            foreach ($productDispatches as $dispatchRow) {
                // When the previous shift closed today, its closing_quantity already incorporates
                // every dispatch received during that shift. Counting them again here would
                // inflate expected_opening and produce a false variance.
                if ($previousStock && $previousStock->stock_date?->isToday()) {
                    continue;
                }

                $qty = (float) ($dispatchRow->received_quantity ?? $dispatchRow->quantity);
                $todayAdditions += $qty;
            }

            $previousClosing = $previousStock ? (float) $previousStock->closing_quantity : 0;
            $expectedOpening = $previousClosing + $todayAdditions;
            $actualOpening = $todayStock ? (float) $todayStock->opening_quantity : $expectedOpening;
            $variance = $actualOpening - $expectedOpening;

            $stockOpenings[] = [
                'product_id' => $product->id,
                // Home sales department of the product. In a combined sales point
                // this keeps each product's stock filed under its own department.
                'department_id' => (int) ($product->sales_department_id ?: ($primaryDeptId ?? 0)),
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_uom' => $product->unitOfMeasure?->symbol,
                'yesterday_closing' => $previousClosing,
                'today_additions' => $todayAdditions,
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
        // Recalculate variances if actual opening changes
        foreach ($this->stockOpenings as $index => $entry) {
            $expected = (float) ($entry['expected_opening'] ?? 0);
            $actual = (float) ($entry['actual_opening'] ?? 0);
            $this->stockOpenings[$index]['variance'] = $actual - $expected;
        }
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
            // File the row under the product's own home department so a combined
            // sales point never mixes one department's counts into another.
            $deptId = (int) ($entry['department_id'] ?? 0) ?: $primaryDeptId;

            // Match on the unique constraint columns — (dept, product, date, shift_type) is guaranteed unique,
            // so shift_id is not needed and would miss records created without a shift link.
            $existingStock = ProductStock::where('stock_date', $stockDate)
                ->where('product_id', $entry['product_id'])
                ->where('department_id', $deptId)
                ->where('shift_type', $this->shiftType)
                ->first();

            $updateData = [
                'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                'opening_quantity' => $entry['actual_opening'],
                'addition_quantity' => $entry['today_additions'] ?? 0,
                'production_date' => ! empty($entry['production_date']) ? Carbon::parse($entry['production_date']) : null,
                'expiry_date' => ! empty($entry['expiry_date']) ? Carbon::parse($entry['expiry_date']) : null,
                'notes' => $entry['notes'] ?? '',
                'shift_type' => $this->shiftType,
                'is_workflow_verified' => true,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'workflow_step' => 'opening_verified',
            ];

            // When the found record was already closed by a prior shift, preserve its
            // workflow/audit state — only update opening data and the shift link.
            if ($existingStock && $existingStock->workflow_step === 'closing_completed') {
                unset($updateData['workflow_step'], $updateData['is_workflow_verified'],
                      $updateData['verified_at'], $updateData['verified_by']);
            }

            if ($existingStock) {
                // Use DB::table to bypass the saving hook so closing_quantity on
                // closing_completed records (e.g. corrected variances) is never
                // accidentally recalculated by an opening_quantity/addition_quantity change.
                //
                // For rows that are NOT yet closed, we must still keep the derived
                // availability in sync with the opening/addition values being written.
                // Otherwise a dispatch received before this opening save is dropped from
                // closing_quantity and the POS (which reads stored closing_quantity)
                // under-reports available stock. Mirror ProductStock::calculateTotalAvailable/
                // calculateClosing so this raw write stays consistent with the model hook.
                if ($existingStock->workflow_step !== 'closing_completed') {
                    $opening   = (float) ($updateData['opening_quantity'] ?? 0);
                    $additions = (float) ($updateData['addition_quantity'] ?? 0);
                    $totalAvailable = $opening + $additions
                        - (float) $existingStock->callback_quantity
                        - (float) $existingStock->redress_quantity;
                    $closing = $totalAvailable
                        - (float) $existingStock->transfer_quantity
                        - (float) $existingStock->glovo_quantity
                        - (float) $existingStock->quantity_sold
                        - (float) ($existingStock->quantity_reserved ?? 0);

                    $updateData['total_available']  = $totalAvailable;
                    $updateData['closing_quantity'] = $closing;
                }

                DB::table('product_stocks')
                    ->where('id', $existingStock->id)
                    ->update(array_merge($updateData, ['updated_at' => now()]));
            } else {
                $createData = array_merge($updateData, [
                    'sales_shift_id' => null,
                    'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                    'branch_id' => $this->b_id,
                    'department_id' => $deptId,
                    'product_id' => $entry['product_id'],
                    'stock_date' => $stockDate,
                    'quantity_sold' => 0,
                ]);
                ProductStock::create($createData);
            }

            // Create or update variance record if actual differs from expected
            $variance = (float)($entry['actual_opening'] ?? 0) - (float)($entry['expected_opening'] ?? 0);
            if (abs($variance) > 0.001) {
                StockVariance::updateOrCreate(
                    [
                        'branch_id'      => $this->b_id,
                        'department_id'  => $deptId,
                        'product_id'     => $entry['product_id'],
                        'variance_date'  => $stockDate,
                        'shift_type'     => $this->shiftType,
                    ],
                    [
                        'quantity'          => abs($variance),
                        'expected_quantity' => $entry['expected_opening'] ?? 0,
                        'reason'            => $variance < 0 ? 'shortage' : 'excess',
                        'notes'             => 'Stock opening variance recorded during shift start.',
                        'status'            => 'pending',
                    ]
                );
            }

            // Mark as saved in local state
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
