<?php

namespace App\Services;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\StockMovement;
use App\Models\Item;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAccountingService
{
    /**
     * Record inventory received (purchase)
     * Debit: Inventory Asset, Credit: AP or Cash
     */
    public function recordInventoryReceived(Item $item, float $quantity, float $unitCost, ?string $supplier = null): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            // Determine which inventory account based on item category
            $inventoryAccount = $this->getInventoryAccount($item);
            $apAccount = GlAccount::where('account_number', '2101')->firstOrFail();

            $totalAmount = $quantity * $unitCost;

            // Entry: Debit Inventory, Credit AP
            $this->createEntry([
                'gl_account_id' => $inventoryAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => Item::class,
                'reference_id' => $item->id,
                'reference_number' => $item->sku,
                'description' => "Inventory received - {$item->name} ({$quantity} units @ {$unitCost})",
                'debit' => $totalAmount,
                'credit' => 0,
                'entry_date' => now(),
            ]);

            $this->createEntry([
                'gl_account_id' => $apAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => Item::class,
                'reference_id' => $item->id,
                'reference_number' => $item->sku,
                'description' => "AP for {$item->name} - {$supplier}",
                'debit' => 0,
                'credit' => $totalAmount,
                'entry_date' => now(),
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Inventory received GL posting failed: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * Record inventory movement (internal transfer/adjustment)
     * Used for: Purchase, return, transfer between locations
     */
    public function recordInventoryMovement(StockMovement $movement): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $item = $movement->item;
            $fromAccount = $this->getInventoryAccount($item);
            $toAccount = $this->getInventoryAccount($item); // Could be different based on movement type

            $totalAmount = $movement->quantity * $movement->unit_cost;

            // For normal movement, same account (stock adjustment)
            // For purchase returns, different accounts
            if ($movement->movement_type === 'return') {
                $apAccount = GlAccount::where('account_number', '2101')->firstOrFail();

                $this->createEntry([
                    'gl_account_id' => $apAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'description' => "Return inventory - {$item->name}",
                    'debit' => $totalAmount,
                    'credit' => 0,
                    'entry_date' => $movement->movement_date,
                ]);

                $this->createEntry([
                    'gl_account_id' => $fromAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'description' => "Return inventory reduction - {$item->name}",
                    'debit' => 0,
                    'credit' => $totalAmount,
                    'entry_date' => $movement->movement_date,
                ]);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stock movement GL posting failed: ' . $e->getMessage(), [
                'movement_id' => $movement->id,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * Perform inventory valuation at period end
     * Calculate FIFO/Weighted average costs
     */
    public function performPeriodEndValuation(AccountingPeriod $period): array
    {
        $results = [
            'valuations' => [],
            'adjustments' => [],
        ];

        try {
            $items = Item::where('status', 'active')->get();

            foreach ($items as $item) {
                $currentValue = $this->calculateInventoryValue($item, $period);
                $results['valuations'][] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'quantity' => $item->getCurrentStock(),
                    'value' => $currentValue,
                ];
            }

            return $results;
        } catch (Exception $e) {
            Log::error('Period end valuation failed: ' . $e->getMessage());
            return $results;
        }
    }

    /**
     * Calculate inventory value using weighted average
     */
    private function calculateInventoryValue(Item $item, AccountingPeriod $period): float
    {
        $movements = StockMovement::where('item_id', $item->id)
            ->where('movement_date', '<=', $period->period_end)
            ->where('movement_type', '!=', 'sale')
            ->get();

        if ($movements->isEmpty()) {
            return 0;
        }

        $totalCost = $movements->sum(function ($m) {
            return $m->quantity * $m->unit_cost;
        });

        $totalQuantity = $movements->sum('quantity');

        if ($totalQuantity == 0) {
            return 0;
        }

        return ($totalCost / $totalQuantity) * $item->getCurrentStock();
    }

    /**
     * Record inventory writeoff/obsolescence
     */
    public function recordInventoryWriteoff(Item $item, float $quantity, float $unitCost, string $reason): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $inventoryAccount = $this->getInventoryAccount($item);
            $writeoffAccount = GlAccount::where('account_number', '5210')->firstOrFail();

            $totalAmount = $quantity * $unitCost;

            // Entry: Debit Writeoff Expense, Credit Inventory
            $this->createEntry([
                'gl_account_id' => $writeoffAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => Item::class,
                'reference_id' => $item->id,
                'description' => "Inventory writeoff - {$item->name}: {$reason}",
                'debit' => $totalAmount,
                'credit' => 0,
                'entry_date' => now(),
            ]);

            $this->createEntry([
                'gl_account_id' => $inventoryAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'adjustment',
                'reference_type' => Item::class,
                'reference_id' => $item->id,
                'description' => "Inventory reduction for writeoff - {$item->name}",
                'debit' => 0,
                'credit' => $totalAmount,
                'entry_date' => now(),
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Inventory writeoff GL posting failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current open accounting period
     */
    private function getCurrentPeriod(): ?AccountingPeriod
    {
        return AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();
    }

    /**
     * Determine which inventory account to use
     */
    private function getInventoryAccount(Item $item): GlAccount
    {
        $categoryLower = strtolower($item->category ?? '');

        if (strpos($categoryLower, 'raw') !== false) {
            return GlAccount::where('account_number', '1310')->firstOrFail();
        } elseif (strpos($categoryLower, 'wip') !== false) {
            return GlAccount::where('account_number', '1320')->firstOrFail();
        } elseif (strpos($categoryLower, 'finished') !== false || strpos($categoryLower, 'product') !== false) {
            return GlAccount::where('account_number', '1330')->firstOrFail();
        } else {
            return GlAccount::where('account_number', '1340')->firstOrFail(); // Default to supplies
        }
    }

    /**
     * Create and post a GL entry
     */
    private function createEntry(array $data): GlEntry
    {
        $entry = GlEntry::create([
            ...$data,
            'status' => 'draft',
            'entered_by_id' => auth()->id() ?? null,
            'entered_by_type' => auth()->user() ? get_class(auth()->user()) : null,
        ]);

        // Auto-post
        $entry->post(auth()->id() ?? 1);

        return $entry;
    }
}
