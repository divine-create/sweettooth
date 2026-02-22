<?php

namespace App\Services;

use App\Models\ProductionRecord;
use App\Models\ProductionMilestone;

class ProductionBatchProgressCalculator
{
    public function calculateProgress(ProductionRecord $record): array
    {
        $produced = (float) $record->quantity_produced;
        $approved = (float) $record->quantity_approved;
        $rejected = (float) $record->quantity_rejected;
        $sentOut = (float) $record->quantity_sent_out;
        $forOrder = (float) $record->quantity_for_order;

        // Quality progress
        $qualityProgress = $produced > 0 ? (($approved + $rejected) / $produced) * 100 : 0;

        // Dispatch progress
        $dispatchable = $approved - $rejected;
        $dispatched = $sentOut + $forOrder;
        $dispatchProgress = $dispatchable > 0 ? ($dispatched / $dispatchable) * 100 : 0;

        // Stage progress based on milestones
        $stageProgress = $this->calculateStageProgress($record);

        // Overall progress (weighted average)
        $overallProgress = $this->calculateOverallProgress([
            'quality' => $qualityProgress,
            'dispatch' => $dispatchProgress,
            'stage' => $stageProgress['percentage']
        ]);

        return [
            'overall_progress' => $overallProgress,
            'quality_progress' => $qualityProgress,
            'dispatch_progress' => $dispatchProgress,
            'stage_progress' => $stageProgress,
            'produced_quantity' => $produced,
            'approved_quantity' => $approved,
            'rejected_quantity' => $rejected,
            'remaining_quantity' => $approved - $sentOut - $forOrder,
            'status' => $this->determineBatchStatus($record, $overallProgress)
        ];
    }

    private function calculateStageProgress(ProductionRecord $record): array
    {
        $milestones = \App\Models\ProductionMilestone::where('production_record_id', $record->id)
            ->orderBy('achieved_at')
            ->get();

        $stages = ['started', 'ingredients_collected', 'processing', 'quality_check', 'packaging', 'ready_for_dispatch'];
        $completedStages = $milestones->count();
        $currentStage = $stages[min($completedStages, count($stages) - 1)];

        return [
            'total_stages' => count($stages),
            'completed_stages' => $completedStages,
            'current_stage' => $currentStage,
            'percentage' => (count($stages) > 0) ? ($completedStages / count($stages)) * 100 : 0,
            'milestones' => $milestones
        ];
    }

    private function calculateOverallProgress(array $components): float
    {
        // Weighted average: Quality 40%, Dispatch 30%, Stage 30%
        return (
            ($components['quality'] * 0.4) +
            ($components['dispatch'] * 0.3) +
            ($components['stage'] * 0.3)
        );
    }

    private function determineBatchStatus(ProductionRecord $record, float $progress): string
    {
        if ($progress >= 100) return 'completed';
        if ($record->quality_status === 'rejected') return 'rejected';
        if ($progress > 0) return 'in_progress';
        return 'pending';
    }
}