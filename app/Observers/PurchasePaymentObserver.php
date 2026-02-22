<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\GlPostingService;
use Exception;

class PurchasePaymentObserver
{
    public function __construct(private GlPostingService $glPostingService)
    {
    }

    public function created(PurchasePayment $payment): void
    {
        if ($payment->status === 'paid' && $payment->gl_posting_status === 'pending') {
            $this->postToGl($payment);
        }

        $this->syncPurchasePaymentStatus($payment->purchase);
    }

    public function updated(PurchasePayment $payment): void
    {
        if ($payment->wasChanged('status') &&
            $payment->status === 'paid' &&
            $payment->gl_posting_status === 'pending') {
            $this->postToGl($payment);
        }

        if ($payment->wasChanged('purchase_id')) {
            $originalPurchaseId = $payment->getOriginal('purchase_id');
            if ($originalPurchaseId) {
                $this->syncPurchasePaymentStatus(Purchase::find($originalPurchaseId));
            }
        }

        $this->syncPurchasePaymentStatus($payment->purchase);
    }

    public function deleted(PurchasePayment $payment): void
    {
        $this->syncPurchasePaymentStatus($payment->purchase);
    }

    private function postToGl(PurchasePayment $payment): void
    {
        try {
            $this->glPostingService->postPurchasePayment($payment);

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

            \Log::error('Failed to post purchase payment to GL', [
                'purchase_payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncPurchasePaymentStatus(?Purchase $purchase): void
    {
        if (! $purchase) {
            return;
        }

        $purchase->syncPaymentStatusFromPayments();
    }
}
