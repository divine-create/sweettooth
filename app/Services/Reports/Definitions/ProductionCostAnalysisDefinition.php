<?php

namespace App\Services\Reports\Definitions;

use App\Models\ItemRequestDetail;
use App\Models\ProductionRecord;
use Carbon\Carbon;

class ProductionCostAnalysisDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Cost Analysis Report',
            'type' => 'cost_analysis',
            'category' => 'production',
            'order' => 4,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $dispatchedItems = ItemRequestDetail::query()
            ->with(['item', 'itemRequest'])
            ->whereHas('itemRequest', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->where('quantity_dispatched', '>', 0)
            ->whereBetween('updated_at', [$context['period_from'], $context['period_to']])
            ->get();

        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy'])
            ->whereHas('dailyProduce.shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('production_time', [$context['period_from'], $context['period_to']])
            ->get();

        $periodDays = Carbon::parse($context['period_from'])
            ->diffInDays(Carbon::parse($context['period_to'])) + 1;

        return [
            'cost_overview' => $this->buildCostOverview($dispatchedItems, $productionRecords),
            'ingredient_costs' => $this->buildIngredientCosts($dispatchedItems),
            'product_costs' => $this->buildProductCosts($productionRecords),
            'cost_efficiency' => $this->buildCostEfficiency($productionRecords),
            'cost_trends' => $this->buildCostTrends($dispatchedItems, $productionRecords),
            'period_info' => [
                'from' => $context['period_from'],
                'to' => $context['period_to'],
                'total_days' => $periodDays,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $overview = $data['cost_overview'] ?? [];
        $efficiency = $data['cost_efficiency'] ?? [];

        return [
            'total_material_cost' => $overview['total_material_cost'] ?? 0,
            'total_production_cost' => $overview['total_production_cost'] ?? 0,
            'total_cost' => $overview['total_cost'] ?? 0,
            'cost_per_unit' => $overview['cost_per_unit'] ?? 0,
            'items_tracked' => $overview['items_dispatched'] ?? 0,
            'products_analyzed' => count($data['product_costs'] ?? []),
            'cost_efficiency_score' => $efficiency['cost_efficiency_score'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [
            'cost_breakdown_chart' => [
                'type' => 'pie',
                'labels' => ['Material Costs', 'Production Costs'],
                'data' => [
                    $data['cost_overview']['total_material_cost'] ?? 0,
                    $data['cost_overview']['total_production_cost'] ?? 0,
                ],
                'colors' => ['#3b82f6', '#10b981'],
            ],
            'ingredient_cost_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($data['ingredient_costs'] ?? [], 0, 10), 'item_name'),
                'datasets' => [
                    [
                        'label' => 'Total Cost',
                        'data' => array_column(array_slice($data['ingredient_costs'] ?? [], 0, 10), 'total_cost'),
                        'color' => '#8b5cf6',
                    ],
                ],
            ],
            'product_cost_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($data['product_costs'] ?? [], 0, 10), 'product_name'),
                'datasets' => [
                    [
                        'label' => 'Total Cost',
                        'data' => array_column(array_slice($data['product_costs'] ?? [], 0, 10), 'total_cost'),
                        'color' => '#f59e0b',
                    ],
                ],
            ],
            'cost_trends_chart' => [
                'type' => 'line',
                'labels' => array_column($data['cost_trends']['daily_costs'] ?? [], 'date'),
                'datasets' => [
                    [
                        'label' => 'Daily Costs',
                        'data' => array_column($data['cost_trends']['daily_costs'] ?? [], 'cost'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'item_costs' => [
                'headers' => ['Item', 'Unit Cost', 'Dispatched', 'Total Cost', 'Dispatches'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['item_name'] ?? '-',
                        $row['unit_cost'] ?? 0,
                        $row['total_dispatched'] ?? 0,
                        $row['total_cost'] ?? 0,
                        $row['dispatches_count'] ?? 0,
                    ];
                }, $data['ingredient_costs'] ?? []),
            ],
            'ingredient_costs' => [
                'headers' => ['Ingredient', 'Unit Cost', 'Dispatched', 'Total Cost', 'Dispatches'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['item_name'] ?? '-',
                        $row['unit_cost'] ?? 0,
                        $row['total_dispatched'] ?? 0,
                        $row['total_cost'] ?? 0,
                        $row['dispatches_count'] ?? 0,
                    ];
                }, $data['ingredient_costs'] ?? []),
            ],
            'product_costs' => [
                'headers' => ['Product', 'Produced', 'Total Cost', 'Cost / Unit', 'Batches'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product_name'] ?? '-',
                        $row['total_produced'] ?? 0,
                        $row['total_cost'] ?? 0,
                        $row['cost_per_unit'] ?? 0,
                        $row['batches_count'] ?? 0,
                    ];
                }, $data['product_costs'] ?? []),
            ],
            'daily_costs' => [
                'headers' => ['Date', 'Total Cost'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['cost'] ?? 0,
                    ];
                }, $data['cost_trends']['daily_costs'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        $costPerUnit = $summary['cost_per_unit'] ?? 0;
        $effScore = $summary['cost_efficiency_score'] ?? 0;

        if ($effScore >= 85) {
            $highlights[] = "Cost efficiency score is strong at {$effScore}.";
        } elseif ($effScore < 60) {
            $concerns[] = "Cost efficiency is low at {$effScore}; review wastage and process bottlenecks.";
        }

        if ($costPerUnit > 0) {
            $highlights[] = "Average cost per unit is {$costPerUnit}.";
        }

        return [
            'overview' => "Production cost analysis (per item and per product) for {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $this->buildRecommendations($summary),
        ];
    }

    private function buildRecommendations(array $summary): array
    {
        $recommendations = [];

        if (($summary['cost_efficiency_score'] ?? 0) < 70) {
            $recommendations[] = 'Review material usage and reduce production waste.';
        }

        if (($summary['cost_per_unit'] ?? 0) <= 0) {
            $recommendations[] = 'Verify unit costs and production records for accuracy.';
        }

        return $recommendations;
    }

    private function buildCostOverview($dispatchedItems, $productionRecords): array
    {
        $totalMaterialCost = $dispatchedItems->sum(function ($item) {
            return $item->quantity_dispatched * ($item->item->cost_per_unit ?? 0);
        });

        $totalProductionCost = $productionRecords->sum(function ($record) {
            return $record->quantity_produced * ($record->unit_cost ?? 0);
        });

        $totalProduced = $productionRecords->sum('quantity_produced');
        $costPerUnit = $totalProduced > 0 ? ($totalMaterialCost + $totalProductionCost) / $totalProduced : 0;

        return [
            'total_material_cost' => $totalMaterialCost,
            'total_production_cost' => $totalProductionCost,
            'total_cost' => $totalMaterialCost + $totalProductionCost,
            'items_dispatched' => $dispatchedItems->count(),
            'batches_produced' => $productionRecords->count(),
            'total_produced' => $totalProduced,
            'cost_per_unit' => $costPerUnit,
        ];
    }

    private function buildIngredientCosts($dispatchedItems): array
    {
        return $dispatchedItems->groupBy('item_id')->map(function ($itemDispatches) {
            $item = $itemDispatches->first()->item;
            $totalDispatched = $itemDispatches->sum('quantity_dispatched');
            $totalCost = $totalDispatched * ($item->cost_per_unit ?? 0);

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_cost' => $item->cost_per_unit ?? 0,
                'total_dispatched' => $totalDispatched,
                'total_cost' => $totalCost,
                'dispatches_count' => $itemDispatches->count(),
            ];
        })->sortByDesc('total_cost')->values()->toArray();
    }

    private function buildProductCosts($productionRecords): array
    {
        return $productionRecords->groupBy('recipe_id')->map(function ($recipeRecords) {
            $recipe = $recipeRecords->first()->recipe;
            $totalProduced = $recipeRecords->sum('quantity_produced');
            $totalCost = $recipeRecords->sum(function ($record) {
                return $record->quantity_produced * ($record->unit_cost ?? 0);
            });

            return [
                'recipe_id' => $recipe?->id,
                'product_name' => $recipe?->product_name ?? 'Unknown',
                'total_produced' => $totalProduced,
                'total_cost' => $totalCost,
                'cost_per_unit' => $totalProduced > 0 ? $totalCost / $totalProduced : 0,
                'batches_count' => $recipeRecords->count(),
            ];
        })->sortByDesc('total_cost')->values()->toArray();
    }

    private function buildCostEfficiency($productionRecords): array
    {
        $totalCost = $productionRecords->sum(function ($record) {
            return $record->quantity_produced * ($record->unit_cost ?? 0);
        });
        $totalProduced = $productionRecords->sum('quantity_produced');

        return [
            'total_production_cost' => $totalCost,
            'total_units_produced' => $totalProduced,
            'average_cost_per_unit' => $totalProduced > 0 ? $totalCost / $totalProduced : 0,
            'cost_efficiency_score' => ($totalProduced > 0 && $totalCost > 0) ? min(100, 1000 / ($totalCost / $totalProduced)) : 0,
        ];
    }

    private function buildCostTrends($dispatchedItems, $productionRecords): array
    {
        $dailyCosts = collect();

        foreach ($dispatchedItems as $item) {
            $date = Carbon::parse($item->updated_at)->format('Y-m-d');
            $cost = $item->quantity_dispatched * ($item->item->cost_per_unit ?? 0);
            $dailyCosts[$date] = ($dailyCosts[$date] ?? 0) + $cost;
        }

        foreach ($productionRecords as $record) {
            $date = Carbon::parse($record->production_time)->format('Y-m-d');
            $cost = $record->quantity_produced * ($record->unit_cost ?? 0);
            $dailyCosts[$date] = ($dailyCosts[$date] ?? 0) + $cost;
        }

        return [
            'daily_costs' => $dailyCosts->map(function ($cost, $date) {
                return ['date' => $date, 'cost' => $cost];
            })->values()->toArray(),
        ];
    }
}
