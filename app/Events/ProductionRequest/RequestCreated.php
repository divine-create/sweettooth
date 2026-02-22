<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public ProductionRequest $request;

    public function __construct(ProductionRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Notify production department of new request
            new PrivateChannel("production-dept.{$this->request->production_department_id}"),
            // Notify sales user of their request
            new PrivateChannel("sales-user.{$this->request->created_by_id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->request->id,
            'production_department_id' => $this->request->production_department_id,
            'sales_department_id' => $this->request->sales_department_id,
            'quantity' => $this->request->planned_production_quantity,
            'priority' => $this->request->priority,
            'status' => $this->request->status,
            'created_by' => $this->request->createdBy?->name,
            'created_at' => $this->request->created_at->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'request.created';
    }
}
