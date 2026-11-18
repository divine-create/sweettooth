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
        // Do NOT gate on gl_posting_status here: on the `created` event a freshly
        // created model has that attribute unhydrated (null) — the DB default
        // 'pending' isn't loaded into the instance — so checking it would wrongly
        // skip posting. Gate on status only; postToGL() guards against re-posting.
        if ($purchase->status === 'approved') {
            $this->postToGL($purchase);
        }

        if ($purchase->supplier_id) {
            $this->syncSupplierBalance($purchase->supplier_id);
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

        if ($purchase->supplier_id) {
            $this->syncSupplierBalance($purchase->supplier_id);
        }
    }

    /**
     * Handle the Purchase "deleted" event.
     */
    public function deleted(Purchase $purchase): void
    {
        if ($purchase->supplier_id) {
            $this->syncSupplierBalance($purchase->supplier_id);
        }
    }



    /**
     * Recompute and sync supplier outstanding balance
     */
    private function syncSupplierBalance(?int $supplierId): void
    {
        if (!$supplierId) {
            return;
        }

        try {
            $pendingTotal = Purchase::where('supplier_id', $supplierId)
                ->where('payment_status', '!=', 'paid')
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(landing_cost, total_cost, 0)'));

            \App\Models\Supplier::where('id', $supplierId)->update([
                'outstanding_balance' => $pendingTotal,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to sync supplier outstanding balance: ' . $e->getMessage());
        }
    }

    /**
     * Post purchase transaction to GL
     */
    private function postToGL(Purchase $purchase): void
    {
        try {
            // Avoid duplicate posting. null (unhydrated on create) and 'pending'
            // both mean "not yet posted"; only skip when already posted. The GL
            // service also enforces idempotency at the entry level.
            if ($purchase->gl_posting_status === 'posted') {
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
