<?php

namespace App\Services;

use App\Helpers\Settings;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockBatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockBatchService
{
    /**
     * Create a new stock batch when a purchase item is received.
     * Call this after the Stock record has been updated (quantity + average_cost).
     */
    public function createBatchFromPurchase(
        Stock $stock,
        PurchaseItem $purchaseItem,
        float $baseQuantity,
        float $baseCostPerUnit
    ): StockBatch {
        $batch = StockBatch::create([
            'stock_id'           => $stock->id,
            'branch_id'          => $stock->branch_id,
            'purchase_item_id'   => $purchaseItem->id,
            'batch_number'       => $purchaseItem->batch_number ?: null,
            'expiry_date'        => $purchaseItem->expiry_date ?: null,
            'quantity_received'  => $baseQuantity,
            'quantity_remaining' => $baseQuantity,
            'unit_cost'          => $baseCostPerUnit,
            'status'             => 'active',
            'received_at'        => $purchaseItem->purchase?->purchase_date ?? today(),
        ]);

        $this->syncStockExpiryFromBatches($stock);

        return $batch;
    }

    /**
     * FEFO consumption: deduct $quantity from the soonest-expiring active batches.
     * The caller is responsible for updating stocks.quantity_available.
     *
     * Returns an array of [batch_id => quantity_consumed] for audit notes.
     *
     * @throws \RuntimeException when batch quantities are exhausted before $quantity is satisfied
     */
    public function consumeFefo(Stock $stock, float $quantity): array
    {
        $consumed = [];
        $remaining = $quantity;

        $batches = StockBatch::where('stock_id', $stock->id)
            ->fefoOrdered()
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->quantity_remaining, $remaining);
            $batch->quantity_remaining = (float) $batch->quantity_remaining - $take;

            if ($batch->quantity_remaining <= 0) {
                $batch->status = 'depleted';
            }

            $batch->save();
            $consumed[$batch->id] = $take;
            $remaining -= $take;
        }

        if ($remaining > 0.001) {
            throw new \RuntimeException(
                "Batch stock exhausted: could only consume " .
                number_format($quantity - $remaining, 2) . " of " . number_format($quantity, 2) . " requested."
            );
        }

        $this->syncStockExpiryFromBatches($stock->fresh());

        return $consumed;
    }

    /**
     * Sync stocks.expiry_date to the earliest non-null expiry among active batches.
     * Also upgrades health_status based on the expiry window (never downgrades).
     */
    public function syncStockExpiryFromBatches(Stock $stock): void
    {
        $earliestExpiry = StockBatch::where('stock_id', $stock->id)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->min('expiry_date');

        $stock->expiry_date = $earliestExpiry ? Carbon::parse($earliestExpiry) : null;

        $warningDays = (int) Settings::inventoryManagement('expiry_warning_days', 7);
        $hasExpired = StockBatch::where('stock_id', $stock->id)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->exists();

        $hasExpiringSoon = ! $hasExpired && StockBatch::where('stock_id', $stock->id)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', today()->addDays($warningDays))
            ->exists();

        // Only upgrade health_status, never downgrade
        if ($hasExpired) {
            $stock->health_status = 'expired';
        } elseif ($hasExpiringSoon && in_array($stock->health_status, ['good', 'warning'])) {
            $stock->health_status = 'warning';
        }

        $stock->save();
    }

    /**
     * Nightly job: mark batches as 'expired' where expiry_date < today.
     * Returns the count of newly expired batches.
     */
    public function markExpiredBatches(string $branchId): int
    {
        $count = 0;

        $expiredBatches = StockBatch::forBranch($branchId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->with('stock')
            ->get();

        $affectedStockIds = $expiredBatches->pluck('stock_id')->unique();

        $updated = StockBatch::forBranch($branchId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->update(['status' => 'expired']);

        $count = $updated;

        // Re-sync expiry on each affected stock record
        foreach ($affectedStockIds as $stockId) {
            $stock = Stock::find($stockId);
            if ($stock) {
                $this->syncStockExpiryFromBatches($stock);
            }
        }

        return $count;
    }

    /**
     * Get active batches approaching expiry within $warningDays days.
     */
    public function getExpiringBatches(string $branchId, ?int $warningDays = null): Collection
    {
        $warningDays = $warningDays ?? (int) Settings::inventoryManagement('expiry_warning_days', 7);

        return StockBatch::forBranch($branchId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', today())
            ->whereDate('expiry_date', '<=', today()->addDays($warningDays))
            ->with('stock.item')
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get expired batches that still have remaining quantity.
     */
    public function getExpiredBatchesWithStock(string $branchId): Collection
    {
        return StockBatch::forBranch($branchId)
            ->whereIn('status', ['active', 'expired'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->where('quantity_remaining', '>', 0)
            ->with('stock.item')
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Build a human-readable note string from FEFO consumed array for StockMovement.notes.
     * Example: "FEFO batches: #12(30.00), #15(8.00)"
     */
    public function buildFefoNote(array $consumed): string
    {
        if (empty($consumed)) {
            return '';
        }

        $parts = array_map(
            fn ($batchId, $qty) => "#{$batchId}(" . number_format($qty, 2) . ")",
            array_keys($consumed),
            array_values($consumed)
        );

        return 'FEFO batches: ' . implode(', ', $parts);
    }
}
