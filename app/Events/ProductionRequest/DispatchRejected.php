<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispatchRejected implements ShouldBroadcast
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
        return 'dispatch.rejected';
    }

    public function broadcastWith(): array
    {
        if (!$this->request) {
            return [];
        }

        return [
            'request_id' => $this->request->id,
            'status' => 'rejected',
            'rejected_at' => now()->toISOString(),
            'rejected_by' => auth()->id(),
        ];
    }
}