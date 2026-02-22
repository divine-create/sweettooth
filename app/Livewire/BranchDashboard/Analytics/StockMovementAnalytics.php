<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\StockMovement;
use App\Models\Stock;
use App\Models\Department;
use App\Services\Reports\AnalyticsSnapshotReportService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Url};
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class StockMovementAnalytics extends Component
{
    use WithPagination, Exportable;

    public $dateFrom;
    public $dateTo;
    public $movementType = '';
    public $selectedItem = null;
    public $searchTerm = '';
    public $itemSearch = '';
    public $filterShift = '';
    public $filterDepartment = '';
    #[Url(keep: true)]
    public $viewMode = 'table';

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $branchId = null;
    public ?string $generatedReportId = null;

    protected array $bulkActions = [
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected $listeners = ['refresh' => '$refresh'];

    protected $queryString = [
        'dateFrom',
        'dateTo',
        'movementType',
        'selectedItem',
        'b_id',
        'viewMode'
    ];

    public function mount()
    {
        $this->branchId = request()->get('b_id')
            ?? Auth::guard('web')->user()?->branch_id
            ?? current_branch_id();
        $this->b_id = $this->branchId;
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function hydrate()
    {
        $this->branchId = $this->branchId
            ?? $this->b_id
            ?? Auth::guard('web')->user()?->branch_id
            ?? current_branch_id();
        $this->b_id = $this->branchId;
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;

        return redirect()->route('branch-dashboard.analytics.stock-movement', [
            'b_id' => $this->branchId,
            'movementType' => $this->movementType,
            'viewMode' => $this->viewMode
        ]);
    }

    public function updated($property)
    {
        if (in_array($property, ['selectedItem', 'movementType', 'dateFrom', 'dateTo', 'filterShift', 'filterDepartment', 'searchTerm'])) {
            if (in_array($property, ['dateFrom', 'dateTo'])) {
                $this->validateDateRange();
            }
            $this->resetPage();
        }
    }

    private function validateDateRange()
    {
        $from = Carbon::parse($this->dateFrom);
        $to = Carbon::parse($this->dateTo);

        if ($from->greaterThan($to)) {
            // Swap dates
            $temp = $this->dateFrom;
            $this->dateFrom = $this->dateTo;
            $this->dateTo = $temp;

            session()->flash('warning', 'Date range was automatically corrected.');
        }

        // Warn if range > 1 year
        if ($from->diffInDays($to) > 365) {
            session()->flash('warning', 'Large date ranges may impact performance.');
        }
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->movementType = '';
        $this->selectedItem = null;
        $this->searchTerm = '';
        $this->itemSearch = '';
        $this->filterShift = '';
        $this->filterDepartment = '';
        $this->resetPage();

        session()->flash('success', 'Filters reset successfully.');
    }

    protected function getModelClass(): string
    {
        return StockMovement::class;
    }

    protected function getAllSelectableIds(): array
    {
        $branchId = $this->branchId;
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo   = Carbon::parse($this->dateTo)->endOfDay();

        $query = StockMovement::query()
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->orderBy('movement_date', 'desc');

        $this->applyFiltersToQuery($query);

        return $query->pluck('id')->toArray();
    }

    private function getAnalyticsSummary($branchId)
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $baseQuery = StockMovement::query()
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo]);

        $filteredQuery = clone $baseQuery;
        $this->applyFiltersToQuery($filteredQuery);

        // OPTIMIZED: Single query for period aggregations instead of 8+ cloned queries
        $periodData = $filteredQuery->clone()->selectRaw('
            SUM(CASE WHEN type IN ("in","return") THEN quantity ELSE 0 END) as stock_in,
            ABS(SUM(CASE WHEN type IN ("out","damaged","transfer") THEN quantity ELSE 0 END)) as stock_out,
            COUNT(CASE WHEN type = "transfer" THEN 1 END) as transfers,
            COUNT(*) as total_movements,
            COUNT(CASE WHEN type = "adjustment" THEN 1 END) as adjustments,
            ABS(SUM(CASE WHEN type = "damaged" THEN quantity ELSE 0 END)) as damaged
        ')->first();

        // OPTIMIZED: Single query for today aggregations
        $todayData = StockMovement::query()
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('movement_date', today())
            ->selectRaw('
                SUM(CASE WHEN type IN ("in","return") THEN quantity ELSE 0 END) as stock_in,
                ABS(SUM(CASE WHEN type IN ("out","damaged","transfer") THEN quantity ELSE 0 END)) as stock_out,
                COUNT(CASE WHEN type = "transfer" THEN 1 END) as transfers,
                COUNT(*) as total_movements
            ')->first();

        $daysDiff = $dateFrom->diffInDays($dateTo);
        $previousPeriodQuery = StockMovement::query()
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [
                $dateFrom->copy()->subDays($daysDiff + 1),
                $dateFrom->copy()->subDay()->endOfDay()
            ]);

        return [
            'today' => [
                'stock_in' => $todayData?->stock_in ?? 0,
                'stock_out' => $todayData?->stock_out ?? 0,
                'transfers' => $todayData?->transfers ?? 0,
                'total_movements' => $todayData?->total_movements ?? 0,
            ],
            'period' => [
                'stock_in' => $periodData?->stock_in ?? 0,
                'stock_out' => $periodData?->stock_out ?? 0,
                'transfers' => $periodData?->transfers ?? 0,
                'total_movements' => $periodData?->total_movements ?? 0,
                'adjustments' => $periodData?->adjustments ?? 0,
                'damaged' => $periodData?->damaged ?? 0,
            ],
            'previous_period' => [
                'total_movements' => $previousPeriodQuery->count(),
            ],
            'latest_movement' => $filteredQuery->latest('movement_date')->first()?->movement_date,
            'most_moved_item' => $this->getMostMovedItem($branchId),
            'most_active_user' => $this->getMostActiveUser($branchId),
            'peak_hours' => $this->getPeakActivityHours($branchId),
            'daily_breakdown' => $this->getDailyBreakdown($branchId),
        ];
    }

    private function applyFiltersToQuery($query)
    {
        $query->when($this->selectedItem, fn ($q) => $q->where('stock_id', $this->selectedItem))
            ->when($this->movementType, fn ($q) => $q->where('type', $this->movementType))
            ->when($this->searchTerm, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('stock.item', fn ($ssq) => $ssq->where('name', 'like', "%{$this->searchTerm}%")
                        ->orWhere('sku', 'like', "%{$this->searchTerm}%"))
                        ->orWhereHas('mover', fn ($ssq) => $ssq->where('name', 'like', "%{$this->searchTerm}%"))
                        ->orWhere('notes', 'like', "%{$this->searchTerm}%");
                });
            })
            // FIXED: Now uses denormalized shift/department fields instead of morphTo
            // This allows filtering across ALL movement types (ItemRequest, Purchase, Adjustment, etc)
            ->when($this->filterShift, fn ($q) => $q->where('shift', $this->filterShift))
            ->when($this->filterDepartment, fn ($q) => $q->where('department_id', $this->filterDepartment));
    }

    private function getDailyBreakdown($branchId)
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::query()
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(movement_date) as date')
            ->selectRaw('SUM(CASE WHEN type IN ("in", "return") THEN quantity ELSE 0 END) as stock_in')
            ->selectRaw('ABS(SUM(CASE WHEN type IN ("out", "transfer", "damaged") THEN quantity ELSE 0 END)) as stock_out')
            ->selectRaw('COUNT(*) as total_movements')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
    }

    private function getMostMovedItem($branchId)
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::query()
            ->select('stock_id', DB::raw('COUNT(*) as movement_count'))
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->groupBy('stock_id')
            ->orderByDesc('movement_count')
            ->with('stock.item')
            ->first();
    }

    private function getMostActiveUser($branchId)
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::query()
            ->select('moved_by_id', 'moved_by_type', DB::raw('COUNT(*) as operation_count'))
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('moved_by_id')
            ->whereNotNull('moved_by_type')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->groupBy('moved_by_id', 'moved_by_type')
            ->orderByDesc('operation_count')
            ->with('mover')
            ->first();
    }

    private function getPeakActivityHours($branchId)
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::query()
            ->select(DB::raw('HOUR(movement_date) as hour'), DB::raw('COUNT(*) as count'))
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(fn ($item) => [
                'hour' => $item->hour,
                'count' => $item->count,
                'formatted' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
            ]);
    }

    // FIXED: Activity Feed now shows latest 20 movements (ignores filters)
    private function getActivityFeed($branchId, $limit = 20)
    {
        return StockMovement::with(['stock.item', 'mover', 'reference'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('movement_date', 'desc')
            ->limit($limit)
            ->get()
            ->append('department_name');
    }

    public function getAvailableItems()
    {
        $branchId = $this->branchId;

        return Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->itemSearch, fn ($q) => $q->whereHas('item', fn ($sq) => $sq->where('name', 'like', "%{$this->itemSearch}%")->orWhere('sku', 'like', "%{$this->itemSearch}%")))
            ->limit(50)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->item->name,
                'sku' => $s->item->sku,
                'uom' => $s->item->unitOfMeasure?->symbol,
            ]);
    }


    // FIXED: CSV export — safe, no more crashes
    public function exportCsv()
    {
        $branchId = $this->branchId;
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo   = Carbon::parse($this->dateTo)->endOfDay();

        $movements = StockMovement::with(['stock.item', 'mover', 'reference'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->orderBy('movement_date', 'desc')
            ->get()
            ->append('department_name');

        // Apply filters manually
        $movements = $movements->filter(function ($m) {
            if ($this->selectedItem && $m->stock_id != $this->selectedItem) return false;
            if ($this->movementType && $m->type != $this->movementType) return false;
            if ($this->searchTerm) {
                $haystack = strtolower("{$m->stock->item->name} {$m->stock->item->sku} {$m->mover?->name} {$m->notes}");
                if (!str_contains($haystack, strtolower($this->searchTerm))) return false;
            }
            return true;
        });

        $csvData = [[
            'Date', 'Time', 'Item Name', 'SKU', 'Type', 'Quantity Change',
            'Qty Before', 'Qty After', 'UOM', 'Moved By', 'Department', 'Shift', 'Reference', 'Notes'
        ]];

        foreach ($movements as $m) {
            $ref = $m->reference;
            $isRequest = $ref instanceof \App\Models\ItemRequest;

            $csvData[] = [
                $m->movement_date->format('Y-m-d'),
                $m->movement_date->format('H:i:s'),
                $m->stock->item->name ?? 'N/A',
                $m->stock->item->sku ?? 'N/A',
                ucfirst($m->type),
                ($m->isInbound() ? '+' : '-') . number_format(abs($m->quantity), 2),
                number_format($m->quantity_before, 2),
                number_format($m->quantity_after, 2),
                $m->stock->item->uom ?? '',
                $m->mover?->name ?? 'System',
                $m->department_name ?? 'N/A',
                $isRequest ? ucfirst($ref->shift ?? '') : 'N/A',
                $ref ? ($ref->request_number ?? $ref->reference_number ?? "#{$m->reference_id}") : 'N/A',
                $m->notes ?? '',
            ];
        }

        $filename = 'stock-movements-' . now()->format('Y-m-d-His') . '.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) fputcsv($handle, $row);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(fn () => print $csv, $filename, ['Content-Type' => 'text/csv']);
    }

    public function generateReport(): void
    {
        $branchId = $this->branchId ?? current_branch_id();
        $this->validateDateRange();

        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        try {
            $analytics = $this->getAnalyticsSummary($branchId);
            $topMovedItems = $this->mapTopMovedItems($this->getTopMovedItems());
            $velocityAnalysis = $this->mapVelocityItems($this->getVelocityAnalysis());
            $dailyBreakdown = collect($analytics['daily_breakdown'] ?? [])
                ->map(function ($row) {
                    return [
                        'date' => Carbon::parse($row->date)->toDateString(),
                        'stock_in' => (float) ($row->stock_in ?? 0),
                        'stock_out' => (float) ($row->stock_out ?? 0),
                        'net_change' => (float) (($row->stock_in ?? 0) - ($row->stock_out ?? 0)),
                        'total_movements' => (int) ($row->total_movements ?? 0),
                    ];
                })
                ->values()
                ->toArray();
            $peakHours = collect($analytics['peak_hours'] ?? [])->values()->toArray();

            $mostMovedItem = $analytics['most_moved_item'];
            $mostActiveUser = $analytics['most_active_user'];

            $movementRows = $this->buildFilteredMovementsQuery($branchId, $dateFrom, $dateTo)
                ->limit(300)
                ->get()
                ->append('department_name')
                ->map(function ($movement) {
                    return [
                        'movement_date' => optional($movement->movement_date)->toDateTimeString(),
                        'type' => $movement->type,
                        'item_name' => $movement->stock->item->name ?? 'N/A',
                        'sku' => $movement->stock->item->sku ?? 'N/A',
                        'quantity' => (float) $movement->quantity,
                        'quantity_before' => (float) $movement->quantity_before,
                        'quantity_after' => (float) $movement->quantity_after,
                        'uom' => $movement->stock->item->uom ?? ($movement->stock->item->unitOfMeasure?->symbol),
                        'moved_by' => $movement->mover?->name ?? 'System',
                        'department' => $movement->department_name ?? null,
                        'shift' => $movement->shift,
                        'notes' => $movement->notes,
                    ];
                })
                ->values()
                ->toArray();

            $reportData = [
                'source_page' => 'analytics.stock-movement',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'movement_type' => $this->movementType,
                    'selected_item' => $this->selectedItem,
                    'search_term' => $this->searchTerm,
                    'shift' => $this->filterShift,
                    'department_filter' => $this->filterDepartment,
                    'view_mode' => $this->viewMode,
                ],
                'summary' => [
                    'today' => $analytics['today'] ?? [],
                    'period' => $analytics['period'] ?? [],
                    'previous_period' => $analytics['previous_period'] ?? [],
                    'latest_movement' => optional($analytics['latest_movement'] ?? null)->toDateTimeString(),
                    'most_moved_item' => [
                        'item_name' => $mostMovedItem?->stock?->item?->name,
                        'sku' => $mostMovedItem?->stock?->item?->sku,
                        'movement_count' => (int) ($mostMovedItem?->movement_count ?? 0),
                    ],
                    'most_active_user' => [
                        'name' => $mostActiveUser?->mover?->name,
                        'operation_count' => (int) ($mostActiveUser?->operation_count ?? 0),
                    ],
                ],
                'daily_breakdown' => $dailyBreakdown,
                'peak_hours' => $peakHours,
                'top_moved_items' => $topMovedItems,
                'velocity_analysis' => $velocityAnalysis,
                'table_rows' => $movementRows,
            ];

            $summaryMetrics = [
                'total_movements' => (int) ($analytics['period']['total_movements'] ?? 0),
                'stock_in' => (float) ($analytics['period']['stock_in'] ?? 0),
                'stock_out' => (float) ($analytics['period']['stock_out'] ?? 0),
                'transfers' => (int) ($analytics['period']['transfers'] ?? 0),
                'adjustments' => (int) ($analytics['period']['adjustments'] ?? 0),
                'damaged' => (float) ($analytics['period']['damaged'] ?? 0),
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'department_id' => $this->filterDepartment ?: null,
                'report_category' => 'inventory',
                'report_type' => 'stock_movement_analytics',
                'report_name' => 'Stock Movement Analytics Report',
                'period_from' => $dateFrom->toDateString(),
                'period_to' => $dateTo->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => $summaryMetrics,
                'charts_data' => [
                    'daily_breakdown' => $dailyBreakdown,
                    'peak_hours' => $peakHours,
                    'top_moved_items' => $topMovedItems,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Stock movement analytics report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate report. Please try again.');
        }
    }

    public function getTopMovedItems()
    {
        $branchId = $this->branchId;
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::with(['stock.item'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->selectedItem, fn ($q) => $q->where('stock_id', $this->selectedItem))
            ->when($this->movementType, fn ($q) => $q->where('type', $this->movementType))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->selectRaw('stock_id, COUNT(*) as movement_count, SUM(ABS(quantity)) as total_moved')
            ->groupBy('stock_id')
            ->orderByDesc('total_moved')
            ->limit(10)
            ->get();
    }

    public function getVelocityAnalysis()
    {
        $branchId = $this->branchId;
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return StockMovement::with(['stock.item'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->selectedItem, fn ($q) => $q->where('stock_id', $this->selectedItem))
            ->when($this->movementType, fn ($q) => $q->where('type', $this->movementType))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'transfer'])
            ->selectRaw('stock_id, COUNT(*) as movement_count, SUM(ABS(quantity)) as total_quantity')
            ->groupBy('stock_id')
            ->orderByDesc('movement_count')
            ->limit(10)
            ->get();
    }

    private function isAnyFilterActive()
    {
        return $this->movementType || $this->selectedItem || $this->searchTerm || $this->filterShift || $this->filterDepartment;
    }

    private function buildFilteredMovementsQuery(?string $branchId, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = StockMovement::with(['stock.item', 'mover', 'reference'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->orderBy('movement_date', 'desc');

        $this->applyFiltersToQuery($query);

        return $query;
    }

    private function mapTopMovedItems($items): array
    {
        return $items->map(function ($item) {
            return [
                'item_name' => $item->stock->item->name ?? 'N/A',
                'sku' => $item->stock->item->sku ?? 'N/A',
                'movement_count' => (int) ($item->movement_count ?? 0),
                'total_moved' => (float) ($item->total_moved ?? 0),
                'uom' => $item->stock->item->uom ?? ($item->stock->item->unitOfMeasure?->symbol),
            ];
        })->values()->toArray();
    }

    private function mapVelocityItems($items): array
    {
        return $items->map(function ($item) {
            return [
                'item_name' => $item->stock->item->name ?? 'N/A',
                'sku' => $item->stock->item->sku ?? 'N/A',
                'movement_count' => (int) ($item->movement_count ?? 0),
                'total_quantity' => (float) ($item->total_quantity ?? 0),
                'uom' => $item->stock->item->uom ?? ($item->stock->item->unitOfMeasure?->symbol),
            ];
        })->values()->toArray();
    }

    public function render()
    {
        $branchId = $this->branchId;
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo   = Carbon::parse($this->dateTo)->endOfDay();

        $movements = $this->buildFilteredMovementsQuery($branchId, $dateFrom, $dateTo)->paginate(15);
        $movements->through(fn ($m) => $m->append('department_name'));

        $activityFeed = $this->viewMode === 'feed'
            ? $this->getActivityFeed($branchId)
            : collect();

        return view('livewire.branch-dashboard.analytics.stock-movement-analytics', [
            'movements'         => $movements,
            'movementTypes'     => ['in', 'out', 'adjustment', 'transfer', 'damaged', 'return'],
            'availableItems'    => $this->getAvailableItems(),
            'analytics'         => $this->getAnalyticsSummary($branchId),
            'topMovedItems'     => $this->getTopMovedItems(),
            'velocityAnalysis'  => $this->getVelocityAnalysis(),
            'activityFeed'      => $activityFeed,
            'departments'       => Department::orderBy('name')->get(),
            'isAnyFilterActive' => $this->isAnyFilterActive(),
        ]);
    }
}
