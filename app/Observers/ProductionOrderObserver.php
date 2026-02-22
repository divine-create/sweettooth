<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use App\Services\GlPostingService;
use Exception;

class ProductionOrderObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the ProductionOrder "updated" event.
     * Post to GL when status changes to 'completed'
     */
    public function updated(ProductionOrder $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'completed') {
            $this->postToGL($order);
        }
    }

    /**
     * Post production order to GL
     * Debit: Finished Goods Inventory, Credit: Raw Materials Inventory + WIP
     */
    private function postToGL(ProductionOrder $order): void
    {
        try {
            if ($order->gl_posting_status !== 'pending') {
                return;
            }

            $this->glPostingService->postProductionOrder($order);

            $order->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Production order posted to GL', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'output_product' => $order->outputProduct?->name,
                'total_cost' => $order->total_cost,
            ]);
        } catch (Exception $e) {
            $order->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post production order to GL', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
