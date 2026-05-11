<?php

namespace App\Services\Reports\Definitions;

use App\Models\ProductionRecord;
use Carbon\Carbon;

class ProductionEfficiencyDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Production Efficiency Report',
            'type' => 'production_efficiency',
            'category' => 'production',
            'order' => 2,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $productionRecords = ProductionRecord::with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('production_time', [
                Carbon::parse($context['period_from'])->startOfDay(),
                Carbon::parse($context['period_to'])->endOfDay()
            ])
            ->get();

        $periodDays = Carbon::parse($context['period_from'])
            ->diffInDays(Carbon::parse($context['period_to'])) + 1;

        return [
            'daily_summary' => $this->buildDailySummary($productionRecords),
            'product_efficiency' => $this->buildProductEfficiency($productionRecords),
            'shift_performance' => $this->buildShiftPerformance($productionRecords),
            'variance_analysis' => $this->buildVarianceAnalysis($productionRecords),
            'employee_performance' => $this->buildEmployeePerformance($productionRecords),
            'trends' => $this->buildTrends($productionRecords),
            'period_info' => [
                'from' => $context['period_from'],
                'to' => $context['period_to'],
                'total_days' => $periodDays,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $daily = collect($data['daily_summary'] ?? []);
        $totalPlanned = $daily->sum('planned');
        $totalActual = $daily->sum('actual');
        $totalVariance = $totalActual - $totalPlanned;
        $overallEfficiency = $totalPlanned > 0 ? round(($totalActual / $totalPlanned) * 100, 2) : 0;

        return [
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'overall_efficiency' => $overallEfficiency,
            'average_daily_production' => $daily->avg('actual') ?? 0,
            'best_day' => $daily->sortByDesc('actual')->first(),
            'worst_day' => $daily->sortBy('actual')->first(),
            'products_tracked' => count($data['product_efficiency'] ?? []),
            'shifts_analyzed' => count($data['shift_performance'] ?? []),
        ];
    }

    public function charts(array $data, array $context): array
    {
        $daily = $data['daily_summary'] ?? [];
        $products = $data['product_efficiency'] ?? [];
        $variance = $data['variance_analysis'] ?? [];

        return [
            'daily_efficiency_chart' => [
                'type' => 'line',
                'labels' => array_column($daily, 'date'),
                'datasets' => [
                    [
                        'label' => 'Planned',
                        'data' => array_column($daily, 'planned'),
                        'color' => '#3b82f6',
                    ],
                    [
                        'label' => 'Actual',
                        'data' => array_column($daily, 'actual'),
                        'color' => '#10b981',
                    ],
                ],
            ],
            'product_efficiency_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($products, 0, 10), 'product_name'),
                'datasets' => [
                    [
                        'label' => 'Efficiency %',
                        'data' => array_column(array_slice($products, 0, 10), 'efficiency_percentage'),
                        'color' => '#8b5cf6',
                    ],
                ],
            ],
            'variance_distribution' => [
                'type' => 'pie',
                'labels' => ['Over Production', 'Under Production', 'On Target'],
                'data' => [
                    $variance['over_production']['percentage'] ?? 0,
                    $variance['under_production']['percentage'] ?? 0,
                    $variance['on_target']['percentage'] ?? 0,
                ],
                'colors' => ['#3b82f6', '#ef4444', '#10b981'],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'daily_summary' => [
                'headers' => ['Date', 'Planned', 'Actual', 'Variance', 'Efficiency %', 'Batches'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['actual'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                        $row['batches_count'] ?? 0,
                    ];
                }, $data['daily_summary'] ?? []),
            ],
            'product_efficiency' => [
                'headers' => ['Product', 'Planned', 'Actual', 'Variance', 'Efficiency %', 'Production Days'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product_name'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['actual'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                        $row['production_days'] ?? 0,
                    ];
                }, $data['product_efficiency'] ?? []),
            ],
            'shift_performance' => [
                'headers' => ['Shift', 'Type', 'Planned', 'Actual', 'Variance', 'Efficiency %', 'Production Days'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['shift_name'] ?? '-',
                        $row['shift_type'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['actual'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                        $row['production_days'] ?? 0,
                    ];
                }, $data['shift_performance'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        $eff = $summary['overall_efficiency'] ?? 0;
        if ($eff >= 95) {
            $highlights[] = "Excellent overall efficiency at {$eff}% for the period.";
        } elseif ($eff < 85) {
            $concerns[] = "Overall efficiency is low at {$eff}%. Review staffing, equipment, and planning.";
        }

        $variance = $summary['total_variance'] ?? 0;
        if ($variance < 0) {
            $concerns[] = 'Total production variance is negative, indicating under‑production.';
        } elseif ($variance > 0) {
            $highlights[] = 'Total production variance is positive, indicating over‑production.';
        }

        $bestDay = $summary['best_day']['date'] ?? null;
        if ($bestDay) {
            $highlights[] = "Best day was {$bestDay}.";
        }

        return [
            'overview' => "Production efficiency averaged {$eff}% from {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $this->buildRecommendations($summary),
        ];
    }

    private function buildRecommendations(array $summary): array
    {
        $recommendations = [];

        $eff = $summary['overall_efficiency'] ?? 0;
        if ($eff < 90) {
            $recommendations[] = 'Review shift planning and target setting to improve efficiency.';
        }

        if (($summary['total_variance'] ?? 0) < 0) {
            $recommendations[] = 'Investigate under‑production causes and adjust resource allocation.';
        }

        return $recommendations;
    }

    private function buildDailySummary($productionRecords): array
    {
        return $productionRecords->groupBy(function ($record) {
            return $record->production_time->format('Y-m-d');
        })->map(function ($dayRecords) {
            $planned = $dayRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $actual = $dayRecords->sum('quantity_produced');
            $variance = $actual - $planned;

            return [
                'date' => $dayRecords->first()->production_time->format('Y-m-d'),
                'planned' => $planned,
                'actual' => $actual,
                'variance' => $variance,
                'efficiency_percentage' => $planned > 0 ? round(($actual / $planned) * 100, 2) : 0,
                'batches_count' => $dayRecords->count(),
            ];
        })->values()->toArray();
    }

    private function buildProductEfficiency($productionRecords): array
    {
        return $productionRecords->groupBy('recipe_id')->map(function ($recipeRecords) {
            $recipe = $recipeRecords->first()->recipe;
            $planned = $recipeRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $actual = $recipeRecords->sum('quantity_produced');

            return [
                'product_id' => $recipe->id ?? null,
                'product_name' => $recipe->product_name ?? 'Unknown Recipe',
                'planned' => $planned,
                'actual' => $actual,
                'variance' => $actual - $planned,
                'efficiency_percentage' => $planned > 0 ? round(($actual / $planned) * 100, 2) : 0,
                'production_days' => $recipeRecords->unique(fn($r) => $r->production_time->format('Y-m-d'))->count(),
            ];
        })->sortByDesc('actual')->values()->toArray();
    }

    private function buildShiftPerformance($productionRecords): array
    {
        return $productionRecords->groupBy(function ($r) {
            return $r->dailyProduce->shift_id ?? 'none';
        })->map(function ($shiftRecords) {
            $shift = $shiftRecords->first()->dailyProduce->shift ?? null;
            $planned = $shiftRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $actual = $shiftRecords->sum('quantity_produced');

            return [
                'shift_id' => $shift->id ?? null,
                'shift_name' => $shift->name ?? 'Unknown',
                'shift_type' => $shift->shift_type ?? 'unknown',
                'planned' => $planned,
                'actual' => $actual,
                'variance' => $actual - $planned,
                'efficiency_percentage' => $planned > 0 ? round(($actual / $planned) * 100, 2) : 0,
                'production_days' => $shiftRecords->unique(fn($r) => $r->production_time->format('Y-m-d'))->count(),
            ];
        })->values()->toArray();
    }

    private function buildVarianceAnalysis($productionRecords): array
    {
        $dailyData = collect($this->buildDailySummary($productionRecords));
        $over = $dailyData->where('variance', '>', 0)->count();
        $under = $dailyData->where('variance', '<', 0)->count();
        $onTarget = $dailyData->where('variance', '=', 0)->count();
        $total = $dailyData->count();

        return [
            'over_production' => [
                'count' => $over,
                'percentage' => $this->percent($over, $total),
            ],
            'under_production' => [
                'count' => $under,
                'percentage' => $this->percent($under, $total),
            ],
            'on_target' => [
                'count' => $onTarget,
                'percentage' => $this->percent($onTarget, $total),
            ],
        ];
    }

    private function buildEmployeePerformance($productionRecords): array
    {
        return $productionRecords->groupBy('produced_by')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->producedBy;

            return [
                'employee_id' => $employee->id ?? null,
                'employee_name' => $employee->name ?? 'Unknown',
                'total_batches' => $employeeRecords->count(),
                'total_produced' => $employeeRecords->sum('quantity_produced'),
                'total_approved' => $employeeRecords->sum('quantity_approved'),
                'total_rejected' => $employeeRecords->sum('quantity_rejected'),
                'approval_rate' => $this->approvalRate($employeeRecords),
            ];
        })->sortByDesc('total_produced')->values()->toArray();
    }

    private function buildTrends($productionRecords): array
    {
        $weeklyTrends = $productionRecords->groupBy(function ($item) {
            return $item->production_time->startOfWeek()->format('Y-m-d');
        })->map(function ($weekRecords, $week) {
            $planned = $weekRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $actual = $weekRecords->sum('quantity_produced');

            return [
                'week_start' => $week,
                'planned' => $planned,
                'actual' => $actual,
                'efficiency' => $planned > 0 ? round(($actual / $planned) * 100, 2) : 0,
            ];
        })->values()->toArray();

        return ['weekly' => $weeklyTrends];
    }

    private function approvalRate($records): float
    {
        $totalProduced = $records->sum('quantity_produced');
        $totalApproved = $records->sum('quantity_approved');

        return $totalProduced > 0 ? round(($totalApproved / $totalProduced) * 100, 2) : 0;
    }

    private function percent($part, $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }
}

