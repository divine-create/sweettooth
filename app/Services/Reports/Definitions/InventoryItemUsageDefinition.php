<?php

namespace App\Services\Reports\Definitions;

use App\Models\ItemDispatch;
use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryItemUsageDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Item Usage',
            'type' => 'inventory_item_usage',
            'category' => 'inventory',
            'order' => 4,
            'requires_department' => false,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $departmentId = $context['department_id'] ?? null;
        $from = $context['period_from'] ?? now()->toDateString();
        $to = $context['period_to'] ?? now()->toDateString();

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $stockMovements = StockMovement::with(['stock.item', 'stock.item.category'])
            ->where('branch_id', $branchId)
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->get();

        $dispatches = ItemDispatch::with(['item', 'item.category', 'department'])
            ->where('branch_id', $branchId)
            ->whereBetween('dispatch_time', [$fromDate, $toDate])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->get();

        return [
            'usage_summary' => $this->buildUsageSummary($stockMovements, $dispatches),
            'usage_by_source' => $this->buildUsageBySource($stockMovements, $dispatches),
            'usage_by_category' => $this->buildUsageByCategory($stockMovements, $dispatches),
            'top_consumed_items' => $this->buildTopConsumedItems($stockMovements, $dispatches),
            'daily_usage' => $this->buildDailyUsage($stockMovements, $dispatches),
            'dispatch_breakdown' => $this->buildDispatchBreakdown($dispatches),
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $summary = $data['usage_summary'] ?? [];

        return [
            'total_quantity_used' => $summary['total_quantity_used'] ?? 0,
            'production_usage' => $summary['production_usage'] ?? 0,
            'dispatch_usage' => $summary['dispatch_usage'] ?? 0,
            'adjustment_usage' => $summary['adjustment_usage'] ?? 0,
            'damaged_usage' => $summary['damaged_usage'] ?? 0,
            'unique_items_used' => $summary['unique_items_used'] ?? 0,
            'average_daily_usage' => $summary['average_daily_usage'] ?? 0,
            'dispatches_count' => $summary['dispatches_count'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        $usageBySource = $data['usage_by_source'] ?? [];
        $dailyUsage = $data['daily_usage'] ?? [];
        $categoryBreakdown = $data['usage_by_category'] ?? [];

        return [
            'usage_by_source_chart' => [
                'type' => 'pie',
                'labels' => array_column($usageBySource, 'source'),
                'data' => array_column($usageBySource, 'quantity'),
                'colors' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
            ],
            'daily_usage_chart' => [
                'type' => 'bar',
                'labels' => array_column($dailyUsage, 'date'),
                'datasets' => [
                    [
                        'label' => 'Usage',
                        'data' => array_column($dailyUsage, 'quantity'),
                        'color' => '#8b5cf6',
                    ],
                ],
            ],
            'category_usage_chart' => [
                'type' => 'bar',
                'labels' => array_slice(array_column($categoryBreakdown, 'category'), 0, 10),
                'datasets' => [
                    [
                        'label' => 'Quantity',
                        'data' => array_slice(array_column($categoryBreakdown, 'quantity'), 0, 10),
                        'color' => '#06b6d4',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'usage_summary' => [
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Total Quantity Used', number_format($summary['total_quantity_used'] ?? 0, 2)],
                    ['Production Usage', number_format($summary['production_usage'] ?? 0, 2)],
                    ['Dispatch Usage', number_format($summary['dispatch_usage'] ?? 0, 2)],
                    ['Adjustment Usage', number_format($summary['adjustment_usage'] ?? 0, 2)],
                    ['Damaged Usage', number_format($summary['damaged_usage'] ?? 0, 2)],
                    ['Unique Items Used', number_format($summary['unique_items_used'] ?? 0)],
                    ['Average Daily Usage', number_format($summary['average_daily_usage'] ?? 0, 2)],
                    ['Total Dispatches', number_format($summary['dispatches_count'] ?? 0)],
                ],
            ],
            'usage_by_source' => [
                'headers' => ['Source', 'Movements', 'Quantity', '% of Total'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['source'],
                        number_format($item['count']),
                        number_format($item['quantity'], 2),
                        number_format($item['percentage'], 1).'%',
                    ];
                }, $data['usage_by_source'] ?? []),
            ],
            'top_consumed_items' => [
                'headers' => ['Item', 'SKU', 'Category', 'Total Qty', 'Movements', 'Avg Qty'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['item_name'],
                        $item['sku'],
                        $item['category'],
                        number_format($item['total_quantity'], 2),
                        number_format($item['movement_count']),
                        number_format($item['avg_quantity'], 2),
                    ];
                }, $data['top_consumed_items'] ?? []),
            ],
            'dispatch_breakdown' => [
                'headers' => ['Department', 'Dispatches', 'Quantity', '% of Total'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['department'],
                        number_format($item['count']),
                        number_format($item['quantity'], 2),
                        number_format($item['percentage'], 1).'%',
                    ];
                }, $data['dispatch_breakdown'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];
        $recommendations = [];

        $totalUsed = $summary['total_quantity_used'] ?? 0;
        $productionUsage = $summary['production_usage'] ?? 0;
        $dispatchUsage = $summary['dispatch_usage'] ?? 0;
        $uniqueItems = $summary['unique_items_used'] ?? 0;
        $damagedUsage = $summary['damaged_usage'] ?? 0;

        if ($totalUsed > 0) {
            $highlights[] = number_format($totalUsed, 2).' units consumed during the period.';
        }

        if ($productionUsage > 0 && $dispatchUsage > 0) {
            $productionPct = round(($productionUsage / $totalUsed) * 100, 1);
            $dispatchPct = round(($dispatchUsage / $totalUsed) * 100, 1);
            $highlights[] = "Production: {$productionPct}%, Dispatch: {$dispatchPct}%.";
        } elseif ($productionUsage > 0) {
            $highlights[] = 'All usage was for production.';
        } elseif ($dispatchUsage > 0) {
            $highlights[] = 'All usage was for dispatches.';
        }

        if ($uniqueItems > 0) {
            $highlights[] = "{$uniqueItems} different items were consumed.";
        }

        if ($damagedUsage > 0) {
            $damagedPct = $totalUsed > 0 ? round(($damagedUsage / $totalUsed) * 100, 1) : 0;
            $concerns[] = "{$damagedPct}% of usage was due to damage.";
        }

        $topItems = $data['top_consumed_items'] ?? [];
        if (! empty($topItems)) {
            $topItem = $topItems[0] ?? null;
            if ($topItem) {
                $recommendations[] = "Most consumed: {$topItem['item_name']} at ".number_format($topItem['total_quantity'], 2).' units.';
            }
        }

        return [
            'overview' => 'Total item usage: '.number_format($totalUsed, 2)." units across {$uniqueItems} items.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $recommendations,
        ];
    }

    private function buildUsageSummary($stockMovements, $dispatches): array
    {
        $totalUsed = $stockMovements->sum('quantity');
        $productionUsage = $stockMovements->where('type', 'out')->sum('quantity');
        $adjustmentUsage = $stockMovements->where('type', 'adjustment')->sum('quantity');
        $damagedUsage = $stockMovements->where('type', 'damaged')->sum('quantity');
        $dispatchUsage = $dispatches->sum('quantity');

        $uniqueItems = $stockMovements->pluck('stock.item_id')
            ->merge($dispatches->pluck('item_id'))
            ->filter()
            ->unique()
            ->count();

        $days = $stockMovements->groupBy(fn ($m) => Carbon::parse($m->movement_date)->format('Y-m-d'))->count();
        $days = max($days, 1);
        $avgDaily = $totalUsed / $days;

        return [
            'total_quantity_used' => $totalUsed + $dispatchUsage,
            'production_usage' => $productionUsage,
            'dispatch_usage' => $dispatchUsage,
            'adjustment_usage' => $adjustmentUsage,
            'damaged_usage' => $damagedUsage,
            'unique_items_used' => $uniqueItems,
            'average_daily_usage' => $avgDaily,
            'dispatches_count' => $dispatches->count(),
        ];
    }

    private function buildUsageBySource($stockMovements, $dispatches): array
    {
        $total = $stockMovements->sum('quantity') + $dispatches->sum('quantity');

        $sources = [];

        $outUsage = $stockMovements->where('type', 'out')->sum('quantity');
        if ($outUsage > 0) {
            $sources[] = [
                'source' => 'Production',
                'count' => $stockMovements->where('type', 'out')->count(),
                'quantity' => $outUsage,
                'percentage' => $total > 0 ? ($outUsage / $total) * 100 : 0,
            ];
        }

        $dispatchUsage = $dispatches->sum('quantity');
        if ($dispatchUsage > 0) {
            $sources[] = [
                'source' => 'Dispatch',
                'count' => $dispatches->count(),
                'quantity' => $dispatchUsage,
                'percentage' => $total > 0 ? ($dispatchUsage / $total) * 100 : 0,
            ];
        }

        $adjUsage = $stockMovements->where('type', 'adjustment')->sum('quantity');
        if ($adjUsage > 0) {
            $sources[] = [
                'source' => 'Adjustment',
                'count' => $stockMovements->where('type', 'adjustment')->count(),
                'quantity' => $adjUsage,
                'percentage' => $total > 0 ? ($adjUsage / $total) * 100 : 0,
            ];
        }

        $damagedUsage = $stockMovements->where('type', 'damaged')->sum('quantity');
        if ($damagedUsage > 0) {
            $sources[] = [
                'source' => 'Damaged',
                'count' => $stockMovements->where('type', 'damaged')->count(),
                'quantity' => $damagedUsage,
                'percentage' => $total > 0 ? ($damagedUsage / $total) * 100 : 0,
            ];
        }

        return $sources;
    }

    private function buildUsageByCategory($stockMovements, $dispatches): array
    {
        $usage = collect();

        foreach ($stockMovements as $movement) {
            $category = $movement->stock?->item?->category?->name ?? 'Uncategorized';
            $usage[$category] = ($usage[$category] ?? 0) + $movement->quantity;
        }

        foreach ($dispatches as $dispatch) {
            $category = $dispatch->item?->category?->name ?? 'Uncategorized';
            $usage[$category] = ($usage[$category] ?? 0) + $dispatch->quantity;
        }

        return $usage->map(fn ($qty, $cat) => [
            'category' => $cat,
            'quantity' => $qty,
        ])->sortByDesc('quantity')->values()->toArray();
    }

    private function buildTopConsumedItems($stockMovements, $dispatches): array
    {
        $items = collect();

        foreach ($stockMovements as $movement) {
            $itemId = $movement->stock?->item_id;
            if (! $itemId) {
                continue;
            }

            $current = $items->get($itemId, [
                'item_name' => $movement->stock?->item?->name ?? 'Unknown',
                'sku' => $movement->stock?->item?->sku ?? 'N/A',
                'category' => $movement->stock?->item?->category?->name ?? 'Uncategorized',
                'total_quantity' => 0,
                'movement_count' => 0,
            ]);

            $current['total_quantity'] += $movement->quantity;
            $current['movement_count']++;
            $items[$itemId] = $current;
        }

        foreach ($dispatches as $dispatch) {
            $itemId = $dispatch->item_id;
            if (! $itemId) {
                continue;
            }

            $current = $items->get($itemId, [
                'item_name' => $dispatch->item?->name ?? 'Unknown',
                'sku' => $dispatch->item?->sku ?? 'N/A',
                'category' => $dispatch->item?->category?->name ?? 'Uncategorized',
                'total_quantity' => 0,
                'movement_count' => 0,
            ]);

            $current['total_quantity'] += $dispatch->quantity;
            $current['movement_count']++;
            $items[$itemId] = $current;
        }

        return $items->map(function ($item) {
            $item['avg_quantity'] = $item['movement_count'] > 0 ? $item['total_quantity'] / $item['movement_count'] : 0;

            return $item;
        })->sortByDesc('total_quantity')->take(20)->values()->toArray();
    }

    private function buildDailyUsage($stockMovements, $dispatches): array
    {
        $daily = collect();

        foreach ($stockMovements as $movement) {
            $date = Carbon::parse($movement->movement_date)->format('M d');
            $daily[$date] = ($daily[$date] ?? 0) + $movement->quantity;
        }

        foreach ($dispatches as $dispatch) {
            $date = Carbon::parse($dispatch->dispatch_time)->format('M d');
            $daily[$date] = ($daily[$date] ?? 0) + $dispatch->quantity;
        }

        return $daily->map(fn ($qty, $date) => [
            'date' => $date,
            'quantity' => $qty,
        ])->values()->toArray();
    }

    private function buildDispatchBreakdown($dispatches): array
    {
        $total = $dispatches->sum('quantity');

        return $dispatches->groupBy(fn ($d) => $d->itemRequest?->department?->name ?? 'Unknown')
            ->map(function ($group, $dept) use ($total) {
                return [
                    'department' => $dept,
                    'count' => $group->count(),
                    'quantity' => $group->sum('quantity'),
                    'percentage' => $total > 0 ? ($group->sum('quantity') / $total) * 100 : 0,
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->toArray();
    }
}
