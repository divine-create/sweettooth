<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\MySales;

use App\Livewire\BaseComponent;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\PosDocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\{Layout, Url, Computed, On};
use Carbon\Carbon;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    public string $departmentName = 'My Sales';
    public string $branchName = '';
    public ?string $employeeId = null;
    public array $soldByTypes = [];

    // Date filters
    public $dateFrom;
    public $dateTo;
    public $selectedPeriod = 'today';

    // Tab management
    public $activeTab = 'overview';

    protected function getModelClass(): string
    {
        return Sale::class;
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
        $this->mountBase();
        $this->b_id = request()->query('b_id') ?? current_branch_id();
        $this->branchId = $this->b_id;
        $this->salesDeptSlug = $this->salesDeptSlug
            ?? request()->route('salesDeptSlug')
            ?? request()->query('salesDeptSlug')
            ?? request()->query('sales_dept_slug');
        $this->loadBranchAndDepartment();
        $this->employeeId = auth()->id();
        $currentActorType = auth()->user() ? get_class(auth()->user()) : User::class;
        $this->soldByTypes = array_values(array_unique([
            $currentActorType,
            User::class,
            Employee::class,
        ]));
        $this->setDateRange('today');
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->branchId = $branchId;
        $this->loadBranchAndDepartment();
    }

    protected function loadBranchAndDepartment(): void
    {
        // Load branch - use helper for super admin support
        if (!$this->branchId) {
            $this->branchId = current_branch_id();
        }
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        // Load department
        if ($this->salesDeptSlug) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            if (!$department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
                $this->departmentName = $department->name;
            }
        } else {
            $employee = auth()->user();
            if ($employee && $employee->department_id) {
                $this->departmentId = $employee->department_id;
                $department = Department::find($employee->department_id);
                $this->departmentName = $department?->name ?? 'My Sales';
            }
        }
    }

    public function setDateRange($period)
    {
        $this->selectedPeriod = $period;

        switch ($period) {
            case 'today':
                $this->dateFrom = now()->startOfDay()->format('Y-m-d H:i:s');
                $this->dateTo = now()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'yesterday':
                $this->dateFrom = now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                $this->dateTo = now()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'this_week':
                $this->dateFrom = now()->startOfWeek()->format('Y-m-d H:i:s');
                $this->dateTo = now()->endOfWeek()->format('Y-m-d H:i:s');
                break;
            case 'this_month':
                $this->dateFrom = now()->startOfMonth()->format('Y-m-d H:i:s');
                $this->dateTo = now()->endOfMonth()->format('Y-m-d H:i:s');
                break;
            case 'last_month':
                $this->dateFrom = now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
                $this->dateTo = now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
                break;
        }
    }

    public function printReceipt(int $saleId): void
    {
        try {
            $sale = Sale::query()
                ->with(['saleItems.product', 'receipts'])
                ->where('id', $saleId)
                ->where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->first();

            if (! $sale) {
                $this->toast()->error('Sale not found.')->send();
                return;
            }

            $receipt = $sale->receipts()->latest()->first();
            if (! $receipt) {
                $documentService = new PosDocumentService();
                $receiptHtml = $documentService->generateBrandedReceiptHtml($sale);

                $receipt = Receipt::create([
                    'sale_id' => $sale->id,
                    'content' => $receiptHtml,
                    'subtotal' => $sale->subtotal,
                    'tax' => $sale->tax,
                    'discount' => $sale->discount,
                    'total' => $sale->total,
                    'payments' => [],
                    'change_due' => 0,
                    'meta' => ['source' => 'my-sales'],
                ]);
            }

            $this->dispatch('print-receipt', html: $receipt->content);
        } catch (\Throwable $e) {
            $this->toast()->error('Failed to generate receipt: ' . $e->getMessage())->send();
        }
    }

    #[Computed]
    public function salesOverview()
    {
        return Cache::remember($this->getCacheKey('my_sales_overview'), 300, function() {
            $sales = Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalSales = $sales->sum('total');
            $totalOrders = $sales->count();
            $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
            $totalDiscount = $sales->sum('discount');

            // Previous period comparison
            $periodDiff = Carbon::parse($this->dateTo)->diffInDays(Carbon::parse($this->dateFrom));
            $prevFrom = Carbon::parse($this->dateFrom)->subDays($periodDiff + 1)->format('Y-m-d H:i:s');
            $prevTo = Carbon::parse($this->dateFrom)->subSecond()->format('Y-m-d H:i:s');

            $prevSales = Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->whereBetween('sale_time', [$prevFrom, $prevTo])
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $growthRate = $prevSales > 0 ? (($totalSales - $prevSales) / $prevSales) * 100 : 0;

            return [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
                'total_discount' => $totalDiscount,
                'growth_rate' => $growthRate,
                'prev_sales' => $prevSales,
            ];
        });
    }

    #[Computed]
    public function topSellingProducts()
    {
        return Cache::remember($this->getCacheKey('my_top_products'), 300, function() {
            return SaleItem::whereHas('sale', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->where('sold_by_id', $this->employeeId)
                  ->whereIn('sold_by_type', $this->soldByTypes)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->with('product')
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_id) as order_count')
            )
            ->groupBy('product_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();
        });
    }

    #[Computed]
    public function hourlySalesData()
    {
        return Cache::remember($this->getCacheKey('my_hourly_sales'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled')
                ->select(
                    DB::raw('HOUR(sale_time) as hour'),
                    DB::raw('SUM(total) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
        });
    }

    #[Computed]
    public function dailySalesData()
    {
        return Cache::remember($this->getCacheKey('my_daily_sales'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled')
                ->select(
                    DB::raw('DATE(sale_time) as date'),
                    DB::raw('SUM(total) as total'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(total) as avg_order')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    #[Computed]
    public function paymentBreakdown()
    {
        return Cache::remember($this->getCacheKey('my_payments'), 300, function() {
            return Payment::whereHas('sale', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->where('sold_by_id', $this->employeeId)
                  ->whereIn('sold_by_type', $this->soldByTypes)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
        });
    }

    #[Computed]
    public function orderTypeBreakdown()
    {
        return Cache::remember($this->getCacheKey('my_order_types'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->where('sold_by_id', $this->employeeId)
                ->whereIn('sold_by_type', $this->soldByTypes)
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled')
                ->select('order_type', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('order_type')
                ->get();
        });
    }

    #[Computed]
    public function recentSales()
    {
        return Sale::where('branch_id', $this->branchId)
            ->where('department_id', $this->departmentId)
            ->where('sold_by_id', $this->employeeId)
            ->whereIn('sold_by_type', $this->soldByTypes)
            ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
            ->where('status', '!=', 'cancelled')
            ->with(['saleItems.product', 'payments'])
            ->orderBy('sale_time', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getCacheKey($suffix)
    {
        return sprintf(
            'my_sales_%s_%s_%s_%s_%s_%s_%s',
            $this->employeeId,
            $this->branchId,
            $this->departmentId ?? 'no_department',
            $this->salesDeptSlug ?? 'no_slug',
            $this->dateFrom,
            $this->dateTo,
            $suffix
        );
    }

    public function refreshData()
    {
        $suffixes = ['my_sales_overview', 'my_top_products', 'my_hourly_sales', 'my_daily_sales', 'my_payments', 'my_order_types'];
        foreach ($suffixes as $suffix) {
            Cache::forget($this->getCacheKey($suffix));
        }

        unset($this->salesOverview);
        unset($this->topSellingProducts);
        unset($this->hourlySalesData);
        unset($this->dailySalesData);
        unset($this->paymentBreakdown);
        unset($this->orderTypeBreakdown);

        $this->toast()->success('Data refreshed successfully')->send();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.my-sales.index', [
            'overview' => $this->salesOverview,
            'topProducts' => $this->topSellingProducts,
            'hourlySales' => $this->hourlySalesData,
            'dailySales' => $this->dailySalesData,
            'payments' => $this->paymentBreakdown,
            'orderTypes' => $this->orderTypeBreakdown,
            'recentSales' => $this->recentSales,
        ]);
    }
}
