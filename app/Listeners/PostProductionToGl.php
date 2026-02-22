<?php

namespace App\Listeners;

use App\Events\ProductionCompleted;
use App\Services\ProductionAccountingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostProductionToGl implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private ProductionAccountingService $productionService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(ProductionCompleted $event): void
    {
        $production = $event->production;

        // Post production completion entries
        $this->productionService->recordProductionCompletion($production);

        // If there are rejections, record them
        if ($production->quantity_rejected > 0) {
            $this->productionService->recordProductionRejection($production);
        }
    }
}
