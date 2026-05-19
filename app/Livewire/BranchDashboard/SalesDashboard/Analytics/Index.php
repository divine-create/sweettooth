<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Analytics;

use App\Helpers\Settings;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesShift;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\CurrencyFormattingService;
use App\Services\Reports\AnalyticsSnapshotReportService;
use App\Traits\Exportable;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use function is_super_admin;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Exportable;

    public $dateFrom;
    public $dateTo;
    public $branchId;
    public $departmentId;
    public $orderType = 'all';
    public $paymentMethod = 'all';
    public $selectedPeriod = 'today';
    public ?string $generatedReportId = null;
    
    // Tab management
    public $activeTab = 'overview';
    
    // Real-time refresh
    public $autoRefresh = false;
    
    protected $queryString = [
        'dateFrom',
        'dateTo',
        'orderType',
        'paymentMethod',
        'activeTab'
    ];

    public function mount()
    {
        $this->branchId = $this->resolveBranchId();
        // Super Admin can see all departments, so don't restrict to their department
        if (!is_super_admin()) {
            $this->departmentId = auth()->user()->department_id;
        }
        $this->setDateRange('today');
    }

    private function resolveBranchId(): ?string
    {
        return request()->get('b_id')
            ?? $this->branchId
            ?? auth()->user()?->branch_id
            ?? current_branch_id();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo'])) {
            $this->validateDateRange();
        }
    }

    protected function validateDateRange()
    {
        if ($this->dateFrom && $this->dateTo) {
            $from = Carbon::parse($this->dateFrom);
            $to = Carbon::parse($this->dateTo);

            if ($from->gt($to)) {
                $this->addError('dateFrom', 'Start date must be before end date');
                return false;
            }

            if ($from->diffInDays($to) > 365) {
                $this->addError('dateFrom', 'Date range cannot exceed 365 days');
                return false;
            }
        }
        return true;
    }

    protected function getCacheKey($suffix)
    {
        return sprintf(
            'analytics_%s_%s_%s_%s_%s_%s',
            $this->branchId,
            $this->departmentId,
            $this->dateFrom,
            $this->dateTo,
            $this->orderType,
            $suffix
        );
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
            case 'last_week':
                $this->dateFrom = now()->subWeek()->startOfWeek()->format('Y-m-d H:i:s');
                $this->dateTo = now()->subWeek()->endOfWeek()->format('Y-m-d H:i:s');
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

    #[Computed]
    public function salesOverview()
    {
        return Cache::remember($this->getCacheKey('overview'), 300, function() {
            $query = Sale::query()
                ->where('branch_id', $this->branchId)
                ->when(!is_super_admin(), function($q) {
                    $q->where('department_id', $this->departmentId);
                })
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled');

            if ($this->orderType !== 'all') {
                $query->where('order_type', $this->orderType);
            }

            $sales = $query->get();

            // Cancelled sales for tracking
            $cancelledSales = Sale::where('branch_id', $this->branchId)
                ->when(!is_super_admin(), function($q) {
                    $q->where('department_id', $this->departmentId);
                })
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', 'cancelled')
                ->get();

            // Calculate core metrics
            $totalSales = $sales->sum('total');
            $totalOrders = $sales->count();
            $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
            $totalDiscount = $sales->sum('discount');
            $totalTax = $sales->sum('tax');
            $subtotal = $sales->sum('subtotal');

            // Calculate net revenue (total - discounts)
            $netRevenue = $totalSales - $totalDiscount;

            // Calculate refunds/cancellations
            $totalRefunds = $cancelledSales->sum('total');
            $refundCount = $cancelledSales->count();

            // Calculate actual revenue after refunds
            $actualRevenue = $netRevenue - $totalRefunds;

            // Previous period comparison
            $periodDiff = Carbon::parse($this->dateTo)->diffInDays(Carbon::parse($this->dateFrom));
            $prevFrom = Carbon::parse($this->dateFrom)->subDays($periodDiff + 1)->format('Y-m-d H:i:s');
            $prevTo = Carbon::parse($this->dateFrom)->subSecond()->format('Y-m-d H:i:s');

            $prevQuery = Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->whereBetween('sale_time', [$prevFrom, $prevTo])
                ->where('status', '!=', 'cancelled');

            if ($this->orderType !== 'all') {
                $prevQuery->where('order_type', $this->orderType);
            }

            $prevSales = $prevQuery->sum('total');
            $prevOrders = $prevQuery->count();

            $growthRate = $prevSales > 0 ? (($totalSales - $prevSales) / $prevSales) * 100 : 0;
            $orderGrowthRate = $prevOrders > 0 ? (($totalOrders - $prevOrders) / $prevOrders) * 100 : 0;

            return [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'subtotal' => $subtotal,
                'net_revenue' => $netRevenue,
                'total_refunds' => $totalRefunds,
                'refund_count' => $refundCount,
                'actual_revenue' => $actualRevenue,
                'growth_rate' => $growthRate,
                'order_growth_rate' => $orderGrowthRate,
                'prev_sales' => $prevSales,
                'prev_orders' => $prevOrders,
                'refund_rate' => $totalOrders > 0 ? ($refundCount / ($totalOrders + $refundCount)) * 100 : 0,
                'discount_rate' => $subtotal > 0 ? ($totalDiscount / $subtotal) * 100 : 0,
            ];
        });
    }

    #[Computed]
    public function paymentBreakdown()
    {
        return Cache::remember($this->getCacheKey('payments'), 300, function() {
            $query = Payment::whereHas('sale', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            })->where('status', 'completed');

            if ($this->paymentMethod !== 'all') {
                $query->where('payment_method', $this->paymentMethod);
            }

            return $query->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();
        });
    }

    #[Computed]
    public function orderTypeBreakdown()
    {
        return Cache::remember($this->getCacheKey('order_types'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                ->where('status', '!=', 'cancelled')
                ->select('order_type', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('order_type')
                ->get();
        });
    }

    #[Computed]
    public function topSellingProducts()
    {
        return Cache::remember($this->getCacheKey('top_products'), 300, function() {
            $saleFilter = function ($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            };

            $products = SaleItem::whereHas('sale', $saleFilter)
                ->whereNotNull('product_id')
                ->with('product')
                ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total) as total_revenue'), DB::raw('COUNT(DISTINCT sale_id) as order_count'))
                ->groupBy('product_id')
                ->get();

            $items = SaleItem::whereHas('sale', $saleFilter)
                ->whereNotNull('item_id')
                ->with('item')
                ->select('item_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total) as total_revenue'), DB::raw('COUNT(DISTINCT sale_id) as order_count'))
                ->groupBy('item_id')
                ->get();

            return $products->concat($items)
                ->sortByDesc('total_revenue')
                ->take(10)
                ->values();
        });
    }

    #[Computed]
    public function hourlySalesData()
    {
        return Cache::remember($this->getCacheKey('hourly_sales'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
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
        return Cache::remember($this->getCacheKey('daily_sales'), 300, function() {
            return Sale::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
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
    public function shiftPerformance()
    {
        return Cache::remember($this->getCacheKey('shifts'), 300, function() {
            return SalesShift::where('branch_id', $this->branchId)
                ->where('department_id', $this->departmentId)
                ->whereBetween('shift_date', [
                    Carbon::parse($this->dateFrom)->format('Y-m-d'),
                    Carbon::parse($this->dateTo)->format('Y-m-d')
                ])
                ->with(['employee', 'sales'])
                ->get()
                ->map(function($shift) {
                    $totalSales = $shift->sales->sum('total');
                    $totalOrders = $shift->sales->count();

                    return [
                        'shift_number' => $shift->shift_number,
                        'shift_type' => $shift->shift_type,
                        'shift_date' => $shift->shift_date,
                        'employee_name' => $shift->employee->name ?? 'N/A',
                        'total_sales' => $totalSales,
                        'total_orders' => $totalOrders,
                        'opening_cash' => $shift->opening_cash,
                        'closing_cash' => $shift->closing_cash,
                        'cash_variance' => $shift->cash_variance,
                        'status' => $shift->status
                    ];
                });
        });
    }

    #[Computed]
    public function categorySales()
    {
        return Cache::remember($this->getCacheKey('categories'), 300, function() {
            return SaleItem::whereHas('sale', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->whereNotNull('sale_items.product_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('product_types', 'products.product_type_id', '=', 'product_types.id')
            ->where('product_types.department_id', $this->departmentId)
            ->select(
                'product_types.id',
                'product_types.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as order_count'),
                DB::raw('COUNT(DISTINCT sale_items.product_id) as product_count')
            )
            ->groupBy('product_types.id', 'product_types.name')
            ->orderBy('total_revenue', 'desc')
            ->get();
        });
    }

    #[Computed]
    public function profitAnalysis()
    {
        return Cache::remember($this->getCacheKey('profit'), 300, function() {
            $items = SaleItem::whereHas('sale', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('department_id', $this->departmentId)
                  ->whereBetween('sale_time', [$this->dateFrom, $this->dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->with('product')
            ->get();

            $totalRevenue = $items->sum('total');
            $totalCost = $items->sum(function($item) {
                return ($item->product->cost_price ?? 0) * $item->quantity;
            });

            $grossProfit = $totalRevenue - $totalCost;
            $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

            return [
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'gross_margin' => $grossMargin,
            ];
        });
    }

    public function refreshData()
    {
        // Clear all caches
        $suffixes = ['overview', 'payments', 'order_types', 'top_products', 'hourly_sales', 'daily_sales', 'shifts', 'categories', 'profit'];
        foreach ($suffixes as $suffix) {
            Cache::forget($this->getCacheKey($suffix));
        }

        // Reset computed properties
        unset($this->salesOverview);
        unset($this->paymentBreakdown);
        unset($this->orderTypeBreakdown);
        unset($this->topSellingProducts);
        unset($this->hourlySalesData);
        unset($this->dailySalesData);
        unset($this->shiftPerformance);
        unset($this->categorySales);
        unset($this->profitAnalysis);

        $this->dispatch('refresh-charts');
        $this->dispatch('notify', ['message' => 'Data refreshed successfully', 'type' => 'success']);
    }

    public function exportData($format = 'csv')
    {
        try {
            $data = $this->prepareExportData();

            if ($format !== 'csv') {
                $this->dispatch('notify', ['message' => 'Only CSV export is supported.', 'type' => 'error']);
                return;
            }

            return $this->exportToCSV($data);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Export failed: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    public function generateReport(): void
    {
        if (!$this->validateDateRange()) {
            session()->flash('warning', 'Invalid date range. Please fix the date filters before generating report.');
            return;
        }

        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            session()->flash('warning', 'Branch context is required to generate report.');
            return;
        }

        try {
            $overview = $this->salesOverview;
            $profit = $this->profitAnalysis;

            $payments = $this->paymentBreakdown->map(function ($row) {
                return [
                    'payment_method' => $row->payment_method,
                    'transactions' => (int) $row->count,
                    'total_amount' => (float) $row->total,
                ];
            })->values()->toArray();

            $orderTypes = $this->orderTypeBreakdown->map(function ($row) {
                return [
                    'order_type' => $row->order_type,
                    'orders' => (int) $row->count,
                    'total_amount' => (float) $row->total,
                ];
            })->values()->toArray();

            $topProducts = $this->topSellingProducts->map(function ($row) {
                return [
                    'product_name' => $row->product?->name ?? 'N/A',
                    'sku' => $row->product?->sku ?? null,
                    'quantity_sold' => (float) $row->total_quantity,
                    'total_revenue' => (float) $row->total_revenue,
                    'order_count' => (int) $row->order_count,
                ];
            })->values()->toArray();

            $hourlySales = $this->hourlySalesData->map(function ($row) {
                return [
                    'hour' => (int) $row->hour,
                    'total_sales' => (float) $row->total,
                    'orders' => (int) $row->count,
                ];
            })->values()->toArray();

            $dailySales = $this->dailySalesData->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->date)->toDateString(),
                    'total_sales' => (float) $row->total,
                    'orders' => (int) $row->count,
                    'avg_order' => (float) $row->avg_order,
                ];
            })->values()->toArray();

            $shiftPerformance = collect($this->shiftPerformance)->map(function ($shift) {
                return [
                    'shift_number' => $shift['shift_number'] ?? null,
                    'shift_type' => $shift['shift_type'] ?? null,
                    'shift_date' => isset($shift['shift_date']) ? Carbon::parse($shift['shift_date'])->toDateString() : null,
                    'employee_name' => $shift['employee_name'] ?? 'N/A',
                    'total_sales' => (float) ($shift['total_sales'] ?? 0),
                    'total_orders' => (int) ($shift['total_orders'] ?? 0),
                    'cash_variance' => (float) ($shift['cash_variance'] ?? 0),
                    'status' => $shift['status'] ?? null,
                ];
            })->values()->toArray();

            $categorySales = $this->categorySales->map(function ($row) {
                return [
                    'category_name' => $row->category_name,
                    'products' => (int) $row->product_count,
                    'orders' => (int) $row->order_count,
                    'quantity' => (float) $row->total_quantity,
                    'total_revenue' => (float) $row->total_revenue,
                ];
            })->values()->toArray();

            $periodFrom = Carbon::parse($this->dateFrom)->toDateString();
            $periodTo = Carbon::parse($this->dateTo)->toDateString();

            $reportData = [
                'source_page' => 'sales-dashboard.analytics',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $this->dateFrom,
                    'date_to' => $this->dateTo,
                    'selected_period' => $this->selectedPeriod,
                    'order_type' => $this->orderType,
                    'payment_method' => $this->paymentMethod,
                    'department_id' => $this->departmentId,
                ],
                'overview' => $overview,
                'profit' => $profit,
                'payments' => $payments,
                'order_types' => $orderTypes,
                'top_products' => $topProducts,
                'hourly_sales' => $hourlySales,
                'daily_sales' => $dailySales,
                'shift_performance' => $shiftPerformance,
                'category_sales' => $categorySales,
                'tables' => [
                    'payment_breakdown' => [
                        'headers' => ['Payment Method', 'Transactions', 'Total Amount'],
                        'rows' => array_map(
                            fn (array $row) => [$row['payment_method'], $row['transactions'], $row['total_amount']],
                            $payments
                        ),
                    ],
                    'order_type_breakdown' => [
                        'headers' => ['Order Type', 'Orders', 'Total Amount'],
                        'rows' => array_map(
                            fn (array $row) => [$row['order_type'], $row['orders'], $row['total_amount']],
                            $orderTypes
                        ),
                    ],
                    'top_products' => [
                        'headers' => ['Product', 'SKU', 'Quantity Sold', 'Revenue', 'Orders'],
                        'rows' => array_map(
                            fn (array $row) => [
                                $row['product_name'],
                                $row['sku'],
                                $row['quantity_sold'],
                                $row['total_revenue'],
                                $row['order_count'],
                            ],
                            $topProducts
                        ),
                    ],
                    'daily_sales' => [
                        'headers' => ['Date', 'Total Sales', 'Orders', 'Average Order'],
                        'rows' => array_map(
                            fn (array $row) => [$row['date'], $row['total_sales'], $row['orders'], $row['avg_order']],
                            $dailySales
                        ),
                    ],
                    'shift_performance' => [
                        'headers' => ['Shift No', 'Shift Type', 'Date', 'Employee', 'Sales', 'Orders', 'Variance', 'Status'],
                        'rows' => array_map(
                            fn (array $row) => [
                                $row['shift_number'],
                                $row['shift_type'],
                                $row['shift_date'],
                                $row['employee_name'],
                                $row['total_sales'],
                                $row['total_orders'],
                                $row['cash_variance'],
                                $row['status'],
                            ],
                            $shiftPerformance
                        ),
                    ],
                ],
            ];

            $departmentId = is_numeric($this->departmentId) ? (int) $this->departmentId : null;

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'report_category' => 'sales',
                'report_type' => 'sales_analytics',
                'report_name' => 'Sales Analytics Report',
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_sales' => (float) ($overview['total_sales'] ?? 0),
                    'total_orders' => (int) ($overview['total_orders'] ?? 0),
                    'avg_order_value' => (float) ($overview['avg_order_value'] ?? 0),
                    'net_revenue' => (float) ($overview['net_revenue'] ?? 0),
                    'gross_profit' => (float) ($profit['gross_profit'] ?? 0),
                    'gross_margin' => (float) ($profit['gross_margin'] ?? 0),
                    'refund_rate' => (float) ($overview['refund_rate'] ?? 0),
                ],
                'charts_data' => [
                    'payments' => $payments,
                    'order_types' => $orderTypes,
                    'hourly_sales' => $hourlySales,
                    'daily_sales' => $dailySales,
                    'category_sales' => $categorySales,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Sales analytics report generated and submitted for review.');
            $this->dispatch('notify', ['message' => 'Report generated successfully.', 'type' => 'success']);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate sales analytics report. Please try again.');
            $this->dispatch('notify', ['message' => 'Failed to generate report.', 'type' => 'error']);
        }
    }

    protected function prepareExportData()
    {
        return [
            'overview' => $this->salesOverview,
            'payments' => $this->paymentBreakdown->toArray(),
            'order_types' => $this->orderTypeBreakdown->toArray(),
            'top_products' => $this->topSellingProducts->toArray(),
            'hourly_sales' => $this->hourlySalesData->toArray(),
            'daily_sales' => $this->dailySalesData->toArray(),
            'shifts' => $this->shiftPerformance->toArray(),
            'categories' => $this->categorySales->toArray(),
            'profit' => $this->profitAnalysis,
            'period' => [
                'from' => $this->dateFrom,
                'to' => $this->dateTo,
                'period' => $this->selectedPeriod
            ]
        ];
    }

    protected function exportToCSV($data)
    {
        $filename = 'sales_analytics_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // Write overview section
        fputcsv($handle, ['Sales Analytics Report']);
        fputcsv($handle, ['Period', Carbon::parse($data['period']['from'])->format('Y-m-d H:i') . ' to ' . Carbon::parse($data['period']['to'])->format('Y-m-d H:i')]);
        fputcsv($handle, []);

        // Overview metrics
        fputcsv($handle, ['Overview Metrics']);
        fputcsv($handle, ['Metric', 'Value']);
        foreach ($data['overview'] as $key => $value) {
            fputcsv($handle, [ucwords(str_replace('_', ' ', $key)), is_numeric($value) ? number_format($value, 2) : $value]);
        }
        fputcsv($handle, []);

        // Top Products
        fputcsv($handle, ['Top Selling Products']);
        fputcsv($handle, ['Product', 'Quantity', 'Revenue', 'Orders']);
        foreach ($data['top_products'] as $product) {
            fputcsv($handle, [
                $product['product']['name'] ?? 'N/A',
                $product['total_quantity'],
                $product['total_revenue'],
                $product['order_count']
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(function() use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }


    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.analytics.index', [
            'overview' => $this->salesOverview,
            'payments' => $this->paymentBreakdown,
            'orderTypes' => $this->orderTypeBreakdown,
            'topProducts' => $this->topSellingProducts,
            'hourlySales' => $this->hourlySalesData,
            'dailySales' => $this->dailySalesData,
            'shifts' => $this->shiftPerformance,
            'categories' => $this->categorySales,
            'profit' => $this->profitAnalysis
        ]);
    }

    /**
     * Format currency value for display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService();
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        return $service->getSymbol($currency);
    }

    /**
     * Format percentage for display
     */
    protected function formatPercentage(float $value, int $decimals = 2): string
    {
        $service = new CurrencyFormattingService();
        return $service->formatPercentage($value, $decimals);
    }
}
