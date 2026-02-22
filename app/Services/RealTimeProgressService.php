<?php

namespace App\Services;

use App\Events\ProductionProgressUpdated;
use App\Events\DailyProduceProgressUpdated;
use App\Events\ProductionAlertTriggered;
use App\Models\ProductionRecord;
use App\Models\DailyProduce;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RealTimeProgressService
{
    protected ProductionBatchProgressCalculator $batchCalculator;

    public function __construct(ProductionBatchProgressCalculator $batchCalculator)
    {
        $this->batchCalculator = $batchCalculator;
    }

    public function updateProgress(ProductionRecord $record): void
    {
        try {
            // Calculate new progress
            $progressData = $this->batchCalculator->calculateProgress($record);

            // Update database
            $record->update([
                'progress_percentage' => $progressData['overall_progress'],
                'dispatch_status' => $this->mapProgressToStatus($progressData['overall_progress']),
            ]);

            // Clear relevant caches
            $this->clearProgressCaches($record);

            // Broadcast update
            broadcast(new ProductionProgressUpdated($record, $progressData));

            // Check for alerts
            $this->checkAndTriggerAlerts($record, $progressData);

            Log::info('Production progress updated', [
                'record_id' => $record->id,
                'progress' => $progressData['overall_progress'],
                'status' => $progressData['status']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update production progress', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateDailyProduceProgress(DailyProduce $dailyProduce): void
    {
        try {
            // Calculate progress based on associated production records
            $totalProduced = $dailyProduce->productionRecords->sum('quantity_produced');
            $progressPercentage = $dailyProduce->requested_quantity > 0
                ? ($totalProduced / $dailyProduce->requested_quantity) * 100
                : 0;

            $progressData = [
                'progress_percentage' => min(100, $progressPercentage),
                'produced_quantity' => $totalProduced,
                'requested_quantity' => $dailyProduce->requested_quantity,
                'status' => $progressPercentage >= 100 ? 'completed' : ($progressPercentage > 0 ? 'in_progress' : 'pending'),
                'timestamp' => now()->toISOString(),
            ];

            $dailyProduce->update([
                'progress_percentage' => $progressData['progress_percentage'],
                'status' => $progressData['status'],
            ]);

            // Broadcast to department channel
            broadcast(new DailyProduceProgressUpdated($dailyProduce, $progressData));

        } catch (\Exception $e) {
            Log::error('Failed to update daily produce progress', [
                'daily_produce_id' => $dailyProduce->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function clearProgressCaches(ProductionRecord $record): void
    {
        $departmentId = $record->dailyProduce->shift->department_id;
        $shiftId = $record->dailyProduce->shift_id;
        $date = $record->dailyProduce->produce_date->format('Y-m-d');

        Cache::forget("production_progress_dept_{$departmentId}_shift_{$shiftId}_{$date}");
        Cache::forget("department_summary_{$departmentId}_{$date}");
        Cache::forget("production_alerts_{$departmentId}");
    }

    protected function checkAndTriggerAlerts(ProductionRecord $record, array $progressData): void
    {
        $alerts = [];

        // Check for delays
        if ($this->isDelayed($record, $progressData)) {
            $alerts[] = [
                'type' => 'delay',
                'severity' => 'medium',
                'message' => "Production delayed for batch {$record->batch_number}",
                'record_id' => $record->id,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Check for quality issues
        if ($progressData['quality_progress'] < 80) {
            $alerts[] = [
                'type' => 'quality',
                'severity' => 'high',
                'message' => "Quality issues detected in batch {$record->batch_number}",
                'record_id' => $record->id,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Check for completion milestones
        if ($progressData['overall_progress'] >= 100) {
            $alerts[] = [
                'type' => 'completion',
                'severity' => 'low',
                'message' => "Batch {$record->batch_number} completed successfully",
                'record_id' => $record->id,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Broadcast alerts
        foreach ($alerts as $alert) {
            $departmentId = $record->dailyProduce->shift->department_id;
            $branchId = $record->dailyProduce->shift->branch_id;

            broadcast(new ProductionAlertTriggered($alert, $departmentId, $branchId));
        }
    }

    protected function isDelayed(ProductionRecord $record, array $progressData): bool
    {
        if (!$record->estimated_completion_time) {
            return false;
        }

        $expectedProgress = $this->calculateExpectedProgress($record);
        $actualProgress = $progressData['overall_progress'];

        // Consider delayed if actual progress is 20% behind expected
        return ($expectedProgress - $actualProgress) > 20;
    }

    protected function calculateExpectedProgress(ProductionRecord $record): float
    {
        $startTime = $record->created_at;
        $expectedEndTime = $record->estimated_completion_time ?? now()->addHours(8);
        $totalDuration = $startTime->diffInMinutes($expectedEndTime);
        $elapsedDuration = $startTime->diffInMinutes(now());

        if ($totalDuration <= 0) return 100;

        return min(100, ($elapsedDuration / $totalDuration) * 100);
    }

    protected function mapProgressToStatus(float $progress): string
    {
        if ($progress >= 100) return 'fully_dispatched';
        if ($progress > 50) return 'partial';
        if ($progress > 0) return 'available';
        return 'pending';
    }
}