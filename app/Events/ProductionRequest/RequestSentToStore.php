<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionRequest;
use App\Models\ItemRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestSentToStore implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductionRequest $productionRequest;
    public ?ItemRequest $itemRequest;

    public function __construct(ProductionRequest $productionRequest, ?ItemRequest $itemRequest = null)
    {
        $this->productionRequest = $productionRequest;
        $this->itemRequest = $itemRequest;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to sales department channel
        if ($this->productionRequest->sales_department_id) {
            $channels[] = new PrivateChannel('department.' . $this->productionRequest->sales_department_id);
        }

        // Broadcast to store/inventory channel
        $channels[] = new PrivateChannel('store.requests');

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->productionRequest->id,
            'status' => 'in_progress',
            'message' => 'Raw materials request sent to store for production request #' . $this->productionRequest->id,
            'item_request_id' => $this->itemRequest?->id,
            'item_request_number' => $this->itemRequest?->request_number,
            'recipe' => $this->productionRequest->recipe?->product_name,
            'quantity' => $this->productionRequest->planned_production_quantity,
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'production.request.sent_to_store';
    }
}
