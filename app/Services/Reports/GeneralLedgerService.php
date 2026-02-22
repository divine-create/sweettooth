<?php

namespace App\Services\Reports;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class GeneralLedgerService
{
    /**
     * Get general ledger entries for a specific GL account
     * 
     * @param int|GlAccount $account
     * @param null|AccountingPeriod $period
     * @param null|Carbon $startDate
     * @param null|Carbon $endDate
     * @return Collection
     */
    public function getAccountLedger($account, ?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): Collection
    {
        $accountId = $account instanceof GlAccount ? $account->id : $account;

        $query = GlEntry::where('gl_account_id', $accountId)
            ->where('status', 'posted')
            ->orderBy('entry_date', 'asc')
            ->orderBy('created_at', 'asc');

        if ($period) {
            $query->where('accounting_period_id', $period->id);
        }

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get full GL report with running balances
     */
    public function getFullLedger(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): array
    {
        $accounts = GlAccount::where('is_active', true)
            ->orderBy('account_number')
            ->get();

        $report = [];

        foreach ($accounts as $account) {
            $entries = $this->getAccountLedger($account, $period, $startDate, $endDate, $branchId);

            if ($entries->isEmpty()) {
                continue;
            }

            $debitBalance = 0;
            $creditBalance = 0;
            $entryDetails = [];

            foreach ($entries as $entry) {
                $debitBalance += $entry->debit;
                $creditBalance += $entry->credit;
                $balance = $debitBalance - $creditBalance;

                $entryDetails[] = [
                    'date' => $entry->entry_date,
                    'reference' => $entry->reference_number,
                    'description' => $entry->description,
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'balance' => $balance,
                ];
            }

            $report[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'total_debit' => $debitBalance,
                'total_credit' => $creditBalance,
                'balance' => $debitBalance - $creditBalance,
                'entries' => $entryDetails,
            ];
        }

        return $report;
    }

    /**
     * Get GL summary by account
     */
    public function getSummary(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): Collection
    {
        $query = GlEntry::where('status', 'posted')
            ->selectRaw('gl_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('gl_account_id');

        if ($period) {
            $query->where('accounting_period_id', $period->id);
        }

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->with('glAccount')
            ->get()
            ->map(function ($entry) {
                return [
                    'account' => $entry->glAccount,
                    'total_debit' => $entry->total_debit,
                    'total_credit' => $entry->total_credit,
                    'balance' => $entry->total_debit - $entry->total_credit,
                ];
            });
    }

    /**
     * Export GL entries to array format
     */
    public function export(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): array
    {
        return [
            'generated_at' => now(),
            'period' => $period ? $period->period_name : 'Custom Range',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ledger' => $this->getFullLedger($period, $startDate, $endDate, $branchId),
        ];
    }
}
