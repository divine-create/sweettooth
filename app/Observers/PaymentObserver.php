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

        // Fix: If this payment is for a completed sale that hasn't posted yet, trigger it
        if ($payment->sale && 
            $payment->sale->status === 'completed' && 
            $payment->sale->gl_posting_status === 'pending') {
            $this->postSaleToGL($payment->sale);
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

        // Fix: If this payment completion makes a sale postable
        if ($payment->sale && 
            $payment->sale->status === 'completed' && 
            $payment->sale->gl_posting_status === 'pending') {
            $this->postSaleToGL($payment->sale);
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

    /**
     * Post parent sale to GL
     */
    private function postSaleToGL(\App\Models\Sale $sale): void
    {
        try {
            // Use the service to post
            $this->glPostingService->postSaleTransaction($sale);

            $sale->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);
        } catch (Exception $e) {
            $sale->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);
        }

        // Post the cash-overage income adjustment if the customer overpaid and kept
        // the change. Idempotent and self-balancing (Dr Cash / Cr Overage Income).
        $this->postOverShortToGL($sale);
    }

    /**
     * Recognise kept overage as income. No-op for ordinary sales.
     */
    private function postOverShortToGL(\App\Models\Sale $sale): void
    {
        if ($sale->over_short_disposition !== 'overage'
            || (float) ($sale->over_short_amount ?? 0) <= 0
            || $sale->over_short_gl_status === 'posted') {
            return;
        }

        try {
            $this->glPostingService->postSaleOverShortAdjustment($sale);
            $sale->update(['over_short_gl_status' => 'posted']);
        } catch (Exception $e) {
            $sale->update(['over_short_gl_status' => 'failed']);
            \Log::error('Failed to post sale overage to GL', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
