<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationDetail;
use App\Models\DailyBankTransaction;
use App\Models\DailyBankPosition;
use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BankReconciliationService
{
    /**
     * Create a new reconciliation session
     */
    public function createReconciliation(
        int $bankAccountId,
        Carbon $reconciliationDate,
        float $bankBalance,
        string $branchId
    ): ?BankReconciliation {
        $account = BankAccount::find($bankAccountId);
        if (!$account) {
            CleanError::show('Bank account not found. Please refresh and try again.');
            return null;
        }

        $bookBalance = $this->getGlClosingBalance($account, $reconciliationDate);
        $difference = floatval($bankBalance) - floatval($bookBalance);

        return BankReconciliation::create([
            'branch_id' => $branchId,
            'bank_account_id' => $account->id,
            'reconciliation_date' => $reconciliationDate->toDateString(),
            'bank_balance' => $bankBalance,
            'book_balance' => $bookBalance,
            'difference' => $difference,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Get unreconciled GL entries for a bank account as of a date
     */
    public function getUnreconciledGlEntries(int $bankAccountId, Carbon $asOfDate): Collection
    {
        $account = BankAccount::find($bankAccountId);
        if (!$account) {
            CleanError::show('Bank account not found. Please refresh and try again.');
            return collect();
        }
        $reconciliation = $this->getActiveReconciliationForAccount($bankAccountId);
        if (!$reconciliation) {
            CleanError::show('Active reconciliation not found for this account.');
            return collect();
        }

        return GlEntry::query()
            ->where('gl_account_id', $account->gl_account_id)
            ->where('status', 'posted')
            ->where('entry_date', '<=', $asOfDate)
            ->where(function ($query) {
                $query->whereNull('reconciled')->orWhere('reconciled', false);
            })
            ->whereNotIn('id', function ($sub) use ($reconciliation) {
                $sub->select('gl_entry_id')
                    ->from('bank_reconciliation_details')
                    ->where('bank_reconciliation_id', $reconciliation->id)
                    ->whereNull('deleted_at')
                    ->whereNotNull('gl_entry_id');
            })
            ->orderBy('entry_date')
            ->get();
    }

    /**
     * Get unreconciled bank transactions for a bank account as of a date
     */
    public function getUnreconciledBankTransactions(int $bankAccountId, Carbon $asOfDate): Collection
    {
        $reconciliation = $this->getActiveReconciliationForAccount($bankAccountId);
        if (!$reconciliation) {
            CleanError::show('Active reconciliation not found for this account.');
            return collect();
        }

        return DailyBankTransaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('transaction_date', '<=', $asOfDate)
            ->where(function ($query) {
                $query->whereNull('reconciled')->orWhere('reconciled', false);
            })
            ->whereNotIn('id', function ($sub) use ($reconciliation) {
                $sub->select('daily_bank_transaction_id')
                    ->from('bank_reconciliation_details')
                    ->where('bank_reconciliation_id', $reconciliation->id)
                    ->whereNull('deleted_at')
                    ->whereNotNull('daily_bank_transaction_id');
            })
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Get reconciliation stats for UI display
     */
    public function getReconciliationStats(int $reconciliationId): array
    {
        $reconciliation = BankReconciliation::find($reconciliationId);
        if (!$reconciliation) {
            CleanError::show('Reconciliation not found.');
            return [
                'bank_balance' => 0.0,
                'book_balance' => 0.0,
                'difference' => 0.0,
                'matched_count' => 0,
                'matched_amount' => 0.0,
                'unmatched_gl_count' => 0,
                'unmatched_bank_count' => 0,
                'unmatched_gl_total' => 0.0,
                'unmatched_bank_total' => 0.0,
                'is_balanced' => false,
            ];
        }
        $asOfDate = Carbon::parse($reconciliation->reconciliation_date);
        $account = BankAccount::find($reconciliation->bank_account_id);
        if (!$account) {
            CleanError::show('Bank account not found for reconciliation.');
            return [
                'bank_balance' => floatval($reconciliation->bank_balance),
                'book_balance' => 0.0,
                'difference' => 0.0,
                'matched_count' => 0,
                'matched_amount' => 0.0,
                'unmatched_gl_count' => 0,
                'unmatched_bank_count' => 0,
                'unmatched_gl_total' => 0.0,
                'unmatched_bank_total' => 0.0,
                'is_balanced' => false,
            ];
        }

        // Recalculate balances in case GL entries were posted after reconciliation started
        $bookBalance = $this->getGlClosingBalance($account, $asOfDate);
        $difference = floatval($reconciliation->bank_balance) - floatval($bookBalance);

        $unmatchedGlEntries = $this->getUnreconciledGlEntries($account->id, $asOfDate);
        $unmatchedBankTransactions = $this->getUnreconciledBankTransactions($account->id, $asOfDate);

        $unmatchedGlTotal = $unmatchedGlEntries->sum(function ($entry) {
            return floatval($entry->debit) - floatval($entry->credit);
        });

        $unmatchedBankTotal = $unmatchedBankTransactions->sum(function ($tx) {
            $amount = floatval($tx->amount);
            return $tx->transaction_type === 'outflow' ? -$amount : $amount;
        });

        $matchedCount = $reconciliation->details()->count();
        $matchedAmount = floatval($reconciliation->details()->sum('matched_amount'));

        // Keep reconciliation snapshot in sync for reporting
        $reconciliation->update([
            'book_balance' => $bookBalance,
            'difference' => $difference,
        ]);

        return [
            'bank_balance' => floatval($reconciliation->bank_balance),
            'book_balance' => floatval($bookBalance),
            'difference' => $difference,
            'matched_count' => $matchedCount,
            'matched_amount' => $matchedAmount,
            'unmatched_gl_count' => $unmatchedGlEntries->count(),
            'unmatched_bank_count' => $unmatchedBankTransactions->count(),
            'unmatched_gl_total' => floatval($unmatchedGlTotal),
            'unmatched_bank_total' => floatval($unmatchedBankTotal),
            'is_balanced' => abs($difference) < 0.01
                && $unmatchedGlEntries->isEmpty()
                && $unmatchedBankTransactions->isEmpty(),
        ];
    }

    /**
     * Manually match a GL entry and bank transaction
     */
    public function matchTransaction(int $glEntryId, int $bankTransactionId): ?BankReconciliationDetail
    {
        $glEntry = GlEntry::find($glEntryId);
        if (!$glEntry) {
            CleanError::show('GL entry not found. Please refresh and try again.');
            return null;
        }
        $bankTransaction = DailyBankTransaction::find($bankTransactionId);
        if (!$bankTransaction) {
            CleanError::show('Bank transaction not found. Please refresh and try again.');
            return null;
        }

        $accountId = $bankTransaction->bank_account_id;
        $reconciliation = $this->getActiveReconciliationForAccount($accountId);

        if ($glEntry->gl_account_id !== $bankTransaction->bankAccount?->gl_account_id) {
            CleanError::show('GL entry does not match bank account.');
            return null;
        }

        $alreadyMatched = BankReconciliationDetail::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where(function ($q) use ($glEntryId, $bankTransactionId) {
                $q->where('gl_entry_id', $glEntryId)
                    ->orWhere('daily_bank_transaction_id', $bankTransactionId);
            })
            ->whereNull('deleted_at')
            ->exists();

        if ($alreadyMatched) {
            CleanError::show('One of the selected items is already matched.');
            return null;
        }

        $matchedAmount = floatval($bankTransaction->amount);

        return BankReconciliationDetail::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'gl_entry_id' => $glEntry->id,
            'daily_bank_transaction_id' => $bankTransaction->id,
            'matched_amount' => $matchedAmount,
            'matched_at' => now(),
            'matched_by_id' => auth()->id(),
            'match_type' => 'manual',
        ]);
    }

    /**
     * Remove a match
     */
    public function unmatchTransaction(int $detailId): void
    {
        $detail = BankReconciliationDetail::find($detailId);
        if (!$detail) {
            CleanError::show('Match detail not found.');
            return;
        }
        $detail->delete();

        if ($detail->gl_entry_id) {
            GlEntry::where('id', $detail->gl_entry_id)->update([
                'reconciled' => false,
                'reconciled_at' => null,
                'reconciled_by_id' => null,
            ]);
        }

        if ($detail->daily_bank_transaction_id) {
            DailyBankTransaction::where('id', $detail->daily_bank_transaction_id)->update([
                'reconciled' => false,
                'reconciled_at' => null,
                'reconciled_by_id' => null,
            ]);
        }
    }

    /**
     * Auto-match transactions for a bank account
     */
    public function autoMatchTransactions(int $bankAccountId): int
    {
        $reconciliation = $this->getActiveReconciliationForAccount($bankAccountId);
        $glEntries = $this->getUnreconciledGlEntries($bankAccountId, $reconciliation->reconciliation_date);
        $bankTransactions = $this->getUnreconciledBankTransactions($bankAccountId, $reconciliation->reconciliation_date);

        $matchedCount = 0;

        foreach ($bankTransactions as $bankTx) {
            $glEntry = $glEntries->first(function ($gl) use ($bankTx) {
                $glAmount = $bankTx->transaction_type === 'inflow'
                    ? floatval($gl->debit)
                    : floatval($gl->credit);

                if ($glAmount <= 0) {
                    $glAmount = floatval($gl->debit) + floatval($gl->credit);
                }

                $dateDiff = $gl->entry_date->diffInDays($bankTx->transaction_date);

                return abs(floatval($bankTx->amount) - $glAmount) < 0.01 && $dateDiff <= 5;
            });

            if ($glEntry) {
                BankReconciliationDetail::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'gl_entry_id' => $glEntry->id,
                    'daily_bank_transaction_id' => $bankTx->id,
                    'matched_amount' => floatval($bankTx->amount),
                    'matched_at' => now(),
                    'matched_by_id' => auth()->id(),
                    'match_type' => 'auto',
                ]);

                $glEntries = $glEntries->reject(fn ($g) => $g->id === $glEntry->id);
                $matchedCount++;
            }
        }

        return $matchedCount;
    }

    /**
     * Complete reconciliation
     */
    public function completeReconciliation(int $reconciliationId): void
    {
        $reconciliation = BankReconciliation::find($reconciliationId);
        if (!$reconciliation) {
            CleanError::show('Reconciliation not found.');
            return;
        }

        if ($reconciliation->status !== 'in_progress') {
            CleanError::show('Reconciliation is not in progress.');
            return;
        }

        $reconciliation->markCompleted(auth()->id());

        $detailIds = $reconciliation->details()->pluck('id');
        $details = BankReconciliationDetail::whereIn('id', $detailIds)->get();

        $glEntryIds = $details->pluck('gl_entry_id')->filter()->unique()->values();
        $bankTransactionIds = $details->pluck('daily_bank_transaction_id')->filter()->unique()->values();

        if ($glEntryIds->isNotEmpty()) {
            GlEntry::whereIn('id', $glEntryIds)->update([
                'reconciled' => true,
                'reconciled_at' => now(),
                'reconciled_by_id' => auth()->id(),
            ]);
        }

        if ($bankTransactionIds->isNotEmpty()) {
            DailyBankTransaction::whereIn('id', $bankTransactionIds)->update([
                'reconciled' => true,
                'reconciled_at' => now(),
                'reconciled_by_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Perform bank reconciliation for a specific account and date range
     */
    public function reconcileBankAccount(
        BankAccount $account,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        $bankTransactions = $this->getBankTransactions($account, $fromDate, $toDate);
        $glTransactions = $this->getGlTransactions($account, $fromDate, $toDate);

        $matched = $this->matchTransactions($bankTransactions, $glTransactions);
        $unmatched = $this->getUnmatchedTransactions($bankTransactions, $glTransactions, $matched);

        return [
            'bank_account' => $account,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'bank_opening_balance' => $this->getOpeningBalance($account, $fromDate),
            'bank_closing_balance' => $this->getClosingBalance($account, $toDate),
            'gl_opening_balance' => $this->getGlOpeningBalance($account, $fromDate),
            'gl_closing_balance' => $this->getGlClosingBalance($account, $toDate),
            'matched_count' => $matched->count(),
            'unmatched_count' => $unmatched->count(),
            'matched_transactions' => $matched,
            'unmatched_bank_transactions' => $unmatched['bank'],
            'unmatched_gl_transactions' => $unmatched['gl'],
            'reconciliation_status' => $this->determineStatus($matched, $unmatched),
            'variance' => $this->calculateVariance($account, $toDate),
        ];
    }

    /**
     * Get bank transactions for date range
     */
    private function getBankTransactions(BankAccount $account, Carbon $fromDate, Carbon $toDate): Collection
    {
        return DailyBankTransaction::where('bank_account_id', $account->id)
            ->whereBetween('position_date', [$fromDate, $toDate])
            ->orderBy('position_date')
            ->get();
    }

    /**
     * Get GL transactions for the bank account
     */
    private function getGlTransactions(BankAccount $account, Carbon $fromDate, Carbon $toDate): Collection
    {
        return GlEntry::where('gl_account_id', $account->gl_account_id)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->where('status', 'posted')
            ->orderBy('entry_date')
            ->get();
    }

    /**
     * Match bank and GL transactions
     */
    private function matchTransactions(Collection $bankTransactions, Collection $glTransactions): Collection
    {
        $matched = collect();

        foreach ($bankTransactions as $bankTx) {
            // Try to find matching GL transaction
            $glTx = $glTransactions->first(function ($gl) use ($bankTx) {
                return $this->transactionsMatch($bankTx, $gl);
            });

            if ($glTx) {
                $matched->push([
                    'bank_transaction' => $bankTx,
                    'gl_entry' => $glTx,
                    'bank_amount' => $bankTx->amount,
                    'gl_amount' => $bankTx->transaction_type === 'inflow' ? $glTx->debit : $glTx->credit,
                    'matched_date' => now(),
                ]);

                // Remove from GL collection to avoid double matching
                $glTransactions = $glTransactions->reject(fn($g) => $g->id === $glTx->id);
            }
        }

        return $matched;
    }

    /**
     * Check if bank and GL transactions match
     */
    private function transactionsMatch($bankTx, $glTx): bool
    {
        // Match by amount and approximate date (within 5 days)
        $amount = floatval($bankTx->amount);
        $glAmount = floatval($bankTx->transaction_type === 'inflow' ? $glTx->debit : $glTx->credit);
        $dateDiff = $glTx->entry_date->diffInDays($bankTx->position_date);

        return abs($amount - $glAmount) < 0.01 && $dateDiff <= 5;
    }

    /**
     * Get unmatched transactions
     */
    private function getUnmatchedTransactions(
        Collection $bankTransactions,
        Collection $glTransactions,
        Collection $matched
    ): array {
        $matchedBankIds = $matched->pluck('bank_transaction.id');
        $matchedGlIds = $matched->pluck('gl_entry.id');

        return [
            'bank' => $bankTransactions->reject(fn($tx) => $matchedBankIds->contains($tx->id))->values(),
            'gl' => $glTransactions->reject(fn($tx) => $matchedGlIds->contains($tx->id))->values(),
        ];
    }

    /**
     * Get opening balance
     */
    private function getOpeningBalance(BankAccount $account, Carbon $date): float
    {
        $position = DailyBankPosition::where('bank_account_id', $account->id)
            ->where('position_date', '<', $date)
            ->orderBy('position_date', 'desc')
            ->first();

        return $position ? floatval($position->available_balance) : floatval($account->opening_balance);
    }

    /**
     * Get closing balance
     */
    private function getClosingBalance(BankAccount $account, Carbon $date): float
    {
        $position = DailyBankPosition::where('bank_account_id', $account->id)
            ->where('position_date', '<=', $date)
            ->orderBy('position_date', 'desc')
            ->first();

        return $position ? floatval($position->available_balance) : floatval($account->opening_balance);
    }

    /**
     * Get GL opening balance
     */
    private function getGlOpeningBalance(BankAccount $account, Carbon $date): float
    {
        $account = $account->glAccount;
        return floatval($account->debit_balance) - floatval($account->credit_balance);
    }

    /**
     * Get GL closing balance
     */
    private function getGlClosingBalance(BankAccount $account, Carbon $date): float
    {
        $entries = GlEntry::where('gl_account_id', $account->gl_account_id)
            ->where('entry_date', '<=', $date)
            ->where('status', 'posted')
            ->get();

        $debit = floatval($entries->sum('debit'));
        $credit = floatval($entries->sum('credit'));

        return $debit - $credit;
    }

    /**
     * Calculate variance between bank and GL
     */
    private function calculateVariance(BankAccount $account, Carbon $date): float
    {
        $bankBalance = $this->getClosingBalance($account, $date);
        $glBalance = $this->getGlClosingBalance($account, $date);

        return floatval($bankBalance) - floatval($glBalance);
    }

    /**
     * Determine reconciliation status
     */
    private function determineStatus(Collection $matched, array $unmatched): string
    {
        if ($unmatched['bank']->isEmpty() && $unmatched['gl']->isEmpty()) {
            return 'reconciled';
        } elseif ($unmatched['bank']->isEmpty() || $unmatched['gl']->isEmpty()) {
            return 'partially_reconciled';
        } else {
            return 'unreconciled';
        }
    }

    /**
     * Get AR aging report
     */
    public function getArAgingReport(AccountingPeriod $period): array
    {
        $today = now();
        $arAccount = GlAccount::where('account_number', '1200')->first();

        if (!$arAccount) {
            return [];
        }

        $entries = GlEntry::where('gl_account_id', $arAccount->id)
            ->where('accounting_period_id', '<=', $period->id)
            ->where('status', 'posted')
            ->get();

        $currentBalance = floatval($entries->sum('debit')) - floatval($entries->sum('credit'));

        return [
            'period' => $period->getDisplayName(),
            'as_of_date' => $today->toDateString(),
            'total_ar' => $currentBalance,
            'aging_buckets' => [
                'current' => $this->calculateAging($entries, 0, 30),
                '31_60' => $this->calculateAging($entries, 31, 60),
                '61_90' => $this->calculateAging($entries, 61, 90),
                '91_plus' => $this->calculateAging($entries, 91, 999),
            ],
            'percentage_current' => $currentBalance > 0 
                ? ($this->calculateAging($entries, 0, 30) / $currentBalance) * 100 
                : 0,
        ];
    }

    /**
     * Calculate aging bucket
     */
    private function calculateAging(Collection $entries, int $daysMin, int $daysMax): float
    {
        $today = now();
        $total = 0;

        foreach ($entries as $entry) {
            $daysDiff = $entry->entry_date->diffInDays($today);

            if ($daysDiff >= $daysMin && $daysDiff <= $daysMax) {
                $debit = floatval($entry->debit);
                $credit = floatval($entry->credit);
                $total += $debit - $credit;
            }
        }

        return $total;
    }

    /**
     * Get inventory reconciliation report
     */
    public function getInventoryReconciliation(AccountingPeriod $period): array
    {
        $fgAccount = GlAccount::where('account_number', '1330')->first();
        
        if (!$fgAccount) {
            return [];
        }

        $entries = GlEntry::where('gl_account_id', $fgAccount->id)
            ->where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        $glBalance = floatval($entries->sum('debit')) - floatval($entries->sum('credit'));

        // This would be compared against physical inventory count
        return [
            'period' => $period->getDisplayName(),
            'account' => '1330 - Finished Goods Inventory',
            'gl_balance' => $glBalance,
            'physical_count' => 0, // Would be populated from inventory module
            'variance' => 0, // Would be calculated
            'variance_percentage' => 0,
        ];
    }

    /**
     * Mark transactions as reconciled
     */
    public function markTransactionsReconciled(array $transactionIds): bool
    {
        try {
            DailyBankTransaction::whereIn('id', $transactionIds)
                ->update(['reconciled' => true, 'reconciled_at' => now()]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to mark transactions reconciled: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reconciliation discrepancies
     */
    public function getDiscrepancies(BankAccount $account, Carbon $date): Collection
    {
        $discrepancies = collect();

        // Check for duplicate transactions
        $transactions = DailyBankTransaction::where('bank_account_id', $account->id)
            ->where('position_date', '<=', $date)
            ->get();

        $grouped = $transactions->groupBy(function ($item) {
            return $item->amount . '|' . $item->position_date->toDateString();
        });

        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                $discrepancies->push([
                    'type' => 'duplicate',
                    'count' => $group->count(),
                    'amount' => $group->first()->amount,
                    'date' => $group->first()->position_date,
                    'transactions' => $group,
                ]);
            }
        }

        return $discrepancies;
    }

    /**
     * Generate reconciliation summary
     */
    public function getReconciliationSummary(BankAccount $account, Carbon $startDate, Carbon $endDate): array
    {
        $result = $this->reconcileBankAccount($account, $startDate, $endDate);

        return [
            'bank_account_number' => $account->account_number,
            'bank_account_name' => $account->bank_name,
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'bank_opening_balance' => $result['bank_opening_balance'],
            'bank_deposits' => $this->calculateDeposits($result['matched_transactions']),
            'bank_withdrawals' => $this->calculateWithdrawals($result['matched_transactions']),
            'bank_closing_balance' => $result['bank_closing_balance'],
            'gl_opening_balance' => $result['gl_opening_balance'],
            'gl_deposits' => $this->calculateGlDeposits($result['matched_transactions']),
            'gl_withdrawals' => $this->calculateGlWithdrawals($result['matched_transactions']),
            'gl_closing_balance' => $result['gl_closing_balance'],
            'variance' => $result['variance'],
            'unmatched_items' => $result['unmatched_count'],
            'status' => $result['reconciliation_status'],
        ];
    }

    /**
     * Calculate total deposits
     */
    private function calculateDeposits(Collection $transactions): float
    {
        return floatval($transactions
            ->filter(fn($t) => $t['bank_transaction']->transaction_type === 'inflow')
            ->sum('bank_amount'));
    }

    /**
     * Calculate total withdrawals
     */
    private function calculateWithdrawals(Collection $transactions): float
    {
        return floatval($transactions
            ->filter(fn($t) => $t['bank_transaction']->transaction_type === 'outflow')
            ->sum('bank_amount'));
    }

    /**
     * Calculate GL deposits
     */
    private function calculateGlDeposits(Collection $transactions): float
    {
        return floatval($transactions
            ->filter(fn($t) => $t['bank_transaction']->transaction_type === 'inflow')
            ->sum('gl_amount'));
    }

    /**
     * Calculate GL withdrawals
     */
    private function calculateGlWithdrawals(Collection $transactions): float
    {
        return floatval($transactions
            ->filter(fn($t) => $t['bank_transaction']->transaction_type === 'outflow')
            ->sum('gl_amount'));
    }

    private function getActiveReconciliationForAccount(int $bankAccountId): ?BankReconciliation
    {
        $reconciliation = BankReconciliation::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('status', 'in_progress')
            ->orderByDesc('id')
            ->first();

        if (! $reconciliation) {
            CleanError::show('No active reconciliation found for this account.');
            return null;
        }

        return $reconciliation;
    }
}
