<?php

namespace App\Observers;

use App\Models\TaxPayment;
use App\Services\GlPostingService;
use Exception;

class TaxPaymentObserver
{
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    public function created(TaxPayment $payment): void
    {
        if ($payment->status === 'paid' && $payment->gl_posting_status === 'pending') {
            $this->postToGl($payment);
        }
    }

    public function updated(TaxPayment $payment): void
    {
        if ($payment->wasChanged('status') &&
            $payment->status === 'paid' &&
            $payment->gl_posting_status === 'pending') {
            $this->postToGl($payment);
        }
    }

    private function postToGl(TaxPayment $payment): void
    {
        try {
            $this->glPostingService->postTaxPayment($payment);

            $payment->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);
        } catch (Exception $e) {
            $payment->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post tax payment to GL', [
                'tax_payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
