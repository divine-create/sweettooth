<?php

namespace App\Observers;

use App\Models\InventoryAdjustment;
use App\Services\GlPostingService;
use Exception;

class InventoryAdjustmentObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the InventoryAdjustment "created" event.
     * Post to GL when an adjustment is approved
     */
    public function created(InventoryAdjustment $adjustment): void
    {
        // Only post if pre-approved (has approved_by set)
        if ($adjustment->approved_by) {
            $this->postToGL($adjustment);
        }
    }

    /**
     * Handle the InventoryAdjustment "updated" event.
     * Post to GL when approved
     */
    public function updated(InventoryAdjustment $adjustment): void
    {
        // Post when approved
        if ($adjustment->wasChanged('approved_by') && $adjustment->approved_by) {
            $this->postToGL($adjustment);
        }
    }

    /**
     * Post inventory adjustment to GL
     */
    private function postToGL(InventoryAdjustment $adjustment): void
    {
        try {
            if ($adjustment->gl_posting_status !== 'pending') {
                return;
            }

            // Only post adjustments that have a cost impact
            if (!$adjustment->cost_impact || $adjustment->cost_impact == 0) {
                $adjustment->update(['gl_posting_status' => 'not_applicable']);
                return;
            }

            $this->glPostingService->postInventoryAdjustmentEntry($adjustment);

            $adjustment->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Inventory adjustment posted to GL', [
                'adjustment_id' => $adjustment->id,
                'adjustment_number' => $adjustment->adjustment_number,
                'type' => $adjustment->type,
                'cost_impact' => $adjustment->cost_impact,
            ]);
        } catch (Exception $e) {
            $adjustment->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post inventory adjustment to GL', [
                'adjustment_id' => $adjustment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
