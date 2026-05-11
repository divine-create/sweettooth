<?php

namespace App\Services\Reports\Definitions;

use App\Models\ProductionRecord;
use App\Models\ProductionCallback;
use Carbon\Carbon;

class ProductionWasteAnalysisDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Waste Analysis Report',
            'type' => 'waste_analysis',
            'category' => 'production',
            'order' => 5,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->where('quantity_rejected', '>', 0)
            ->whereBetween('production_time', [
                Carbon::parse($context['period_from'])->startOfDay(),
                Carbon::parse($context['period_to'])->endOfDay()
            ])
            ->get();

        $callbacks = ProductionCallback::query()
            ->with(['shift', 'item', 'product', 'recordedBy'])
            ->whereHas('shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('callback_time', [
                Carbon::parse($context['period_from'])->startOfDay(),
                Carbon::parse($context['period_to'])->endOfDay()
            ])
            ->get();

        $periodDays = Carbon::parse($context['period_from'])
            ->diffInDays(Carbon::parse($context['period_to'])) + 1;

        $finishedWaste = $productionRecords->filter(fn($p) => !($p->recipe?->is_wip ?? false));
        $wipWaste = $productionRecords->filter(fn($p) => (bool)($p->recipe?->is_wip ?? false));

        return [
            'waste_overview' => $this->buildWasteOverview($productionRecords, $callbacks),
            'production_waste' => $this->buildProductionWaste($productionRecords),
            'callback_waste' => $this->buildCallbackWaste($callbacks),
            'waste_by_reason' => $this->buildWasteByReason($productionRecords, $callbacks),
            'finished_goods_waste' => $this->buildWasteByProduct($finishedWaste, $callbacks),
            'wip_goods_waste' => $this->buildWasteByProduct($wipWaste, collect()),
            'waste_by_item' => $this->buildWasteByItem($callbacks),
            'daily_waste_trends' => $this->buildDailyWasteTrends($productionRecords, $callbacks),
            'employee_waste_analysis' => $this->buildEmployeeWasteAnalysis($productionRecords),
            'period_info' => [
                'from' => $context['period_from'],
                'to' => $context['period_to'],
                'total_days' => $periodDays,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $overview = $data['waste_overview'] ?? [];
        $trends = collect($data['daily_waste_trends'] ?? []);

        return [
            'total_waste' => $overview['total_waste_quantity'] ?? 0,
            'production_waste' => $overview['production_rejection_waste'] ?? 0,
            'callback_waste' => $overview['callback_waste'] ?? 0,
            'total_incidents' => $overview['total_waste_incidents'] ?? 0,
            'average_daily_waste' => $trends->avg('total_waste') ?? 0,
            'highest_waste_day' => $trends->sortByDesc('total_waste')->first(),
            'lowest_waste_day' => $trends->sortBy('total_waste')->where('total_waste', '>', 0)->first(),
            'top_waste_reason' => !empty($data['waste_by_reason'])
                ? $data['waste_by_reason'][0]['reason']
                : 'N/A',
            'top_waste_product' => !empty($data['waste_by_product'])
                ? $data['waste_by_product'][0]['product']
                : 'N/A',
            'top_waste_item' => !empty($data['waste_by_item'])
                ? $data['waste_by_item'][0]['item']
                : 'N/A',
        ];
    }

    public function charts(array $data, array $context): array
    {
        $trends = $data['daily_waste_trends'] ?? [];
        $byReason = array_slice($data['waste_by_reason'] ?? [], 0, 5);
        $byProduct = array_slice($data['waste_by_product'] ?? [], 0, 10);

        return [
            'daily_waste_chart' => [
                'type' => 'line',
                'labels' => array_column($trends, 'date'),
                'datasets' => [
                    [
                        'label' => 'Production Waste',
                        'data' => array_column($trends, 'production_waste'),
                        'color' => '#ef4444',
                    ],
                    [
                        'label' => 'Callback Waste',
                        'data' => array_column($trends, 'callback_waste'),
                        'color' => '#f59e0b',
                    ],
                ],
            ],
            'waste_by_reason_chart' => [
                'type' => 'pie',
                'labels' => array_column($byReason, 'reason'),
                'data' => array_column($byReason, 'quantity'),
                'colors' => ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e'],
            ],
            'waste_by_product_chart' => [
                'type' => 'bar',
                'labels' => array_column($byProduct, 'product'),
                'datasets' => [
                    [
                        'label' => 'Total Waste',
                        'data' => array_column($byProduct, 'total_waste'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
            'waste_by_item_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($data['waste_by_item'] ?? [], 0, 10), 'item'),
                'datasets' => [
                    [
                        'label' => 'Total Waste',
                        'data' => array_column(array_slice($data['waste_by_item'] ?? [], 0, 10), 'quantity'),
                        'color' => '#f97316',
                    ],
                ],
            ],
            'waste_type_distribution' => [
                'type' => 'doughnut',
                'labels' => ['Production Rejection', 'Raw Material Callback', 'Finished Product Callback'],
                'data' => [
                    $data['waste_overview']['production_rejection_waste'] ?? 0,
                    $data['waste_overview']['raw_material_waste'] ?? 0,
                    ($data['waste_overview']['callback_waste'] ?? 0) - ($data['waste_overview']['raw_material_waste'] ?? 0),
                ],
                'colors' => ['#ef4444', '#f59e0b', '#eab308'],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'waste_by_reason' => [
                'headers' => ['Reason', 'Incidents', 'Quantity'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['reason'] ?? '-',
                        $row['count'] ?? 0,
                        $row['quantity'] ?? 0,
                    ];
                }, $data['waste_by_reason'] ?? []),
            ],
            'finished_goods_waste' => [
                'headers' => ['Produce Finished Good', 'Production Waste', 'Callback Waste', 'Total Waste', 'Incidents'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product'] ?? '-',
                        $row['production_waste'] ?? 0,
                        $row['callback_waste'] ?? 0,
                        $row['total_waste'] ?? 0,
                        $row['incidents'] ?? 0,
                    ];
                }, $data['finished_goods_waste'] ?? []),
            ],
            'wip_goods_waste' => [
                'headers' => ['WIP Produce', 'Production Waste', 'Callback Waste', 'Total Waste', 'Incidents'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product'] ?? '-',
                        $row['production_waste'] ?? 0,
                        $row['callback_waste'] ?? 0,
                        $row['total_waste'] ?? 0,
                        $row['incidents'] ?? 0,
                    ];
                }, $data['wip_goods_waste'] ?? []),
            ],
            'waste_by_item' => [
                'headers' => ['Item', 'Callback Waste', 'Incidents'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['item'] ?? '-',
                        $row['quantity'] ?? 0,
                        $row['incidents'] ?? 0,
                    ];
                }, $data['waste_by_item'] ?? []),
            ],
            'daily_waste' => [
                'headers' => ['Date', 'Production Waste', 'Callback Waste', 'Total Waste', 'Incidents'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['production_waste'] ?? 0,
                        $row['callback_waste'] ?? 0,
                        $row['total_waste'] ?? 0,
                        $row['incidents'] ?? 0,
                    ];
                }, $data['daily_waste_trends'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        $totalWaste = $summary['total_waste'] ?? 0;
        $avgDaily = $summary['average_daily_waste'] ?? 0;

        if ($avgDaily > 0) {
            $highlights[] = "Average daily waste is {$avgDaily}.";
        }

        if ($totalWaste > 0 && ($summary['production_waste'] ?? 0) > ($summary['callback_waste'] ?? 0)) {
            $concerns[] = 'Production rejections contribute more waste than callbacks.';
        }

        $topReason = $summary['top_waste_reason'] ?? null;
        if ($topReason && $topReason !== 'N/A') {
            $concerns[] = "Top waste reason: {$topReason}.";
        }

        return [
            'overview' => "Waste analysis (per product and per item) for {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $this->buildRecommendations($summary),
        ];
    }

    private function buildRecommendations(array $summary): array
    {
        $recommendations = [];

        if (($summary['total_waste'] ?? 0) > 0) {
            $recommendations[] = 'Review top waste reasons and implement prevention controls.';
        }

        if (($summary['production_waste'] ?? 0) > ($summary['callback_waste'] ?? 0)) {
            $recommendations[] = 'Strengthen in‑process quality checks to reduce rejections.';
        }

        return $recommendations;
    }

    private function buildWasteOverview($productionRecords, $callbacks): array
    {
        $totalProductionWaste = $productionRecords->sum('quantity_rejected');
        $totalCallbackWaste = $callbacks->sum('quantity');
        $totalWaste = $totalProductionWaste + $totalCallbackWaste;

        $rawMaterialCallbacks = $callbacks->where('source_type', 'raw_material_from_stock');
        $finishedProductCallbacks = $callbacks->where('source_type', 'finished_product_reject');

        return [
            'total_waste_quantity' => $totalWaste,
            'production_rejection_waste' => $totalProductionWaste,
            'callback_waste' => $totalCallbackWaste,
            'raw_material_waste' => $rawMaterialCallbacks->sum('quantity'),
            'finished_product_waste' => $finishedProductCallbacks->sum('quantity') + $totalProductionWaste,
            'production_waste_percentage' => $totalWaste > 0
                ? round(($totalProductionWaste / $totalWaste) * 100, 2)
                : 0,
            'callback_waste_percentage' => $totalWaste > 0
                ? round(($totalCallbackWaste / $totalWaste) * 100, 2)
                : 0,
            'total_waste_incidents' => $productionRecords->count() + $callbacks->count(),
            'production_rejection_incidents' => $productionRecords->count(),
            'callback_incidents' => $callbacks->count(),
        ];
    }

    private function buildProductionWaste($productionRecords): array
    {
        return $productionRecords->map(function ($record) {
            return [
                'date' => Carbon::parse($record->production_time)->format('Y-m-d'),
                'time' => Carbon::parse($record->production_time)->format('H:i'),
                'product' => $record->recipe->name ?? 'Unknown',
                'quantity_rejected' => $record->quantity_rejected,
                'reason' => $record->rejection_reason ?? 'Not specified',
                'quality_status' => $record->quality_status,
                'produced_by' => $record->producedBy->name ?? 'Unknown',
            ];
        })->toArray();
    }

    private function buildCallbackWaste($callbacks): array
    {
        return $callbacks->map(function ($callback) {
            return [
                'date' => Carbon::parse($callback->callback_time)->format('Y-m-d'),
                'time' => Carbon::parse($callback->callback_time)->format('H:i'),
                'type' => $callback->source_type,
                'item' => $callback->item_name,
                'quantity' => $callback->quantity,
                'uom' => $callback->uom,
                'reason' => $callback->formatted_reason,
                'status' => $callback->formatted_status,
                'recorded_by' => $callback->recordedBy->name ?? 'Unknown',
            ];
        })->toArray();
    }

    private function buildWasteByReason($productionRecords, $callbacks): array
    {
        $reasons = [];

        foreach ($productionRecords->groupBy('rejection_reason') as $reason => $records) {
            $reasonKey = $reason ?: 'Not specified';
            if (!isset($reasons[$reasonKey])) {
                $reasons[$reasonKey] = [
                    'reason' => $reasonKey,
                    'count' => 0,
                    'quantity' => 0,
                ];
            }
            $reasons[$reasonKey]['count'] += $records->count();
            $reasons[$reasonKey]['quantity'] += $records->sum('quantity_rejected');
        }

        foreach ($callbacks->groupBy('reason') as $reason => $cbRecords) {
            $reasonKey = ucwords(str_replace('_', ' ', $reason ?: 'Not specified'));
            if (!isset($reasons[$reasonKey])) {
                $reasons[$reasonKey] = [
                    'reason' => $reasonKey,
                    'count' => 0,
                    'quantity' => 0,
                ];
            }
            $reasons[$reasonKey]['count'] += $cbRecords->count();
            $reasons[$reasonKey]['quantity'] += $cbRecords->sum('quantity');
        }

        usort($reasons, function ($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });

        return $reasons;
    }

    private function buildWasteByProduct($productionRecords, $callbacks): array
    {
        $products = [];

        foreach ($productionRecords->groupBy('recipe_id') as $recipeId => $records) {
            $recipe = $records->first()->recipe;
            $productName = $recipe->name ?? 'Unknown';

            if (!isset($products[$productName])) {
                $products[$productName] = [
                    'product' => $productName,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }

            $products[$productName]['production_waste'] += $records->sum('quantity_rejected');
            $products[$productName]['incidents'] += $records->count();
        }

        $finishedProductCallbacks = $callbacks->where('source_type', 'finished_product_reject');
        foreach ($finishedProductCallbacks->groupBy('product_id') as $productId => $cbRecords) {
            $product = $cbRecords->first()->product;
            $productName = $product->name ?? 'Unknown';

            if (!isset($products[$productName])) {
                $products[$productName] = [
                    'product' => $productName,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }

            $products[$productName]['callback_waste'] += $cbRecords->sum('quantity');
            $products[$productName]['incidents'] += $cbRecords->count();
        }

        $result = array_values($products);
        foreach ($result as &$product) {
            $product['total_waste'] = $product['production_waste'] + $product['callback_waste'];
        }

        usort($result, function ($a, $b) {
            return $b['total_waste'] <=> $a['total_waste'];
        });

        return $result;
    }

    private function buildWasteByItem($callbacks): array
    {
        $items = [];

        $rawMaterialCallbacks = $callbacks->where('source_type', 'raw_material_from_stock');
        foreach ($rawMaterialCallbacks->groupBy('item_id') as $itemId => $cbRecords) {
            $item = $cbRecords->first()->item;
            $itemName = $item?->name ?? 'Unknown';

            if (!isset($items[$itemName])) {
                $items[$itemName] = [
                    'item' => $itemName,
                    'quantity' => 0,
                    'incidents' => 0,
                ];
            }

            $items[$itemName]['quantity'] += $cbRecords->sum('quantity');
            $items[$itemName]['incidents'] += $cbRecords->count();
        }

        $result = array_values($items);
        usort($result, function ($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });

        return $result;
    }

    private function buildDailyWasteTrends($productionRecords, $callbacks): array
    {
        $dailyData = [];

        foreach ($productionRecords->groupBy(function ($record) {
            return Carbon::parse($record->production_time)->format('Y-m-d');
        }) as $date => $records) {
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }
            $dailyData[$date]['production_waste'] += $records->sum('quantity_rejected');
            $dailyData[$date]['incidents'] += $records->count();
        }

        foreach ($callbacks->groupBy(function ($callback) {
            return Carbon::parse($callback->callback_time)->format('Y-m-d');
        }) as $date => $cbRecords) {
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }
            $dailyData[$date]['callback_waste'] += $cbRecords->sum('quantity');
            $dailyData[$date]['incidents'] += $cbRecords->count();
        }

        $result = array_values($dailyData);
        foreach ($result as &$day) {
            $day['total_waste'] = $day['production_waste'] + $day['callback_waste'];
        }

        usort($result, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $result;
    }

    private function buildEmployeeWasteAnalysis($productionRecords): array
    {
        return $productionRecords->groupBy('produced_by')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->producedBy;
            $totalProduced = $employeeRecords->sum('quantity_produced');
            $totalRejected = $employeeRecords->sum('quantity_rejected');

            return [
                'employee_id' => $employee->id ?? null,
                'employee_name' => $employee->name ?? 'Unknown',
                'total_produced' => $totalProduced,
                'total_rejected' => $totalRejected,
                'rejection_rate' => $totalProduced > 0
                    ? round(($totalRejected / $totalProduced) * 100, 2)
                    : 0,
                'incidents' => $employeeRecords->count(),
            ];
        })->sortByDesc('total_rejected')->values()->toArray();
    }
}
