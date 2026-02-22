<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\DailyBankPosition;
use App\Models\CashPosition;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CashFlowStatementService
{
    /**
     * Get cash flow statement
     */
    public function getCashFlowStatement(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        $branchId = $branchId ?? current_branch_id();
        // Operating Activities
        $operatingCash = $this->getOperatingActivities($startDate, $endDate, $branchId);

        // Investing Activities
        $investingCash = $this->getInvestingActivities($startDate, $endDate, $branchId);

        // Financing Activities
        $financingCash = $this->getFinancingActivities($startDate, $endDate, $branchId);

        // Net Change in Cash
        $netChange = $operatingCash['total'] + $investingCash['total'] + $financingCash['total'];

        // Opening and Closing Cash
        $openingCash = $this->getOpeningCash($startDate, $branchId);
        $closingCash = $openingCash + $netChange;

        return [
            'operating' => $operatingCash,
            'investing' => $investingCash,
            'financing' => $financingCash,
            'operating_activities' => $operatingCash['activities'],
            'investing_activities' => $investingCash['activities'],
            'financing_activities' => $financingCash['activities'],
            'net_change' => $netChange,
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'branch_id' => $branchId,
        ];
    }

    /**
     * Get operating activities cash flow
     */
    protected function getOperatingActivities(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        // Net Income (within the selected period)
        $netIncome = $this->getNetIncomeForPeriod($startDate, $endDate, $branchId);

        // Changes in Working Capital
        // Accounts Receivable (1100)
        $arChange = $this->getAccountChangeInPeriod(1100, $startDate, $endDate, $branchId);

        // Inventory (1200-1220)
        $invChange = $this->getAccountChangeInPeriod(1200, $startDate, $endDate, $branchId)
                  + $this->getAccountChangeInPeriod(1210, $startDate, $endDate, $branchId)
                  + $this->getAccountChangeInPeriod(1220, $startDate, $endDate, $branchId);

        // Accounts Payable (2010)
        $apChange = $this->getAccountChangeInPeriod(2010, $startDate, $endDate, $branchId);

        // Operating Cash Flow
        $operatingCash = $netIncome - $arChange - $invChange + $apChange;

        return [
            'net_income' => $netIncome,
            'ar_change' => -$arChange,
            'inventory_change' => -$invChange,
            'ap_change' => $apChange,
            'other_adjustments' => 0,
            'total' => $operatingCash,
            'activities' => [
                'Net Income' => $netIncome,
                'Accounts Receivable Change' => -$arChange,
                'Inventory Change' => -$invChange,
                'Accounts Payable Change' => $apChange,
                'Other Adjustments' => 0,
            ],
        ];
    }

    /**
     * Get investing activities cash flow
     */
    protected function getInvestingActivities(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        // Fixed Assets changes (1300-1399)
        $fixedAssetChange = 0;
        for ($i = 1300; $i <= 1399; $i++) {
            $fixedAssetChange += $this->getAccountChangeInPeriod($i, $startDate, $endDate, $branchId);
        }

        // Typically negative (cash outflow for purchases)
        $investingCash = -abs($fixedAssetChange);

        return [
            'fixed_assets_purchases' => $investingCash,
            'asset_sales' => 0,
            'other_investing' => 0,
            'total' => $investingCash,
            'activities' => [
                'Fixed Assets Purchases' => $investingCash,
                'Asset Sales' => 0,
                'Other Investing' => 0,
            ],
        ];
    }

    /**
     * Get financing activities cash flow
     */
    protected function getFinancingActivities(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        // Debt changes (2100-2200)
        $debtChange = 0;
        for ($i = 2100; $i <= 2200; $i++) {
            $debtChange += $this->getAccountChangeInPeriod($i, $startDate, $endDate, $branchId);
        }

        // Equity changes (3000-3030)
        $equityChange = 0;
        for ($i = 3000; $i <= 3030; $i++) {
            $equityChange += $this->getAccountChangeInPeriod($i, $startDate, $endDate, $branchId);
        }

        $financingCash = $debtChange + $equityChange;

        return [
            'debt_issuance' => $debtChange > 0 ? $debtChange : 0,
            'debt_repayment' => $debtChange < 0 ? abs($debtChange) : 0,
            'equity_issuance' => $equityChange > 0 ? $equityChange : 0,
            'dividends' => $equityChange < 0 ? abs($equityChange) : 0,
            'total' => $financingCash,
            'activities' => [
                'Debt Issuance' => $debtChange > 0 ? $debtChange : 0,
                'Debt Repayment' => $debtChange < 0 ? abs($debtChange) : 0,
                'Equity Issuance' => $equityChange > 0 ? $equityChange : 0,
                'Dividends' => $equityChange < 0 ? abs($equityChange) : 0,
            ],
        ];
    }

    /**
     * Get account change in period
     */
    protected function getAccountChangeInPeriod(
        int $accountNumber,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): float {
        $account = GlAccount::where('account_number', (string)$accountNumber)->first();
        if (!$account) {
            return 0;
        }

        $query = GlEntry::where('gl_account_id', $account->id)
            ->where('status', 'posted');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        $debits = (float) $query->sum('debit');
        $credits = (float) $query->sum('credit');

        return $account->normal_balance === 'debit' ? $debits - $credits : $credits - $debits;
    }

    /**
     * Get opening cash balance
     */
    protected function getOpeningCash(?Carbon $date = null, ?string $branchId = null): float
    {
        if (!$date) {
            $date = now()->startOfMonth();
        }

        // Sum of cash accounts (1010, 1020, 1030, 1040, 1050-1070)
        $cashAccounts = [1010, 1020, 1030, 1040, 1050, 1060, 1070];
        $totalCash = 0;

        foreach ($cashAccounts as $accountNum) {
            $account = GlAccount::where('account_number', (string)$accountNum)->first();
            if ($account) {
                $balanceQuery = GlEntry::where('gl_account_id', $account->id)
                    ->where('status', 'posted')
                    ->where('entry_date', '<', $date);

                if ($branchId) {
                    $balanceQuery->where('branch_id', $branchId);
                }

                $balance = (float) $balanceQuery->sum('debit') - (float) (clone $balanceQuery)->sum('credit');
                $totalCash += $balance;
            }
        }

        return $totalCash;
    }

    /**
     * Get daily bank positions summary
     */
    public function getBankPositionsSummary(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        $query = DailyBankPosition::query()->with('bankAccount');

        if ($branchId) {
            $query->whereHas('bankAccount', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        if ($startDate) {
            $query->where('position_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('position_date', '<=', $endDate);
        }

        $positions = $query->get();

        return $positions
            ->groupBy('bank_account_id')
            ->map(function ($group) {
                $sorted = $group->sortBy('position_date');
                $first = $sorted->first();
                $last = $sorted->last();
                $bankAccount = $first?->bankAccount;

                return [
                    'bank_account' => $bankAccount ? ($bankAccount->bank_name.' • '.$bankAccount->account_number) : 'N/A',
                    'opening_balance' => $first?->opening_balance ?? 0,
                    'total_deposits' => $group->sum('inflows_total'),
                    'total_withdrawals' => $group->sum('outflows_total'),
                    'closing_balance' => $last?->available_balance ?? 0,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get daily cash positions summary
     */
    public function getCashPositionsSummary(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        $query = CashPosition::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('position_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('position_date', '<=', $endDate);
        }

        $positions = $query->get();

        return $positions
            ->groupBy('cash_type')
            ->map(function ($group, $type) {
                $sorted = $group->sortBy('position_date');
                $first = $sorted->first();
                $last = $sorted->last();

                $label = match ($type) {
                    CashPosition::SALES_CASH => 'Sales Cash',
                    CashPosition::PETTY_CASH => 'Petty Cash',
                    CashPosition::DRAWER_CASH => 'Drawer Cash',
                    default => Str::headline((string) $type),
                };

                return [
                    'location' => $label,
                    'opening_balance' => $first?->opening_balance ?? 0,
                    'total_inflows' => $group->sum('sales_receipts'),
                    'total_outflows' => $group->sum('withdrawals'),
                    'closing_balance' => $last?->closing_balance ?? 0,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Export cash flow statement
     */
    public function exportCashFlowStatement(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): array {
        $cfs = $this->getCashFlowStatement($startDate, $endDate, $branchId);

        return [
            ['CASH FLOW STATEMENT', '', ''],
            ['', '', ''],
            ['OPERATING ACTIVITIES', '', ''],
            ['  Net Income', '', number_format($cfs['operating']['net_income'], 2)],
            ['  Accounts Receivable Change', '', number_format($cfs['operating']['ar_change'], 2)],
            ['  Inventory Change', '', number_format($cfs['operating']['inventory_change'], 2)],
            ['  Accounts Payable Change', '', number_format($cfs['operating']['ap_change'], 2)],
            ['Net Cash from Operating Activities', '', number_format($cfs['operating']['total'], 2)],
            ['', '', ''],
            ['INVESTING ACTIVITIES', '', ''],
            ['  Fixed Assets Purchases', '', number_format($cfs['investing']['fixed_assets_purchases'], 2)],
            ['Net Cash from Investing Activities', '', number_format($cfs['investing']['total'], 2)],
            ['', '', ''],
            ['FINANCING ACTIVITIES', '', ''],
            ['  Debt Issuance', '', number_format($cfs['financing']['debt_issuance'], 2)],
            ['  Debt Repayment', '', number_format($cfs['financing']['debt_repayment'], 2)],
            ['  Equity Issuance', '', number_format($cfs['financing']['equity_issuance'], 2)],
            ['  Dividends', '', number_format($cfs['financing']['dividends'], 2)],
            ['Net Cash from Financing Activities', '', number_format($cfs['financing']['total'], 2)],
            ['', '', ''],
            ['Net Change in Cash', '', number_format($cfs['net_change'], 2)],
            ['Cash at Beginning of Period', '', number_format($cfs['opening_cash'], 2)],
            ['Cash at End of Period', '', number_format($cfs['closing_cash'], 2)],
        ];
    }

    protected function getNetIncomeForPeriod(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): float {
        $revenues = $this->getAccountBalancesForRange(4000, 4099, $startDate, $endDate, $branchId);
        $cogs = $this->getAccountBalancesForRange(5000, 5099, $startDate, $endDate, $branchId);
        $opex = $this->getAccountBalancesForRange(6000, 6999, $startDate, $endDate, $branchId);
        $admin = $this->getAccountBalancesForRange(7000, 7999, $startDate, $endDate, $branchId);
        $finance = $this->getAccountBalancesForRange(8000, 8999, $startDate, $endDate, $branchId);
        $taxes = $this->getAccountBalancesForRange(9000, 9999, $startDate, $endDate, $branchId);

        $totalRevenue = $revenues->sum();
        $totalCogs = $cogs->sum();
        $totalOpex = $opex->sum();
        $totalAdmin = $admin->sum();
        $totalFinance = $finance->sum();
        $totalTaxes = $taxes->sum();

        $grossProfit = $totalRevenue - $totalCogs;
        $ebit = $grossProfit - $totalOpex - $totalAdmin;
        $ebt = $ebit - $totalFinance;

        return $ebt - $totalTaxes;
    }

    protected function getAccountBalancesForRange(
        int $startNumber,
        int $endNumber,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ) {
        $accounts = GlAccount::where('is_active', true)
            ->whereBetween('account_number', [(string)$startNumber, (string)$endNumber])
            ->where('account_type', '!=', 'header')
            ->get();

        return $accounts->map(function ($account) use ($startDate, $endDate, $branchId) {
            return $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate, $branchId);
        })->filter(fn($balance) => $balance != 0);
    }

    protected function getAccountBalanceForPeriod(
        int $accountId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $branchId = null,
    ): float {
        $query = GlEntry::where('gl_account_id', $accountId)
            ->where('status', 'posted');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        $debits = (float) $query->sum('debit');
        $credits = (float) $query->sum('credit');

        $account = GlAccount::find($accountId);
        if ($account && $account->normal_balance === 'credit') {
            return $credits - $debits;
        }

        return $debits - $credits;
    }
}
