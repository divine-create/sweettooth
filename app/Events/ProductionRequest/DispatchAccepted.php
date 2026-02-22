<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispatchAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductionRequest $request;

    public function __construct(ProductionRequest $request)
    {
        $this->request = $request;
    }

    public function broadcastOn(): array
    {
        if (!$this->request) {
            return [];
        }

        return [
            new PrivateChannel('production-dept.' . $this->request->production_department_id),
            new PrivateChannel('sales-user.' . $this->request->created_by_id),
            new PrivateChannel('production-request.' . $this->request->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dispatch.accepted';
    }

    public function broadcastWith(): array
    {
        if (!$this->request) {
            return [];
        }

        return [
            'request_id' => $this->request->id,
            'status' => 'accepted',
            'accepted_at' => now()->toISOString(),
            'accepted_by' => auth()->id(),
        ];
    }
}