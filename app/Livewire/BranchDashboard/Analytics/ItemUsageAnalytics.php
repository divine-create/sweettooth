<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\Department;
use App\Models\ItemDispatch;
use App\Models\StockMovement;
use App\Services\Reports\AnalyticsSnapshotReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class ItemUsageAnalytics extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $dateFrom;

    public string $dateTo;

    public string $categoryFilter = '';

    public string $departmentFilter = '';

    public string $usageType = 'all';

    public string $searchTerm = '';

    public ?string $generatedReportId = null;

    public array $summaryData = [];

    public array $usageBySource = [];

    public array $usageByCategory = [];

    public array $topConsumedItems = [];

    public array $dailyUsage = [];

    public array $dispatchBreakdown = [];

    protected $queryString = [
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'categoryFilter',
        'departmentFilter',
        'usageType',
        'searchTerm',
        'b_id',
    ];

    public function mount()
    {
        $this->branchId = request()->get('b_id')
            ?? Auth::guard('web')->user()?->branch_id
            ?? current_branch_id();
        $this->b_id = $this->branchId;
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->loadData();
    }

    public function getBranchIdProperty()
    {
        return $this->b_id ?? current_branch_id();
    }

    public function getCategoriesProperty()
    {
        return \App\Models\ItemCategory::active()
            ->forBranch($this->branchId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getDepartmentsProperty()
    {
        return Department::where('branch_id', $this->branchId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updated($property)
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'categoryFilter', 'departmentFilter', 'usageType', 'searchTerm'])) {
            $this->loadData();
        }
    }

    public function loadData()
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $this->summaryData = $this->getUsageSummary($dateFrom, $dateTo);
        $this->usageBySource = $this->getUsageBySource($dateFrom, $dateTo);
        $this->usageByCategory = $this->getUsageByCategory($dateFrom, $dateTo);
        $this->topConsumedItems = $this->getTopConsumedItems($dateFrom, $dateTo);
        $this->dailyUsage = $this->getDailyUsage($dateFrom, $dateTo);
        $this->dispatchBreakdown = $this->getDispatchBreakdown($dateFrom, $dateTo);
    }

    private function getUsageSummary($dateFrom, $dateTo): array
    {
        $stockMovements = StockMovement::where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $dispatches = ItemDispatch::where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $productionUsage = $stockMovements->where('type', 'out')->sum('quantity');
        $adjustmentUsage = $stockMovements->where('type', 'adjustment')->sum('quantity');
        $damagedUsage = $stockMovements->where('type', 'damaged')->sum('quantity');
        $dispatchUsage = $dispatches->sum('quantity');
        $totalUsed = $productionUsage + $dispatchUsage;

        $days = max($dateFrom->diffInDays($dateTo), 1);
        $avgDaily = $totalUsed / $days;

        return [
            'total_quantity_used' => $totalUsed,
            'production_usage' => $productionUsage,
            'dispatch_usage' => $dispatchUsage,
            'adjustment_usage' => $adjustmentUsage,
            'damaged_usage' => $damagedUsage,
            'unique_items' => $stockMovements->pluck('stock.item_id')
                ->merge($dispatches->pluck('item_id'))
                ->filter()
                ->unique()
                ->count(),
            'average_daily_usage' => $avgDaily,
            'dispatches_count' => $dispatches->count(),
            'movements_count' => $stockMovements->count(),
        ];
    }

    private function getUsageBySource($dateFrom, $dateTo): array
    {
        $stockMovements = StockMovement::where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $dispatches = ItemDispatch::where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $sources = [];
        $total = $stockMovements->sum('quantity') + $dispatches->sum('quantity');

        if ($stockMovements->where('type', 'out')->sum('quantity') > 0) {
            $qty = $stockMovements->where('type', 'out')->sum('quantity');
            $sources[] = [
                'source' => 'Production',
                'count' => $stockMovements->where('type', 'out')->count(),
                'quantity' => $qty,
                'percentage' => $total > 0 ? round(($qty / $total) * 100, 1) : 0,
            ];
        }

        if ($dispatches->sum('quantity') > 0) {
            $qty = $dispatches->sum('quantity');
            $sources[] = [
                'source' => 'Dispatch',
                'count' => $dispatches->count(),
                'quantity' => $qty,
                'percentage' => $total > 0 ? round(($qty / $total) * 100, 1) : 0,
            ];
        }

        if ($stockMovements->where('type', 'adjustment')->sum('quantity') > 0) {
            $qty = $stockMovements->where('type', 'adjustment')->sum('quantity');
            $sources[] = [
                'source' => 'Adjustment',
                'count' => $stockMovements->where('type', 'adjustment')->count(),
                'quantity' => $qty,
                'percentage' => $total > 0 ? round(($qty / $total) * 100, 1) : 0,
            ];
        }

        if ($stockMovements->where('type', 'damaged')->sum('quantity') > 0) {
            $qty = $stockMovements->where('type', 'damaged')->sum('quantity');
            $sources[] = [
                'source' => 'Damaged',
                'count' => $stockMovements->where('type', 'damaged')->count(),
                'quantity' => $qty,
                'percentage' => $total > 0 ? round(($qty / $total) * 100, 1) : 0,
            ];
        }

        return $sources;
    }

    private function getUsageByCategory($dateFrom, $dateTo): array
    {
        $stockMovements = StockMovement::with(['stock.item.category'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $dispatches = ItemDispatch::with(['item.category'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->get();

        $usage = collect();

        foreach ($stockMovements as $m) {
            $cat = $m->stock?->item?->category?->name ?? 'Uncategorized';
            if ($this->categoryFilter && $m->stock?->item?->category_id != $this->categoryFilter) {
                continue;
            }
            $usage[$cat] = ($usage[$cat] ?? 0) + $m->quantity;
        }

        foreach ($dispatches as $d) {
            $cat = $d->item?->category?->name ?? 'Uncategorized';
            if ($this->categoryFilter && $d->item?->category_id != $this->categoryFilter) {
                continue;
            }
            $usage[$cat] = ($usage[$cat] ?? 0) + $d->quantity;
        }

        return $usage->map(fn ($qty, $cat) => [
            'category' => $cat,
            'quantity' => $qty,
            'percentage' => $usage->sum() > 0 ? round(($qty / $usage->sum()) * 100, 1) : 0,
        ])->sortByDesc('quantity')->values()->toArray();
    }

    private function getTopConsumedItems($dateFrom, $dateTo): array
    {
        $stockMovements = StockMovement::with(['stock.item'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->searchTerm, fn ($q) => $q->whereHas('stock.item', fn ($iq) => $iq->where('name', 'like', "%{$this->searchTerm}%")->orWhere('sku', 'like', "%{$this->searchTerm}%")))
            ->get();

        $dispatches = ItemDispatch::with(['item'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->searchTerm, fn ($q) => $q->whereHas('item', fn ($iq) => $iq->where('name', 'like', "%{$this->searchTerm}%")->orWhere('sku', 'like', "%{$this->searchTerm}%")))
            ->get();

        $items = collect();

        foreach ($stockMovements as $m) {
            $itemId = $m->stock?->item_id;
            if (! $itemId) {
                continue;
            }

            $current = $items->get($itemId, [
                'item_name' => $m->stock?->item?->name ?? 'Unknown',
                'sku' => $m->stock?->item?->sku ?? 'N/A',
                'category' => $m->stock?->item?->category?->name ?? 'Uncategorized',
                'total_quantity' => 0,
                'movement_count' => 0,
            ]);

            $current['total_quantity'] += $m->quantity;
            $current['movement_count']++;
            $items[$itemId] = $current;
        }

        foreach ($dispatches as $d) {
            $itemId = $d->item_id;
            if (! $itemId) {
                continue;
            }

            $current = $items->get($itemId, [
                'item_name' => $d->item?->name ?? 'Unknown',
                'sku' => $d->item?->sku ?? 'N/A',
                'category' => $d->item?->category?->name ?? 'Uncategorized',
                'total_quantity' => 0,
                'movement_count' => 0,
            ]);

            $current['total_quantity'] += $d->quantity;
            $current['movement_count']++;
            $items[$itemId] = $current;
        }

        return $items->map(function ($item) {
            $item['avg_quantity'] = $item['movement_count'] > 0 ? $item['total_quantity'] / $item['movement_count'] : 0;

            return $item;
        })->sortByDesc('total_quantity')->take(20)->values()->toArray();
    }

    private function getDailyUsage($dateFrom, $dateTo): array
    {
        $stockMovements = StockMovement::where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->selectRaw('DATE(movement_date) as date, SUM(quantity) as total')
            ->groupBy('date')
            ->get();

        $dispatches = ItemDispatch::where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->selectRaw('DATE(dispatch_time) as date, SUM(quantity) as total')
            ->groupBy('date')
            ->get();

        $daily = collect();

        foreach ($stockMovements as $m) {
            $date = Carbon::parse($m->date)->format('M d');
            $daily[$date] = ($daily[$date] ?? 0) + $m->total;
        }

        foreach ($dispatches as $d) {
            $date = Carbon::parse($d->date)->format('M d');
            $daily[$date] = ($daily[$date] ?? 0) + $d->total;
        }

        return $daily->map(fn ($qty, $date) => [
            'date' => $date,
            'quantity' => round($qty, 2),
        ])->values()->toArray();
    }

    private function getDispatchBreakdown($dateFrom, $dateTo): array
    {
        $dispatches = ItemDispatch::with('itemRequest.department')
            ->where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn ($q) => $q->where('request_id', $this->departmentFilter))
            ->get();

        $total = $dispatches->sum('quantity');

        return $dispatches->groupBy(fn ($d) => $d->itemRequest?->department?->name ?? 'Unknown')
            ->map(function ($group, $dept) use ($total) {
                return [
                    'department' => $dept,
                    'count' => $group->count(),
                    'quantity' => $group->sum('quantity'),
                    'percentage' => $total > 0 ? round(($group->sum('quantity') / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->toArray();
    }

    public function generateReport(): void
    {
        $branchId = $this->branchId ?? current_branch_id();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        try {
            $reportData = [
                'source_page' => 'analytics.item-usage',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'category' => $this->categoryFilter,
                    'department' => $this->departmentFilter,
                    'usage_type' => $this->usageType,
                    'search_term' => $this->searchTerm,
                ],
                'usage_summary' => $this->summaryData,
                'usage_by_source' => $this->usageBySource,
                'usage_by_category' => $this->usageByCategory,
                'top_consumed_items' => $this->topConsumedItems,
                'daily_usage' => $this->dailyUsage,
                'dispatch_breakdown' => $this->dispatchBreakdown,
            ];

            $summaryMetrics = [
                'total_quantity_used' => $this->summaryData['total_quantity_used'] ?? 0,
                'production_usage' => $this->summaryData['production_usage'] ?? 0,
                'dispatch_usage' => $this->summaryData['dispatch_usage'] ?? 0,
                'unique_items' => $this->summaryData['unique_items'] ?? 0,
                'average_daily_usage' => $this->summaryData['average_daily_usage'] ?? 0,
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'department_id' => $this->departmentFilter ?: null,
                'report_category' => 'inventory',
                'report_type' => 'item_usage_analytics',
                'report_name' => 'Item Usage Analytics Report',
                'period_from' => $dateFrom->toDateString(),
                'period_to' => $dateTo->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => $summaryMetrics,
                'charts_data' => [
                    'usage_by_source' => $this->usageBySource,
                    'daily_usage' => $this->dailyUsage,
                    'usage_by_category' => array_slice($this->usageByCategory, 0, 10),
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Item usage analytics report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to generate report. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.analytics.item-usage', [
            'categories' => $this->categories,
            'departments' => $this->departments,
        ]);
    }
}
