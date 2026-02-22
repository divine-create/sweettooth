<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductionRequest $productionRequest;

    public function __construct(ProductionRequest $productionRequest)
    {
        $this->productionRequest = $productionRequest;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to sales department channel if it exists
        if ($this->productionRequest->sales_department_id) {
            $channels[] = new PrivateChannel('department.' . $this->productionRequest->sales_department_id);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->productionRequest->id,
            'status' => 'rejected',
            'message' => 'Production request #' . $this->productionRequest->id . ' has been rejected',
            'recipe' => $this->productionRequest->recipe?->product_name,
            'quantity' => $this->productionRequest->planned_production_quantity,
            'notes' => $this->productionRequest->notes,
            'rejected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'production.request.rejected';
    }
}
