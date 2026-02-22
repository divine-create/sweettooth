<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $alertData;
    public int $departmentId;
    public int $branchId;

    public function __construct(array $alertData, int $departmentId, int $branchId)
    {
        $this->alertData = $alertData;
        $this->departmentId = $departmentId;
        $this->branchId = $branchId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("production-alerts-{$this->departmentId}"),
            new Channel("production-updates-{$this->branchId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return $this->alertData;
    }

    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }
}