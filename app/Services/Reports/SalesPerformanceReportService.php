<?php

namespace App\Services\Reports;

use App\Models\Callback;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockVariance;
use Illuminate\Support\Facades\DB;

class SalesPerformanceReportService extends ReportService
{
    protected string $reportCategory = 'sales';
    protected string $reportType = 'sales_performance';

    /**
     * Get report name.
     */
    protected function getReportName(): string
    {
        return 'Sales Performance Report';
    }

    /**
     * Generate the sales performance report data.
     */
    protected function generateReportData(): array
    {
        $this->validateParameters();

        $sales = Sale::query()
            ->with(['soldBy', 'department', 'salesShift'])
            ->where('branch_id', $this->branchId)
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->when($this->shiftType, fn($q) => $q->where(fn($w) => $w
                ->whereHas('salesShift', fn($sq) => $sq->where('shift_type', $this->shiftType))
                ->orWhereHas('shift', fn($sq) => $sq->where('shift_type', $this->shiftType))))
            ->whereBetween('sale_time', [$this->periodFrom, $this->periodTo])
            ->where('status', '!=', 'cancelled')
            ->get();

        $saleItems = SaleItem::query()
            ->with(['product', 'item', 'sale'])
            ->whereHas('sale', function ($q) {
                $q->where('branch_id', $this->branchId)
                    ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
                    ->when($this->shiftType, fn($q) => $q->where(fn($w) => $w
                        ->whereHas('salesShift', fn($sq) => $sq->where('shift_type', $this->shiftType))
                        ->orWhereHas('shift', fn($sq) => $sq->where('shift_type', $this->shiftType))))
                    ->whereBetween('sale_time', [$this->periodFrom, $this->periodTo])
                    ->where('status', '!=', 'cancelled');
            })
            ->get();

        $variances = StockVariance::query()
            ->with(['product'])
            ->where('branch_id', $this->branchId)
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->shiftType, fn ($q) => $q->where('shift_type', $this->shiftType))
            ->whereBetween('variance_date', [
                \Carbon\Carbon::parse($this->periodFrom)->toDateString(),
                \Carbon\Carbon::parse($this->periodTo)->toDateString(),
            ])
            ->get();

