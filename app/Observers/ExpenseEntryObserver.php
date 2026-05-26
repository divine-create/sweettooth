<?php

namespace App\Observers;

use App\Models\ExpenseEntry;
use App\Services\GlPostingService;
use Exception;

class ExpenseEntryObserver
{
    public function __construct(private GlPostingService $glPostingService) {}

    public function created(ExpenseEntry $entry): void
    {
        try {
            $this->glPostingService->postExpenseEntry($entry);

            $entry->updateQuietly([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
                'gl_posting_error' => null,
            ]);
        } catch (Exception $e) {
            $entry->updateQuietly([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            \Log::error('Failed to post expense entry to GL', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
