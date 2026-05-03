<?php

namespace App\Services\Reports\Definitions;

use App\Models\Stock;
use Carbon\Carbon;

class InventoryStockValuationDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Stock Valuation',
            'type' => 'inventory_stock_valuation',
            'category' => 'inventory',
            'order' => 2,
            'requires_department' => false,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $from = $context['period_from'] ?? now()->toDateString();
        $to = $context['period_to'] ?? now()->toDateString();

        $stocks = Stock::with(['item', 'item.category', 'item.unitOfMeasure'])
            ->where('branch_id', $branchId)
            ->get();

        return [
            'valuation_summary' => $this->buildValuationSummary($stocks),
            'category_valuation' => $this->buildCategoryValuation($stocks),
            'top_value_items' => $this->buildTopValueItems($stocks),
            'value_distribution' => $this->buildValueDistribution($stocks),
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $summary = $data['valuation_summary'] ?? [];

        return [
            'total_value' => $summary['total_value'] ?? 0,
            'available_value' => $summary['available_value'] ?? 0,
            'reserved_value' => $summary['reserved_value'] ?? 0,
            'damaged_value' => $summary['damaged_value'] ?? 0,
            'total_items' => $summary['total_items'] ?? 0,
            'average_item_value' => $summary['average_item_value'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        $categoryValuation = $data['category_valuation'] ?? [];

        return [
            'category_value_chart' => [
                'type' => 'bar',
                'labels' => array_slice(array_column($categoryValuation, 'category'), 0, 10),
                'datasets' => [
                    [
                        'label' => 'Value',
                        'data' => array_slice(array_column($categoryValuation, 'total_value'), 0, 10),
                        'color' => '#10b981',
                    ],
                ],
            ],
            'value_breakdown_chart' => [
                'type' => 'pie',
                'labels' => ['Available', 'Reserved', 'Damaged'],
                'data' => [
                    $data['valuation_summary']['available_value'] ?? 0,
                    $data['valuation_summary']['reserved_value'] ?? 0,
                    $data['valuation_summary']['damaged_value'] ?? 0,
                ],
                'colors' => ['#10b981', '#3b82f6', '#ef4444'],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'valuation_summary' => [
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Total Inventory Value', number_format($summary['total_value'] ?? 0, 2)],
                    ['Available Value', number_format($summary['available_value'] ?? 0, 2)],
                    ['Reserved Value', number_format($summary['reserved_value'] ?? 0, 2)],
                    ['Damaged Value', number_format($summary['damaged_value'] ?? 0, 2)],
                    ['Total Items', number_format($summary['total_items'] ?? 0)],
                    ['Average Value per Item', number_format($summary['average_item_value'] ?? 0, 2)],
                ],
            ],
            'category_valuation' => [
                'headers' => ['Category', 'Items', 'Total Qty', 'Total Value', '% of Total'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['category'],
                        $item['item_count'],
                        number_format($item['total_quantity'], 0),
                        number_format($item['total_value'], 2),
                        number_format($item['percentage'], 1).'%',
                    ];
                }, $data['category_valuation'] ?? []),
            ],
            'top_value_items' => [
                'headers' => ['Item', 'SKU', 'Category', 'Qty', 'Avg Cost', 'Total Value'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['item_name'],
                        $item['sku'],
                        $item['category'],
                        number_format($item['quantity'], 2),
                        number_format($item['average_cost'], 2),
                        number_format($item['total_value'], 2),
                    ];
                }, $data['top_value_items'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];
        $recommendations = [];

        $totalValue = $summary['total_value'] ?? 0;
        $totalItems = $summary['total_items'] ?? 0;
        $damagedValue = $summary['damaged_value'] ?? 0;

        if ($totalValue > 0) {
            $highlights[] = 'Total inventory value is '.number_format($totalValue, 2).'.';
        }

        if ($totalItems > 0) {
            $avgValue = $totalValue / $totalItems;
            $highlights[] = 'Average value per item: '.number_format($avgValue, 2).'.';
        }

        if ($damagedValue > 0) {
            $concerns[] = 'Damaged inventory value is '.number_format($damagedValue, 2).'. Consider reviewing damage policies.';
        }

        $topItems = $data['top_value_items'] ?? [];
        if (! empty($topItems)) {
            $topItem = $topItems[0] ?? null;
            if ($topItem) {
                $recommendations[] = "Highest value item: {$topItem['item_name']} at ".number_format($topItem['total_value'], 2).'.';
            }
        }

        return [
            'overview' => 'Total inventory valuation for the period is '.number_format($totalValue, 2)." across {$totalItems} items.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $recommendations,
        ];
    }

    private function buildValuationSummary($stocks): array
    {
        $totalValue = $stocks->sum(fn ($s) => ($s->quantity_available + $s->quantity_reserved) * $s->average_cost);
        $availableValue = $stocks->sum(fn ($s) => $s->quantity_available * $s->average_cost);
        $reservedValue = $stocks->sum(fn ($s) => $s->quantity_reserved * $s->average_cost);
        $damagedValue = $stocks->sum(fn ($s) => $s->quantity_damaged * $s->average_cost);

        return [
            'total_value' => $totalValue,
            'available_value' => $availableValue,
            'reserved_value' => $reservedValue,
            'damaged_value' => $damagedValue,
            'total_items' => $stocks->count(),
            'average_item_value' => $stocks->count() > 0 ? $totalValue / $stocks->count() : 0,
        ];
    }

    private function buildCategoryValuation($stocks): array
    {
        $grouped = $stocks->groupBy(fn ($s) => $s->item?->category?->name ?? 'Uncategorized');
        $totalValue = $stocks->sum(fn ($s) => ($s->quantity_available + $s->quantity_reserved) * $s->average_cost);

        return $grouped->map(function ($group, $category) use ($totalValue) {
            $categoryValue = $group->sum(fn ($s) => ($s->quantity_available + $s->quantity_reserved) * $s->average_cost);

            return [
                'category' => $category,
                'item_count' => $group->count(),
                'total_quantity' => $group->sum('quantity_available'),
                'total_value' => $categoryValue,
                'percentage' => $totalValue > 0 ? ($categoryValue / $totalValue) * 100 : 0,
            ];
        })->sortByDesc('total_value')->values()->toArray();
    }

    private function buildTopValueItems($stocks): array
    {
        return $stocks->map(function ($stock) {
            $totalValue = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;

            return [
                'item_name' => $stock->item?->name ?? 'Unknown',
                'sku' => $stock->item?->sku ?? 'N/A',
                'category' => $stock->item?->category?->name ?? 'Uncategorized',
                'quantity' => $stock->quantity_available + $stock->quantity_reserved,
                'average_cost' => $stock->average_cost,
                'total_value' => $totalValue,
            ];
        })->sortByDesc('total_value')->take(20)->values()->toArray();
    }

    private function buildValueDistribution($stocks): array
    {
        return [
            'available' => $stocks->sum(fn ($s) => $s->quantity_available * $s->average_cost),
            'reserved' => $stocks->sum(fn ($s) => $s->quantity_reserved * $s->average_cost),
            'damaged' => $stocks->sum(fn ($s) => $s->quantity_damaged * $s->average_cost),
        ];
    }
}
