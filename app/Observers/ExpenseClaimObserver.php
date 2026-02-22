<?php

namespace App\Observers;

use App\Models\ExpenseClaim;
use App\Services\GlPostingService;
use Exception;

class ExpenseClaimObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the ExpenseClaim "updated" event.
     * Post to GL when claim status changes to 'paid'
     */
    public function updated(ExpenseClaim $claim): void
    {
        // Only post when status changes to paid
        if ($claim->wasChanged('status') && $claim->status === 'paid') {
            $this->postToGL($claim);
        }
    }

    /**
     * Post expense claim to GL
     * Debit: Expense Accounts, Credit: Cash/Bank
     */
    private function postToGL(ExpenseClaim $claim): void
    {
        try {
            if ($claim->gl_posting_status !== 'pending') {
                return;
            }

            $this->glPostingService->postExpenseClaim($claim);

            $claim->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Expense claim posted to GL', [
                'claim_id' => $claim->id,
                'employee' => $claim->employee?->name,
                'amount' => $claim->total_amount,
            ]);
        } catch (Exception $e) {
            $claim->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post expense claim to GL', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
