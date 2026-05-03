<?php

namespace App\Services\Reports\Definitions;

use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryStockMovementDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Stock Movement',
            'type' => 'inventory_stock_movement',
            'category' => 'inventory',
            'order' => 3,
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

        $query = StockMovement::with(['stock.item', 'stock.item.category'])
            ->where('branch_id', $branchId)
            ->whereBetween('movement_date', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

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

    public function summary(array $data, array $context): array
    {
        $summary = $data['movement_summary'] ?? [];

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

    public function charts(array $data, array $context): array
    {
        $dailyMovements = $data['daily_movements'] ?? [];
        $movementByType = $data['movement_by_type'] ?? [];

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

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'movement_summary' => [
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Total Movements', number_format($summary['total_movements'] ?? 0)],
                    ['Total Stock In', number_format($summary['total_in'] ?? 0, 2)],
                    ['Total Stock Out', number_format($summary['total_out'] ?? 0, 2)],
                    ['Net Change', number_format($summary['net_change'] ?? 0, 2)],
                    ['Adjustments', number_format($summary['adjustments'] ?? 0, 2)],
                    ['Transfers', number_format($summary['transfers'] ?? 0, 2)],
                    ['Damaged', number_format($summary['damaged'] ?? 0, 2)],
                    ['Returns', number_format($summary['returns'] ?? 0, 2)],
                    ['Avg Daily Movements', number_format($summary['average_daily_movements'] ?? 0, 1)],
                ],
            ],
            'movement_by_type' => [
                'headers' => ['Type', 'Movements', 'Total Quantity'],
                'rows' => array_map(function ($item) {
                    return [
                        ucfirst($item['type']),
                        number_format($item['count']),
                        number_format($item['quantity'], 2),
                    ];
                }, $data['movement_by_type'] ?? []),
            ],
            'top_moving_items' => [
                'headers' => ['Item', 'SKU', 'Movements', 'Total Qty', 'Avg Qty'],
                'rows' => array_map(function ($item) {
                    return [
                        $item['item_name'],
                        $item['sku'],
                        number_format($item['movement_count']),
                        number_format($item['total_quantity'], 2),
                        number_format($item['avg_quantity'], 2),
                    ];
                }, $data['top_moving_items'] ?? []),
            ],
            'movement_details' => [
                'headers' => ['Date', 'Item', 'Type', 'Qty', 'Reference', 'Notes'],
                'rows' => array_map(function ($item) {
                    return [
                        Carbon::parse($item['date'])->format('M d, H:i'),
                        $item['item_name'],
                        ucfirst($item['type']),
                        number_format($item['quantity'], 2),
                        $item['reference'] ?? '-',
                        $item['notes'] ?? '-',
                    ];
                }, array_slice($data['movement_details'] ?? [], 0, 50)),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];
        $recommendations = [];

        $totalIn = $summary['total_in'] ?? 0;
        $totalOut = $summary['total_out'] ?? 0;
        $netChange = $summary['net_change'] ?? 0;
        $damaged = $summary['damaged'] ?? 0;

        if ($netChange > 0) {
            $highlights[] = 'Net stock increase of '.number_format($netChange, 2).' units during the period.';
        } elseif ($netChange < 0) {
            $concerns[] = 'Net stock decrease of '.number_format(abs($netChange), 2).' units during the period.';
        } else {
            $highlights[] = 'Stock levels remained balanced during the period.';
        }

        if ($totalIn > $totalOut) {
            $highlights[] = 'More stock entered ('.number_format($totalIn, 2).') than left ('.number_format($totalOut, 2).').';
        } elseif ($totalOut > $totalIn) {
            $recommendations[] = 'Stock outflow exceeded inflow. Monitor reorder levels.';
        }

        if ($damaged > 0) {
            $concerns[] = number_format($damaged, 2).' units were marked as damaged.';
        }

        $topMoving = $data['top_moving_items'] ?? [];
        if (! empty($topMoving)) {
            $topItem = $topMoving[0] ?? null;
            if ($topItem && $topItem['movement_count'] > 10) {
                $recommendations[] = "Most active item: {$topItem['item_name']} with {$topItem['movement_count']} movements.";
            }
        }

        return [
            'overview' => "{$summary['total_movements']} stock movements recorded. Net change: ".number_format($netChange, 2).' units.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $recommendations,
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
