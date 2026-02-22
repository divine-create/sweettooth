<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Services\GlPostingService;
use Exception;

class PurchaseObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the Purchase "created" event.
     * Post to GL when purchase is created as approved
     */
    public function created(Purchase $purchase): void
    {
        if ($purchase->status === 'approved' && $purchase->gl_posting_status === 'pending') {
            $this->postToGL($purchase);
        }
    }

    /**
     * Handle the Purchase "updated" event.
     * Post to GL when purchase is approved
     */
    public function updated(Purchase $purchase): void
    {
        // Only post if status changes to approved and not already posted
        if ($purchase->wasChanged('status') && 
            $purchase->status === 'approved' && 
            $purchase->gl_posting_status === 'pending') {
            $this->postToGL($purchase);
        }
    }

    /**
     * Post purchase transaction to GL
     */
    private function postToGL(Purchase $purchase): void
    {
        try {
            // Avoid duplicate posting
            if ($purchase->gl_posting_status !== 'pending') {
                return;
            }

            // Post to GL
            $this->glPostingService->postPurchaseTransaction($purchase);

            // Update posting status
            $purchase->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Purchase posted to GL', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'amount' => $purchase->calculateTotalCost(),
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            $purchase->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post purchase to GL', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
