<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Helpers\Settings;
use App\Livewire\BaseComponent;
use App\Models\Item;
use App\Models\ItemRequest;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\CurrencyFormattingService;
use App\Traits\Exportable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Analytics extends BaseComponent
{
    use Exportable, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->loadAnalytics();
    }

    // Filters
    public $dateFrom;

    public $dateTo;

    public $departmentId = null;

    public $categoryId = null;

    public $autoRefresh = false;

    // Summary Metrics
    public $totalStockValue = 0;

    public $totalItems = 0;

    public $purchaseValue = 0;

    public $totalPurchases = 0;

    public $movementsIn = 0;

    public $movementsOut = 0;

    public $totalMovements = 0;

    public $pendingRequests = 0;

    public $completedRequests = 0;

    public $lowStockItems = 0;

    public $criticalItems = 0;

    public $expiredItems = 0;

    // Insights Data
    public $insights = [];

    public $stockHealthData = [];

    public $topAlerts = [];

    public $recentActivity = [];

    public $departmentBreakdown = [];

    public $performanceMetrics = [];

    // Previous period comparison
    public $previousStockValue = 0;

    public $stockValueChange = 0;

    public $stockValueChangePercentage = 0;

    protected function getModelClass(): string
    {
        return Stock::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        // Default to last 30 days
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');

        $this->loadAnalytics();
    }

    public function updatedDateFrom()
    {
        $this->loadAnalytics();
    }

    public function updatedDateTo()
    {
        $this->loadAnalytics();
    }

    public function updatedDepartmentId()
    {
        $this->loadAnalytics();
    }

    public function updatedCategoryId()
    {
        $this->loadAnalytics();
    }

    public function refresh()
    {
        $this->loadAnalytics();
        $this->toast()->success('Analytics refreshed successfully!')->send();
    }

    public function loadAnalytics()
    {
        $branchId = $this->getBranchId();
        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        // Calculate summary metrics
        $this->calculateSummaryMetrics($branchId, $dateFrom, $dateTo);

        // Generate insights
        $this->generateInsights($branchId, $dateFrom, $dateTo);

        // Load stock health data
        $this->loadStockHealth($branchId);

        // Load top alerts
        $this->loadTopAlerts($branchId);

        // Load recent activity
        $this->loadRecentActivity($branchId, $dateFrom, $dateTo);

        // Load department breakdown
        $this->loadDepartmentBreakdown($branchId, $dateFrom, $dateTo);

        // Calculate performance metrics
        $this->calculatePerformanceMetrics($branchId, $dateFrom, $dateTo);
    }

    protected function calculateSummaryMetrics($branchId, $dateFrom, $dateTo)
    {
        // Total Stock Value and Items
        $stockData = Stock::where('branch_id', $branchId)
            ->with('item')
            ->get();

        $this->totalItems = $stockData->count();
        $this->totalStockValue = $stockData->sum(function ($stock) {
            return ($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0);
        });

        // Previous period stock value (for comparison)
        $previousPeriodDays = $dateTo->diffInDays($dateFrom);
        $previousDateFrom = $dateFrom->copy()->subDays($previousPeriodDays);
        $previousDateTo = $dateFrom->copy()->subDay();

        // Approximate previous stock value based on movements
        $this->previousStockValue = $this->totalStockValue; // Simplified - you can enhance this
        $this->stockValueChange = $this->totalStockValue - $this->previousStockValue;
        $this->stockValueChangePercentage = $this->previousStockValue > 0
            ? round(($this->stockValueChange / $this->previousStockValue) * 100, 2)
            : 0;

        // Purchase metrics
        $purchases = Purchase::where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->get();

        $this->totalPurchases = $purchases->count();
        $this->purchaseValue = $purchases->sum('total_amount');

        // Stock movements
        $movements = StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->get();

        $this->totalMovements = $movements->count();
        $this->movementsIn = $movements->where('type', 'in')->sum('quantity');
        $this->movementsOut = abs($movements->where('type', 'out')->sum('quantity'));

        // Item requests
        $requests = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $this->pendingRequests = $requests->where('status', 'pending')->count();
        $this->completedRequests = $requests->whereIn('status', ['approved', 'dispatched'])->count();

        // Critical stock items
        $this->lowStockItems = $stockData->filter(function ($stock) {
            return $stock->isBelowReorderLevel();
        })->count();

        $this->criticalItems = $stockData->filter(function ($stock) {
            return $stock->health_status === 'critical';
        })->count();

        $this->expiredItems = $stockData->filter(function ($stock) {
            return $stock->expiry_date && $stock->expiry_date->isPast();
        })->count();
    }

    protected function generateInsights($branchId, $dateFrom, $dateTo)
    {
        $this->insights = [];

        // Insight 1: Stock value change
        if ($this->stockValueChangePercentage != 0) {
            $direction = $this->stockValueChangePercentage > 0 ? 'increased' : 'decreased';
            $this->insights[] = [
                'type' => $this->stockValueChangePercentage > 0 ? 'positive' : 'negative',
                'icon' => $this->stockValueChangePercentage > 0 ? '📈' : '📉',
                'message' => "Inventory value {$direction} by ".abs($this->stockValueChangePercentage).'% since last period.',
            ];
        }

        // Insight 2: Top depleting items
        $topDepletingItems = StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
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
                $this->insights[] = [
                    'type' => 'info',
                    'icon' => '📦',
                    'message' => "Top 3 items with highest usage: {$itemNames}",
                ];
            }
        }

        // Insight 3: Purchase activity
        if ($this->totalPurchases == 0) {
            $this->insights[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'message' => 'No purchases recorded in the selected period.',
            ];
        }

        // Insight 4: Low stock alert
        if ($this->lowStockItems > 0) {
            $this->insights[] = [
                'type' => 'critical',
                'icon' => '🔴',
                'message' => "{$this->lowStockItems} item(s) are below reorder level and need restocking.",
            ];
        }

        // Insight 5: Expired items
        if ($this->expiredItems > 0) {
            $this->insights[] = [
                'type' => 'critical',
                'icon' => '⏰',
                'message' => "{$this->expiredItems} item(s) have expired and should be removed from inventory.",
            ];
        }
    }

    protected function loadStockHealth($branchId)
    {
        $this->stockHealthData = Stock::where('branch_id', $branchId)
            ->with('item')
            ->get()
            ->map(function ($stock) {
                $item = $stock->item;
                $reorderLevel = $item->reorder_level ?? 100;
                $healthPercentage = $reorderLevel > 0
                    ? min(100, round(($stock->quantity_available / $reorderLevel) * 100, 0))
                    : 100;

                $status = 'good';
                $statusIcon = '🟢';
                $statusColor = 'green';

                if ($healthPercentage < 30) {
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

                $lastMovement = StockMovement::where('stock_id', $stock->id)
                    ->latest('movement_date')
                    ->first();

                return [
                    'id' => $stock->id,
                    'item_name' => $item->name ?? 'N/A',
                    'stock_level' => $stock->quantity_available,
                    'reorder_level' => $reorderLevel,
                    'status' => $status,
                    'status_icon' => $statusIcon,
                    'status_color' => $statusColor,
                    'health_percentage' => $healthPercentage,
                    'last_movement' => $lastMovement ? $lastMovement->movement_date->format('d M Y') : 'N/A',
                    'uom' => $item->uom ?? 'units',
                ];
            })
            ->sortBy('health_percentage')
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function loadTopAlerts($branchId)
    {
        $this->topAlerts = [];

        $stocks = Stock::where('branch_id', $branchId)
            ->with('item')
            ->get();

        foreach ($stocks as $stock) {
            $item = $stock->item;
            if (! $item) {
                continue;
            }

            // Critical condition
            if ($stock->health_status === 'critical') {
                $this->topAlerts[] = [
                    'priority' => 1,
                    'type' => 'critical',
                    'icon' => '🔴',
                    'message' => "{$item->name} in critical condition",
                    'action' => 'View Item',
                    'item_id' => $item->id,
                ];
            }

            // Below reorder level
            if ($stock->isBelowReorderLevel()) {
                $this->topAlerts[] = [
                    'priority' => 2,
                    'type' => 'warning',
                    'icon' => '⚠️',
                    'message' => "{$item->name} below reorder level",
                    'action' => 'Restock',
                    'item_id' => $item->id,
                ];
            }

            // Expired
            if ($stock->expiry_date && $stock->expiry_date->isPast()) {
                $this->topAlerts[] = [
                    'priority' => 1,
                    'type' => 'expired',
                    'icon' => '⏰',
                    'message' => "{$item->name} has expired",
                    'action' => 'Remove',
                    'item_id' => $item->id,
                ];
            }

            // Expiring soon (within 7 days)
            if ($stock->expiry_date && $stock->expiry_date->isFuture() && $stock->expiry_date->diffInDays(now()) <= 7) {
                $this->topAlerts[] = [
                    'priority' => 2,
                    'type' => 'expiring',
                    'icon' => '🟠',
                    'message' => "{$item->name} expiring in {$stock->expiry_date->diffInDays(now())} days",
                    'action' => 'Use Soon',
                    'item_id' => $item->id,
                ];
            }
        }

        // Sort by priority and limit to top 10
        $this->topAlerts = collect($this->topAlerts)
            ->sortBy('priority')
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function loadRecentActivity($branchId, $dateFrom, $dateTo)
    {
        $this->recentActivity = StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->with(['stock.item', 'mover'])
            ->latest('movement_date')
            ->limit(20)
            ->get()
            ->map(function ($movement) {
                $icon = match ($movement->type) {
                    'in' => '📥',
                    'out' => '📤',
                    'transfer' => '🔄',
                    'damaged' => '⚠️',
                    default => '📦',
                };

                $typeLabel = match ($movement->type) {
                    'in' => 'Stock In',
                    'out' => 'Stock Out',
                    'transfer' => 'Transfer',
                    'damaged' => 'Damaged',
                    default => ucfirst($movement->type),
                };

                return [
                    'icon' => $icon,
                    'type' => $typeLabel,
                    'timestamp' => $movement->movement_date->format('M d, Y H:i'),
                    'actor' => $movement->mover->name ?? 'System',
                    'item_name' => $movement->stock->item->name ?? 'N/A',
                    'quantity' => abs($movement->quantity),
                    'uom' => $movement->stock->item->uom ?? 'units',
                    'notes' => $movement->notes,
                ];
            })
            ->toArray();
    }

    protected function loadDepartmentBreakdown($branchId, $dateFrom, $dateTo)
    {
        // Group stocks by item category or department
        $this->departmentBreakdown = Stock::where('branch_id', $branchId)
            ->with('item')
            ->get()
            ->groupBy(function ($stock) {
                return $stock->item->category ?? 'Uncategorized';
            })
            ->map(function ($stocks, $category) use ($dateFrom, $dateTo) {
                $stockValue = $stocks->sum(function ($stock) {
                    return ($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0);
                });

                $stockIds = $stocks->pluck('id');
                $movements = StockMovement::whereIn('stock_id', $stockIds)
                    ->whereBetween('movement_date', [$dateFrom, $dateTo])
                    ->get();

                $stockIn = $movements->where('type', 'in')->sum('quantity');
                $stockOut = abs($movements->where('type', 'out')->sum('quantity'));

                $lowItems = $stocks->filter(function ($stock) {
                    return $stock->isBelowReorderLevel();
                })->count();

                $requests = ItemRequest::whereHas('requestDetails', function ($query) use ($stocks) {
                    $query->whereIn('item_id', $stocks->pluck('item_id'));
                })->whereBetween('created_at', [$dateFrom, $dateTo])->count();

                return [
                    'category' => $category,
                    'stock_value' => $stockValue,
                    'stock_in' => $stockIn,
                    'stock_out' => $stockOut,
                    'low_items' => $lowItems,
                    'requests' => $requests,
                    'item_count' => $stocks->count(),
                ];
            })
            ->sortByDesc('stock_value')
            ->values()
            ->toArray();
    }

    protected function calculatePerformanceMetrics($branchId, $dateFrom, $dateTo)
    {
        $days = max(1, $dateTo->diffInDays($dateFrom));

        // Average stock turnover rate
        $totalMovementsOut = abs(StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->sum('quantity'));

        $averageStockValue = $this->totalStockValue > 0 ? $this->totalStockValue : 1;
        $turnoverRate = round($totalMovementsOut / $days, 2);

        // Fastest moving item
        $fastestMoving = StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->select('stock_id', DB::raw('SUM(ABS(quantity)) as total'))
            ->groupBy('stock_id')
            ->orderByDesc('total')
            ->with('stock.item')
            ->first();

        // Slowest moving item
        $slowestMoving = StockMovement::whereHas('stock', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where('type', 'out')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->select('stock_id', DB::raw('SUM(ABS(quantity)) as total'))
            ->groupBy('stock_id')
            ->orderBy('total')
            ->with('stock.item')
            ->first();

        // Most requested item
        $mostRequested = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('requestDetails.item')
            ->get()
            ->flatMap(function ($request) {
                return $request->requestDetails;
            })
            ->groupBy('item_id')
            ->map(function ($details) {
                return [
                    'item' => $details->first()->item,
                    'total_requested' => $details->sum('quantity_requested'),
                ];
            })
            ->sortByDesc('total_requested')
            ->first();

        $this->performanceMetrics = [
            'average_turnover_rate' => $turnoverRate,
            'fastest_moving' => $fastestMoving ? [
                'name' => $fastestMoving->stock->item->name ?? 'N/A',
                'quantity' => abs($fastestMoving->total),
            ] : null,
            'slowest_moving' => $slowestMoving ? [
                'name' => $slowestMoving->stock->item->name ?? 'N/A',
                'quantity' => abs($slowestMoving->total),
            ] : null,
            'most_requested' => $mostRequested ? [
                'name' => $mostRequested['item']->name ?? 'N/A',
                'quantity' => $mostRequested['total_requested'],
            ] : null,
            'expired_vs_active_percentage' => $this->totalItems > 0
                ? round(($this->expiredItems / $this->totalItems) * 100, 2)
                : 0,
        ];
    }


    public function exportCSV()
    {
        $data = $this->prepareExportData();

        $csvData = [];
        $csvData[] = ['Inventory Analytics Report'];
        $csvData[] = ['Period', $this->dateFrom.' to '.$this->dateTo];
        $csvData[] = ['Generated', now()->format('Y-m-d H:i:s')];
        $csvData[] = [];

        // Summary metrics
        $csvData[] = ['SUMMARY METRICS'];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Stock Value', number_format($data['total_stock_value'] ?? 0, 2)];
        $csvData[] = ['Total Items', $data['total_items'] ?? 0];
        $csvData[] = ['Total Purchases', $data['total_purchases'] ?? 0];
        $csvData[] = ['Purchase Value', number_format($data['purchase_value'] ?? 0, 2)];
        $csvData[] = ['Stock In', number_format($data['movements_in'] ?? 0, 2)];
        $csvData[] = ['Stock Out', number_format($data['movements_out'] ?? 0, 2)];
        $csvData[] = ['Low Stock Items', $data['low_stock_items'] ?? 0];
        $csvData[] = ['Critical Items', $data['critical_items'] ?? 0];
        $csvData[] = ['Expired Items', $data['expired_items'] ?? 0];
        $csvData[] = [];

        // Stock Health
        $csvData[] = ['STOCK HEALTH STATUS'];
        $csvData[] = ['Item Name', 'Stock Level', 'Reorder Level', 'Health %', 'Status', 'Last Movement', 'UOM'];
        foreach ($data['stock_health_data'] ?? [] as $stock) {
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

    protected function prepareExportData(): array
    {
        return [
            'period' => ['from' => $this->dateFrom, 'to' => $this->dateTo],
            'branch_name' => current_branch()?->name ?? 'All Branches',
            'total_stock_value' => $this->totalStockValue,
            'total_items' => $this->totalItems,
            'total_purchases' => $this->totalPurchases,
            'purchase_value' => $this->purchaseValue,
            'movements_in' => $this->movementsIn,
            'movements_out' => $this->movementsOut,
            'total_movements' => $this->totalMovements,
            'pending_requests' => $this->pendingRequests,
            'completed_requests' => $this->completedRequests,
            'low_stock_items' => $this->lowStockItems,
            'critical_items' => $this->criticalItems,
            'expired_items' => $this->expiredItems,
            'insights' => $this->insights,
            'stock_health_data' => $this->stockHealthData,
            'top_alerts' => $this->topAlerts,
            'department_breakdown' => $this->departmentBreakdown,
            'performance_metrics' => $this->performanceMetrics,
        ];
    }

    /**
     * Format currency value for inventory display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    /**
     * Get currency symbol for display
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService;
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');

        return $service->getSymbol($currency);
    }

    /**
     * Format percentage for display
     */
    protected function formatPercentage(float $value, int $decimals = 2): string
    {
        $service = new CurrencyFormattingService;

        return $service->formatPercentage($value, $decimals);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.inventory.analytics');
    }
}
