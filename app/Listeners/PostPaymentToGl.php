<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\GlPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostPaymentToGl implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        $this->glPostingService->postPaymentTransaction($payment);
    }
}
