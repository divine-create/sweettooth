<?php

namespace App\Services\Reports\Definitions;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;

class SalesPerformanceDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Sales Performance',
            'type' => 'sales_performance',
            'category' => 'sales',
            'requires_department' => true,
            'permissions' => [],
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $departmentId = $context['department_id'] ?? null;
        $from = $context['period_from'] ?? null;
        $to = $context['period_to'] ?? null;
        $shiftType = $context['shift_type'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        // NOTE: 'soldBy' is intentionally NOT eager-loaded. Eager-loading this
        // morphTo returns a null name here (Laravel morphTo/eager quirk with the
        // UUID-keyed User/Employee models), which made every row show "System".
        // Lazy access (used in buildSalesDetails/staff performance) resolves the
        // real cashier name correctly.
        $sales = Sale::query()
            ->with(['department', 'salesShift', 'shift', 'saleItems.product', 'saleItems.salesUom', 'payments'])
            ->where('branch_id', $branchId)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($shiftType, fn($q) => $q->where(fn($w) => $w
                ->whereHas('salesShift', fn($sq) => $sq->where('shift_type', $shiftType))
                ->orWhereHas('shift', fn($sq) => $sq->where('shift_type', $shiftType))))
            ->when($fromDate && $toDate, fn($q) => $q->whereBetween('sale_time', [$fromDate, $toDate]))
            ->where('status', '!=', 'cancelled')
            ->get();

        $saleItems = SaleItem::query()
            ->with(['product', 'item', 'sale'])
            ->whereHas('sale', function ($q) use ($branchId, $departmentId, $from, $to, $shiftType) {
                $q->where('branch_id', $branchId)
                    ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                    ->when($shiftType, fn($q) => $q->where(fn($w) => $w
                        ->whereHas('salesShift', fn($sq) => $sq->where('shift_type', $shiftType))
                        ->orWhereHas('shift', fn($sq) => $sq->where('shift_type', $shiftType))))
                    ->when($from && $to, function ($query) use ($from, $to) {
                        $query->whereBetween('sale_time', [
                            Carbon::parse($from)->startOfDay(),
                            Carbon::parse($to)->endOfDay(),
                        ]);
                    })
                    ->where('status', '!=', 'cancelled');
            })
            ->get();

        return [
            'revenue_overview' => $this->generateRevenueOverview($sales),
            'daily_sales' => $this->generateDailySales($sales),
            'product_performance' => $this->generateProductPerformance($saleItems),
            'staff_performance' => $this->generateStaffPerformance($sales),
            'order_type_analysis' => $this->generateOrderTypeAnalysis($sales),
            'payment_analysis' => $this->generatePaymentAnalysis($sales),
            'hourly_distribution' => $this->generateHourlyDistribution($sales),
            'sales_trends' => $this->generateSalesTrends($sales),
            'sales_details' => $this->buildSalesDetails($sales),
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $overview = $data['revenue_overview'] ?? [];
        $dailySales = collect($data['daily_sales'] ?? []);
        $products = collect($data['product_performance'] ?? []);

        $bestDay = $dailySales->sortByDesc('revenue')->first();
        $worstDay = $dailySales->sortBy('revenue')->where('revenue', '>', 0)->first();
        $topProduct = $products->first();
        $staff = collect($data['staff_performance'] ?? []);
        $topStaff = $staff->first();

        return [
            'total_revenue' => $overview['total_revenue'] ?? 0,
            'total_orders' => $overview['total_orders'] ?? 0,
            'average_order_value' => $overview['average_order_value'] ?? 0,
            'total_discount' => $overview['total_discount'] ?? 0,
            'average_daily_revenue' => $dailySales->avg('revenue') ?? 0,
            'best_day' => $bestDay['date'] ?? null,
            'best_day_revenue' => $bestDay['revenue'] ?? 0,
            'worst_day' => $worstDay['date'] ?? null,
            'worst_day_revenue' => $worstDay['revenue'] ?? 0,
            'top_selling_product' => $topProduct['product_name'] ?? 'N/A',
            'products_sold' => $products->count(),
            'active_sales_staff' => $staff->count(),
            'top_sales_staff' => $topStaff['staff_name'] ?? 'N/A',
            'top_sales_staff_revenue' => $topStaff['revenue'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        $dailySales = $data['daily_sales'] ?? [];
        $products = array_slice($data['product_performance'] ?? [], 0, 10);
        $orderTypes = $data['order_type_analysis'] ?? [];
        $hourly = $data['hourly_distribution'] ?? [];
        $payment = $data['payment_analysis'] ?? [];

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
                        'color' => '#f59e0b',
                    ],
                ],
            ],
            'order_type_chart' => [
                'type' => 'pie',
                'labels' => array_column($orderTypes, 'order_type'),
                'data' => array_column($orderTypes, 'revenue'),
                'colors' => ['#22c55e', '#3b82f6', '#ef4444', '#8b5cf6', '#06b6d4'],
            ],
            'payment_methods_chart' => [
                'type' => 'bar',
                'labels' => array_keys($payment),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_map(fn($row) => $row['revenue'] ?? 0, $payment),
                        'color' => '#6366f1',
                    ],
                ],
            ],
            'hourly_sales_chart' => [
                'type' => 'line',
                'labels' => array_column($hourly, 'hour'),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_column($hourly, 'revenue'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'sales_details' => [
                'headers' => ['Sale #', 'Time', 'Shift', 'Sold By', 'Items', 'Subtotal', 'Discount', 'Tax', 'Total', 'Cash', 'Transfer', 'POS', 'Other', 'Status'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['sale_number'],
                        $row['sale_time'],
                        $row['shift'],
                        $row['sold_by'],
                        $row['items'],
                        $row['subtotal'],
                        $row['discount'],
                        $row['tax'],
                        $row['total'],
                        $row['payment_breakdown']['cash'] ?? 0,
                        $row['payment_breakdown']['transfer'] ?? 0,
                        $row['payment_breakdown']['pos'] ?? 0,
                        $row['payment_breakdown']['other'] ?? 0,
                        $row['status'],
                    ];
                }, $data['sales_details'] ?? []),
            ],
            'daily_sales' => [
                'headers' => ['Date', 'Orders', 'Revenue', 'Avg Order', 'Discount', 'Tax'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'],
                        $row['orders'],
                        $row['revenue'],
                        $row['average_order_value'],
                        $row['discount_total'],
                        $row['tax_total'],
                    ];
                }, $data['daily_sales'] ?? []),
            ],
            'top_products' => [
                'headers' => ['Product', 'Quantity', 'Revenue', 'Avg Price', 'Orders'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product_name'],
                        $row['quantity_sold'],
                        $row['revenue'],
                        $row['average_price'],
                        $row['orders_count'],
                    ];
                }, array_slice($data['product_performance'] ?? [], 0, 15)),
            ],
            'staff_performance' => [
                'headers' => ['Staff', 'Orders', 'Revenue', 'Avg Order', 'Discount', 'Items Sold'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['staff_name'],
                        $row['orders'],
                        $row['revenue'],
                        $row['average_order_value'],
                        $row['discount_total'],
                        $row['items_sold'],
                    ];
                }, array_slice($data['staff_performance'] ?? [], 0, 15)),
            ],
            'order_type_analysis' => [
                'headers' => ['Order Type', 'Orders', 'Revenue', '% Revenue', 'Avg Order'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['order_type'],
                        $row['orders'],
                        $row['revenue'],
                        $row['percentage_of_revenue'],
                        $row['average_order_value'],
                    ];
                }, $data['order_type_analysis'] ?? []),
            ],
            'payment_methods' => [
                'headers' => ['Method', 'Orders', 'Revenue'],
                'rows' => collect($data['payment_analysis'] ?? [])->map(function ($row, $method) {
                    return [
                        strtoupper(str_replace('_', ' ', (string) $method)),
                        $row['orders'] ?? 0,
                        $row['revenue'] ?? 0,
                    ];
                })->values()->all(),
            ],
            'hourly_distribution' => [
                'headers' => ['Hour', 'Orders', 'Revenue'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['hour'],
                        $row['orders'],
                        $row['revenue'],
                    ];
                }, $data['hourly_distribution'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        if (!empty($summary['best_day'])) {
            $highlights[] = "Best day was {$summary['best_day']} with revenue of " . number_format($summary['best_day_revenue'], 2);
        }

        if (!empty($summary['top_selling_product']) && $summary['top_selling_product'] !== 'N/A') {
            $highlights[] = "Top selling product: {$summary['top_selling_product']}.";
        }

        if (($summary['total_revenue'] ?? 0) <= 0) {
            $concerns[] = 'No revenue recorded for the selected period.';
        }

        if (($summary['average_order_value'] ?? 0) <= 0) {
            $concerns[] = 'Average order value is zero. Check sales and pricing data.';
        }

        return [
            'overview' => 'Sales generated total revenue of ' . number_format($summary['total_revenue'] ?? 0, 2)
                . ' across ' . number_format($summary['total_orders'] ?? 0) . ' orders.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Review top products and ensure adequate stock availability.',
                'Investigate low-performing days to improve consistency.',
            ],
        ];
    }

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

    private function generateDailySales($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_time)->format('Y-m-d');
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

    private function generatePaymentAnalysis($sales): array
    {
        $completedPayments = $sales->flatMap(function ($sale) {
            return $sale->payments->where('status', 'completed');
        });

        // POS supports cash / transfer / pos. Anything else is bucketed as Other
        // so no revenue is silently dropped.
        $known = ['cash', 'transfer', 'pos'];
        $bucket = function ($rows) {
            return [
                'orders' => $rows->count(),
                'revenue' => $rows->sum('amount'),
            ];
        };

        $analysis = [
            'cash' => $bucket($completedPayments->where('payment_method', 'cash')),
            'transfer' => $bucket($completedPayments->where('payment_method', 'transfer')),
            'pos' => $bucket($completedPayments->where('payment_method', 'pos')),
        ];

        $other = $completedPayments->whereNotIn('payment_method', $known);
        if ($other->isNotEmpty()) {
            $analysis['other'] = $bucket($other);
        }

        return $analysis;
    }

    private function generateHourlyDistribution($sales): array
    {
        return $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_time)->format('H:00');
        })->map(function ($hourSales, $hour) {
            return [
                'hour' => $hour,
                'orders' => $hourSales->count(),
                'revenue' => $hourSales->sum('total'),
            ];
        })->sortBy('hour')->values()->toArray();
    }

    private function generateSalesTrends($sales): array
    {
        $weeklyTrends = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_time)->startOfWeek()->format('Y-m-d');
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

    private function buildSalesDetails($sales): array
    {
        return $sales->sortByDesc('sale_time')->map(function ($sale) {
            $itemsList = $sale->saleItems->map(function ($item) {
                $name = $item->product?->name ?? 'Item';
                $qty = $item->display_quantity ?? $item->quantity;
                $uom = $item->sales_uom_symbol ?? '';
                $suffix = $uom ? " {$uom}" : '';
                return [
                    'name' => $name,
                    'quantity' => (float) $qty,
                    'uom' => $uom,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'discount' => (float) $item->discount,
                    'total' => (float) $item->total,
                    'display' => sprintf('%s x %s%s', $name, rtrim(rtrim(number_format((float) $qty, 2), '0'), '.'), $suffix),
                ];
            });

            $items = $itemsList->pluck('display')->implode(', ');

            $soldBy = $sale->soldBy?->name
                ?? trim(($sale->soldBy?->first_name ?? '').' '.($sale->soldBy?->last_name ?? ''))
                ?: 'System';

            $paymentTotals = $sale->payments->groupBy('payment_method')->map(function ($payments) {
                return (float) $payments->sum('amount');
            });

            $paymentMethods = $sale->payments->groupBy('payment_method')->map(function ($payments, $method) {
                return strtoupper(str_replace('_', ' ', (string) $method)) . ' ' . number_format($payments->sum('amount'), 2);
            })->implode(' | ');

            // POS sales use the general `shift` (shift_id); older records may use
            // the legacy `salesShift` (sales_shift_id). Prefer whichever exists.
            $shiftLabel = $sale->salesShift?->shift_type
                ?? $sale->shift?->shift_type
                ?? $sale->shift?->shift_number
                ?? 'N/A';

            return [
                'sale_number' => $sale->sale_number ?? $sale->id,
                'sale_time' => optional($sale->sale_time)->format('Y-m-d H:i'),
                'shift' => $shiftLabel ? ucfirst((string) $shiftLabel) : 'N/A',
                'sold_by' => $soldBy ?: 'System',
                'items' => $items ?: 'N/A',
                'items_list' => $itemsList->values()->toArray(),
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount,
                'tax' => (float) $sale->tax,
                'total' => (float) $sale->total,
                'payment_breakdown' => [
                    'cash' => $paymentTotals->get('cash', 0),
                    'transfer' => $paymentTotals->get('transfer', 0),
                    'pos' => $paymentTotals->get('pos', 0),
                    'other' => $paymentTotals->except(['cash', 'transfer', 'pos'])->sum(),
                ],
                'payment_methods' => $paymentMethods ?: 'N/A',
                'status' => ucfirst((string) $sale->status),
            ];
        })->values()->toArray();
    }

    private function generateStaffPerformance($sales): array
    {
        return $sales->groupBy('sold_by_id')->map(function ($staffSales) {
            $sale = $staffSales->first();
            $staff = $sale?->soldBy;
            $staffName = $staff?->name
                ?? trim(($staff?->first_name ?? '').' '.($staff?->last_name ?? ''))
                ?: 'System';

            $orders = $staffSales->count();
            $revenue = $staffSales->sum('total');
            $discount = $staffSales->sum('discount');
            $itemsSold = $staffSales->flatMap(function ($sale) {
                return $sale->saleItems;
            })->sum('quantity');

            return [
                'staff_id' => $sale?->sold_by_id,
                'staff_name' => $staffName,
                'orders' => $orders,
                'revenue' => $revenue,
                'average_order_value' => $orders > 0 ? $revenue / $orders : 0,
                'discount_total' => $discount,
                'items_sold' => $itemsSold,
            ];
        })->sortByDesc('revenue')->values()->toArray();
    }
}
