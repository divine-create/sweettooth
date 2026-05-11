<?php

namespace App\Services\Reports\Definitions;

use App\Models\ProductionRecord;
use Carbon\Carbon;

class ProductionDailyProduceDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Produce Finished Good vs WIP Produce Report',
            'type' => 'production_record_summary',
            'category' => 'production',
            'order' => 1,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $productionRecords = ProductionRecord::with(['recipe', 'dailyProduce.shift'])
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

        $finishedProduces = $productionRecords->filter(fn($p) => !($p->recipe?->is_wip ?? false));
        $wipProduces = $productionRecords->filter(fn($p) => (bool)($p->recipe?->is_wip ?? false));

        return [
            'daily_summary' => $this->buildDailySummary($productionRecords),
            'finished_goods' => $this->buildDailyProducts($finishedProduces),
            'wip_goods' => $this->buildDailyProducts($wipProduces),
            'daily_shifts' => $this->buildDailyShifts($productionRecords),
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
        $finished = collect($data['finished_goods'] ?? []);
        $wip = collect($data['wip_goods'] ?? []);

        $totalPlanned = $daily->sum('planned');
        $totalActual = $daily->sum('actual');
        $totalVariance = $totalActual - $totalPlanned;
        $overallEfficiency = $totalPlanned > 0 ? round(($totalActual / $totalPlanned) * 100, 2) : 0;

        $bestDay = $daily->sortByDesc('actual')->first();
        $worstDay = $daily->sortBy('actual')->first();

        $topFinished = $finished->sortByDesc('produced')->first();
        $topWip = $wip->sortByDesc('produced')->first();

        return [
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'overall_efficiency' => $overallEfficiency,
            'average_daily_production' => $daily->avg('actual') ?? 0,
            'best_day' => $bestDay['date'] ?? null,
            'worst_day' => $worstDay['date'] ?? null,
            'finished_tracked' => $finished->pluck('product_id')->unique()->count(),
            'wip_tracked' => $wip->pluck('product_id')->unique()->count(),
            'top_finished' => $topFinished['product_name'] ?? 'N/A',
            'top_wip' => $topWip['product_name'] ?? 'N/A',
        ];
    }

    public function charts(array $data, array $context): array
    {
        $daily = $data['daily_summary'] ?? [];
        $finished = collect($data['finished_goods'] ?? [])->groupBy('product_name')->map(function ($rows, $name) {
            return [
                'product_name' => $name,
                'produced' => $rows->sum('produced'),
            ];
        })->sortByDesc('produced')->values()->all();

        $topFinished = array_slice($finished, 0, 10);

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
            'top_finished_chart' => [
                'type' => 'bar',
                'labels' => array_column($topFinished, 'product_name'),
                'datasets' => [
                    [
                        'label' => 'Produced',
                        'data' => array_column($topFinished, 'produced'),
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
            'finished_goods' => [
                'headers' => ['Date', 'Produce Finished Good', 'Planned', 'Produced', 'Variance', 'Efficiency %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['product_name'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['produced'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                    ];
                }, $data['finished_goods'] ?? []),
            ],
            'wip_goods' => [
                'headers' => ['Date', 'WIP Produce', 'Planned', 'Produced', 'Variance', 'Efficiency %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['date'] ?? '-',
                        $row['product_name'] ?? '-',
                        $row['planned'] ?? 0,
                        $row['produced'] ?? 0,
                        $row['variance'] ?? 0,
                        $row['efficiency_percentage'] ?? 0,
                    ];
                }, $data['wip_goods'] ?? []),
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

        if (!empty($summary['top_finished']) && $summary['top_finished'] !== 'N/A') {
            $highlights[] = "Top Produce Finished Good: {$summary['top_finished']}.";
        }

        return [
            'overview' => "Production record summary from {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Review daily variance drivers and align targets per shift.',
                'Focus on top products to maintain supply consistency.',
            ],
        ];
    }

    private function buildDailySummary($productionRecords): array
    {
        return $productionRecords->groupBy(function ($record) {
            return $record->production_time->format('Y-m-d');
        })->map(function ($dayRecords) {
            // Planned quantity from unique DailyProduce records
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

    private function buildDailyProducts($productionRecords): array
    {
        return $productionRecords->groupBy(function ($row) {
            return $row->production_time->format('Y-m-d') . '|' . $row->recipe_id;
        })->map(function ($rows) {
            $recipe = $rows->first()->recipe;
            // Planned quantity from unique DailyProduce records for this recipe/date
            $planned = $rows->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $produced = $rows->sum('quantity_produced');

            return [
                'date' => $rows->first()->production_time->format('Y-m-d'),
                'product_id' => $recipe->id ?? null,
                'product_name' => $recipe->product_name ?? 'Unknown',
                'planned' => $planned,
                'produced' => $produced,
                'variance' => $produced - $planned,
                'efficiency_percentage' => $planned > 0 ? round(($produced / $planned) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    private function buildDailyShifts($productionRecords): array
    {
        return $productionRecords->groupBy(function ($row) {
            return $row->production_time->format('Y-m-d') . '|' . ($row->dailyProduce->shift_id ?? 'none');
        })->map(function ($rows) {
            $shift = $rows->first()->dailyProduce->shift ?? null;
            $planned = $rows->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $produced = $rows->sum('quantity_produced');

            return [
                'date' => $rows->first()->production_time->format('Y-m-d'),
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

