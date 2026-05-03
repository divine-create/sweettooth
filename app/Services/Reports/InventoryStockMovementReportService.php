<?php

namespace App\Services\Reports;

use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryStockMovementReportService extends ReportService
{
    protected string $reportCategory = 'inventory';

    protected string $reportType = 'inventory_stock_movement';

    protected function getReportName(): string
    {
        return 'Inventory Stock Movement Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $query = StockMovement::with(['stock.item', 'stock.item.category'])
            ->where('branch_id', $this->branchId)
            ->whereBetween('movement_date', [
                Carbon::parse($this->periodFrom)->startOfDay(),
                Carbon::parse($this->periodTo)->endOfDay(),
            ]);

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $from = $this->periodFrom ?? now()->toDateString();
        $to = $this->periodTo ?? now()->toDateString();

        return [
            'movement_summary' => $this->buildMovementSummary($movements),
            'movement_by_type' => $this->buildMovementByType($movements),
            'daily_movements' => $this->buildDailyMovements($movements),
            'top_moving_items' => $this->buildTopMovingItems($movements),
            'movement_details' => $this->buildMovementDetails($movements),
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
            ],
        ];
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        $summary = $reportData['movement_summary'] ?? [];

        return [
            'total_movements' => $summary['total_movements'] ?? 0,
            'total_in' => $summary['total_in'] ?? 0,
            'total_out' => $summary['total_out'] ?? 0,
            'net_change' => $summary['net_change'] ?? 0,
            'adjustments' => $summary['adjustments'] ?? 0,
            'transfers' => $summary['transfers'] ?? 0,
            'damaged' => $summary['damaged'] ?? 0,
            'returns' => $summary['returns'] ?? 0,
            'average_daily_movements' => $summary['average_daily_movements'] ?? 0,
        ];
    }

    protected function generateChartsData(array $reportData): array
    {
        $dailyMovements = $reportData['daily_movements'] ?? [];
        $movementByType = $reportData['movement_by_type'] ?? [];

        return [
            'daily_movement_chart' => [
                'type' => 'bar',
                'labels' => array_column($dailyMovements, 'date'),
                'datasets' => [
                    [
                        'label' => 'Stock In',
                        'data' => array_column($dailyMovements, 'stock_in'),
                        'color' => '#10b981',
                    ],
                    [
                        'label' => 'Stock Out',
                        'data' => array_column($dailyMovements, 'stock_out'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
            'movement_type_chart' => [
                'type' => 'pie',
                'labels' => array_column($movementByType, 'type'),
                'data' => array_column($movementByType, 'quantity'),
                'colors' => ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4'],
            ],
        ];
    }

    private function buildMovementSummary($movements): array
    {
        $totalMovements = $movements->count();
        $totalIn = $movements->whereIn('type', ['in', 'return'])->sum('quantity');
        $totalOut = $movements->whereIn('type', ['out', 'transfer', 'damaged'])->sum('quantity');
        $adjustments = $movements->where('type', 'adjustment')->sum('quantity');
        $transfers = $movements->where('type', 'transfer')->sum('quantity');
        $damaged = $movements->where('type', 'damaged')->sum('quantity');
        $returns = $movements->where('type', 'return')->sum('quantity');

        $days = $movements->groupBy(fn ($m) => Carbon::parse($m->movement_date)->format('Y-m-d'))->count();
        $avgDaily = $days > 0 ? $totalMovements / $days : 0;

        return [
            'total_movements' => $totalMovements,
            'total_in' => $totalIn,
            'total_out' => abs($totalOut),
            'net_change' => $totalIn - abs($totalOut),
            'adjustments' => $adjustments,
            'transfers' => $transfers,
            'damaged' => $damaged,
            'returns' => $returns,
            'average_daily_movements' => $avgDaily,
        ];
    }

    private function buildMovementByType($movements): array
    {
        return $movements->groupBy('type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'quantity' => $group->sum('quantity'),
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    private function buildDailyMovements($movements): array
    {
        return $movements->groupBy(fn ($m) => Carbon::parse($m->movement_date)->format('M d'))
            ->map(function ($dayMovements, $date) {
                return [
                    'date' => $date,
                    'stock_in' => $dayMovements->whereIn('type', ['in', 'return'])->sum('quantity'),
                    'stock_out' => abs($dayMovements->whereIn('type', ['out', 'transfer', 'damaged'])->sum('quantity')),
                    'movements' => $dayMovements->count(),
                ];
            })->values()->toArray();
    }

    private function buildTopMovingItems($movements): array
    {
        return $movements->groupBy('stock_id')
            ->map(function ($itemMovements, $stockId) {
                $stock = $itemMovements->first()->stock;

                return [
                    'item_name' => $stock?->item?->name ?? 'Unknown',
                    'sku' => $stock?->item?->sku ?? 'N/A',
                    'movement_count' => $itemMovements->count(),
                    'total_quantity' => $itemMovements->sum('quantity'),
                    'avg_quantity' => $itemMovements->count() > 0 ? $itemMovements->sum('quantity') / $itemMovements->count() : 0,
                ];
            })
            ->sortByDesc('movement_count')
            ->take(15)
            ->values()
            ->toArray();
    }

    private function buildMovementDetails($movements): array
    {
        return $movements->take(100)->map(function ($movement) {
            return [
                'date' => $movement->movement_date,
                'item_name' => $movement->stock?->item?->name ?? 'Unknown',
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reference' => $movement->reference_type,
                'notes' => $movement->notes,
            ];
        })->values()->toArray();
    }
}
