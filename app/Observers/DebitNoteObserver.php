<?php

namespace App\Observers;

use App\Models\DebitNote;
use App\Services\GlPostingService;
use Exception;

class DebitNoteObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the DebitNote "updated" event.
     * Post to GL when status changes to 'issued'
     */
    public function updated(DebitNote $debitNote): void
    {
        if ($debitNote->wasChanged('status') && $debitNote->status === 'issued') {
            $this->postToGL($debitNote);
        }
    }

    /**
     * Post debit note to GL
     * Reverses the original purchase: Debit: Accounts Payable, Credit: Inventory
     */
    private function postToGL(DebitNote $debitNote): void
    {
        try {
            if ($debitNote->gl_posting_status !== 'pending') {
                return;
            }

            $this->glPostingService->postDebitNote($debitNote);

            $debitNote->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Debit note posted to GL', [
                'debit_note_id' => $debitNote->id,
                'debit_note_number' => $debitNote->debit_note_number,
                'amount' => $debitNote->total,
            ]);
        } catch (Exception $e) {
            $debitNote->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post debit note to GL', [
                'debit_note_id' => $debitNote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
