<?php

namespace App\Services;

use App\Models\ProductionRequest;
use App\Models\DailyProduce;
use Carbon\Carbon;

class ProductionRequestProgressCalculator
{
    public function calculateProgress(ProductionRequest $productionRequest): array
    {
        $plannedQuantity = (float) $productionRequest->planned_production_quantity;

        // Get actual produced quantity from daily produces
        $producedQuantity = DailyProduce::whereHas('productionRequest', function($q) use ($productionRequest) {
            $q->where('id', $productionRequest->id);
        })->sum('produced_quantity');

        $progressPercentage = $plannedQuantity > 0 ? ($producedQuantity / $plannedQuantity) * 100 : 0;

        // Calculate stage progress based on milestones
        $stageProgress = $this->calculateStageProgress($productionRequest);

        return [
            'progress_percentage' => min(100, $progressPercentage),
            'planned_quantity' => $plannedQuantity,
            'produced_quantity' => $producedQuantity,
            'stage_progress' => $stageProgress,
            'status' => $this->determineProductionStatus($progressPercentage, $productionRequest),
            'estimated_completion' => $this->calculateEstimatedCompletion($productionRequest, $stageProgress)
        ];
    }

    private function calculateStageProgress(ProductionRequest $request): array
    {
        $stages = ['ingredients_collected', 'production_started', 'quality_check_passed', 'packaging_completed', 'dispatch_ready'];
        $completedStages = 0;

        foreach ($stages as $stage) {
            if ($this->isStageCompleted($request, $stage)) {
                $completedStages++;
            }
        }

        return [
            'total_stages' => count($stages),
            'completed_stages' => $completedStages,
            'current_stage' => $this->getCurrentStage($request),
            'stage_percentage' => (count($stages) > 0) ? ($completedStages / count($stages)) * 100 : 0
        ];
    }

    private function isStageCompleted(ProductionRequest $productionRequest, string $stage): bool
    {
        // Check production records for milestone completion
        return \App\Models\ProductionRecord::whereHas('dailyProduce.productionRequest', function($q) use ($productionRequest) {
            $q->where('id', $productionRequest->id);
        })
        ->where('progress_stage', $stage)
        ->whereNotNull('stage_end_time')
        ->exists();
    }

    private function getCurrentStage(ProductionRequest $productionRequest): string
    {
        $latestRecord = \App\Models\ProductionRecord::whereHas('dailyProduce.productionRequest', function($q) use ($productionRequest) {
            $q->where('id', $productionRequest->id);
        })
        ->whereNotNull('progress_stage')
        ->orderBy('stage_start_time', 'desc')
        ->first();

        return $latestRecord ? $latestRecord->progress_stage : 'pending';
    }

    private function determineProductionStatus(float $progressPercentage, ProductionRequest $request): string
    {
        if ($progressPercentage >= 100) return 'completed';
        if ($progressPercentage > 0) return 'in_progress';
        if ($request->shift_id && $request->recipe_id) return 'ready';
        return 'pending';
    }

    private function calculateEstimatedCompletion(ProductionRequest $productionRequest, array $stageProgress): ?Carbon
    {
        if ($stageProgress['stage_percentage'] >= 100) {
            return now();
        }

        // Simple estimation based on completed stages
        $remainingStages = $stageProgress['total_stages'] - $stageProgress['completed_stages'];
        if ($remainingStages <= 0) return now();

        // Assume 30 minutes per remaining stage (configurable)
        $estimatedMinutes = $remainingStages * 30;

        return now()->addMinutes($estimatedMinutes);
    }
}