        $callbacks = Callback::query()
            ->with(['product'])
            ->where('branch_id', $this->branchId)
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->shiftType, fn ($q) => $q->where('shift_type', $this->shiftType))
            ->whereBetween('callback_date', [
                \Carbon\Carbon::parse($this->periodFrom)->toDateString(),
                \Carbon\Carbon::parse($this->periodTo)->toDateString(),
            ])
            ->get();

        return [
            'revenue_overview' => $this->generateRevenueOverview($sales),
            'daily_sales' => $this->generateDailySales($sales),
            'product_performance' => $this->generateProductPerformance($saleItems),
            'order_type_analysis' => $this->generateOrderTypeAnalysis($sales),
            'payment_analysis' => $this->generatePaymentAnalysis($sales),
            'hourly_distribution' => $this->generateHourlyDistribution($sales),
            'sales_trends' => $this->generateSalesTrends($sales),
            'variance_analysis' => $this->generateVarianceAnalysis($variances, $saleItems),
            'callback_analysis' => $this->generateCallbackAnalysis($callbacks, $saleItems),
            'period_info' => [
                'from' => $this->periodFrom,
                'to' => $this->periodTo,
                'total_days' => \Carbon\Carbon::parse($this->periodFrom)
                    ->diffInDays(\Carbon\Carbon::parse($this->periodTo)) + 1,
            ],
        ];
    }

    /**
     * Generate revenue overview.
     */
    private function generateRevenueOverview($sales): array
    {
        $totalRevenue = $sales->sum('total');
        $totalSubtotal = $sales->sum('subtotal');
        $totalTax = $sales->sum('tax');
        $totalDiscount = $sales->sum('discount');
        $totalOrders = $sales->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_subtotal' => $totalSubtotal,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'discount_percentage' => $totalSubtotal > 0 ? ($totalDiscount / $totalSubtotal) * 100 : 0,
            'tax_percentage' => $totalSubtotal > 0 ? ($totalTax / $totalSubtotal) * 100 : 0,
        ];
    }

    /**
     * Generate daily sales breakdown.
     */
    private function generateDailySales($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return \Carbon\Carbon::parse($sale->sale_time)->format('Y-m-d');
        })->map(function ($daySales, $date) {
            $revenue = $daySales->sum('total');
            $orders = $daySales->count();

            return [
                'date' => $date,
                'orders' => $orders,
                'revenue' => $revenue,
                'average_order_value' => $orders > 0 ? $revenue / $orders : 0,
                'discount_total' => $daySales->sum('discount'),
                'tax_total' => $daySales->sum('tax'),
            ];
        })->sortBy('date')->values()->toArray();
    }

    /**
     * Generate product performance analysis.
     */
    private function generateProductPerformance($saleItems): array
    {
        $keyed = $saleItems->groupBy(function ($saleItem) {
            return $saleItem->product_id ? 'p_' . $saleItem->product_id : 'i_' . $saleItem->item_id;
        });

        return $keyed->map(function ($items) {
            $first = $items->first();
            $name = $first->product_id
                ? ($first->product->name ?? 'Unknown Product')
                : ($first->item->name ?? 'Unknown Item');
            $totalQuantity = $items->sum('quantity');
            $totalRevenue = $items->sum('total');
            $totalDiscount = $items->sum('discount');

            return [
                'product_id' => $first->product_id,
                'product_name' => $name,
                'quantity_sold' => $totalQuantity,
                'revenue' => $totalRevenue,
                'discount_given' => $totalDiscount,
                'average_price' => $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0,
                'orders_count' => $items->pluck('sale_id')->unique()->count(),
            ];
        })->sortByDesc('revenue')->values()->toArray();
    }

    /**
     * Generate order type analysis (dine-in, takeaway, delivery).
     */
    private function generateOrderTypeAnalysis($sales): array
    {
        return $sales->groupBy('order_type')->map(function ($typeSales, $type) use ($sales) {
            $revenue = $typeSales->sum('total');
            $orders = $typeSales->count();
            $totalRevenue = $sales->sum('total');

            return [
                'order_type' => $type ?: 'Not Specified',
                'orders' => $orders,
                'revenue' => $revenue,
                'percentage_of_revenue' => $totalRevenue > 0 ? ($revenue / $totalRevenue) * 100 : 0,
                'average_order_value' => $orders > 0 ? $revenue / $orders : 0,
            ];
        })->sortByDesc('revenue')->values()->toArray();
    }

    /**
     * Generate payment analysis.
     */
    private function generatePaymentAnalysis($sales): array
    {
        // This assumes sales have a payment_method field
        // Adjust if your schema is different
        $cashSales = $sales->where('payment_method', 'cash');
        $cardSales = $sales->where('payment_method', 'card');
        $mobileSales = $sales->where('payment_method', 'mobile_money');

        return [
            'cash' => [
                'orders' => $cashSales->count(),
                'revenue' => $cashSales->sum('total'),
            ],
            'card' => [
                'orders' => $cardSales->count(),
                'revenue' => $cardSales->sum('total'),
            ],
            'mobile_money' => [
                'orders' => $mobileSales->count(),
                'revenue' => $mobileSales->sum('total'),
            ],
        ];
    }

    /**
     * Generate hourly distribution of sales.
     */
    private function generateHourlyDistribution($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return \Carbon\Carbon::parse($sale->sale_time)->format('H:00');
        })->map(function ($hourSales, $hour) {
            return [
                'hour' => $hour,
                'orders' => $hourSales->count(),
                'revenue' => $hourSales->sum('total'),
            ];
        })->sortBy('hour')->values()->toArray();
    }

    /**
     * Generate sales trends.
     */
    private function generateSalesTrends($sales): array
    {
        $weeklyTrends = $sales->groupBy(function ($sale) {
            return \Carbon\Carbon::parse($sale->sale_time)->startOfWeek()->format('Y-m-d');
        })->map(function ($weekSales, $week) {
            $revenue = $weekSales->sum('total');
            $orders = $weekSales->count();

            return [
                'week_start' => $week,
                'orders' => $orders,
                'revenue' => $revenue,
                'average_order_value' => $orders > 0 ? $revenue / $orders : 0,
            ];
        })->sortBy('week_start')->values()->toArray();

        return [
            'weekly' => $weeklyTrends,
        ];
    }

    /**
     * Generate stock variance analysis.
     */
    private function generateVarianceAnalysis($variances, $saleItems): array
    {
        $totalSoldQty = $saleItems->sum('quantity');
        $totalVarianceQty = $variances->sum('quantity');
        $unresolvedCount = $variances->whereIn('status', ['pending', 'under_review'])->count();

        $byProduct = $variances->groupBy('product_id')->map(function ($rows) {
            $first = $rows->first();
            return [
                'product_name' => $first->product->name ?? 'Unknown',
                'total_variance' => $rows->sum('quantity'),
                'occurrences'   => $rows->count(),
                'unresolved'    => $rows->whereIn('status', ['pending', 'under_review'])->count(),
            ];
        })->sortByDesc('total_variance')->values()->toArray();

        $byResolution = $variances->whereNotNull('resolution_type')
            ->groupBy('resolution_type')->map(function ($rows, $type) {
                return [
                    'type'     => ucfirst(str_replace('_', ' ', $type)),
                    'count'    => $rows->count(),
                    'quantity' => $rows->sum('quantity'),
                ];
            })->values()->toArray();

        return [
            'total_variances'   => $variances->count(),
            'total_qty'         => $totalVarianceQty,
            'unresolved_count'  => $unresolvedCount,
            'variance_rate'     => $totalSoldQty > 0 ? round(($totalVarianceQty / $totalSoldQty) * 100, 2) : 0,
            'by_product'        => $byProduct,
            'by_resolution'     => $byResolution,
        ];
    }

    /**
     * Generate sales callback analysis.
     */
    private function generateCallbackAnalysis($callbacks, $saleItems): array
    {
        $totalSoldQty = $saleItems->sum('quantity');
        $totalCallbackQty = $callbacks->sum('quantity');

        $byProduct = $callbacks->groupBy('product_id')->map(function ($rows) {
            $first = $rows->first();
            return [
                'product_name'     => $first->product->name ?? 'Unknown',
                'total_quantity'   => $rows->sum('quantity'),
                'occurrences'      => $rows->count(),
            ];
        })->sortByDesc('total_quantity')->values()->toArray();

        $byReason = $callbacks->whereNotNull('reason')
            ->groupBy('reason')->map(function ($rows, $reason) {
                return [
                    'reason'   => $reason ?: 'Not specified',
                    'count'    => $rows->count(),
                    'quantity' => $rows->sum('quantity'),
                ];
            })->sortByDesc('count')->values()->toArray();

        return [
            'total_callbacks'   => $callbacks->count(),
            'total_qty'         => $totalCallbackQty,
            'callback_rate'     => $totalSoldQty > 0 ? round(($totalCallbackQty / $totalSoldQty) * 100, 2) : 0,
            'by_product'        => $byProduct,
            'by_reason'         => $byReason,
        ];
    }

    /**
     * Generate summary metrics.
     */
    protected function generateSummaryMetrics(array $reportData): array
    {
        $overview  = $reportData['revenue_overview'];
        $dailySales = collect($reportData['daily_sales']);
        $products  = collect($reportData['product_performance']);
        $variance  = $reportData['variance_analysis'];
        $callback  = $reportData['callback_analysis'];

        $bestDay   = $dailySales->sortByDesc('revenue')->first();
        $worstDay  = $dailySales->sortBy('revenue')->where('revenue', '>', 0)->first();
        $topProduct = $products->first();

        return [
            'total_revenue'           => $overview['total_revenue'],
            'total_orders'            => $overview['total_orders'],
            'average_order_value'     => $overview['average_order_value'],
            'total_discount'          => $overview['total_discount'],
            'average_daily_revenue'   => $dailySales->avg('revenue'),
            'best_day'                => $bestDay,
            'worst_day'               => $worstDay,
            'top_selling_product'     => $topProduct['product_name'] ?? 'N/A',
            'products_sold'           => $products->count(),
            'total_variances'         => $variance['total_variances'],
            'unresolved_variances'    => $variance['unresolved_count'],
            'variance_rate'           => $variance['variance_rate'],
            'total_callbacks'         => $callback['total_callbacks'],
            'total_callback_qty'      => $callback['total_qty'],
            'callback_rate'           => $callback['callback_rate'],
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateChartsData(array $reportData): array
    {
        $dailySales = $reportData['daily_sales'];
        $products = array_slice($reportData['product_performance'], 0, 10);
        $orderTypes = $reportData['order_type_analysis'];
        $hourly = $reportData['hourly_distribution'];

        return [
            'daily_revenue_chart' => [
                'type' => 'line',
                'labels' => array_column($dailySales, 'date'),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_column($dailySales, 'revenue'),
                        'color' => '#10b981',
                    ],
                ],
            ],
            'daily_orders_chart' => [
                'type' => 'bar',
                'labels' => array_column($dailySales, 'date'),
                'datasets' => [
                    [
                        'label' => 'Orders',
                        'data' => array_column($dailySales, 'orders'),
                        'color' => '#3b82f6',
                    ],
                ],
            ],
            'top_products_chart' => [
                'type' => 'bar',
                'labels' => array_column($products, 'product_name'),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_column($products, 'revenue'),
                        'color' => '#8b5cf6',
                    ],
                ],
            ],
            'order_type_distribution' => [
                'type' => 'pie',
                'labels' => array_column($orderTypes, 'order_type'),
                'data' => array_column($orderTypes, 'revenue'),
                'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            ],
            'hourly_distribution_chart' => [
                'type' => 'line',
                'labels' => array_column($hourly, 'hour'),
                'datasets' => [
                    [
                        'label' => 'Revenue by Hour',
                        'data' => array_column($hourly, 'revenue'),
                        'color' => '#6366f1',
                    ],
                ],
            ],
        ];
    }
}
