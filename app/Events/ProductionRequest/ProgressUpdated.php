<?php

namespace App\Events\ProductionRequest;

use App\Models\ProductionProgressFeedback;
use App\Models\ProductionRequest;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public ProductionRequest $request;
    public ProductionProgressFeedback $feedback;

    public function __construct(ProductionRequest $request, ProductionProgressFeedback $feedback)
    {
        $this->request = $request;
        $this->feedback = $feedback;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Notify sales user of progress
            new PrivateChannel("sales-user.{$this->request->created_by_id}"),
            // Notify request-specific channel
            new PrivateChannel("production-request.{$this->request->id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->request->id,
            'feedback_id' => $this->feedback->id,
            'milestone' => $this->feedback->milestone,
            'progress_percentage' => $this->feedback->progress_percentage,
            'notes' => $this->feedback->notes,
            'updated_by' => $this->feedback->updatedBy?->name,
            'created_at' => $this->feedback->created_at->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'progress.updated';
    }
}
