<?php

namespace App\Services\Reports\Definitions;

use App\Models\DailyProduce;
use Carbon\Carbon;

class ProductionDailyProduceDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Daily Produce Report',
            'type' => 'daily_produce',
            'category' => 'production',
            'order' => 1,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $dailyProduces = DailyProduce::with(['recipe', 'shift'])
            ->whereHas('shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('produce_date', [$context['period_from'], $context['period_to']])
            ->get();

        return [
            'daily_summary' => $this->buildDailySummary($dailyProduces),
            'daily_products' => $this->buildDailyProducts($dailyProduces),
            'daily_shifts' => $this->buildDailyShifts($dailyProduces),
            'period_info' => [
                'from' => $context['period_from'],
                'to' => $context['period_to'],
                'total_days' => Carbon::parse($context['period_from'])
                    ->diffInDays(Carbon::parse($context['period_to'])) + 1,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $daily = collect($data['daily_summary'] ?? []);
        $products = collect($data['daily_products'] ?? []);

        $totalPlanned = $daily->sum('planned');
        $totalActual = $daily->sum('actual');
        $totalVariance = $totalActual - $totalPlanned;
        $overallEfficiency = $totalPlanned > 0 ? round(($totalActual / $totalPlanned) * 100, 2) : 0;

        $bestDay = $daily->sortByDesc('actual')->first();
        $worstDay = $daily->sortBy('actual')->first();

        $topProduct = $products->sortByDesc('produced')->first();

        return [
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'overall_efficiency' => $overallEfficiency,
            'average_daily_production' => $daily->avg('actual') ?? 0,
            'best_day' => $bestDay['date'] ?? null,
            'worst_day' => $worstDay['date'] ?? null,
            'products_tracked' => $products->pluck('product_id')->unique()->count(),
            'top_product' => $topProduct['product_name'] ?? 'N/A',
        ];
    }

    public function charts(array $data, array $context): array
    {
        $daily = $data['daily_summary'] ?? [];
        $products = collect($data['daily_products'] ?? [])->groupBy('product_name')->map(function ($rows, $name) {
            return [
                'product_name' => $name,
                'produced' => $rows->sum('produced'),
            ];
        })->sortByDesc('produced')->values()->all();

        $topProducts = array_slice($products, 0, 10);

        return [
            'daily_output_chart' => [
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
            'top_products_chart' => [
                'type' => 'bar',
                'labels' => array_column($topProducts, 'product_name'),
                'datasets' => [
                    [
                        'label' => 'Produced',
                        'data' => array_column($topProducts, 'produced'),
                        'color' => '#f59e0b',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'daily_summary' => [
                'headers' => ['Date', 'Planned', 'Actual', 'Variance', 'Efficiency %', 'Products'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['actual'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                        $row['products_count'] ?? 0,
                    ];
                }, $data['daily_summary'] ?? []),
            ],
            'daily_products' => [
                'headers' => ['Date', 'Product', 'Planned', 'Produced', 'Variance', 'Efficiency %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['product_name'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['produced'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                    ];
                }, $data['daily_products'] ?? []),
            ],
            'daily_shifts' => [
                'headers' => ['Date', 'Shift', 'Type', 'Planned', 'Produced', 'Variance', 'Efficiency %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['shift_name'] ?? '-',
                        $row['shift_type'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['produced'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                    ];
                }, $data['daily_shifts'] ?? []),
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

        if (!empty($summary['top_product']) && $summary['top_product'] !== 'N/A') {
            $highlights[] = "Top produced product: {$summary['top_product']}.";
        }

        return [
            'overview' => "Daily production summary from {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Review daily variance drivers and align targets per shift.',
                'Focus on top products to maintain supply consistency.',
            ],
        ];
    }

    private function buildDailySummary($dailyProduces): array
    {
        return $dailyProduces->groupBy('produce_date')->map(function ($dayProduces) {
            $planned = $dayProduces->sum('requested_quantity');
            $actual = $dayProduces->sum('produced_quantity');
            $variance = $dayProduces->sum('variance');

            return [
                'date' => $dayProduces->first()->produce_date->format('Y-m-d'),
                'planned' => $planned,
                'actual' => $actual,
                'variance' => $variance,
                'efficiency_percentage' => $planned > 0 ? round(($actual / $planned) * 100, 2) : 0,
                'products_count' => $dayProduces->count(),
            ];
        })->values()->toArray();
    }

    private function buildDailyProducts($dailyProduces): array
    {
        return $dailyProduces->groupBy(function ($row) {
            return $row->produce_date->format('Y-m-d') . '|' . $row->recipe_id;
        })->map(function ($rows) {
            $recipe = $rows->first()->recipe;
            $planned = $rows->sum('requested_quantity');
            $produced = $rows->sum('produced_quantity');

            return [
                'date' => $rows->first()->produce_date->format('Y-m-d'),
                'product_id' => $recipe->id ?? null,
                'product_name' => $recipe->product_name ?? 'Unknown',
                'planned' => $planned,
                'produced' => $produced,
                'variance' => $produced - $planned,
                'efficiency_percentage' => $planned > 0 ? round(($produced / $planned) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    private function buildDailyShifts($dailyProduces): array
    {
        return $dailyProduces->groupBy(function ($row) {
            return $row->produce_date->format('Y-m-d') . '|' . $row->shift_id;
        })->map(function ($rows) {
            $shift = $rows->first()->shift;
            $planned = $rows->sum('requested_quantity');
            $produced = $rows->sum('produced_quantity');

            return [
                'date' => $rows->first()->produce_date->format('Y-m-d'),
                'shift_id' => $shift->id ?? null,
                'shift_name' => $shift->name ?? 'Unknown',
                'shift_type' => $shift->shift_type ?? 'unknown',
                'planned' => $planned,
                'produced' => $produced,
                'variance' => $produced - $planned,
                'efficiency_percentage' => $planned > 0 ? round(($produced / $planned) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }
}
