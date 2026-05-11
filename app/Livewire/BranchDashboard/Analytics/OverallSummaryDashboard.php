<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\Item;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestDetail;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\Reports\AnalyticsSnapshotReportService;
use App\Traits\Exportable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class OverallSummaryDashboard extends Component
{
    use Exportable, Interactions;

    public $dateFrom;

    public $dateTo;

    public $departmentFilter = null;

    public $categoryFilter = null;

    public $autoRefresh = false;

    public ?string $generatedReportId = null;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
    }

    // Previous period metrics for comparison
    public $previousStockValue = 0;

    public $stockValueChange = 0;

    public $stockValueChangePercentage = 0;

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedDateFrom()
    {
        $this->dispatch('refresh-analytics');
    }

    public function updatedDateTo()
    {
        $this->dispatch('refresh-analytics');
    }

    public function refresh()
    {
        $this->dispatch('refresh-analytics');
        $this->toast()->success('Analytics refreshed successfully!')->send();
    }

    public function getOverallSummary()
    {
        $branchId = $this->b_id;
        [$dateFrom, $dateTo] = $this->getDateRange();

        // Ensure database connection uses UTF-8
        DB::statement('SET NAMES utf8mb4');
        DB::statement('SET CHARACTER SET utf8mb4');

        $totalValue = (float) Stock::where('branch_id', $branchId)
            ->sum(DB::raw('quantity_available * average_cost'));

        $totalItems = Stock::where('branch_id', $branchId)->count();

        $lowStockItems = Stock::query()
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->where('stocks.branch_id', $branchId)
            ->whereRaw('stocks.quantity_available < COALESCE(items.reorder_level, 100)')
            ->count();

        $criticalItems = Stock::where('branch_id', $branchId)
            ->where('health_status', 'critical')
            ->count();

        $expiredItems = Stock::where('branch_id', $branchId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->count();

        // Calculate previous period for comparison
        $periodDays = $dateTo->diffInDays($dateFrom);
        $previousDateFrom = $dateFrom->copy()->subDays($periodDays);
        $previousDateTo = $dateFrom->copy()->subDay();

        // Approximate previous stock value
        $previousMovementAgg = StockMovement::whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$previousDateFrom, $previousDateTo])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) as total_in,
                COALESCE(SUM(CASE WHEN type = 'out' THEN ABS(quantity) ELSE 0 END), 0) as total_out
            ")
            ->first();

        $valueDifference = ((float) ($previousMovementAgg->total_in ?? 0))
            - ((float) ($previousMovementAgg->total_out ?? 0));

        $this->previousStockValue = max(0, $totalValue - ($valueDifference * 10)); // Simplified estimation
        $this->stockValueChange = $totalValue - $this->previousStockValue;
        $this->stockValueChangePercentage = $this->previousStockValue > 0
            ? round(($this->stockValueChange / $this->previousStockValue) * 100, 2)
            : 0;

        $purchaseAgg = Purchase::where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('COUNT(*) as total_purchases, COALESCE(SUM(landing_cost), 0) as total_purchase_value')
            ->first();

        $movementAgg = StockMovement::whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->selectRaw("
                COUNT(*) as total_movements,
                COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) as stock_in,
                COALESCE(SUM(CASE WHEN type = 'out' THEN ABS(quantity) ELSE 0 END), 0) as stock_out
            ")
            ->first();

        $requestAgg = MaterialRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw("
                COUNT(*) as total_requests,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_requests,
                COALESCE(SUM(CASE WHEN status IN ('approved','completed') THEN 1 ELSE 0 END), 0) as completed_requests
            ")
            ->first();

        return [
            'total_stock_value' => $totalValue,
            'total_items' => $totalItems,
            'low_stock_items' => $lowStockItems,
            'critical_items' => $criticalItems,
            'expired_items' => $expiredItems,
            'total_purchases' => (int) ($purchaseAgg->total_purchases ?? 0),
            'total_purchase_value' => (float) ($purchaseAgg->total_purchase_value ?? 0),
            'total_movements' => (int) ($movementAgg->total_movements ?? 0),
            'stock_in' => (float) ($movementAgg->stock_in ?? 0),
            'stock_out' => (float) ($movementAgg->stock_out ?? 0),
            'total_requests' => (int) ($requestAgg->total_requests ?? 0),
            'pending_requests' => (int) ($requestAgg->pending_requests ?? 0),
            'completed_requests' => (int) ($requestAgg->completed_requests ?? 0),
            'previous_stock_value' => $this->previousStockValue,
            'stock_value_change' => $this->stockValueChange,
            'stock_value_change_percentage' => $this->stockValueChangePercentage,
        ];
    }

    public function getStockHealthOverview()
    {
        $branchId = $this->b_id ?? request()->get('b_id');

        $distribution = Stock::where('branch_id', $branchId)
            ->selectRaw('health_status, COUNT(*) as count')
            ->groupBy('health_status')
            ->get();

        return [
            'labels' => $distribution->pluck('health_status')->map(fn ($s) => ucfirst($s))->toArray(),
            'series' => $distribution->pluck('count')->toArray(),
        ];
    }

    public function getRecentActivity()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        [$dateFrom, $dateTo] = $this->getDateRange();

        return StockMovement::with(['stock.item', 'mover'])
            ->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->latest('movement_date')
            ->limit(10)
            ->get();
    }

    public function getTopAlerts()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        $today = now()->startOfDay();
        $expiringSoon = $today->copy()->addDays(7)->endOfDay();

        $stocks = Stock::query()
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->where('stocks.branch_id', $branchId)
            ->where(function ($query) {
                $query->where('stocks.health_status', 'critical')
                    ->orWhereNotNull('stocks.expiry_date')
                    ->orWhereRaw('stocks.quantity_available < COALESCE(items.reorder_level, 100)');
            })
            ->select(
                'stocks.*',
                'items.name as item_name',
                'items.reorder_level as item_reorder_level'
            )
            ->limit(200)
            ->get();

        $alerts = [];

        foreach ($stocks as $stock) {
            $itemName = $stock->item_name ?? 'Item';
            $reorderLevel = $stock->item_reorder_level ?? 100;

            if ($stock->expiry_date && $stock->expiry_date->isPast()) {
                $alerts[] = [
                    'priority' => 1,
                    'type' => 'expired',
                    'icon' => '[EXPIRED]',
                    'message' => "{$itemName} has expired",
                    'item' => $itemName,
                    'item_id' => $stock->item_id,
                    'action' => 'Remove',
                ];

                continue;
            }

            if ($stock->health_status === 'critical') {
                $alerts[] = [
                    'priority' => 1,
                    'type' => 'critical',
                    'icon' => '[CRITICAL]',
                    'message' => "{$itemName} in critical condition",
                    'item' => $itemName,
                    'item_id' => $stock->item_id,
                    'action' => 'View Item',
                ];

                continue;
            }

            if (($stock->quantity_available ?? 0) < $reorderLevel) {
                $alerts[] = [
                    'priority' => 2,
                    'type' => 'warning',
                    'icon' => '[WARNING]',
                    'message' => "{$itemName} below reorder level",
                    'item' => $itemName,
                    'item_id' => $stock->item_id,
                    'action' => 'Restock',
                ];

                continue;
            }

            if ($stock->expiry_date && $stock->expiry_date->isFuture()) {
                $daysUntilExpiry = $today->diffInDays($stock->expiry_date);
                if ($daysUntilExpiry <= 7) {
                    $alerts[] = [
                        'priority' => 2,
                        'type' => 'expiring',
                        'icon' => '[EXPIRING]',
                        'message' => "{$itemName} expiring in {$daysUntilExpiry} days",
                        'item' => $itemName,
                        'item_id' => $stock->item_id,
                        'action' => 'Use Soon',
                    ];
                }
            }
        }

        return collect($alerts)->sortBy('priority')->take(15);
    }

    public function getInsights()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        [$dateFrom, $dateTo] = $this->getDateRange();

        $summary = $this->getOverallSummary();
        $insights = [];

        // Insight 1: Stock value change
        if ($summary['stock_value_change_percentage'] != 0) {
            $direction = $summary['stock_value_change_percentage'] > 0 ? 'increased' : 'decreased';
            $insights[] = [
                'type' => $summary['stock_value_change_percentage'] > 0 ? 'positive' : 'negative',
                'icon' => $summary['stock_value_change_percentage'] > 0 ? '[UP]' : '[DOWN]',
                'message' => "Inventory value {$direction} by ".abs($summary['stock_value_change_percentage']).'% since last period.',
            ];
        }

        // Insight 2: Top depleting items
        $topDepletingItems = StockMovement::whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->select('stock_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->groupBy('stock_id')
            ->orderByDesc('total_out')
            ->limit(3)
            ->with('stock.item')
            ->get();

        if ($topDepletingItems->isNotEmpty()) {
            $itemNames = $topDepletingItems->pluck('stock.item.name')->filter()->take(3)->implode(', ');
            if ($itemNames) {
                $insights[] = [
                    'type' => 'info',
                    'icon' => '[ITEMS]',
                    'message' => "Top 3 items with highest usage: {$itemNames}",
                ];
            }
        }

        // Insight 3: Purchase activity
        if ($summary['total_purchases'] == 0) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '[WARNING]',
                'message' => 'No purchases recorded in the selected period.',
            ];
        }

        // Insight 4: Low stock alert
        if ($summary['low_stock_items'] > 0) {
            $insights[] = [
                'type' => 'critical',
                'icon' => '[CRITICAL]',
                'message' => "{$summary['low_stock_items']} item(s) below reorder level - restocking recommended.",
            ];
        }

        // Insight 5: Expired items
        if ($summary['expired_items'] > 0) {
            $insights[] = [
                'type' => 'critical',
                'icon' => '[EXPIRED]',
                'message' => "{$summary['expired_items']} item(s) have expired and should be removed.",
            ];
        }

        // Insight 6: Average daily consumption
        $days = max(1, $dateTo->diffInDays($dateFrom));
        $avgDailyConsumption = round($summary['stock_out'] / $days, 2);
        if ($avgDailyConsumption > 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => '[STATS]',
                'message' => "Average daily consumption: {$avgDailyConsumption} units.",
            ];
        }

        return collect($insights);
    }

    public function getStockHealthTable()
    {
        $branchId = $this->b_id ?? request()->get('b_id');

        $latestMovements = StockMovement::select('stock_id', DB::raw('MAX(movement_date) as last_movement'))
            ->groupBy('stock_id');

        return Stock::where('branch_id', $branchId)
            ->with('item')
            ->leftJoinSub($latestMovements, 'latest_movements', function ($join) {
                $join->on('stocks.id', '=', 'latest_movements.stock_id');
            })
            ->select('stocks.*', 'latest_movements.last_movement')
            ->get()
            ->map(function ($stock) {
                $item = $stock->item;
                if (! $item) {
                    return null;
                }

                $reorderLevel = $item->reorder_level ?? 100;
                $healthPercentage = $reorderLevel > 0
                    ? min(100, round(($stock->quantity_available / $reorderLevel) * 100, 0))
                    : 100;

                $status = 'good';
                $statusIcon = '🟢';
                $statusColor = 'green';

                if ($healthPercentage < 30 || $stock->health_status === 'critical') {
                    $status = 'critical';
                    $statusIcon = '🔴';
                    $statusColor = 'red';
                } elseif ($healthPercentage < 60) {
                    $status = 'low';
                    $statusIcon = '🟡';
                    $statusColor = 'yellow';
                } elseif ($healthPercentage < 80) {
                    $status = 'moderate';
                    $statusIcon = '🟠';
                    $statusColor = 'orange';
                }

                return [
                    'id' => $stock->id,
                    'item_name' => $item->name,
                    'stock_level' => $stock->quantity_available,
                    'reorder_level' => $reorderLevel,
                    'status' => $status,
                    'status_icon' => $statusIcon,
                    'status_color' => $statusColor,
                    'health_percentage' => $healthPercentage,
                    'last_movement' => $stock->last_movement ? Carbon::parse($stock->last_movement)->format('d M Y') : 'N/A',
                    'uom' => $item->uom ?? 'units',
                ];
            })
            ->filter()
            ->sortBy('health_percentage')
            ->take(15)
            ->values();
    }

    public function getDepartmentBreakdown()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        [$dateFrom, $dateTo] = $this->getDateRange();

        $stocks = Stock::where('branch_id', $branchId)
            ->with('item')
            ->get()
            ->filter(fn ($stock) => $stock->item);

        if ($stocks->isEmpty()) {
            return collect();
        }

        $stockIds = $stocks->pluck('id')->values();
        $itemIds = $stocks->pluck('item_id')->values();

        $movementByStock = StockMovement::whereIn('stock_id', $stockIds)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->get()
            ->groupBy('stock_id');

        $requestDetails = MaterialRequestDetail::whereIn('item_id', $itemIds)
            ->whereHas('request', function ($query) use ($branchId, $dateFrom, $dateTo) {
                $query->where('branch_id', $branchId)
                    ->whereBetween('request_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
            })
            ->get()
            ->groupBy('item_id');

        return $stocks
            ->groupBy(function ($stock) {
                return $stock->item?->category?->name ?? 'Uncategorized';
            })
            ->map(function ($stocks, $category) use ($movementByStock, $requestDetails) {
                $stockValue = $stocks->sum(function ($stock) {
                    return ($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0);
                });

                $stockIn = 0;
                $stockOut = 0;

                foreach ($stocks as $stock) {
                    $movements = $movementByStock->get($stock->id, collect());
                    if ($movements->isEmpty()) {
                        continue;
                    }

                    $stockIn += $movements->where('type', 'in')->sum('quantity');
                    $stockOut += abs($movements->where('type', 'out')->sum('quantity'));
                }

                $lowItems = $stocks->filter(fn ($s) => $s->isBelowReorderLevel())->count();

                $categoryItemIds = $stocks->pluck('item_id')->unique()->values();
                $requests = $categoryItemIds
                    ->flatMap(fn ($itemId) => $requestDetails->get($itemId, collect()))
                    ->pluck('request_id')
                    ->unique()
                    ->count();

                return [
                    'category' => ucfirst($category),
                    'stock_value' => $stockValue,
                    'stock_in' => $stockIn,
                    'stock_out' => $stockOut,
                    'low_items' => $lowItems,
                    'requests' => $requests,
                    'item_count' => $stocks->count(),
                ];
            })
            ->sortByDesc('stock_value')
            ->take(10)
            ->values();
    }

    public function getPerformanceMetrics()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        [$dateFrom, $dateTo] = $this->getDateRange();

        $summary = $this->getOverallSummary();
        $days = max(1, $dateTo->diffInDays($dateFrom));

        // Fastest moving item
        $fastestMoving = StockMovement::whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->select('stock_id', DB::raw('SUM(ABS(quantity)) as total'))
            ->groupBy('stock_id')
            ->orderByDesc('total')
            ->with('stock.item')
            ->first();

        // Slowest moving item
        $slowestMoving = StockMovement::whereHas('stock', fn ($q) => $q->where('branch_id', $branchId))
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->select('stock_id', DB::raw('SUM(ABS(quantity)) as total'))
            ->groupBy('stock_id')
            ->orderBy('total')
            ->with('stock.item')
            ->first();

        // Most requested item
        $mostRequested = MaterialRequestDetail::whereHas('request', function ($query) use ($branchId, $dateFrom, $dateTo) {
            $query->where('branch_id', $branchId)
                ->whereBetween('request_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
        })
            ->select('item_id', DB::raw('SUM(quantity_requested) as total_requested'))
            ->groupBy('item_id')
            ->orderByDesc('total_requested')
            ->with('item')
            ->first();

        return [
            'average_turnover_rate' => round($summary['stock_out'] / $days, 2),
            'fastest_moving' => $fastestMoving ? [
                'name' => $fastestMoving->stock->item->name ?? 'N/A',
                'quantity' => abs($fastestMoving->total),
            ] : null,
            'slowest_moving' => $slowestMoving ? [
                'name' => $slowestMoving->stock->item->name ?? 'N/A',
                'quantity' => abs($slowestMoving->total),
            ] : null,
            'most_requested' => $mostRequested ? [
                'name' => $mostRequested->item->name ?? 'N/A',
                'quantity' => (float) $mostRequested->total_requested,
            ] : null,
            'expired_vs_active_percentage' => $summary['total_items'] > 0
                ? round(($summary['expired_items'] / $summary['total_items']) * 100, 2)
                : 0,
        ];
    }

    public function exportCSV()
    {
        $data = $this->prepareExportData();

        $csvData = [];

        // Header row
        $csvData[] = ['Inventory Analytics Report'];
        $csvData[] = ['Period', $this->dateFrom.' to '.$this->dateTo];
        $csvData[] = ['Generated', now()->format('Y-m-d H:i:s')];
        $csvData[] = [];

        // Summary metrics
        $csvData[] = ['SUMMARY METRICS'];
        $csvData[] = ['Metric', 'Value'];
        $summary = $data['summary'];
        $csvData[] = ['Total Stock Value', number_format($summary['total_stock_value'] ?? 0, 2)];
        $csvData[] = ['Total Items', $summary['total_items'] ?? 0];
        $csvData[] = ['Total Purchases', $summary['total_purchases'] ?? 0];
        $csvData[] = ['Purchase Value', number_format($summary['total_purchase_value'] ?? 0, 2)];
        $csvData[] = ['Stock In', number_format($summary['stock_in'] ?? 0, 2)];
        $csvData[] = ['Stock Out', number_format($summary['stock_out'] ?? 0, 2)];
        $csvData[] = ['Total Movements', $summary['total_movements'] ?? 0];
        $csvData[] = ['Low Stock Items', $summary['low_stock_items'] ?? 0];
        $csvData[] = ['Critical Items', $summary['critical_items'] ?? 0];
        $csvData[] = ['Expired Items', $summary['expired_items'] ?? 0];
        $csvData[] = ['Pending Requests', $summary['pending_requests'] ?? 0];
        $csvData[] = ['Completed Requests', $summary['completed_requests'] ?? 0];
        $csvData[] = [];

        // Stock Health
        $csvData[] = ['STOCK HEALTH STATUS'];
        $csvData[] = ['Item Name', 'Stock Level', 'Reorder Level', 'Health %', 'Status', 'Last Movement', 'UOM'];
        foreach ($data['stock_health'] ?? [] as $stock) {
            $csvData[] = [
                $stock['item_name'] ?? 'N/A',
                $stock['stock_level'] ?? 0,
                $stock['reorder_level'] ?? 0,
                ($stock['health_percentage'] ?? 0).'%',
                ucfirst($stock['status'] ?? 'good'),
                $stock['last_movement'] ?? 'N/A',
                $stock['uom'] ?? 'units',
            ];
        }
        $csvData[] = [];

        // Category Breakdown
        $csvData[] = ['CATEGORY BREAKDOWN'];
        $csvData[] = ['Category', 'Stock Value', 'Items', 'Stock In', 'Stock Out', 'Low Items', 'Requests'];
        foreach ($data['department_breakdown'] ?? [] as $dept) {
            $csvData[] = [
                $dept['category'] ?? 'N/A',
                number_format($dept['stock_value'] ?? 0, 2),
                $dept['item_count'] ?? 0,
                number_format($dept['stock_in'] ?? 0, 2),
                number_format($dept['stock_out'] ?? 0, 2),
                $dept['low_items'] ?? 0,
                $dept['requests'] ?? 0,
            ];
        }

        $filename = 'inventory-analytics-'.now()->format('Y-m-d-His').'.csv';
        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function generateReport(): void
    {
        $branchId = $this->b_id ?? request()->get('b_id') ?? current_branch_id();
        if (! $branchId) {
            $this->toast()->warning('Branch context is required to generate report.')->send();

            return;
        }

        try {
            $payload = $this->prepareExportData();
            $summary = $payload['summary'] ?? [];

            $reportData = [
                'source_page' => 'analytics.overall-summary',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $this->dateFrom,
                    'date_to' => $this->dateTo,
                    'department_filter' => $this->departmentFilter,
                    'category_filter' => $this->categoryFilter,
                ],
                'summary' => $summary,
                'health_overview' => $payload['health_overview'] ?? [],
                'insights' => $payload['insights'] ?? [],
                'stock_health' => $payload['stock_health'] ?? [],
                'department_breakdown' => $payload['department_breakdown'] ?? [],
                'recent_activity' => $payload['recent_activity'] ?? [],
                'top_alerts' => $payload['top_alerts'] ?? [],
                'performance_metrics' => $payload['performance_metrics'] ?? [],
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'report_category' => 'inventory',
                'report_type' => 'overall_summary_analytics',
                'report_name' => 'Overall Analytics Summary Report',
                'period_from' => Carbon::parse($this->dateFrom)->toDateString(),
                'period_to' => Carbon::parse($this->dateTo)->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_stock_value' => (float) ($summary['total_stock_value'] ?? 0),
                    'total_items' => (int) ($summary['total_items'] ?? 0),
                    'total_purchases' => (int) ($summary['total_purchases'] ?? 0),
                    'total_movements' => (int) ($summary['total_movements'] ?? 0),
                    'total_requests' => (int) ($summary['total_requests'] ?? 0),
                    'low_stock_items' => (int) ($summary['low_stock_items'] ?? 0),
                    'critical_items' => (int) ($summary['critical_items'] ?? 0),
                    'expired_items' => (int) ($summary['expired_items'] ?? 0),
                ],
                'charts_data' => [
                    'health_overview' => $payload['health_overview'] ?? [],
                    'department_breakdown' => $payload['department_breakdown'] ?? [],
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Overall analytics report generated and submitted for review.');
            $this->toast()->success('Report generated successfully.')->send();
        } catch (\Throwable $e) {
            report($e);
            $this->toast()->error('Failed to generate report.')->send();
        }
    }

    protected function prepareExportData(): array
    {
        // Get all data
        $summary = $this->getOverallSummary();
        $stockHealth = $this->getStockHealthTable()->toArray();
        $deptBreakdown = $this->getDepartmentBreakdown()->toArray();
        $insights = $this->getInsights()->toArray();
        $alerts = $this->getTopAlerts()->toArray();
        $metrics = $this->getPerformanceMetrics();
        $overview = $this->getStockHealthOverview();
        $recentActivity = $this->getRecentActivity();

        // Sanitize each data source independently
        $data = [
            'period' => [
                'from' => (string) $this->dateFrom,
                'to' => (string) $this->dateTo,
            ],
            'branch_name' => (string) (current_branch()?->name ?? 'All Branches'),
            'summary' => $this->sanitizeArray($summary),
            'health_overview' => $this->sanitizeArray($overview),
            'insights' => $this->sanitizeArray($insights),
            'stock_health' => $this->sanitizeArray($stockHealth),
            'department_breakdown' => $this->sanitizeArray($deptBreakdown),
            'recent_activity' => $this->sanitizeRecentActivity($recentActivity),
            'top_alerts' => $this->sanitizeArray($alerts),
            'performance_metrics' => $this->sanitizeArray($metrics),
        ];

        return $data;
    }

    private function getDateRange(): array
    {
        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function sanitizeRecentActivity($activities)
    {
        try {
            return $activities->map(function ($activity) {
                $moverName = 'System';
                if ($activity->mover_id && $activity->mover) {
                    $moverName = $activity->mover->name ?? 'System';
                }

                $reference = $this->formatActivityReference($activity->reference ?? null);

                return [
                    'date' => $activity->movement_date ? (string) $activity->movement_date->format('d M Y H:i') : 'N/A',
                    'item' => (string) ($activity->stock?->item?->name ?? 'N/A'),
                    'type' => (string) ($activity->type ?? 'N/A'),
                    'quantity' => (float) ($activity->quantity ?? 0),
                    'reference' => $reference,
                    'notes' => (string) ($activity->notes ?? ''),
                    'mover' => (string) $moverName,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function formatActivityReference($reference): string
    {
        if (is_array($reference)) {
            return (string) ($reference['request_number'] ?? $reference['reference'] ?? $reference['id'] ?? 'N/A');
        }

        if (is_string($reference)) {
            $decoded = json_decode($reference, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return (string) ($decoded['request_number'] ?? $decoded['reference'] ?? $decoded['id'] ?? 'N/A');
            }
        }

        if (is_null($reference) || $reference === '') {
            return 'N/A';
        }

        return (string) $reference;
    }

    private function sanitizeArray($data)
    {
        if (is_string($data)) {
            // Remove invalid UTF-8 and convert to string
            return (string) @iconv('UTF-8', 'UTF-8//IGNORE', $data);
        }

        if (is_numeric($data)) {
            return $data;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[(string) @iconv('UTF-8', 'UTF-8//IGNORE', (string) $key)] = $this->sanitizeArray($value);
            }

            return $result;
        }

        if (is_bool($data) || is_null($data)) {
            return $data;
        }

        // For objects, try to convert to string
        if (is_object($data)) {
            return (string) $data;
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.analytics.overall-summary-dashboard', [
            'summary' => $this->getOverallSummary(),
            'healthOverview' => $this->getStockHealthOverview(),
            'recentActivity' => $this->getRecentActivity(),
            'alerts' => $this->getTopAlerts(),
            'insights' => $this->getInsights(),
            'stockHealthTable' => $this->getStockHealthTable(),
            'departmentBreakdown' => $this->getDepartmentBreakdown(),
            'performanceMetrics' => $this->getPerformanceMetrics(),
        ]);
    }
}
