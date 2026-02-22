<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;

class BalanceSheetService
{
    /**
     * Get balance sheet for a period
     */
    public function getBalanceSheet(?int $periodId = null, ?string $branchId = null): array
    {
        $branchId = $branchId ?? current_branch_id();
        // Assets (1000-1999)
        $assets = $this->getAccountsByRange(1000, 1999, $periodId, $branchId);
        $totalAssets = $assets->sum('balance');

        // Liabilities (2000-2999)
        $liabilities = $this->getAccountsByRange(2000, 2999, $periodId, $branchId);
        $totalLiabilities = $liabilities->sum('balance');

        // Equity (3000-3999)
        $equity = $this->getAccountsByRange(3000, 3999, $periodId, $branchId);
        $totalEquity = $equity->sum('balance');

        // Retained Earnings (calculated)
        $retainedEarnings = $this->calculateRetainedEarnings($periodId, $branchId);

        // Total Equity with Retained Earnings
        $totalEquityWithRE = $totalEquity + $retainedEarnings;

        // Total Liabilities and Equity
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquityWithRE;

        // Check balance
        $isBalanced = abs($totalAssets - $totalLiabilitiesEquity) < 0.01;

        return [
            'assets' => $assets->values()->toArray(),
            'total_assets' => $totalAssets,

            'liabilities' => $liabilities->values()->toArray(),
            'total_liabilities' => $totalLiabilities,

            'equity' => $equity->values()->toArray(),
            'total_equity' => $totalEquity,

            'retained_earnings' => $retainedEarnings,
            'total_equity_with_re' => $totalEquityWithRE,

            'total_liabilities_equity' => $totalLiabilitiesEquity,

            'is_balanced' => $isBalanced,
            'difference' => $totalAssets - $totalLiabilitiesEquity,

            'period_id' => $periodId,
        ];
    }

    /**
     * Calculate retained earnings from income statement
     */
    protected function calculateRetainedEarnings(?int $periodId = null, ?string $branchId = null): float
    {
        $incomeService = new IncomeStatementService();
        $is = $incomeService->getIncomeStatement($periodId, $branchId);
        return $is['net_income'];
    }

    /**
     * Get accounts in a number range
     */
    protected function getAccountsByRange(
        int $startNumber,
        int $endNumber,
        ?int $periodId = null,
        ?string $branchId = null,
    ) {
        $accounts = GlAccount::where('is_active', true)
            ->whereBetween('account_number', [(string)$startNumber, (string)$endNumber])
            ->where('account_type', '!=', 'header')
            ->orderBy('account_number')
            ->get();

        return $accounts->map(function ($account) use ($periodId, $branchId) {
            $balance = $this->getAccountBalance($account->id, $periodId, $branchId);
            return [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'account_category' => $account->account_category,
                'balance' => $balance,
            ];
        })->filter(fn($a) => $a['balance'] != 0);
    }

    /**
     * Get account balance
     */
    protected function getAccountBalance(int $accountId, ?int $periodId = null, ?string $branchId = null): float
    {
        $query = GlEntry::where('gl_account_id', $accountId)
            ->where('status', 'posted');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        $debits = (float) $query->sum('debit');
        $credits = (float) $query->sum('credit');

        $account = GlAccount::find($accountId);
        if ($account && $account->normal_balance === 'credit') {
            return $credits - $debits;
        }

        return $debits - $credits;
    }

    /**
     * Get comparative balance sheet
     */
    public function getComparativeBalanceSheet(
        ?int $periodId1 = null,
        ?int $periodId2 = null,
        ?string $branchId = null,
    ): array {
        $bs1 = $this->getBalanceSheet($periodId1, $branchId);
        $bs2 = $this->getBalanceSheet($periodId2, $branchId);

        return [
            'period1' => $bs1,
            'period2' => $bs2,
            'variance' => [
                'assets_change' => $bs2['total_assets'] - $bs1['total_assets'],
                'assets_change_percent' => $bs1['total_assets'] > 0
                    ? (($bs2['total_assets'] - $bs1['total_assets']) / $bs1['total_assets']) * 100
                    : 0,
                'liabilities_change' => $bs2['total_liabilities'] - $bs1['total_liabilities'],
                'equity_change' => $bs2['total_equity_with_re'] - $bs1['total_equity_with_re'],
            ],
        ];
    }

    /**
     * Get financial ratios
     */
    public function getFinancialRatios(?int $periodId = null, ?string $branchId = null): array
    {
        $bs = $this->getBalanceSheet($periodId, $branchId);

        $currentAssets = collect($bs['assets'])
            ->filter(fn($a) => in_array($a['account_category'], ['cash', 'receivable']))
            ->sum('balance');

        $currentLiabilities = collect($bs['liabilities'])
            ->filter(fn($a) => in_array($a['account_category'], ['payable', 'short_term']))
            ->sum('balance');

        $workingCapital = $currentAssets - $currentLiabilities;

        return [
            'current_ratio' => $currentLiabilities > 0 ? $currentAssets / $currentLiabilities : 0,
            'debt_to_equity' => $bs['total_equity_with_re'] > 0 
                ? $bs['total_liabilities'] / $bs['total_equity_with_re'] 
                : 0,
            'asset_turnover' => 0, // Requires revenue data
            'roa' => 0, // Requires net income data
            'roe' => 0, // Requires net income data
            'working_capital' => $workingCapital,
            'equity_ratio' => $bs['total_assets'] > 0 
                ? $bs['total_equity_with_re'] / $bs['total_assets'] 
                : 0,
        ];
    }

    /**
     * Export balance sheet
     */
    public function exportBalanceSheet(?int $periodId = null, ?string $branchId = null): array
    {
        $bs = $this->getBalanceSheet($periodId, $branchId);

        $data = [
            ['BALANCE SHEET', '', ''],
            ['', '', ''],
            ['ASSETS', '', ''],
        ];

        foreach ($bs['assets'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['TOTAL ASSETS', '', number_format($bs['total_assets'], 2)];
        $data[] = ['', '', ''];

        $data[] = ['LIABILITIES', '', ''];

        foreach ($bs['liabilities'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['TOTAL LIABILITIES', '', number_format($bs['total_liabilities'], 2)];
        $data[] = ['', '', ''];

        $data[] = ['EQUITY', '', ''];

        foreach ($bs['equity'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        if ($bs['retained_earnings'] != 0) {
            $data[] = [
                '  3020',
                'Retained Earnings',
                number_format($bs['retained_earnings'], 2),
            ];
        }

        $data[] = ['TOTAL EQUITY', '', number_format($bs['total_equity_with_re'], 2)];
        $data[] = ['', '', ''];
        $data[] = ['TOTAL LIABILITIES & EQUITY', '', number_format($bs['total_liabilities_equity'], 2)];

        return $data;
    }
}
