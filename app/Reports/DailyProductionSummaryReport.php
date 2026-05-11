<?php

namespace App\Reports;

use App\Models\ProductionRecord;
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
        $query = ProductionRecord::whereDate('production_time', $date)
            ->with(['recipe', 'dailyProduce.shift.department']);

        if ($departmentId) {
            $query->whereHas('dailyProduce.shift', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $records = $query->get();

        return [
            'metadata' => $this->getReportMetadata($date, $departmentId),
            'summary' => [
                'date' => $date->format('Y-m-d'),
                'department' => $departmentId ? \App\Models\Department::find($departmentId)->name : 'All Departments',
                'total_batches' => $records->count(),
                'total_quantity_produced' => $records->sum('quantity_produced'),
                'total_quantity_approved' => $records->sum('quantity_approved'),
                'total_quantity_rejected' => $records->sum('quantity_rejected'),
                'total_quantity_planned' => $records->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity),
                'efficiency_metrics' => $this->calculateEfficiencyMetrics($records),
                'quality_metrics' => $this->calculateQualityMetrics($records),
            ],
            'department_breakdown' => $this->getDepartmentBreakdown($records),
            'hourly_progression' => $this->getHourlyProgression($records),
            'top_performers' => $this->getTopPerformers($records),
            'bottlenecks' => $this->identifyBottlenecks($records),
        ];
    }

    private function calculateEfficiencyMetrics(\Illuminate\Database\Eloquent\Collection $records): array
    {
        $totalPlanned = $records->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
        $totalProduced = $records->sum('quantity_produced');

        $efficiency = $totalPlanned > 0 ? ($totalProduced / $totalPlanned) * 100 : 0;

        return [
            'efficiency_percentage' => round($efficiency, 2),
            'overproduction' => $totalProduced > $totalPlanned ? $totalProduced - $totalPlanned : 0,
            'underproduction' => $totalProduced < $totalPlanned ? $totalPlanned - $totalProduced : 0,
        ];
    }

    private function calculateQualityMetrics(\Illuminate\Database\Eloquent\Collection $records): array
    {
        if ($records->isEmpty()) {
            return ['average_quality_score' => 0, 'rejection_rate' => 0];
        }

        $totalProduced = $records->sum('quantity_produced');
        $totalRejected = $records->sum('quantity_rejected');
        $rejectionRate = $totalProduced > 0 ? ($totalRejected / $totalProduced) * 100 : 0;

        return [
            'rejection_rate' => round($rejectionRate, 2),
            'quality_distribution' => $this->getQualityDistribution($records),
        ];
    }

    private function getDepartmentBreakdown(\Illuminate\Database\Eloquent\Collection $records): array
    {
        return $records->groupBy(function($record) {
            return $record->dailyProduce->shift->department->name ?? 'Unknown';
        })->map(function($deptRecords, $deptName) {
            $planned = $deptRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $produced = $deptRecords->sum('quantity_produced');

            return [
                'department' => $deptName,
                'total_batches' => $deptRecords->count(),
                'total_produced' => $produced,
                'total_planned' => $planned,
                'efficiency' => $planned > 0 ? round(($produced / $planned) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    private function getHourlyProgression(\Illuminate\Database\Eloquent\Collection $records): array
    {
        $hourly = [];

        for ($hour = 6; $hour <= 22; $hour++) {
            $hourRecords = $records->filter(function($r) {
                return $r->production_time->hour === $hour;
            });

            $hourly[] = [
                'hour' => $hour,
                'batches_count' => $hourRecords->count(),
                'quantity_produced' => $hourRecords->sum('quantity_produced'),
            ];
        }

        return $hourly;
    }

    private function getTopPerformers(\Illuminate\Database\Eloquent\Collection $records): array
    {
        return $records->groupBy('recipe_id')->map(function($recipeRecords) {
            $recipe = $recipeRecords->first()->recipe;
            $planned = $recipeRecords->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $produced = $recipeRecords->sum('quantity_produced');

            return [
                'recipe_name' => $recipe->product_name ?? 'Unknown',
                'produced_quantity' => $produced,
                'requested_quantity' => $planned,
                'efficiency' => $planned > 0 ? round(($produced / $planned) * 100, 2) : 0,
            ];
        })->sortByDesc('produced_quantity')->take(5)->values()->toArray();
    }

    private function identifyBottlenecks(\Illuminate\Database\Eloquent\Collection $records): array
    {
        $bottlenecks = [];

        // Check for recipes with high rejection rates
        $highRejection = $records->groupBy('recipe_id')->filter(function($recipeRecords) {
            $produced = $recipeRecords->sum('quantity_produced');
            $rejected = $recipeRecords->sum('quantity_rejected');
            return $produced > 0 && ($rejected / $produced) > 0.1; // More than 10% rejection
        });

        if ($highRejection->count() > 0) {
            $bottlenecks[] = [
                'type' => 'high_rejection_rate',
                'description' => "{$highRejection->count()} recipes with high rejection rates",
                'affected_recipes' => $highRejection->map(fn($r) => $r->first()->recipe->product_name ?? 'Unknown')->values(),
            ];
        }

        return $bottlenecks;
    }

    private function getQualityDistribution(\Illuminate\Database\Eloquent\Collection $records): array
    {
        return [
            'excellent' => $records->where('quality_status', 'excellent')->count(),
            'good' => $records->where('quality_status', 'good')->count(),
            'acceptable' => $records->where('quality_status', 'acceptable')->count(),
            'rejected' => $records->where('quality_status', 'rejected')->count(),
        ];
    }
}