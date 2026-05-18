<?php

namespace App\Jobs;

use App\Models\AccountingPostingFailure;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\GlPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedGlPostingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    private const MAX_RETRY_FAILURES = 3;

    public function handle(GlPostingService $postingService): void
    {
        $succeeded = 0;
        $failed = 0;
        $skipped = 0;

        $transactionTypes = [
            'sales' => [Sale::class, 'postSaleTransaction'],
            'purchases' => [Purchase::class, 'postPurchaseTransaction'],
            'payments' => [Payment::class, 'postPaymentTransaction'],
            'purchase_payments' => [PurchasePayment::class, 'postPurchasePayment'],
        ];

        foreach ($transactionTypes as $type => [$modelClass, $method]) {
            $records = $modelClass::where('gl_posting_status', 'failed')->get();

            foreach ($records as $record) {
                $failureCount = AccountingPostingFailure::where('reference_type', $modelClass)
                    ->where('reference_id', $record->id)
                    ->count();

                if ($failureCount >= self::MAX_RETRY_FAILURES) {
                    $skipped++;
                    continue;
                }

                $record->update(['gl_posting_status' => 'pending', 'gl_posting_error' => null]);

                try {
                    $postingService->$method($record);
                    $record->update(['gl_posting_status' => 'posted', 'gl_posted_at' => now()]);
                    $succeeded++;
                } catch (\Throwable $e) {
                    AccountingPostingFailure::create([
                        'reference_type' => $modelClass,
                        'reference_id' => $record->id,
                        'entry_type' => $type,
                        'error_message' => $e->getMessage(),
                        'context' => ['source' => 'retry_job'],
                        'last_seen_at' => now(),
                    ]);
                    $record->update([
                        'gl_posting_status' => 'failed',
                        'gl_posting_error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
        }

        // Handle adjustments separately (needs adjustmentQuery filter)
        $adjustments = StockMovement::query()
            ->where('gl_posting_status', 'failed')
            ->where(function ($q) {
                $q->where('type', 'damaged')
                    ->orWhere(function ($sq) {
                        $sq->where('type', 'adjustment')->whereNotNull('adjustment_reason');
                    });
            })
            ->get();

        foreach ($adjustments as $record) {
            $failureCount = AccountingPostingFailure::where('reference_type', StockMovement::class)
                ->where('reference_id', $record->id)
                ->count();

            if ($failureCount >= self::MAX_RETRY_FAILURES) {
                $skipped++;
                continue;
            }

            $record->update(['gl_posting_status' => 'pending', 'gl_posting_error' => null]);

            try {
                $postingService->postInventoryAdjustment($record);
                $record->update(['gl_posting_status' => 'posted', 'gl_posted_at' => now()]);
                $succeeded++;
            } catch (\Throwable $e) {
                AccountingPostingFailure::create([
                    'reference_type' => StockMovement::class,
                    'reference_id' => $record->id,
                    'entry_type' => 'adjustments',
                    'error_message' => $e->getMessage(),
                    'context' => ['source' => 'retry_job'],
                    'last_seen_at' => now(),
                ]);
                $record->update([
                    'gl_posting_status' => 'failed',
                    'gl_posting_error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        if ($succeeded > 0 || $failed > 0) {
            Log::info('RetryFailedGlPostingsJob completed', [
                'succeeded' => $succeeded,
                'failed' => $failed,
                'skipped_max_retries' => $skipped,
            ]);
        }
    }
}
