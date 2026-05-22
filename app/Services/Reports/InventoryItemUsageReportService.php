<?php

namespace App\Services\Reports;

use App\Models\ItemDispatch;
use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryItemUsageReportService extends ReportService
{
    protected string $reportCategory = 'inventory';

    protected string $reportType = 'inventory_item_usage';

    protected function getReportName(): string
    {
        return 'Inventory Item Usage Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $fromDate = Carbon::parse($this->periodFrom)->startOfDay();
        $toDate = Carbon::parse($this->periodTo)->endOfDay();

        $stockMovements = StockMovement::with(['stock.item', 'stock.item.category'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->whereIn('type', ['out', 'adjustment', 'damaged'])
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->get();

        $dispatches = ItemDispatch::with(['item', 'item.category', 'itemRequest.department'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('dispatch_time', [$fromDate, $toDate])
            ->when($this->departmentId, fn ($q) => $q->whereHas('itemRequest', fn ($r) => $r->where('department_id', $this->departmentId)))
            ->get();

        return [
            'usage_summary' => $this->buildUsageSummary($stockMovements, $dispatches),
            'usage_by_source' => $this->buildUsageBySource($stockMovements, $dispatches),
            'usage_by_category' => $this->buildUsageByCategory($stockMovements, $dispatches),
            'top_consumed_items' => $this->buildTopConsumedItems($stockMovements, $dispatches),
            'daily_usage' => $this->buildDailyUsage($stockMovements, $dispatches),
            'dispatch_breakdown' => $this->buildDispatchBreakdown($dispatches),
            'period_info' => [
                'from' => $this->periodFrom,
                'to' => $this->periodTo,
                'total_days' => Carbon::parse($this->periodFrom)->diffInDays(Carbon::parse($this->periodTo)) + 1,
            ],
        ];
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        $summary = $reportData['usage_summary'] ?? [];

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

    protected function generateChartsData(array $reportData): array
    {
        $usageBySource = $reportData['usage_by_source'] ?? [];
        $dailyUsage = $reportData['daily_usage'] ?? [];
        $categoryBreakdown = $reportData['usage_by_category'] ?? [];

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
