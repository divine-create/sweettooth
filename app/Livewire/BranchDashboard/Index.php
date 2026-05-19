<?php

namespace App\Livewire\BranchDashboard;

use App\Helpers\Settings;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Item;
use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\ProductionCallback;
use App\Models\ProductDispatchCallback;
use App\Models\ProductStock;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions;

    public $branchId;
    public $branchName;
    public $dateRange = 'today'; // today, week, month, custom
    public $startDate;
    public $endDate;
    public $selectedDepartment = 'all'; // all, inventory, production, sales

    public function mount()
    {
        $this->branchId = request()->query('b_id');
        $branch = Branch::find($this->branchId);
        $this->branchName = $branch?->name ?? 'Unknown Branch';

        // Set default date range to today
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function updatedDateRange($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    /**
     * Get overall metrics
     */
    public function getOverallMetrics()
    {
        // Employee count
        $employeeTotal = Employee::where('branch_id', $this->branchId)->count();
        $activeEmployees = Employee::where('branch_id', $this->branchId)
            ->where('status', 'active')
            ->count();

        // Total Revenue
        $totalRevenue = Sale::where('branch_id', $this->branchId)
            ->whereBetween('sale_time', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Inventory value
        $inventoryValue = Stock::whereHas('item', function($q) {
            $q->where('branch_id', $this->branchId);
        })->with('item')->get()->sum(function($stock) {
            return $stock->quantity_available * ($stock->item->unit_price ?? 0);
        });

        // Active issues (variances, expired items, low stock)
        $lowStockCount = Stock::join('items', 'stocks.item_id', '=', 'items.id')
            ->where('items.branch_id', $this->branchId)
            ->whereRaw('stocks.quantity_available <= items.reorder_level')
            ->whereNotNull('items.reorder_level')
            ->count();

        $expiryAlerts = ProductStock::where('stock_date', '>=', Carbon::today())
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(7))
            ->count();

        $varianceCount = Shift::where('branch_id', $this->branchId)
            ->whereBetween('shift_date', [
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate)
            ])
            ->whereRaw('ABS(CAST(notes AS DECIMAL(10,2))) > 100') // Assuming cash_variance stored in notes
            ->count();

        return [
            'employee_total' => $employeeTotal,
            'active_employees' => $activeEmployees,
            'total_revenue' => $totalRevenue,
            'inventory_value' => $inventoryValue,
            'active_issues' => $lowStockCount + $expiryAlerts + $varianceCount,
            'low_stock_count' => $lowStockCount,
            'expiry_alerts' => $expiryAlerts,
            'variance_count' => $varianceCount,
        ];
    }

    /**
     * Get Sales Department Summary
     */
    public function getSalesSummary()
    {
        $salesDepartments = Department::whereHas('category', function($q) {
                $q->where('name', 'Sales');
            })
            ->where(function($q) {
                $q->where('branch_id', $this->branchId)
                  ->orWhereNull('branch_id');
            })
            ->get();

        $summary = [];
        foreach ($salesDepartments as $dept) {
            $sales = Sale::where('branch_id', $this->branchId)
                ->where('department_id', $dept->id)
                ->whereBetween('sale_time', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay()
                ])
                ->where('status', '!=', 'cancelled');

            $totalSales = $sales->sum('total');
            $orderCount = $sales->count();

            // Top selling product/item (LEFT JOINs so inventory items are included)
            $topProduct = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('items', 'sale_items.item_id', '=', 'items.id')
                ->where('sales.branch_id', $this->branchId)
                ->where('sales.department_id', $dept->id)
                ->whereBetween('sales.sale_time', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay()
                ])
                ->select(
                    DB::raw('COALESCE(products.name, items.name) as name'),
                    DB::raw('SUM(sale_items.quantity) as total_qty')
                )
                ->groupBy(DB::raw('COALESCE(products.name, items.name)'))
                ->orderByDesc('total_qty')
                ->first();

            $summary[] = [
                'department_name' => $dept->name,
                'total_sales' => $totalSales,
                'order_count' => $orderCount,
                'avg_order_value' => $orderCount > 0 ? $totalSales / $orderCount : 0,
                'top_product' => $topProduct->name ?? 'N/A',
                'top_product_qty' => $topProduct->total_qty ?? 0,
            ];
        }

        return $summary;
    }

    /**
     * Get Production Summary
     */
    public function getProductionSummary()
    {
        $productionDepartments = Department::whereHas('category', function($q) {
                $q->where('name', 'Production');
            })
            ->where(function($q) {
                $q->where('branch_id', $this->branchId)
                  ->orWhereNull('branch_id');
            })
            ->get();

        $summary = [];
        foreach ($productionDepartments as $dept) {
            // Get production records
            $productionRecords = ProductionRecord::whereHas('dailyProduce.shift', function($q) use ($dept) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $dept->id)
                  ->whereBetween('shift_date', [
                      Carbon::parse($this->startDate),
                      Carbon::parse($this->endDate)
                  ]);
            })->get();

            $totalProduced = $productionRecords->sum('quantity_produced');
            $totalApproved = $productionRecords->sum('quantity_approved');
            $totalRejected = $productionRecords->sum('quantity_rejected');

            // Calculate efficiency
            $efficiency = $totalProduced > 0 ? ($totalApproved / $totalProduced) * 100 : 0;

            // Get wastage (callbacks) - query through shift relationship
            $wastage = ProductionCallback::whereHas('shift', function($q) use ($dept) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $dept->id)
                  ->whereBetween('shift_date', [
                      Carbon::parse($this->startDate),
                      Carbon::parse($this->endDate)
                  ]);
            })->sum('quantity');

            $summary[] = [
                'department_name' => $dept->name,
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'efficiency' => round($efficiency, 2),
                'wastage' => $wastage,
                'batches_count' => $productionRecords->count(),
            ];
        }

        return $summary;
    }

    /**
     * Get Inventory Summary
     */
    public function getInventorySummary()
    {
        // Total items
        $totalItems = Item::where('branch_id', $this->branchId)->count();

        // Total stock value
        $stockValue = Stock::whereHas('item', function($q) {
            $q->where('branch_id', $this->branchId);
        })->with('item')->get()->sum(function($stock) {
            return $stock->quantity_available * ($stock->item->unit_price ?? 0);
        });

        // Low stock items
        $lowStockItems = Stock::join('items', 'stocks.item_id', '=', 'items.id')
            ->where('items.branch_id', $this->branchId)
            ->whereRaw('stocks.quantity_available <= items.reorder_level')
            ->whereNotNull('items.reorder_level')
            ->count();

        // Out of stock items
        $outOfStock = Stock::whereHas('item', function($q) {
            $q->where('branch_id', $this->branchId);
        })->where('quantity_available', '<=', 0)->count();

        // Recent movements
        $recentMovements = DB::table('stock_movements')
            ->join('stocks', 'stock_movements.stock_id', '=', 'stocks.id')
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->where('items.branch_id', $this->branchId)
            ->whereBetween('stock_movements.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->count();

        return [
            'total_items' => $totalItems,
            'stock_value' => $stockValue,
            'low_stock_items' => $lowStockItems,
            'out_of_stock' => $outOfStock,
            'recent_movements' => $recentMovements,
        ];
    }

    /**
     * Get revenue trend for chart
     */
    public function getRevenueTrend()
    {
        $days = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::parse($this->startDate)->addDays($i);
            $revenue = Sale::where('branch_id', $this->branchId)
                ->whereDate('sale_time', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $trend[] = [
                'date' => $date->format('M d'),
                'revenue' => $revenue,
            ];
        }

        return $trend;
    }

    public function exportReport()
    {
        $this->toast()->success('Report export feature coming soon!')->send();
        // TODO: Implement PDF/Excel export
    }

    public function render()
    {
        $overallMetrics = $this->getOverallMetrics();
        $salesSummary = $this->getSalesSummary();
        $productionSummary = $this->getProductionSummary();
        $inventorySummary = $this->getInventorySummary();
        $revenueTrend = $this->getRevenueTrend();

        return view('livewire.branch-dashboard.index', [
            'employee_total' => $overallMetrics['employee_total'],
            'overallMetrics' => $overallMetrics,
            'salesSummary' => $salesSummary,
            'productionSummary' => $productionSummary,
            'inventorySummary' => $inventorySummary,
            'revenueTrend' => $revenueTrend,
        ]);
    }
}
