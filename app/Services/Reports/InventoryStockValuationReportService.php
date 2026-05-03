<?php

namespace App\Services\Reports;

use App\Models\Stock;
use Carbon\Carbon;

class InventoryStockValuationReportService extends ReportService
{
    protected string $reportCategory = 'inventory';

    protected string $reportType = 'inventory_stock_valuation';

    protected function getReportName(): string
    {
        return 'Inventory Stock Valuation Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $stocks = Stock::with(['item', 'item.category', 'item.unitOfMeasure'])
            ->where('branch_id', $this->branchId)
            ->get();

        $from = $this->periodFrom ?? now()->toDateString();
        $to = $this->periodTo ?? now()->toDateString();

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

    protected function generateSummaryMetrics(array $reportData): array
    {
        $summary = $reportData['valuation_summary'] ?? [];

        return [
            'total_value' => $summary['total_value'] ?? 0,
            'available_value' => $summary['available_value'] ?? 0,
            'reserved_value' => $summary['reserved_value'] ?? 0,
            'damaged_value' => $summary['damaged_value'] ?? 0,
            'total_items' => $summary['total_items'] ?? 0,
            'average_item_value' => $summary['average_item_value'] ?? 0,
        ];
    }

    protected function generateChartsData(array $reportData): array
    {
        $categoryValuation = $reportData['category_valuation'] ?? [];

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
                    $reportData['valuation_summary']['available_value'] ?? 0,
                    $reportData['valuation_summary']['reserved_value'] ?? 0,
                    $reportData['valuation_summary']['damaged_value'] ?? 0,
                ],
                'colors' => ['#10b981', '#3b82f6', '#ef4444'],
            ],
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
