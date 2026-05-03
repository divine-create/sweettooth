<?php

namespace App\Services\Reports;

use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryStockLevelsReportService extends ReportService
{
    protected string $reportCategory = 'inventory';

    protected string $reportType = 'inventory_stock_levels';

    protected function getReportName(): string
    {
        return 'Inventory Stock Levels Report';
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
            'stock_summary' => $this->buildStockSummary($stocks),
            'health_breakdown' => $this->buildHealthBreakdown($stocks),
            'category_breakdown' => $this->buildCategoryBreakdown($stocks),
            'low_stock_items' => $this->buildLowStockItems($stocks),
            'out_of_stock_items' => $this->buildOutOfStockItems($stocks),
            'turnover_analysis' => $this->buildTurnoverAnalysis($stocks, $from, $to),
            'reorder_recommendations' => $this->buildReorderRecommendations($stocks),
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            ],
        ];
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        $summary = $reportData['stock_summary'] ?? [];

        return [
            'total_items' => $summary['total_items'] ?? 0,
            'total_quantity' => $summary['total_quantity'] ?? 0,
            'out_of_stock' => $summary['out_of_stock'] ?? 0,
            'low_stock' => $summary['low_stock'] ?? 0,
            'adequate_stock' => $summary['adequate_stock'] ?? 0,
            'expired_items' => $summary['expired_items'] ?? 0,
            'critical_items' => $summary['critical_items'] ?? 0,
            'warning_items' => $summary['warning_items'] ?? 0,
        ];
    }

    protected function generateChartsData(array $reportData): array
    {
        $healthBreakdown = $reportData['health_breakdown'] ?? [];
        $categoryBreakdown = $reportData['category_breakdown'] ?? [];

        return [
            'health_status_chart' => [
                'type' => 'pie',
                'labels' => array_column($healthBreakdown, 'status'),
                'data' => array_column($healthBreakdown, 'count'),
                'colors' => ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
            ],
            'category_breakdown_chart' => [
                'type' => 'bar',
                'labels' => array_slice(array_column($categoryBreakdown, 'category'), 0, 10),
                'datasets' => [
                    [
                        'label' => 'Items',
                        'data' => array_slice(array_column($categoryBreakdown, 'item_count'), 0, 10),
                        'color' => '#3b82f6',
                    ],
                ],
            ],
        ];
    }

    private function buildStockSummary($stocks): array
    {
        $totalItems = $stocks->count();
        $outOfStock = $stocks->where('quantity_available', '<=', 0)->count();
        $lowStock = $stocks->filter(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;

            return $stock->quantity_available > 0 && $stock->quantity_available < $reorderLevel;
        })->count();
        $adequateStock = $stocks->filter(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;

            return $stock->quantity_available >= $reorderLevel;
        })->count();

        return [
            'total_items' => $totalItems,
            'total_quantity' => $stocks->sum('quantity_available'),
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'adequate_stock' => $adequateStock,
            'expired_items' => $stocks->where('health_status', 'expired')->count(),
            'critical_items' => $stocks->where('health_status', 'critical')->count(),
            'warning_items' => $stocks->where('health_status', 'warning')->count(),
        ];
    }

    private function buildHealthBreakdown($stocks): array
    {
        $grouped = $stocks->groupBy('health_status');

        return $grouped->map(function ($group, $status) use ($stocks) {
            return [
                'status' => $status ?: 'good',
                'count' => $group->count(),
                'percentage' => $stocks->count() > 0 ? ($group->count() / $stocks->count()) * 100 : 0,
            ];
        })->values()->toArray();
    }

    private function buildCategoryBreakdown($stocks): array
    {
        return $stocks->groupBy(function ($stock) {
            return $stock->item?->category?->name ?? 'Uncategorized';
        })->map(function ($group, $category) {
            return [
                'category' => $category,
                'item_count' => $group->count(),
                'total_quantity' => $group->sum('quantity_available'),
            ];
        })->sortByDesc('item_count')->values()->toArray();
    }

    private function buildLowStockItems($stocks): array
    {
        return $stocks->filter(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;

            return $stock->quantity_available > 0 && $stock->quantity_available < $reorderLevel;
        })->sortBy('quantity_available')->take(20)->map(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;

            return [
                'item_name' => $stock->item?->name ?? 'Unknown',
                'sku' => $stock->item?->sku ?? 'N/A',
                'current_qty' => $stock->quantity_available,
                'reorder_level' => $reorderLevel,
                'category' => $stock->item?->category?->name ?? 'Uncategorized',
                'urgency' => $stock->quantity_available <= 0 ? 'Critical' :
                    ($stock->quantity_available <= $reorderLevel * 0.5 ? 'High' : 'Medium'),
            ];
        })->values()->toArray();
    }

    private function buildOutOfStockItems($stocks): array
    {
        return $stocks->where('quantity_available', '<=', 0)->map(function ($stock) {
            return [
                'item_name' => $stock->item?->name ?? 'Unknown',
                'sku' => $stock->item?->sku ?? 'N/A',
                'category' => $stock->item?->category?->name ?? 'Uncategorized',
            ];
        })->values()->toArray();
    }

    private function buildTurnoverAnalysis($stocks, $from, $to): array
    {
        $movements = StockMovement::where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
            ->selectRaw('item_id, SUM(ABS(quantity)) as total_moved, COUNT(*) as movement_count')
            ->groupBy('item_id')
            ->orderByDesc('total_moved')
            ->limit(10)
            ->get();

        return $movements->map(function ($item) use ($stocks) {
            $stock = $stocks->firstWhere('item_id', $item->item_id);
            $avgStock = $stock?->quantity_available ?? 1;
            $turnoverRate = $avgStock > 0 ? ($item->total_moved / $avgStock) : 0;

            return [
                'item_name' => $stock?->item?->name ?? 'Unknown',
                'sku' => $stock?->item?->sku ?? 'N/A',
                'total_moved' => $item->total_moved,
                'movement_count' => $item->movement_count,
                'turnover_rate' => round($turnoverRate, 2),
                'velocity' => $turnoverRate > 2 ? 'High' : ($turnoverRate > 0.5 ? 'Moderate' : 'Low'),
            ];
        })->values()->toArray();
    }

    private function buildReorderRecommendations($stocks): array
    {
        return $stocks->filter(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;

            return $stock->quantity_available < $reorderLevel;
        })->sortBy(function ($stock) {
            return $stock->quantity_available / max($stock->item?->reorder_level ?? 1, 1);
        })->take(15)->map(function ($stock) {
            $reorderLevel = $stock->item?->reorder_level ?? 0;
            $suggestedQty = max($reorderLevel * 2 - $stock->quantity_available, $reorderLevel);

            return [
                'item_name' => $stock->item?->name ?? 'Unknown',
                'current_qty' => $stock->quantity_available,
                'reorder_level' => $reorderLevel,
                'suggested_qty' => $suggestedQty,
                'urgency' => $stock->quantity_available <= 0 ? 'Critical' :
                    ($stock->quantity_available <= $reorderLevel * 0.5 ? 'High' : 'Medium'),
            ];
        })->values()->toArray();
    }
}
