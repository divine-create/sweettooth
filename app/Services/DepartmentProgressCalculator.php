<?php

namespace App\Services;

use App\Models\DailyProduce;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DepartmentProgressCalculator
{
    public function calculateShiftProgress(int $departmentId, int $shiftId, string $date): array
    {
        $cacheKey = "department_progress_{$departmentId}_{$shiftId}_{$date}";

        return Cache::remember($cacheKey, 300, function() use ($departmentId, $shiftId, $date) {
            $productions = DailyProduce::whereHas('shift', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('shift_id', $shiftId)
            ->whereDate('produce_date', $date)
            ->with('productionRecords')
            ->get();

            $totalItems = $productions->count();
            $completedItems = $productions->where('status', 'completed')->count();
            $inProgressItems = $productions->where('status', 'in_progress')->count();

            $totalRequested = $productions->sum('requested_quantity');
            $totalProduced = $productions->sum('produced_quantity');

            // Calculate efficiency metrics
            $efficiency = $this->calculateEfficiency($productions);

            $result = [
                'department_id' => $departmentId,
                'shift_id' => $shiftId,
                'date' => $date,
                'total_items' => $totalItems,
                'completed_items' => $completedItems,
                'in_progress_items' => $inProgressItems,
                'pending_items' => $totalItems - $completedItems - $inProgressItems,
                'total_requested' => $totalRequested,
                'total_produced' => $totalProduced,
                'completion_percentage' => $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0,
                'production_percentage' => $totalRequested > 0 ? ($totalProduced / $totalRequested) * 100 : 0,
                'efficiency_metrics' => $efficiency,
                'alerts' => $this->generateAlerts($productions)
            ];

            return $result;
        });
    }

    private function calculateEfficiency(Collection $productions): array
    {
        $totalTime = 0;
        $totalProduced = 0;

        foreach ($productions as $production) {
            $startTime = $production->shift->start_time ?? now()->startOfDay();
            $endTime = $production->shift->end_time ?? now()->endOfDay();
            $duration = $startTime->diffInMinutes($endTime);

            $totalTime += $duration;
            $totalProduced += $production->produced_quantity;
        }

        return [
            'production_rate_per_hour' => $totalTime > 0 ? ($totalProduced / ($totalTime / 60)) : 0,
            'average_completion_time' => $this->calculateAverageCompletionTime($productions),
            'quality_rate' => $this->calculateQualityRate($productions)
        ];
    }

    private function calculateAverageCompletionTime(Collection $productions): ?float
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

    private function calculateQualityRate(Collection $productions): float
    {
        $totalRecords = $productions->pluck('productionRecords')->flatten()->count();

        if ($totalRecords === 0) return 0;

        $approvedRecords = $productions->pluck('productionRecords')->flatten()
            ->where('quality_status', 'approved')
            ->count();

        return ($approvedRecords / $totalRecords) * 100;
    }

    private function generateAlerts(Collection $productions): array
    {
        $alerts = [];

        // Check for overdue items
        $overdue = $productions->filter(function($p) {
            return $p->status === 'pending' &&
                   $p->produce_date < now()->startOfDay() &&
                   $p->requested_quantity > 0;
        });

        if ($overdue->count() > 0) {
            $alerts[] = [
                'type' => 'overdue',
                'severity' => 'high',
                'message' => "{$overdue->count()} items are overdue",
                'count' => $overdue->count()
            ];
        }

        // Check for low efficiency
        $lowEfficiency = $productions->filter(function($p) {
            return $p->produced_quantity < ($p->requested_quantity * 0.5);
        });

        if ($lowEfficiency->count() > 0) {
            $alerts[] = [
                'type' => 'low_efficiency',
                'severity' => 'medium',
                'message' => "{$lowEfficiency->count()} items have low production efficiency",
                'count' => $lowEfficiency->count()
            ];
        }

        return $alerts;
    }
}