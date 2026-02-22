<?php

namespace App\Services;

use App\Models\ProductionRecord;
use App\Models\DailyProduce;
use App\Models\ItemRequest;
use App\Services\ItemRequestProgressCalculator;
use App\Services\ProductionRequestProgressCalculator;
use App\Services\ProductionBatchProgressCalculator;

class ProgressUpdateService
{
    protected ItemRequestProgressCalculator $itemRequestCalculator;
    protected ProductionRequestProgressCalculator $productionRequestCalculator;
    protected ProductionBatchProgressCalculator $batchCalculator;

    public function __construct(
        ItemRequestProgressCalculator $itemRequestCalculator,
        ProductionRequestProgressCalculator $productionRequestCalculator,
        ProductionBatchProgressCalculator $batchCalculator
    ) {
        $this->itemRequestCalculator = $itemRequestCalculator;
        $this->productionRequestCalculator = $productionRequestCalculator;
        $this->batchCalculator = $batchCalculator;
    }

    public function updateProgress($model): void
    {
        if ($model instanceof ProductionRecord) {
            $this->updateBatchProgress($model);
        } elseif ($model instanceof DailyProduce) {
            $this->updateProductionProgress($model);
        } elseif ($model instanceof ItemRequest) {
            $this->updateRequestProgress($model);
        }

        // Update department summaries
        $this->updateDepartmentSummaries($model);
    }

    private function updateBatchProgress(ProductionRecord $record): void
    {
        $progress = $this->batchCalculator->calculateProgress($record);

        $record->update([
            'progress_percentage' => $progress['overall_progress'],
            'dispatch_status' => $this->mapProgressToStatus($progress['overall_progress'])
        ]);
    }

    private function updateProductionProgress(DailyProduce $production): void
    {
        // Calculate progress based on associated records
        $totalProduced = $production->productionRecords->sum('quantity_produced');
        $progressPercentage = $production->requested_quantity > 0
            ? ($totalProduced / $production->requested_quantity) * 100
            : 0;

        $production->update([
            'produced_quantity' => $totalProduced,
            'progress_percentage' => min(100, $progressPercentage),
            'status' => $this->determineProductionStatus($progressPercentage, $production)
        ]);
    }

    private function updateRequestProgress(ItemRequest $request): void
    {
        $progress = $this->itemRequestCalculator->calculateProgress($request);

        // Update overall request status based on progress
        if ($progress['overall_progress'] >= 100 && $progress['items_progress'] >= 100) {
            $request->update(['status' => 'completed']);
        } elseif ($progress['overall_progress'] > 0 || $progress['items_progress'] > 0) {
            $request->update(['status' => 'in_progress']);
        }
    }

    private function updateDepartmentSummaries($model): void
    {
        // This would trigger recalculation of department summaries
        // Implementation would depend on the caching strategy used
        if ($model instanceof ProductionRecord || $model instanceof DailyProduce) {
            $departmentId = $model->dailyProduce?->shift?->department_id;
            $shiftId = $model->dailyProduce?->shift_id ?? $model->shift_id;
            $date = $model->dailyProduce?->produce_date ?? $model->produce_date;

            if ($departmentId && $shiftId && $date) {
                // Clear relevant caches
                \Illuminate\Support\Facades\Cache::forget("department_progress_{$departmentId}_{$shiftId}_{$date}");
                \Illuminate\Support\Facades\Cache::forget("department_summary_{$departmentId}_{$date}");
            }
        }
    }

    private function mapProgressToStatus(float $progress): string
    {
        if ($progress >= 100) return 'fully_dispatched';
        if ($progress > 50) return 'partial';
        if ($progress > 0) return 'available';
        return 'pending';
    }

    private function determineProductionStatus(float $progressPercentage, DailyProduce $production): string
    {
        if ($progressPercentage >= 100) return 'completed';
        if ($progressPercentage > 0) return 'in_progress';
        return 'pending';
    }
}