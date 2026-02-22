<?php

namespace App\Events;

use App\Models\ProductionRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductionRecord $record;
    public array $progressData;
    public int $departmentId;
    public int $branchId;

    public function __construct(ProductionRecord $record, array $progressData)
    {
        $this->record = $record;
        $this->progressData = $progressData;
        $this->departmentId = $record->dailyProduce->shift->department_id;
        $this->branchId = $record->dailyProduce->shift->branch_id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("production-progress-{$this->departmentId}"),
            new Channel("production-updates-{$this->branchId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'record_id' => $this->record->id,
            'batch_number' => $this->record->batch_number,
            'progress_percentage' => $this->progressData['overall_progress'],
            'status' => $this->progressData['status'],
            'produced_quantity' => $this->progressData['produced_quantity'],
            'remaining_quantity' => $this->progressData['remaining_quantity'],
            'estimated_completion' => $this->progressData['estimated_completion']?->toISOString(),
            'alerts' => $this->progressData['alerts'] ?? [],
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }
}