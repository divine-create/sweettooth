<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\GlPostingService;
use Exception;

class PaymentObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the Payment "created" event.
     * Post to GL when payment is created as completed
     */
    public function created(Payment $payment): void
    {
        if ($payment->status === 'completed' && $payment->gl_posting_status === 'pending') {
            $this->postToGL($payment);
        }
    }

    /**
     * Handle the Payment "updated" event.
     * Post to GL when payment is completed
     */
    public function updated(Payment $payment): void
    {
        // Only post if status changes to completed and not already posted
        if ($payment->wasChanged('status') && 
            $payment->status === 'completed' && 
            $payment->gl_posting_status === 'pending') {
            $this->postToGL($payment);
        }
    }

    /**
     * Post payment transaction to GL
     */
    private function postToGL(Payment $payment): void
    {
        try {
            // Avoid duplicate posting
            if ($payment->gl_posting_status !== 'pending') {
                return;
            }

            // Post to GL
            $this->glPostingService->postPaymentTransaction($payment);

            // Update posting status
            $payment->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Payment posted to GL', [
                'payment_id' => $payment->id,
                'sale_id' => $payment->sale_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            $payment->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post payment to GL', [
                'payment_id' => $payment->id,
                'sale_id' => $payment->sale_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
