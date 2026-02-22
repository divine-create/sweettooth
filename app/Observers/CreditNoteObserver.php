<?php

namespace App\Observers;

use App\Models\CreditNote;
use App\Services\GlPostingService;
use Exception;

class CreditNoteObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the CreditNote "updated" event.
     * Post to GL when status changes to 'issued'
     */
    public function updated(CreditNote $creditNote): void
    {
        if ($creditNote->wasChanged('status') && $creditNote->status === 'issued') {
            $this->postToGL($creditNote);
        }
    }

    /**
     * Post credit note to GL
     * Reverses the original sale: Debit: Revenue, Credit: Accounts Receivable/Cash
     */
    private function postToGL(CreditNote $creditNote): void
    {
        try {
            if ($creditNote->gl_posting_status !== 'pending') {
                return;
            }

            $this->glPostingService->postCreditNote($creditNote);

            $creditNote->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Credit note posted to GL', [
                'credit_note_id' => $creditNote->id,
                'credit_note_number' => $creditNote->credit_note_number,
                'amount' => $creditNote->total,
            ]);
        } catch (Exception $e) {
            $creditNote->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post credit note to GL', [
                'credit_note_id' => $creditNote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
