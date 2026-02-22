<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Services\GlPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostSaleToGl implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private GlPostingService $glPostingService,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(SaleCreated $event): void
    {
        $sale = $event->sale;

        $this->glPostingService->postSaleTransaction($sale);
    }
}
