<?php

namespace App\Reports;

use App\Models\DailyProduce;
use Carbon\Carbon;

class DailyProductionSummaryReport extends BaseReport
{
    protected string $title = 'Daily Production Summary';
    protected array $metrics = [
        'total_produced',
        'total_planned',
        'completion_rate',
        'efficiency_rate',
        'quality_score',
        'average_production_time'
    ];

    public function generate(Carbon $date, ?int $departmentId = null): array
    {
        $query = DailyProduce::whereDate('produce_date', $date)
            ->with(['shift', 'recipe', 'productionRecords']);

        if ($departmentId) {
            $query->whereHas('shift', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $productions = $query->get();

        return [
            'metadata' => $this->getReportMetadata($date, $departmentId),
            'summary' => [
                'date' => $date->format('Y-m-d'),
                'department' => $departmentId ? \App\Models\Department::find($departmentId)->name : 'All Departments',
                'total_productions' => $productions->count(),
                'completed_productions' => $productions->where('status', 'completed')->count(),
                'total_quantity_produced' => $productions->sum('produced_quantity'),
                'total_quantity_planned' => $productions->sum('requested_quantity'),
                'completion_rate' => $this->calculateCompletionRate($productions),
                'efficiency_metrics' => $this->calculateEfficiencyMetrics($productions),
                'quality_metrics' => $this->calculateQualityMetrics($productions),
            ],
            'department_breakdown' => $this->getDepartmentBreakdown($productions),
            'hourly_progression' => $this->getHourlyProgression($productions),
            'top_performers' => $this->getTopPerformers($productions),
            'bottlenecks' => $this->identifyBottlenecks($productions),
        ];
    }

    private function calculateEfficiencyMetrics(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        $totalPlanned = $productions->sum('requested_quantity');
        $totalProduced = $productions->sum('produced_quantity');

        $efficiency = $totalPlanned > 0 ? ($totalProduced / $totalPlanned) * 100 : 0;

        return [
            'efficiency_percentage' => round($efficiency, 2),
            'overproduction' => $totalProduced > $totalPlanned ? $totalProduced - $totalPlanned : 0,
            'underproduction' => $totalProduced < $totalPlanned ? $totalPlanned - $totalProduced : 0,
            'average_production_time' => $this->calculateAverageProductionTime($productions),
        ];
    }

    private function calculateQualityMetrics(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        $records = $productions->pluck('productionRecords')->flatten();

        if ($records->isEmpty()) {
            return ['average_quality_score' => 0, 'rejection_rate' => 0];
        }

        $averageQuality = $records->avg('quality_score') ?? 0;
        $totalProduced = $records->sum('quantity_produced');
        $totalRejected = $records->sum('quantity_rejected');
        $rejectionRate = $totalProduced > 0 ? ($totalRejected / $totalProduced) * 100 : 0;

        return [
            'average_quality_score' => round($averageQuality, 2),
            'rejection_rate' => round($rejectionRate, 2),
            'quality_distribution' => $this->getQualityDistribution($records),
        ];
    }

    private function getDepartmentBreakdown(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        return $productions->groupBy(function($production) {
            return $production->shift->department->name;
        })->map(function($deptProductions, $deptName) {
            return [
                'department' => $deptName,
                'total_productions' => $deptProductions->count(),
                'completed' => $deptProductions->where('status', 'completed')->count(),
                'total_produced' => $deptProductions->sum('produced_quantity'),
                'completion_rate' => $this->calculateCompletionRate($deptProductions),
            ];
        })->values()->toArray();
    }

    private function getHourlyProgression(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        $hourly = [];

        for ($hour = 6; $hour <= 22; $hour++) {
            $hourProductions = $productions->filter(function($p) use ($hour) {
                return $p->created_at->hour === $hour;
            });

            $hourly[] = [
                'hour' => $hour,
                'productions_count' => $hourProductions->count(),
                'quantity_produced' => $hourProductions->sum('produced_quantity'),
                'completed_count' => $hourProductions->where('status', 'completed')->count(),
            ];
        }

        return $hourly;
    }

    private function getTopPerformers(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        return $productions->sortByDesc(function($p) {
            return $p->produced_quantity / max($p->requested_quantity, 1);
        })->take(5)->map(function($p) {
            return [
                'recipe_name' => $p->recipe->name,
                'produced_quantity' => $p->produced_quantity,
                'requested_quantity' => $p->requested_quantity,
                'efficiency' => round(($p->produced_quantity / max($p->requested_quantity, 1)) * 100, 2),
                'production_time' => $p->productionRecords->avg('production_time_minutes'),
            ];
        })->values()->toArray();
    }

    private function identifyBottlenecks(\Illuminate\Database\Eloquent\Collection $productions): array
    {
        $bottlenecks = [];

        // Check for productions with low efficiency
        $lowEfficiency = $productions->filter(function($p) {
            $efficiency = $p->produced_quantity / max($p->requested_quantity, 1);
            return $efficiency < 0.8; // Less than 80% efficiency
        });

        if ($lowEfficiency->count() > 0) {
            $bottlenecks[] = [
                'type' => 'low_efficiency',
                'description' => "{$lowEfficiency->count()} productions with low efficiency",
                'affected_productions' => $lowEfficiency->pluck('recipe.name'),
            ];
        }

        // Check for long production times
        $avgTime = $productions->avg(function($p) {
            return $p->productionRecords->avg('production_time_minutes') ?? 0;
        });

        $longProductions = $productions->filter(function($p) use ($avgTime) {
            $prodTime = $p->productionRecords->avg('production_time_minutes') ?? 0;
            return $prodTime > $avgTime * 1.5; // 50% longer than average
        });

        if ($longProductions->count() > 0) {
            $bottlenecks[] = [
                'type' => 'long_production_times',
                'description' => "{$longProductions->count()} productions taking longer than average",
                'affected_productions' => $longProductions->pluck('recipe.name'),
            ];
        }

        return $bottlenecks;
    }

    private function calculateAverageProductionTime(\Illuminate\Database\Eloquent\Collection $productions): ?float
    {
        $completedProductions = $productions->where('status', 'completed');

        if ($completedProductions->isEmpty()) return null;

        $totalTime = 0;
        $count = 0;

        foreach ($completedProductions as $production) {
            if ($production->actual_completion_time && $production->created_at) {
                $completionTime = $production->created_at->diffInMinutes($production->actual_completion_time);
                $totalTime += $completionTime;
                $count++;
            }
        }

        return $count > 0 ? $totalTime / $count : null;
    }

    private function getQualityDistribution(\Illuminate\Database\Eloquent\Collection $records): array
    {
        return [
            'excellent' => $records->where('quality_score', '>=', 90)->count(),
            'good' => $records->whereBetween('quality_score', [80, 89])->count(),
            'acceptable' => $records->whereBetween('quality_score', [70, 79])->count(),
            'poor' => $records->where('quality_score', '<', 70)->count(),
        ];
    }
}