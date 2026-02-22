<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Services\GlPostingService;
use Exception;

class StockMovementObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the StockMovement "created" event.
     * Post to GL for accounting-relevant adjustments
     */
    public function created(StockMovement $movement): void
    {
        $isDamagedType = $movement->type === 'damaged';
        $isAdjustmentWithReason = $movement->type === 'adjustment' && ! empty($movement->adjustment_reason);

        if (($isDamagedType || $isAdjustmentWithReason) && $movement->approved_by_id) {
            $this->postToGL($movement);
        }
    }

    /**
     * Handle the StockMovement "updated" event.
     * Post to GL once approval is granted.
     */
    public function updated(StockMovement $movement): void
    {
        $isDamagedType = $movement->type === 'damaged';
        $isAdjustmentWithReason = $movement->type === 'adjustment' && ! empty($movement->adjustment_reason);

        if (($isDamagedType || $isAdjustmentWithReason) &&
            $movement->wasChanged('approved_by_id') &&
            $movement->approved_by_id) {
            $this->postToGL($movement);
        }
    }

    /**
     * Post inventory adjustment to GL
     */
    private function postToGL(StockMovement $movement): void
    {
        try {
            // Avoid duplicate posting
            if ($movement->gl_posting_status !== 'pending') {
                return;
            }

            // Post to GL
            $this->glPostingService->postInventoryAdjustment($movement);

            // Update posting status
            $movement->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Stock movement posted to GL', [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'stock_id' => $movement->stock_id,
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            $movement->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post stock movement to GL', [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
