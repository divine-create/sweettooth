<?php

namespace App\Listeners;

use App\Events\ProductionCompleted;
use App\Services\ProductionAccountingService;

class PostProductionToGl
{
    /**
     * Create the event listener.
     */
    public function __construct(private ProductionAccountingService $productionService)
    {
    }

    /**
     * Handle the event.
     *
     * Runs synchronously (not queued) so production posts to the ledger even
     * when no queue worker is running, mirroring how sales posting works.
     * recordProductionCompletion records approved output, rejects and consumed
     * inputs in one balanced transaction, so rejection is no longer posted
     * separately here.
     */
    public function handle(ProductionCompleted $event): void
    {
        $this->productionService->recordProductionCompletion($event->production);
    }
}
