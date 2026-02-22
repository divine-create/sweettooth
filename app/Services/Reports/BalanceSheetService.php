<?php

namespace App\Services\Reports;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Carbon\Carbon;

class BalanceSheetService
{
    /**
     * Generate Balance Sheet (Statement of Financial Position)
     * 
     * Format:
     * ASSETS
     * - Current Assets
     * - Non-Current Assets
     * = Total Assets
     * 
     * LIABILITIES & EQUITY
     * - Current Liabilities
     * - Non-Current Liabilities
     * - Equity
     * = Total Liabilities & Equity
     */
    public function generate(?AccountingPeriod $period = null, ?Carbon $asOfDate = null, ?string $branchId = null): array
    {
        $assets = $this->getAccountBalances('asset', $period, $asOfDate, $branchId);
        $liabilities = $this->getAccountBalances('liability', $period, $asOfDate, $branchId);
        $equity = $this->getAccountBalances('equity', $period, $asOfDate, $branchId);

        $totalAssets = abs($assets['balance']);
        $totalLiabilities = abs($liabilities['balance']);
        $totalEquity = abs($equity['balance']);
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        return [
            'period' => $period ? $period->period_name : 'Custom Date',
            'generated_at' => now(),
            'as_of_date' => $asOfDate,
            'assets' => [
                'items' => $assets['accounts'],
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'items' => $liabilities['accounts'],
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'items' => $equity['accounts'],
                'total' => $totalEquity,
            ],
            'totals' => [
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'total_liabilities_equity' => $totalLiabilitiesEquity,
            ],
            'balance_check' => [
                'is_balanced' => abs($totalAssets - $totalLiabilitiesEquity) < 0.01,
                'difference' => $totalAssets - $totalLiabilitiesEquity,
            ],
        ];
    }

    /**
     * Get account balances by type
     */
    private function getAccountBalances(string $type, ?AccountingPeriod $period = null, ?Carbon $asOfDate = null, ?string $branchId = null): array
    {
        $query = GlAccount::where('account_type', $type)
            ->where('is_active', true);

        $accounts = $query->get();
        $totalDebit = 0;
        $totalCredit = 0;
        $accountDetails = [];

        foreach ($accounts as $account) {
            $entryQuery = GlEntry::where('gl_account_id', $account->id)
                ->where('status', 'posted');

            if ($period) {
                $entryQuery->where('accounting_period_id', $period->id);
            }

            if ($asOfDate) {
                $entryQuery->whereDate('entry_date', '<=', $asOfDate);
            }

            if ($branchId) {
                $entryQuery->where('branch_id', $branchId);
            }

            $debit = $entryQuery->sum('debit');
            $credit = $entryQuery->sum('credit');

            if ($debit > 0 || $credit > 0) {
                $accountDetails[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->account_name,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                ];

                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        return [
            'accounts' => $accountDetails,
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => $totalDebit - $totalCredit,
        ];
    }

    /**
     * Validate balance sheet equation (Assets = Liabilities + Equity)
     */
    public function validate(?AccountingPeriod $period = null): bool
    {
        $report = $this->generate($period);
        return $report['balance_check']['is_balanced'];
    }

    /**
     * Compare balance sheet with previous period
     */
    public function compareWithPrevious(AccountingPeriod $period): array
    {
        $current = $this->generate($period);
        $previous = $this->generate($period->previousPeriod);

        return [
            'current_period' => $current,
            'previous_period' => $previous,
            'variance' => [
                'assets' => $current['totals']['total_assets'] - $previous['totals']['total_assets'],
                'liabilities' => $current['totals']['total_liabilities'] - $previous['totals']['total_liabilities'],
                'equity' => $current['totals']['total_equity'] - $previous['totals']['total_equity'],
            ],
        ];
    }

    /**
     * Export balance sheet to array
     */
    public function export(?AccountingPeriod $period = null, ?Carbon $asOfDate = null): array
    {
        return $this->generate($period, $asOfDate);
    }
}
