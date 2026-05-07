<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\StockOpening;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductStock;
use App\Models\ProductType;
use App\Models\Shift;
use App\Services\SalesWorkflowService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, SalesDepartmentContext, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Note: salesDeptSlug, branchId, departmentId, departmentName, branchName
    // are now provided by SalesDepartmentContext trait

    public ?int $quantity = 20;

    public int $productsPerPage = 20;

    protected string $pageName = 'stock_opening_page';

    public string $page = '';

    public ?string $search = null;

    public ?int $filterProductType = null;

    public ?string $filterStatus = null;

    public ?string $selectedProductId = null;

    public array $selectedProductIds = [];

    public array $productLookupOptions = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProductType' => ['except' => ''],
        'page' => ['except' => 1],
    ];
    #[Url(keep: true)]

    // Stock opening data
    public array $stockOpenings = [];

    public array $rows = [];

    public array $unclosedProducts = [];

    public bool $isVerified = false;
    public bool $isEditing = false;

    public ?string $currentShiftId = null;

    public string $shiftType = 'morning';

    public $stockDate;

    public $availableShifts = [];

    public $selectedShiftForViewing = null;

    public array $productTypes = [];

    public ?string $expectedPreviousClosingDate = null;

    public ?string $expectedPreviousClosingShift = null;

    public ?ProductStock $selectedStockItem = null;

    public ?string $stock_products_cursor = null;

    private ?bool $productStocksHasDepartmentColumn = null;

    private ?array $cachedSalesDepartmentIds = null;

    private ?string $cachedSalesDepartmentSignature = null;

    protected ?LengthAwarePaginator $productsPaginatorForView = null;

    // Table headers
    public array $headers = [
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'yesterday_closing', 'label' => 'Previous Closing', 'collapsible' => true],
        ['index' => 'today_additions', 'label' => 'Production Sent', 'collapsible' => true],
        ['index' => 'expected_opening', 'label' => 'Expected Opening', 'collapsible' => true],
        ['index' => 'actual_opening', 'label' => 'Actual Opening'],
        ['index' => 'variance', 'label' => 'Variance', 'collapsible' => true],
        ['index' => 'variance_source', 'label' => 'Variance From', 'collapsible' => true],
        ['index' => 'production_date', 'label' => 'Production Date', 'collapsible' => true],
        ['index' => 'shelf_life', 'label' => 'Shelf Life', 'collapsible' => true],
        ['index' => 'notes', 'label' => 'Notes', 'collapsible' => true],
        ['index' => 'action', 'label' => 'Action'],
    ];

    public function updatedSelectedStockItem()
    {
        // Intentionally kept for backward compatibility with existing bindings.
    }

    public function updatedSelectedProductId($value): void
    {
        if (! empty($value)) {
            $this->loadSingleStockData();
        }
    }

    public function loadSingleStockData(): void
    {
        if (empty($this->selectedProductId)) {
            return;
        }

        $productId = (string) $this->selectedProductId;
        if (! in_array($productId, $this->selectedProductIds, true)) {
            $this->selectedProductIds[] = $productId;
        }

        $this->loadStockOpeningData($this->selectedProductIds);
    }

    protected function getModelClass(): string
    {
        return ProductStock::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ?: $this->branchId ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->mountBase();
        $this->initializeDepartmentContext(); // Using trait method
        $this->departmentName = $this->departmentName ?: 'Stock Opening';
        $this->stockDate = Carbon::today()->format('Y-m-d');
        $this->loadAvailableShifts();
        $this->loadCurrentShift();
        $this->loadStockOpeningData();
    }

    // loadBranchAndDepartment is now handled by SalesDepartmentContext trait

    /**
     * Load available shifts for date/shift selection
     */
    protected function loadAvailableShifts()
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        if (empty($salesDepartmentIds)) {
            $this->availableShifts = [];

            return;
        }

        // Get shifts from last 30 days for the sales department
        $this->availableShifts = Shift::query()
            ->where('branch_id', $this->getBranchId())
            ->whereIn('department_id', $salesDepartmentIds)
            ->where('shift_date', '>=', Carbon::today()->subDays(30))
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    /**
     * When user selects a different shift to view
     */
    public function updatedSelectedShiftForViewing($shiftId)
    {
        if ($shiftId) {
            $shift = Shift::find($shiftId);
            if ($shift) {
                $this->stockDate = $shift->shift_date->format('Y-m-d');
                $this->shiftType = $shift->shift_type;
                $this->currentShiftId = $shiftId;
                $this->loadStockOpeningData();
            }
        }
    }

    /**
     * When stock date changes
     */
    public function updatedStockDate($value)
    {
        $this->loadStockOpeningData();
    }

    /**
     * Load current active shift or create new one
     */
    protected function loadCurrentShift()
    {
        $employee = auth()->user();

        // Get active shift for today
        $activeShift = Shift::where('employee_id', $employee->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->first();

        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
            $this->shiftType = $activeShift->shift_type ?? 'morning';
        }
    }

    /**
     * Load stock opening data only for selected products.
     *
     * @param  array<int|string>|null  $productIds
     */
    public function loadStockOpeningData(?array $productIds = null): void
    {
        $stockDate = Carbon::parse($this->stockDate)->toDateString();
        $yesterday = Carbon::parse($stockDate)->subDay()->toDateString();
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        $this->loadProductTypes($salesDepartmentIds);
        $this->loadProductLookupOptions();

        if (empty($salesDepartmentIds)) {
            $this->stockOpenings = [];
            $this->rows = [];
            $this->unclosedProducts = [];
            $this->isVerified = false;

            return;
        }

        $targetProductIds = collect($productIds ?? $this->selectedProductIds)
            ->filter(static fn ($id): bool => $id !== null && (string) $id !== '')
            ->map(static fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($targetProductIds)) {
            $productsPaginator = $this->resolvePaginatedProductsForStockOpening($salesDepartmentIds);
            $this->productsPaginatorForView = $productsPaginator;
            $targetProductIds = $productsPaginator->getCollection()
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->values()
                ->all();
            $this->selectedProductIds = $targetProductIds;
        } else {
            $this->productsPaginatorForView = $this->resolvePaginatedProductsForStockOpening($salesDepartmentIds);
        }

        if (empty($targetProductIds)) {
            $this->stockOpenings = [];
            $this->rows = [];
            $this->unclosedProducts = [];
            $this->isVerified = $this->hasVerifiedStockOpening($salesDepartmentIds);

            return;
        }

        $primarySalesDepartmentId = $this->resolvePrimarySalesDepartmentId($salesDepartmentIds);
        $hasDepartmentColumn = $this->hasProductStocksDepartmentColumn();
        $products = Product::query()
            ->whereIn('id', $targetProductIds)
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->select(['id', 'name', 'sku', 'uom_id', 'product_type_id', 'shelf_life_days'])
            ->with(['unitOfMeasure:id,symbol'])
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            $this->selectedProductIds = [];
            $this->stockOpenings = [];
            $this->rows = [];
            $this->unclosedProducts = [];
            $this->isVerified = $this->hasVerifiedStockOpening($salesDepartmentIds);

            return;
        }

        $productIds = $products->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
        $this->selectedProductIds = $productIds;
        if ($this->selectedProductId !== null && ! in_array((string) $this->selectedProductId, $productIds, true)) {
            $this->selectedProductId = null;
        }

        $stockSelect = [
            'id',
            'product_id',
            'stock_date',
            'shift_type',
            'opening_quantity',
            'closing_quantity',
            'production_date',
            'expiry_date',
            'notes',
        ];
        if ($hasDepartmentColumn) {
            $stockSelect[] = 'department_id';
        }

        $currentUserId = auth()->id();
        $todayStocksQuery = ProductStock::query()
            ->select($stockSelect)
            ->whereIn('product_id', $productIds)
            ->where('stock_date', $stockDate)
            ->where('shift_type', $this->getProductStockShiftType())
            ->orderBy('product_id');

        if ($hasDepartmentColumn) {
            $todayStocksQuery->whereIn('department_id', $salesDepartmentIds);
            if ($primarySalesDepartmentId !== null) {
                $todayStocksQuery->orderByRaw('department_id = ? DESC', [$primarySalesDepartmentId]);
            }
        }

        if ($this->currentShiftId) {
            // Per-employee isolation: only load this staff member's own stock rows
            $todayStocksQuery->where('shift_id', (int) $this->currentShiftId);
        } elseif ($currentUserId) {
            // Legacy fallback for records without shift_id
            $todayStocksQuery->where(function ($q) use ($currentUserId) {
                $q->where('verified_by', $currentUserId)
                    ->orWhereNull('verified_by');
            })->whereIn('workflow_step', ['opening_verified', 'opening_draft']);
        }

        $todayStocks = $todayStocksQuery->orderByDesc('id')->get();
        $todayStockByProduct = $this->buildPreferredStockMap(
            $todayStocks,
            $primarySalesDepartmentId,
            $hasDepartmentColumn
        );
        $todayStockIds = $todayStocks->pluck('id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $previousStockByProduct = $this->getPreviousStockByProduct(
            $productIds,
            $stockDate,
            $salesDepartmentIds,
            $primarySalesDepartmentId,
            $hasDepartmentColumn,
            $stockSelect,
            $todayStockIds
        );

        $dispatchRows = ProductDispatch::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->whereIn('status', ['pending_verification', 'accepted', 'received'])
            ->where(function ($query) use ($stockDate) {
                $query->whereDate('dispatch_date', $stockDate)
                    ->orWhereDate('dispatch_time', $stockDate)
                    ->orWhereDate('received_at', $stockDate);
            })
            ->get([
                'product_id',
                'status',
                'quantity',
                'received_quantity',
                'dispatch_date',
                'dispatch_time',
                'received_at',
                'created_at',
            ]);
        $dispatchRowsByProduct = [];
        foreach ($dispatchRows as $dispatchRow) {
            $dispatchRowsByProduct[(string) $dispatchRow->product_id][] = $dispatchRow;
        }

        $expectedPrevious = $this->getExpectedPreviousShiftContext(
            $stockDate,
            $this->getProductStockShiftType()
        );
        $this->expectedPreviousClosingDate = $expectedPrevious['date'];
        $this->expectedPreviousClosingShift = $expectedPrevious['shift'];

        $stockOpenings = [];
        $unclosedProducts = [];

        foreach ($products as $product) {
            $productId = (string) $product->id;
            $previousStock = $previousStockByProduct[$productId] ?? null;
            $todayStock = $todayStockByProduct[$productId] ?? null;
            $fallbackClosing = 0.0;
            $carryForwardSourceDate = null;
            $carryForwardShiftType = null;

            if ($previousStock !== null) {
                $fallbackClosing = (float) $previousStock->closing_quantity;
                $carryForwardSourceDate = $previousStock->stock_date?->format('Y-m-d') ?? null;
                $carryForwardShiftType = $previousStock->shift_type;
            }

            if ($previousStock !== null) {
                $expectedDate = $expectedPrevious['date'];
                $expectedShift = $expectedPrevious['shift'];
                $previousDate = $previousStock->stock_date?->format('Y-m-d') ?? null;
                $previousShift = $previousStock->shift_type ?? null;

                $isExpectedPrevious = $expectedDate !== null && $expectedShift !== null
                    ? ($previousDate === $expectedDate && $previousShift === $expectedShift)
                    : true;

                if (! $isExpectedPrevious && (float) $previousStock->closing_quantity > 0) {
                    $unclosedProducts[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'product_uom' => $product->unitOfMeasure?->symbol,
                        'last_closing' => (float) $previousStock->closing_quantity,
                        'last_stock_date' => $previousDate,
                        'last_shift_type' => $previousShift,
                    ];
                }
            }

            $previousDate = $previousStock?->stock_date?->format('Y-m-d');
            $cutoffTimestamp = $previousDate === $stockDate
                ? ($previousStock?->created_at ?? null)
                : null;

            $todayAdditions = 0.0;
            $dispatchCount = 0;
            $productDispatches = $dispatchRowsByProduct[$productId] ?? [];
            foreach ($productDispatches as $dispatchRow) {
                // If previous stock record is from TODAY, we must be careful not to double-count
                if ($previousStock && $previousStock->stock_date?->isToday()) {
                    // Skip if dispatch was for a different shift type (likely already captured in previous stock)
                    if ($dispatchRow->shift_type && $dispatchRow->shift_type !== $this->getProductStockShiftType()) {
                        continue;
                    }

                    // Secondary safety: use cutoff timestamp if shift_type is null or ambiguous
                    $dispatchTimestamp = $dispatchRow->received_at ?? $dispatchRow->dispatch_time ?? $dispatchRow->created_at;
                    if ($dispatchTimestamp && $previousStock->created_at && Carbon::parse($dispatchTimestamp)->lte($previousStock->created_at)) {
                        continue;
                    }
                } elseif ($cutoffTimestamp) {
                    // Fallback to cutoff for non-today previous stocks if applicable (usually null)
                    $dispatchTimestamp = $dispatchRow->received_at ?? $dispatchRow->dispatch_time ?? $dispatchRow->created_at;
                    if ($dispatchTimestamp && Carbon::parse($dispatchTimestamp)->lte($cutoffTimestamp)) {
                        continue;
                    }
                }

                $quantity = $dispatchRow->status === 'received'
                    ? (float) ($dispatchRow->received_quantity ?? $dispatchRow->quantity ?? 0)
                    : (float) ($dispatchRow->quantity ?? 0);

                $todayAdditions += $quantity;
                $dispatchCount++;
            }

            $previousClosing = $previousStock
                ? (float) $previousStock->closing_quantity
                : $fallbackClosing;
            $previousClosingSource = $previousStock
                ? ($previousStock->stock_date?->format('Y-m-d') ?? $yesterday)
                : ($carryForwardSourceDate ?? $yesterday);
            $previousClosingShift = $previousStock
                ? $previousStock->shift_type
                : $carryForwardShiftType;
            $isCarriedForward = ! $previousStock && $fallbackClosing > 0;
            $expectedOpening = $previousClosing + $todayAdditions;
            $actualOpening = $todayStock ? $todayStock->opening_quantity : $expectedOpening;
            $variance = $actualOpening - $expectedOpening;

            $varianceSource = 'None';
            if ($variance !== 0.0) {
                $sourceDate = $previousStock?->stock_date?->format('Y-m-d') ?? 'unknown date';
                $sourceShift = $previousStock?->shift_type ?? 'unknown shift';
                $varianceSource = "Difference vs last recorded closing ({$sourceDate} {$sourceShift}) + additions";
            }

            $stockOpeningEntry = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_uom' => $product->unitOfMeasure?->symbol,
                'yesterday_closing' => $previousClosing,
                'previous_closing_source' => $previousClosingSource,
                'previous_closing_shift' => $previousClosingShift,
                'is_carried_forward' => $isCarriedForward,
                'today_additions' => $todayAdditions,
                'dispatch_count' => $dispatchCount,
                'expected_opening' => $expectedOpening,
                'actual_opening' => $actualOpening,
                'variance' => $variance,
                'variance_source' => $varianceSource,
                'production_date' => $todayStock ? $todayStock->production_date?->format('Y-m-d') : Carbon::today()->format('Y-m-d'),
                'expiry_date' => $todayStock ? $todayStock->expiry_date?->format('Y-m-d') : null,
                'shelf_life_days' => $product->shelf_life_days,
                'notes' => $todayStock ? $todayStock->notes : '',
                'is_saved' => $todayStock !== null,
            ];

            // Preserve user-entered values from existing stockOpenings
            $existingEntry = $this->stockOpenings[$productId] ?? null;
            if ($existingEntry !== null) {
                // Keep user's actual_opening, production_date, expiry_date, notes if they were modified
                if (isset($existingEntry['actual_opening']) && $existingEntry['actual_opening'] !== $expectedOpening) {
                    $stockOpeningEntry['actual_opening'] = $existingEntry['actual_opening'];
                    $stockOpeningEntry['variance'] = $stockOpeningEntry['actual_opening'] - $expectedOpening;
                }
                if (! empty($existingEntry['production_date'])) {
                    $stockOpeningEntry['production_date'] = $existingEntry['production_date'];
                }
                if (! empty($existingEntry['expiry_date'])) {
                    $stockOpeningEntry['expiry_date'] = $existingEntry['expiry_date'];
                }
                if (! empty($existingEntry['notes'])) {
                    $stockOpeningEntry['notes'] = $existingEntry['notes'];
                }
            }

            $stockOpenings[$productId] = $stockOpeningEntry;
        }

        $this->stockOpenings = array_values($stockOpenings);
        $this->syncRowsFromStockOpenings();
        $this->unclosedProducts = $unclosedProducts;
        $this->isVerified = $this->hasVerifiedStockOpening($salesDepartmentIds);
    }

    /**
     * @param  Collection<int, ProductStock>  $stocks
     * @return array<string, ProductStock>
     */
    private function buildPreferredStockMap(Collection $stocks, ?int $primarySalesDepartmentId, bool $hasDepartmentColumn): array
    {
        $map = [];

        foreach ($stocks as $stock) {
            $productId = (string) $stock->product_id;
            if (! isset($map[$productId])) {
                $map[$productId] = $stock;

                continue;
            }

            if (! $hasDepartmentColumn || $primarySalesDepartmentId === null) {
                continue;
            }

            $existingDate = $map[$productId]->stock_date?->toDateString()
                ?? (string) ($map[$productId]->stock_date ?? '');
            $candidateDate = $stock->stock_date?->toDateString()
                ?? (string) ($stock->stock_date ?? '');
            if ($existingDate !== '' && $candidateDate !== '' && $existingDate !== $candidateDate) {
                continue;
            }

            if ((int) ($map[$productId]->department_id ?? 0) === $primarySalesDepartmentId) {
                continue;
            }

            if ((int) ($stock->department_id ?? 0) === $primarySalesDepartmentId) {
                $map[$productId] = $stock;
            }
        }

        return $map;
    }

    private function getExpectedPreviousShiftContext(string $stockDate, string $currentShiftType): array
    {
        return [
            'date' => null, // Date no longer strictly enforced for previous
            'shift' => 'Previous Recorded Shift',
        ];
    }

    /**
     * @param  array<int, string>  $productIds
     * @param  array<int>  $salesDepartmentIds
     * @param  array<int, string>  $stockSelect
     * @param  array<int>  $excludeStockIds
     * @return array<string, ProductStock>
     */
    private function getPreviousStockByProduct(
        array $productIds,
        string $stockDate,
        array $salesDepartmentIds,
        ?int $primarySalesDepartmentId,
        bool $hasDepartmentColumn,
        array $stockSelect,
        array $excludeStockIds
    ): array {
        if (empty($productIds)) {
            return [];
        }

        $query = ProductStock::query()
            ->select($stockSelect)
            ->whereIn('product_id', $productIds);

        // Department isolation: ONLY look at the specific department being opened
        if ($primarySalesDepartmentId !== null) {
            $query->where('department_id', $primarySalesDepartmentId);
        }

        // Chronological logic: ignore shift names/dates and just look for the absolute 
        // latest record created before the current shift's records.
        if (! empty($excludeStockIds)) {
            $minCurrentId = min($excludeStockIds);
            $query->where('id', '<', $minCurrentId);
        }

        // Final fail-safe chronological sort
        $query->orderByDesc('id');

        return $this->buildPreferredStockMap($query->get(), $primarySalesDepartmentId, $hasDepartmentColumn);
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     */
    private function hasVerifiedStockOpening(array $salesDepartmentIds): bool
    {
        if (! $this->currentShiftId || empty($salesDepartmentIds)) {
            return false;
        }

        $query = ProductStock::query()
            ->where('stock_date', $this->stockDate)
            ->where('shift_type', $this->getProductStockShiftType())
            ->where('workflow_step', 'opening_verified')
            ->where('shift_id', (int) $this->currentShiftId);

        if ($this->hasProductStocksDepartmentColumn()) {
            $query->whereIn('department_id', $salesDepartmentIds);
        }

        return $query->exists();
    }

    private function hasProductStocksDepartmentColumn(): bool
    {
        if ($this->productStocksHasDepartmentColumn === null) {
            $this->productStocksHasDepartmentColumn = Schema::hasColumn('product_stocks', 'department_id');
        }

        return $this->productStocksHasDepartmentColumn;
    }

    private function syncRowsFromStockOpenings(): void
    {
        $this->rows = array_map(static function (array $stock, int $index): object {
            $stock['index'] = $index;

            return (object) $stock;
        }, $this->stockOpenings, array_keys($this->stockOpenings));
    }

    /**
     * Update actual opening quantity for a product
     */
    public function updateActualOpening($productId, $value)
    {
        $index = collect($this->stockOpenings)->search(function ($item) use ($productId) {
            return $item['product_id'] == $productId;
        });

        if ($index !== false) {
            $this->stockOpenings[$index]['actual_opening'] = (float) $value;
            $this->stockOpenings[$index]['variance'] =
            $this->stockOpenings[$index]['actual_opening'] -
            $this->stockOpenings[$index]['expected_opening'];
            $this->syncRowsFromStockOpenings();
        }
    }

    /**
     * Update production date for a product
     */
    public function updateProductionDate($productId, $value)
    {
        $index = collect($this->stockOpenings)->search(function ($item) use ($productId) {
            return $item['product_id'] == $productId;
        });

        if ($index !== false) {
            $this->stockOpenings[$index]['production_date'] = $value;

            $shelfLifeDays = (int) ($this->stockOpenings[$index]['shelf_life_days'] ?? 0);
            if ($shelfLifeDays > 0 && ! empty($value)) {
                $productionDate = Carbon::parse($value);
                $this->stockOpenings[$index]['expiry_date'] =
                    $productionDate->copy()->addDays($shelfLifeDays)->format('Y-m-d');
            }

            $this->syncRowsFromStockOpenings();
        }
    }

    /**
     * Update notes for a product
     */
    public function updateNotes($productId, $value)
    {
        $index = collect($this->stockOpenings)->search(function ($item) use ($productId) {
            return $item['product_id'] == $productId;
        });

        if ($index !== false) {
            $this->stockOpenings[$index]['notes'] = $value;
            $this->syncRowsFromStockOpenings();
        }
    }

    public function removeProduct($productId): void
    {
        $productId = (string) $productId;

        $this->selectedProductIds = array_values(array_filter(
            $this->selectedProductIds,
            static fn ($id): bool => (string) $id !== $productId
        ));

        $this->stockOpenings = array_values(array_filter(
            $this->stockOpenings,
            static fn (array $row): bool => (string) $row['product_id'] !== $productId
        ));

        $this->unclosedProducts = array_values(array_filter(
            $this->unclosedProducts,
            static fn (array $row): bool => (string) $row['product_id'] !== $productId
        ));

        if ((string) ($this->selectedProductId ?? '') === $productId) {
            $this->selectedProductId = null;
        }

        $this->syncRowsFromStockOpenings();
    }

    /**
     * Bulk-match actual opening with expected opening for all products in current view
     */
    public function matchAllExpected()
    {
        foreach ($this->stockOpenings as $index => $opening) {
            if (! ($opening['is_saved'] ?? false)) {
                $this->stockOpenings[$index]['actual_opening'] = $opening['expected_opening'];
                $this->stockOpenings[$index]['variance'] = 0.0;
            }
        }
        $this->syncRowsFromStockOpenings();
        $this->toast()->success('All products matched to expected values.')->send();
    }

    /**
     * Save the current stock opening as a draft
     */
    public function saveAsDraft()
    {
        if (empty($this->stockOpenings)) {
            $this->toast()->warning('No products to save.')->send();

            return;
        }

        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        $primarySalesDepartmentId = $this->resolvePrimarySalesDepartmentId($salesDepartmentIds);

        if (empty($salesDepartmentIds) || $primarySalesDepartmentId === null) {
            $this->toast()->error('Sales department context is missing.')->send();

            return;
        }

        DB::beginTransaction();
        try {
            $hasDepartmentColumn = $this->hasProductStocksDepartmentColumn();

            foreach ($this->stockOpenings as $stockOpening) {
                $lookup = [
                    'product_id' => $stockOpening['product_id'],
                    'stock_date' => $this->stockDate,
                    'shift_type' => $this->getProductStockShiftType(),
                    'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                ];

                if ($hasDepartmentColumn) {
                    $lookup['department_id'] = $primarySalesDepartmentId;
                }

                ProductStock::updateOrCreate(
                    $lookup,
                    [
                        'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                        'opening_quantity' => (float) $stockOpening['actual_opening'],
                        'production_date' => $stockOpening['production_date'],
                        'expiry_date' => $stockOpening['expiry_date'],
                        'notes' => $stockOpening['notes'],
                        'is_workflow_verified' => false,
                        'workflow_step' => 'opening_draft',
                        'verified_by' => auth()->id(),
                        'department_id' => $hasDepartmentColumn ? $primarySalesDepartmentId : null,
                    ]
                );
            }

            DB::commit();
            $this->toast()->success('Progress saved as draft.')->send();
            $this->loadStockOpeningData();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error saving draft: '.$e->getMessage())->send();
        }
    }

    /**
     * Save all stock openings
     */
    public function saveStockOpenings()
    {
        if (empty($this->stockOpenings)) {
            $this->toast()->warning('Select at least one product before saving.')->send();

            return;
        }

        if (! $this->currentShiftId) {
            $this->toast()->error('No active shift found. Please clock in first.')->send();

            return;
        }

        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        $primarySalesDepartmentId = $this->resolvePrimarySalesDepartmentId($salesDepartmentIds);
        if (empty($salesDepartmentIds) || $primarySalesDepartmentId === null) {
            $this->toast()->error('Sales department context is missing. Refresh and try again.')->send();

            return;
        }

        DB::beginTransaction();
        try {
            $hasDepartmentColumn = $this->hasProductStocksDepartmentColumn();

            // Get selected product IDs
            $selectedProductIds = collect($this->stockOpenings)->pluck('product_id')->toArray();

            // Save selected products with actual values
            foreach ($this->stockOpenings as $stockOpening) {
                $yesterdayClosing = (float) ($stockOpening['yesterday_closing'] ?? 0);
                $todayAdditions = (float) ($stockOpening['today_additions'] ?? 0);
                $actualOpening = (float) ($stockOpening['actual_opening'] ?? 0);

                // If actual opening already includes some/all additions, only keep the remaining additions
                // so totals do not double-count production sent.
                $additionsAlreadyCounted = max(0.0, $actualOpening - $yesterdayClosing);
                $remainingAdditions = max(0.0, $todayAdditions - $additionsAlreadyCounted);

                // Use shift_id from shifts table (sales department shift)
                // sales_shift_id can be null since we're using the general shifts table
                // addition_quantity represents the total quantity yield (approved quantity sent from production)
                $lookup = [
                    'product_id' => $stockOpening['product_id'],
                    'stock_date' => $this->stockDate,
                    'shift_type' => $this->getProductStockShiftType(),
                    'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                ];

                if ($hasDepartmentColumn) {
                    $lookup['department_id'] = $primarySalesDepartmentId;
                }

                ProductStock::updateOrCreate(
                    $lookup,
                    [
                        'sales_shift_id' => null, // Nullable - we use shifts table instead
                        'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                        'department_id' => $hasDepartmentColumn
                            ? $primarySalesDepartmentId
                            : null,
                        'opening_quantity' => $actualOpening,
                        'addition_quantity' => $remainingAdditions, // prevent double-counting additions
                        'production_date' => $stockOpening['production_date'],
                        'expiry_date' => $stockOpening['expiry_date'],
                        'notes' => $stockOpening['notes'],
                        'is_workflow_verified' => true,
                        'verified_at' => now(),
                        'verified_by' => auth()->id() ?? auth()->id(),
                        'workflow_step' => 'opening_verified',
                    ]
                );
            }

            // Get tracked products (selected + dispatch-linked + existing stock rows) to find unselected ones.
            $trackedProductIds = array_values(array_unique(array_merge(
                array_map(static fn ($id): string => (string) $id, $selectedProductIds),
                $this->resolveDefaultProductIdsForStockOpening($salesDepartmentIds, $this->stockDate)
            )));

            $trackedProducts = Product::query()
                ->active()
                ->available()
                ->whereIn('id', $trackedProductIds)
                ->whereIn('sales_department_id', $salesDepartmentIds)
                ->select(['id', 'shelf_life_days'])
                ->get();

            // Save unselected products with 0.00 opening quantity
            foreach ($trackedProducts as $product) {
                if (in_array($product->id, $selectedProductIds)) {
                    continue; // Skip already saved products
                }

                $lookup = [
                    'product_id' => $product->id,
                    'stock_date' => $this->stockDate,
                    'shift_type' => $this->getProductStockShiftType(),
                    'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                ];

                if ($hasDepartmentColumn) {
                    $lookup['department_id'] = $primarySalesDepartmentId;
                }

                // Check if record already exists for this shift
                $existingStock = ProductStock::where($lookup)->first();

                if (! $existingStock) {
                    // Create new record with 0.00 opening for unselected products
                    $productionDate = Carbon::today()->format('Y-m-d');
                    $expiryDate = null;

                    if ($product->shelf_life_days > 0) {
                        $expiryDate = Carbon::parse($productionDate)->addDays($product->shelf_life_days)->format('Y-m-d');
                    }

                    ProductStock::create(
                        array_merge($lookup, [
                            'sales_shift_id' => null,
                            'shift_id' => $this->currentShiftId ? (int) $this->currentShiftId : null,
                            'department_id' => $hasDepartmentColumn
                                ? $primarySalesDepartmentId
                                : null,
                            'opening_quantity' => 0.00,
                            'addition_quantity' => 0.00,
                            'production_date' => $productionDate,
                            'expiry_date' => $expiryDate,
                            'notes' => 'Auto-recorded - product not selected during stock opening',
                            'is_workflow_verified' => true,
                            'verified_at' => now(),
                            'verified_by' => auth()->id() ?? auth()->id(),
                            'workflow_step' => 'opening_verified',
                        ])
                    );
                }
            }

            DB::commit();
            $this->isVerified = true;
            $this->isEditing = false;

            // Mark workflow step as completed for all users to unlock POS access
            $workflowService = app(SalesWorkflowService::class);
            $workflowService->completeStep(
                auth()->id(),
                $this->currentShiftId,
                'stock_opening'
            );

            $this->toast()->success('Stock opening completed! Redirecting to POS...')->send();

            // Auto-redirect to POS with department context
            $this->redirectToPos();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error saving stock opening: '.$e->getMessage())->send();
        }
    }

    /**
     * Toggle editing mode for verified stock
     */
    public function toggleEdit(): void
    {
        // Safety check: Only allow editing if shift is still active
        $activeShift = Shift::where('id', $this->currentShiftId)
            ->where('status', 'active')
            ->exists();

        if (!$activeShift && !is_super_admin()) {
            $this->toast()->error('Cannot edit stock for a completed or inactive shift.')->send();
            return;
        }

        $this->isEditing = !$this->isEditing;
        
        if ($this->isEditing) {
            $this->toast()->info('Stock opening unlocked for editing. Updates will recalculate closing balances.')->send();
        }
    }

    /**
     * Check if stock opening is verified
     */
    public function checkVerificationStatus()
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        $this->isVerified = $this->hasVerifiedStockOpening($salesDepartmentIds);

        return $this->isVerified;
    }

    protected function getFilteredQuery()
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        $query = ProductStock::query()
            ->where('stock_date', $this->stockDate)
            ->where('shift_type', $this->getProductStockShiftType());

        if (! $this->hasProductStocksDepartmentColumn()) {
            return $query;
        }

        if (empty($salesDepartmentIds)) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        return $query->whereIn('department_id', $salesDepartmentIds);
    }

    public function updatedSearch()
    {
        $this->resetPage($this->pageName);
        $this->loadStockOpeningData();
    }

    public function updatedFilterProductType()
    {
        $this->resetPage($this->pageName);
        $this->loadStockOpeningData();
    }

    public function updatedPage(): void
    {
        // Load stock data for the new page while preserving user-entered values
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        // Get current page product IDs
        $this->productsPaginatorForView = null;
        $paginator = $this->resolvePaginatedProductsForStockOpening($salesDepartmentIds);
        $this->productsPaginatorForView = $paginator;

        $currentPageProductIds = $paginator->getCollection()
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();

        // Merge current page products with existing selection
        $mergedProductIds = array_unique(array_merge($this->selectedProductIds, $currentPageProductIds));

        // Load stock data for merged products (this preserves existing values and adds new ones)
        $this->loadStockOpeningData($mergedProductIds);
    }

    public function gotoPage($page, $pageName = 'page')
    {
        // Set page number explicitly before changing
        $this->page = (string) $page;
        $this->setPage($page, $pageName);

        // Load stock data for the new page while preserving user-entered values
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        // Get current page product IDs
        $this->productsPaginatorForView = null;
        $paginator = $this->resolvePaginatedProductsForStockOpening($salesDepartmentIds);
        $this->productsPaginatorForView = $paginator;

        $currentPageProductIds = $paginator->getCollection()
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();

        // Merge current page products with existing selection
        $mergedProductIds = array_unique(array_merge($this->selectedProductIds, $currentPageProductIds));

        // Load stock data for merged products (this preserves existing values and adds new ones)
        $this->loadStockOpeningData($mergedProductIds);
    }

    public function render()
    {
        if ($this->productsPaginatorForView === null) {
            $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
            $this->productsPaginatorForView = $this->resolvePaginatedProductsForStockOpening($salesDepartmentIds);
        }

        return view('livewire.branch-dashboard.sales-dashboard.stock-opening.index', [
            'productTypes' => $this->productTypes,
            'productLookupOptions' => $this->productLookupOptions,
            'rows' => $this->rows,
            'stockOpenings' => $this->stockOpenings,
            'productsPaginator' => $this->productsPaginatorForView,
        ]);
    }

    private function loadProductLookupOptions(): void
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        if (empty($salesDepartmentIds)) {
            $this->productLookupOptions = [];

            return;
        }

        $search = trim((string) $this->search);

        $products = Product::query()
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->when($this->filterProductType, function ($query) {
                $query->where('product_type_id', $this->filterProductType);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $this->productLookupOptions = $products->map(static function (Product $product): array {
            return [
                'id' => (string) $product->id,
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? '-'),
                'label' => trim((string) $product->name.' ('.(string) ($product->sku ?? '-').')'),
            ];
        })->all();
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     */
    private function loadProductTypes(array $salesDepartmentIds): void
    {
        if (empty($salesDepartmentIds)) {
            $this->productTypes = [];

            return;
        }

        $this->productTypes = ProductType::query()
            ->whereIn('id', Product::query()
                ->active()
                ->available()
                ->whereIn('sales_department_id', $salesDepartmentIds)
                ->select('product_type_id')
                ->distinct())
            ->active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(static fn (ProductType $type): array => [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
            ])
            ->all();
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     * @return array<int, string>
     */
    private function resolveAllProductIdsForStockOpening(array $salesDepartmentIds): array
    {
        if (empty($salesDepartmentIds)) {
            return [];
        }

        $search = trim((string) $this->search);

        return Product::query()
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->when($this->filterProductType, function ($query) {
                $query->where('product_type_id', $this->filterProductType);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     */
    private function resolvePaginatedProductsForStockOpening(array $salesDepartmentIds): LengthAwarePaginator
    {
        if (empty($salesDepartmentIds)) {
            return new LengthAwarePaginator(
                collect(),
                0,
                $this->productsPerPage,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => request()->url()]
            );
        }

        $search = trim((string) $this->search);

        return Product::query()
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->when($this->filterProductType, function ($query) {
                $query->where('product_type_id', $this->filterProductType);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(
                $this->productsPerPage,
                ['id', 'name', 'sku', 'uom_id', 'product_type_id', 'shelf_life_days'],
                $this->pageName
            );
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     * @return array<int, string>
     */
    private function resolveDefaultProductIdsForStockOpening(array $salesDepartmentIds, string $stockDate): array
    {
        $dispatchProductIds = $this->resolveDispatchProductIdsForDate($salesDepartmentIds, $stockDate);

        $stockQuery = ProductStock::query()
            ->where('stock_date', $stockDate)
            ->where('shift_type', $this->getProductStockShiftType())
            ->when($this->currentShiftId, fn ($q) => $q->where('shift_id', (int) $this->currentShiftId));

        if ($this->hasProductStocksDepartmentColumn()) {
            $stockQuery->whereIn('department_id', $salesDepartmentIds);
        }

        $stockProductIds = $stockQuery->whereHas('product', function ($query) use ($salesDepartmentIds) {
            $query->whereIn('sales_department_id', $salesDepartmentIds)
                ->where('is_active', true);
        })
            ->pluck('product_id')
            ->map(static fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($dispatchProductIds, $stockProductIds)));
    }

    /**
     * @param  array<int>  $salesDepartmentIds
     * @return array<int, string>
     */
    private function resolveDispatchProductIdsForDate(array $salesDepartmentIds, string $stockDate): array
    {
        if (empty($salesDepartmentIds)) {
            return [];
        }

        return ProductDispatch::query()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->whereIn('status', ['pending_verification', 'accepted', 'received'])
            ->where(function ($query) use ($stockDate) {
                $query->whereDate('dispatch_date', $stockDate)
                    ->orWhereDate('dispatch_time', $stockDate)
                    ->orWhereDate('received_at', $stockDate);
            })
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->map(static fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveEquivalentSalesDepartmentIds(): array
    {
        $signature = implode('|', [
            (string) ($this->salesDeptSlug ?? ''),
            (string) ($this->departmentId ?? ''),
            (string) ($this->getBranchId() ?? ''),
        ]);
        if ($this->cachedSalesDepartmentSignature === $signature && $this->cachedSalesDepartmentIds !== null) {
            return $this->cachedSalesDepartmentIds;
        }

        $branchId = $this->getBranchId();
        $department = null;

        // Prefer explicit slug resolution (branch-specific first, then global).
        if ($this->salesDeptSlug) {
            $department = Department::query()
                ->where('slug', $this->salesDeptSlug)
                ->where('branch_id', $branchId)
                ->first();

            if (! $department) {
                $department = Department::query()
                    ->where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }
        }

        // If no department found by slug, use departmentId from context
        if (! $department && $this->departmentId) {
            $department = Department::find($this->departmentId);
        }

        if ($department) {
            $this->cachedSalesDepartmentSignature = $signature;
            $this->cachedSalesDepartmentIds = [(int) $department->id];

            return $this->cachedSalesDepartmentIds;
        }

        $this->cachedSalesDepartmentSignature = $signature;
        $this->cachedSalesDepartmentIds = [];

        return $this->cachedSalesDepartmentIds;
    }

    /**
     * @param  array<int>  $departmentIds
     */
    private function resolvePrimarySalesDepartmentId(array $departmentIds): ?int
    {
        $departmentIds = array_values(array_unique(array_map(static fn ($id) => (int) $id, $departmentIds)));
        if (empty($departmentIds)) {
            return null;
        }

        if ($this->departmentId && in_array((int) $this->departmentId, $departmentIds, true)) {
            return (int) $this->departmentId;
        }

        return $departmentIds[0] ?? null;
    }

    private function getProductStockShiftType(): string
    {
        return ProductStock::normalizeShiftType($this->shiftType);
    }
}
