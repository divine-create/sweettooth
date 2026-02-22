<?php

namespace App\Events;

use App\Models\DailyProduce;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyProduceProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DailyProduce $dailyProduce;
    public array $progressData;
    public int $departmentId;
    public int $branchId;

    public function __construct(DailyProduce $dailyProduce, array $progressData)
    {
        $this->dailyProduce = $dailyProduce;
        $this->progressData = $progressData;
        $this->departmentId = $dailyProduce->shift->department_id;
        $this->branchId = $dailyProduce->shift->branch_id;
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
            'daily_produce_id' => $this->dailyProduce->id,
            'recipe_name' => $this->dailyProduce->recipe->name ?? 'Unknown',
            'progress_percentage' => $this->progressData['progress_percentage'],
            'status' => $this->progressData['status'],
            'produced_quantity' => $this->progressData['produced_quantity'],
            'requested_quantity' => $this->progressData['requested_quantity'],
            'timestamp' => $this->progressData['timestamp'],
        ];
    }

    public function broadcastAs(): string
    {
        return 'daily_progress.updated';
    }
}