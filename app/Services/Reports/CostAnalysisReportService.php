<?php

namespace App\Services\Reports;

use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\ItemRequestDetail;

class CostAnalysisReportService extends ReportService
{
    protected string $reportCategory = 'production';

    protected string $reportType = 'cost_analysis';

    /**
     * Get report name.
     */
    protected function getReportName(): string
    {
        return 'Cost Analysis Report';
    }

    /**
     * Get summary metrics for report data.
     */
    public function getSummaryMetrics(array $reportData): array
    {
        return $this->generateSummaryMetrics($reportData);
    }

    /**
     * Get charts data for report data.
     */
    public function getChartsData(array $reportData): array
    {
        return $this->generateChartsData($reportData);
    }

    /**
     * Generate cache key for report.
     */
    protected function validateParameters(): void
    {
        if (!$this->branchId) {
            throw new \InvalidArgumentException('Branch ID is required');
        }

        if (!$this->periodFrom || !$this->periodTo) {
            throw new \InvalidArgumentException('Period dates are required');
        }
    }

    protected function getCacheKey(): string
    {
        return sprintf(
            'report:%s:%s:%s:%s:%s:%s',
            $this->reportCategory,
            $this->reportType,
            $this->branchId,
            $this->departmentId,
            $this->periodFrom,
            $this->periodTo
        );
    }

    /**
     * Generate the cost analysis report data.
     */
    protected function generateReportData(): array
    {
        $this->validateParameters();

        // Get dispatched items with costs
        $dispatchedItems = ItemRequestDetail::query()
            ->with(['item', 'itemRequest'])
            ->whereHas('itemRequest', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->where('quantity_dispatched', '>', 0)
            ->whereBetween('updated_at', [$this->periodFrom, $this->periodTo])
            ->get();

        // Get production records for cost analysis
        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy'])
            ->whereHas('dailyProduce.shift', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->whereBetween('production_time', [$this->periodFrom, $this->periodTo])
            ->get();

        $reportData = [
            'cost_overview' => $this->generateCostOverview($dispatchedItems, $productionRecords),
            'ingredient_costs' => $this->generateIngredientCosts($dispatchedItems),
            'product_costs' => $this->generateProductCosts($productionRecords),
            'cost_efficiency' => $this->generateCostEfficiency($productionRecords),
            'cost_trends' => $this->generateCostTrends($dispatchedItems, $productionRecords),
            'period_info' => [
                'from' => $this->periodFrom,
                'to' => $this->periodTo,
                'total_days' => \Carbon\Carbon::parse($this->periodFrom)
                    ->diffInDays(\Carbon\Carbon::parse($this->periodTo)) + 1,
            ],
        ];

        // Generate summary metrics
        $reportData['summary_metrics'] = $this->generateSummaryMetrics($reportData);

        return $reportData;
    }

    /**
     * Generate cost overview.
     */
    private function generateCostOverview($dispatchedItems, $productionRecords): array
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

    /**
     * Generate ingredient costs analysis.
     */
    private function generateIngredientCosts($dispatchedItems): array
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

    /**
     * Generate product costs analysis.
     */
    private function generateProductCosts($productionRecords): array
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

    /**
     * Generate cost trends.
     */
    private function generateCostTrends($dispatchedItems, $productionRecords): array
    {
        // Group by date for cost trends
        $dailyCosts = collect();

        // Add dispatched item costs
        foreach ($dispatchedItems as $item) {
            $date = \Carbon\Carbon::parse($item->updated_at)->format('Y-m-d');
            $cost = $item->quantity_dispatched * ($item->item->cost_per_unit ?? 0);
            $dailyCosts[$date] = ($dailyCosts[$date] ?? 0) + $cost;
        }

        // Add production costs
        foreach ($productionRecords as $record) {
            $date = \Carbon\Carbon::parse($record->production_time)->format('Y-m-d');
            $cost = $record->quantity_produced * ($record->unit_cost ?? 0);
            $dailyCosts[$date] = ($dailyCosts[$date] ?? 0) + $cost;
        }

        return [
            'daily_costs' => $dailyCosts->map(function ($cost, $date) {
                return ['date' => $date, 'cost' => $cost];
            })->values()->toArray(),
        ];
    }

    /**
     * Generate summary metrics.
     */
    protected function generateSummaryMetrics(array $reportData): array
    {
        $costOverview = $reportData['cost_overview'];

        return [
            'total_material_cost' => $costOverview['total_material_cost'],
            'total_production_cost' => $costOverview['total_production_cost'],
            'total_cost' => $costOverview['total_cost'],
            'cost_per_unit' => $costOverview['cost_per_unit'],
            'items_tracked' => $costOverview['items_dispatched'],
            'products_analyzed' => count($reportData['product_costs']),
            'cost_efficiency_score' => $reportData['cost_efficiency']['cost_efficiency_score'],
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateChartsData(array $reportData): array
    {
        return [
            'cost_breakdown_chart' => [
                'type' => 'pie',
                'labels' => ['Material Costs', 'Production Costs'],
                'data' => [
                    $reportData['cost_overview']['total_material_cost'],
                    $reportData['cost_overview']['total_production_cost'],
                ],
                'colors' => ['#3b82f6', '#10b981'],
            ],
            'ingredient_cost_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($reportData['ingredient_costs'], 0, 10), 'item_name'),
                'datasets' => [
                    [
                        'label' => 'Total Cost',
                        'data' => array_column(array_slice($reportData['ingredient_costs'], 0, 10), 'total_cost'),
                        'color' => '#8b5cf6',
                    ],
                ],
            ],
            'cost_trends_chart' => [
                'type' => 'line',
                'labels' => array_column($reportData['cost_trends']['daily_costs'], 'date'),
                'datasets' => [
                    [
                        'label' => 'Daily Costs',
                        'data' => array_column($reportData['cost_trends']['daily_costs'], 'cost'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate cost efficiency analysis.
     */
    private function generateCostEfficiency($productionRecords): array
    {
        $totalCost = $productionRecords->sum(function ($record) {
            return $record->quantity_produced * ($record->unit_cost ?? 0);
        });
        $totalProduced = $productionRecords->sum('quantity_produced');

        return [
            'total_production_cost' => $totalCost,
            'total_units_produced' => $totalProduced,
            'average_cost_per_unit' => $totalProduced > 0 ? $totalCost / $totalProduced : 0,
            'cost_efficiency_score' => $totalProduced > 0 ? min(100, 1000 / ($totalCost / $totalProduced)) : 0,
        ];
    }

}
