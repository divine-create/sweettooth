<?php

namespace App\Services\Reports;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Carbon\Carbon;
use Exception;

class IncomeStatementService
{
    /**
     * Generate Income Statement (P&L)
     * 
     * Format:
     * Revenue
     * - Cost of Goods Sold
     * = Gross Profit
     * - Operating Expenses
     * = Operating Income
     * +/- Other Income/Expenses
     * = Net Income
     */
    public function generate(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): array
    {
        $revenues = $this->getAccountBalances('revenue', $period, $startDate, $endDate, $branchId);
        $cogs = $this->getAccountBalances('cost_of_goods_sold', $period, $startDate, $endDate, $branchId);
        $expenses = $this->getAccountBalances('expense', $period, $startDate, $endDate, $branchId);
        $otherIncome = $this->getAccountBalances('other_income', $period, $startDate, $endDate, $branchId);
        $otherExpenses = $this->getAccountBalances('other_expense', $period, $startDate, $endDate, $branchId);

        // Calculate totals
        $totalRevenue = abs($revenues['balance']);
        $totalCogs = abs($cogs['balance']);
        $grossProfit = $totalRevenue - $totalCogs;
        
        $totalExpenses = abs($expenses['balance']);
        $operatingIncome = $grossProfit - $totalExpenses;
        
        $totalOtherIncome = abs($otherIncome['balance']);
        $totalOtherExpenses = abs($otherExpenses['balance']);
        
        $netIncome = $operatingIncome + $totalOtherIncome - $totalOtherExpenses;

        return [
            'period' => $period ? $period->period_name : 'Custom Range',
            'generated_at' => now(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => [
                'items' => $revenues['accounts'],
                'total' => $totalRevenue,
            ],
            'cost_of_goods_sold' => [
                'items' => $cogs['accounts'],
                'total' => $totalCogs,
            ],
            'gross_profit' => $grossProfit,
            'operating_expenses' => [
                'items' => $expenses['accounts'],
                'total' => $totalExpenses,
            ],
            'operating_income' => $operatingIncome,
            'other_income' => [
                'items' => $otherIncome['accounts'],
                'total' => $totalOtherIncome,
            ],
            'other_expenses' => [
                'items' => $otherExpenses['accounts'],
                'total' => $totalOtherExpenses,
            ],
            'net_income' => $netIncome,
            'summary' => [
                'revenue_percentage' => 100.0,
                'cogs_percentage' => ($totalRevenue > 0) ? ($totalCogs / $totalRevenue) * 100 : 0,
                'gross_margin' => ($totalRevenue > 0) ? ($grossProfit / $totalRevenue) * 100 : 0,
                'operating_margin' => ($totalRevenue > 0) ? ($operatingIncome / $totalRevenue) * 100 : 0,
                'net_margin' => ($totalRevenue > 0) ? ($netIncome / $totalRevenue) * 100 : 0,
            ],
        ];
    }

    /**
     * Get account balances by type
     */
    private function getAccountBalances(string $type, ?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): array
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

            if ($startDate) {
                $entryQuery->whereDate('entry_date', '>=', $startDate);
            }

            if ($endDate) {
                $entryQuery->whereDate('entry_date', '<=', $endDate);
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
     * Compare P&L with previous period
     */
    public function compareWithPrevious(AccountingPeriod $period): array
    {
        $current = $this->generate($period);
        $previous = $this->generate($period->previousPeriod);

        return [
            'current_period' => $current,
            'previous_period' => $previous,
            'variance' => [
                'revenue' => $current['revenue']['total'] - $previous['revenue']['total'],
                'cogs' => $current['cost_of_goods_sold']['total'] - $previous['cost_of_goods_sold']['total'],
                'gross_profit' => $current['gross_profit'] - $previous['gross_profit'],
                'expenses' => $current['operating_expenses']['total'] - $previous['operating_expenses']['total'],
                'net_income' => $current['net_income'] - $previous['net_income'],
            ],
        ];
    }

    /**
     * Export P&L to array
     */
    public function export(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return $this->generate($period, $startDate, $endDate);
    }
}
