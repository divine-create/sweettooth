<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use Carbon\Carbon;

class IncomeStatementService
{
    /**
     * Get income statement (P&L) for a period
     */
    public function getIncomeStatement(?int $periodId = null, ?string $branchId = null): array
    {
        $branchId = $branchId ?? current_branch_id();
        // Revenue accounts (4000-4099)
        $revenues = $this->getAccountsByRange(4000, 4099, $periodId, $branchId);
        $totalRevenue = $revenues->sum('balance');

        // COGS accounts (5000-5099)
        $cogs = $this->getAccountsByRange(5000, 5099, $periodId, $branchId);
        $totalCogs = $cogs->sum('balance');

        // Gross Profit
        $grossProfit = $totalRevenue - $totalCogs;

        // Operating Expenses (6000-6999)
        $opex = $this->getAccountsByRange(6000, 6999, $periodId, $branchId);
        $totalOpex = $opex->sum('balance');

        // Administrative Expenses (7000-7999)
        $admin = $this->getAccountsByRange(7000, 7999, $periodId, $branchId);
        $totalAdmin = $admin->sum('balance');

        // Finance Costs (8000-8999)
        $finance = $this->getAccountsByRange(8000, 8999, $periodId, $branchId);
        $totalFinance = $finance->sum('balance');

        // Total Expenses
        $totalExpenses = $totalOpex + $totalAdmin + $totalFinance;

        // EBIT
        $ebit = $grossProfit - $totalOpex - $totalAdmin;

        // EBT (Earnings Before Tax)
        $ebt = $ebit - $totalFinance;

        // Taxes (9000-9999)
        $taxes = $this->getAccountsByRange(9000, 9999, $periodId, $branchId);
        $totalTaxes = $taxes->sum('balance');

        // Net Income
        $netIncome = $ebt - $totalTaxes;

        return [
            'revenues' => $revenues->values()->toArray(),
            'total_revenue' => $totalRevenue,
            
            'cogs' => $cogs->values()->toArray(),
            'total_cogs' => $totalCogs,
            
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,

            'opex' => $opex->values()->toArray(),
            'total_opex' => $totalOpex,

            'admin' => $admin->values()->toArray(),
            'total_admin' => $totalAdmin,

            'finance' => $finance->values()->toArray(),
            'total_finance' => $totalFinance,

            'total_expenses' => $totalExpenses,
            'expenses_ratio' => $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0,

            'ebit' => $ebit,
            'ebit_margin' => $totalRevenue > 0 ? ($ebit / $totalRevenue) * 100 : 0,

            'ebt' => $ebt,
            'ebt_margin' => $totalRevenue > 0 ? ($ebt / $totalRevenue) * 100 : 0,

            'taxes' => $taxes->values()->toArray(),
            'total_taxes' => $totalTaxes,

            'net_income' => $netIncome,
            'net_profit_margin' => $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0,

            'period_id' => $periodId,
        ];
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
                'balance' => $balance,
                'is_active' => $account->is_active,
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
     * Get comparative income statement
     */
    public function getComparativeIncomeStatement(
        ?int $periodId1 = null,
        ?int $periodId2 = null,
        ?string $branchId = null,
    ): array {
        $is1 = $this->getIncomeStatement($periodId1, $branchId);
        $is2 = $this->getIncomeStatement($periodId2, $branchId);

        return [
            'period1' => $is1,
            'period2' => $is2,
            'variance' => [
                'revenue_change' => $is2['total_revenue'] - $is1['total_revenue'],
                'revenue_change_percent' => $is1['total_revenue'] > 0 
                    ? (($is2['total_revenue'] - $is1['total_revenue']) / $is1['total_revenue']) * 100 
                    : 0,
                'net_income_change' => $is2['net_income'] - $is1['net_income'],
                'net_income_change_percent' => $is1['net_income'] > 0
                    ? (($is2['net_income'] - $is1['net_income']) / $is1['net_income']) * 100
                    : 0,
            ],
        ];
    }

    /**
     * Export income statement
     */
    public function exportIncomeStatement(?int $periodId = null, ?string $branchId = null): array
    {
        $is = $this->getIncomeStatement($periodId, $branchId);

        $data = [
            ['INCOME STATEMENT', '', ''],
            ['', '', ''],
            ['REVENUES', '', number_format($is['total_revenue'], 2)],
        ];

        foreach ($is['revenues'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['', '', ''];
        $data[] = ['COST OF GOODS SOLD', '', number_format($is['total_cogs'], 2)];

        foreach ($is['cogs'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['', '', ''];
        $data[] = ['GROSS PROFIT', '', number_format($is['gross_profit'], 2)];
        $data[] = ['Gross Profit Margin', '', round($is['gross_profit_margin'], 2) . '%'];

        $data[] = ['', '', ''];
        $data[] = ['OPERATING EXPENSES', '', number_format($is['total_opex'], 2)];

        foreach ($is['opex'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['', '', ''];
        $data[] = ['ADMINISTRATIVE EXPENSES', '', number_format($is['total_admin'], 2)];

        foreach ($is['admin'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['', '', ''];
        $data[] = ['EBIT (Operating Income)', '', number_format($is['ebit'], 2)];

        $data[] = ['', '', ''];
        $data[] = ['FINANCE COSTS', '', number_format($is['total_finance'], 2)];

        foreach ($is['finance'] as $account) {
            $data[] = [
                '  ' . $account['account_number'],
                $account['account_name'],
                number_format($account['balance'], 2),
            ];
        }

        $data[] = ['', '', ''];
        $data[] = ['EBT (Earnings Before Tax)', '', number_format($is['ebt'], 2)];

        $data[] = ['', '', ''];
        $data[] = ['TAXES', '', number_format($is['total_taxes'], 2)];

        $data[] = ['', '', ''];
        $data[] = ['NET INCOME', '', number_format($is['net_income'], 2)];
        $data[] = ['Net Profit Margin', '', round($is['net_profit_margin'], 2) . '%'];

        return $data;
    }
}
