<?php

namespace App\Observers;

use App\Models\AccountTransfer;
use App\Services\GlPostingService;
use Exception;

class AccountTransferObserver
{
    protected GlPostingService $glPostingService;

    public function __construct(GlPostingService $glPostingService)
    {
        $this->glPostingService = $glPostingService;
    }

    /**
     * Handle the AccountTransfer "created" event.
     * Post to GL immediately when a transfer is created
     */
    public function created(AccountTransfer $transfer): void
    {
        $this->postToGL($transfer);
    }

    /**
     * Post account transfer to GL
     * Debit: To Bank Account, Credit: From Bank Account
     */
    private function postToGL(AccountTransfer $transfer): void
    {
        try {
            if ($transfer->gl_posting_status !== 'pending') {
                return;
            }

            $this->glPostingService->postAccountTransfer($transfer);

            $transfer->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);

            \Log::info('Account transfer posted to GL', [
                'transfer_id' => $transfer->id,
                'from_account' => $transfer->fromBankAccount?->name,
                'to_account' => $transfer->toBankAccount?->name,
                'amount' => $transfer->amount,
            ]);
        } catch (Exception $e) {
            $transfer->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post account transfer to GL', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
