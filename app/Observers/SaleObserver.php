<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\GlPostingService;
use Exception;

class SaleObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the Sale "created" event.
     * Post to GL when a sale is completed and fully paid
     */
    public function created(Sale $sale): void
    {
        // Only post if sale is completed
        if ($sale->status === 'completed') {
            $this->postToGL($sale);
        }
    }

    /**
     * Handle the Sale "updated" event.
     * Post to GL if status changes to completed
     */
    public function updated(Sale $sale): void
    {
        // Only post if just marked as completed and not already posted
        if ($sale->wasChanged('status') && 
            $sale->status === 'completed' && 
            $sale->gl_posting_status === 'pending') {
            $this->postToGL($sale);
        }
    }

    /**
     * Post sale transaction to GL
     */
    private function postToGL(Sale $sale): void
    {
        try {
            // Avoid duplicate posting
            if ($sale->gl_posting_status !== 'pending') {
                return;
            }

            // Post to GL
            $this->glPostingService->postSaleTransaction($sale);

            // Update posting status
            $sale->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Sale posted to GL', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'amount' => $sale->total,
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            $sale->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post sale to GL', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
