<?php

namespace App\Services;

use App\Models\DailyProduce;
use Carbon\Carbon;

class TimeProgressCalculator
{
    public function calculateEstimatedCompletion(DailyProduce $production): ?Carbon
    {
        if ($production->status === 'completed') {
            return $production->actual_completion_time;
        }

        $remainingQuantity = $production->requested_quantity - $production->produced_quantity;
        if ($remainingQuantity <= 0) {
            return now();
        }

        // Calculate production rate (quantity per hour)
        $productionRate = $this->calculateProductionRate($production);

        if ($productionRate <= 0) {
            return null; // Cannot estimate
        }

        $hoursNeeded = $remainingQuantity / $productionRate;
        $estimatedCompletion = now()->addHours($hoursNeeded);

        // Don't estimate beyond shift end
        $shiftEnd = $production->shift->end_time ?? now()->endOfDay();
        if ($estimatedCompletion->greaterThan($shiftEnd)) {
            $estimatedCompletion = $shiftEnd;
        }

        return $estimatedCompletion;
    }

    private function calculateProductionRate(DailyProduce $dailyProduce): float
    {
        // Calculate based on historical data for this recipe/department
        $historicalProductions = DailyProduce::where('recipe_id', $dailyProduce->recipe_id)
            ->whereHas('shift', function($q) use ($dailyProduce) {
                $q->where('department_id', $dailyProduce->shift->department_id);
            })
            ->where('status', 'completed')
            ->whereDate('produce_date', '>=', now()->subDays(30))
            ->get();

        if ($historicalProductions->isEmpty()) {
            return 0;
        }

        $totalProduced = $historicalProductions->sum('produced_quantity');
        $totalHours = $historicalProductions->sum(function($p) {
            $shiftDuration = $p->shift->start_time->diffInHours($p->shift->end_time);
            return $shiftDuration;
        });

        return $totalHours > 0 ? $totalProduced / $totalHours : 0;
    }
}