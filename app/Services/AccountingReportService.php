<?php

namespace App\Services;

use App\Helpers\Settings;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;

class AccountingReportService
{
    /**
     * Generate Balance Sheet
     */
    public function generateBalanceSheet(AccountingPeriod $period): array
    {
        return [
            'period' => $period->getDisplayName(),
            'as_of_date' => $period->period_end->toDateString(),
            'assets' => $this->getAssetsSummary($period),
            'liabilities' => $this->getLiabilitiesSummary($period),
            'equity' => $this->getEquitySummary($period),
            'total_assets' => $this->getTotalAssets($period),
            'total_liabilities_equity' => $this->getTotalLiabilitiesAndEquity($period),
        ];
    }

    /**
     * Generate Income Statement
     */
    public function generateIncomeStatement(AccountingPeriod $period): array
    {
        $revenues = $this->getRevenuesSummary($period);
        $cogs = $this->getCogsSummary($period);
        $expenses = $this->getExpensesSummary($period);

        $grossProfit = $revenues['total'] - $cogs['total'];
        $operatingIncome = $grossProfit - $expenses['total'];
        $netIncome = $operatingIncome;

        return [
            'period' => $period->getDisplayName(),
            'revenues' => $revenues,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $expenses,
            'operating_income' => $operatingIncome,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Generate Trial Balance
     */
    public function generateTrialBalance(AccountingPeriod $period): array
    {
        $accounts = GlAccount::where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_number')
            ->get();

        $data = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account, $period);

            if ($balance['debit'] > 0 || $balance['credit'] > 0) {
                $data[] = [
                    'account_number' => $account->account_number,
                    'account_name' => $account->account_name,
                    'debit' => $balance['debit'],
                    'credit' => $balance['credit'],
                ];

                $totalDebits += $balance['debit'];
                $totalCredits += $balance['credit'];
            }
        }

        return [
            'period' => $period->getDisplayName(),
            'accounts' => $data,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
        ];
    }

    /**
     * Generate General Ledger
     */
    public function generateGeneralLedger(GlAccount $account, AccountingPeriod $period): array
    {
        $entries = GlEntry::where('gl_account_id', $account->id)
            ->where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->orderBy('entry_date')
            ->orderBy('created_at')
            ->get();

        $runningBalance = 0;
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            $debit = floatval($entry->debit);
            $credit = floatval($entry->credit);

            // Calculate running balance based on account type
            if (in_array($account->account_type, ['asset', 'cogs', 'expense'])) {
                $runningBalance += $debit - $credit;
            } else {
                $runningBalance += $credit - $debit;
            }

            $ledgerEntries[] = [
                'date' => $entry->entry_date->toDateString(),
                'description' => $entry->description,
                'reference' => $entry->reference_number,
                'debit' => $debit > 0 ? $debit : null,
                'credit' => $credit > 0 ? $credit : null,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'period' => $period->getDisplayName(),
            'entries' => $ledgerEntries,
            'opening_balance' => floatval($account->debit_balance) - floatval($account->credit_balance),
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Generate Cash Flow Statement
     */
    public function generateCashFlowStatement(AccountingPeriod $period): array
    {
        return [
            'period' => $period->getDisplayName(),
            'operating_activities' => $this->getOperatingCashFlow($period),
            'investing_activities' => $this->getInvestingCashFlow($period),
            'financing_activities' => $this->getFinancingCashFlow($period),
            'net_cash_flow' => $this->getNetCashFlow($period),
        ];
    }

    /**
     * Generate Account Reconciliation Report
     */
    public function generateAccountReconciliation(GlAccount $account, AccountingPeriod $period): array
    {
        $entries = GlEntry::where('gl_account_id', $account->id)
            ->where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        $debits = $entries->sum('debit');
        $credits = $entries->sum('credit');
        $balance = $account->getBalance();

        return [
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'opening_balance' => floatval($account->debit_balance) - floatval($account->credit_balance),
            'period_debits' => $debits,
            'period_credits' => $credits,
            'calculated_balance' => $balance,
            'reconciliation_status' => 'pending',
            'entries_count' => $entries->count(),
        ];
    }

    /**
     * Get assets summary
     */
    private function getAssetsSummary(AccountingPeriod $period): array
    {
        $assets = GlAccount::where('account_type', 'asset')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($assets as $asset) {
            $balance = $this->getAccountBalance($asset, $period);
            $amount = $balance['debit'];

            $items[] = [
                'account_number' => $asset->account_number,
                'account_name' => $asset->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get liabilities summary
     */
    private function getLiabilitiesSummary(AccountingPeriod $period): array
    {
        $liabilities = GlAccount::where('account_type', 'liability')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($liabilities as $liability) {
            $balance = $this->getAccountBalance($liability, $period);
            $amount = $balance['credit'];

            $items[] = [
                'account_number' => $liability->account_number,
                'account_name' => $liability->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get equity summary
     */
    private function getEquitySummary(AccountingPeriod $period): array
    {
        $equity = GlAccount::where('account_type', 'equity')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($equity as $account) {
            $balance = $this->getAccountBalance($account, $period);
            $amount = $balance['credit'];

            $items[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get revenues summary
     */
    private function getRevenuesSummary(AccountingPeriod $period): array
    {
        $revenues = GlAccount::where('account_type', 'revenue')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($revenues as $revenue) {
            $balance = $this->getAccountBalance($revenue, $period);
            $amount = $balance['credit'];

            $items[] = [
                'account_number' => $revenue->account_number,
                'account_name' => $revenue->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get COGS summary
     */
    private function getCogsSummary(AccountingPeriod $period): array
    {
        $cogs = GlAccount::where('account_type', 'cost_of_goods_sold')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($cogs as $account) {
            $balance = $this->getAccountBalance($account, $period);
            $amount = $balance['debit'];

            $items[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get expenses summary
     */
    private function getExpensesSummary(AccountingPeriod $period): array
    {
        $expenses = GlAccount::where('account_type', 'expense')
            ->where('is_header', false)
            ->get();

        $items = [];
        $total = 0;

        foreach ($expenses as $expense) {
            $balance = $this->getAccountBalance($expense, $period);
            $amount = $balance['debit'];

            $items[] = [
                'account_number' => $expense->account_number,
                'account_name' => $expense->account_name,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Get account balance
     */
    private function getAccountBalance(GlAccount $account, AccountingPeriod $period): array
    {
        $entries = GlEntry::where('gl_account_id', $account->id)
            ->where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        return [
            'debit' => floatval($entries->sum('debit')),
            'credit' => floatval($entries->sum('credit')),
        ];
    }

    /**
     * Get total assets
     */
    private function getTotalAssets(AccountingPeriod $period): float
    {
        return floatval($this->getAssetsSummary($period)['total']);
    }

    /**
     * Get total liabilities and equity
     */
    private function getTotalLiabilitiesAndEquity(AccountingPeriod $period): float
    {
        $liabilities = floatval($this->getLiabilitiesSummary($period)['total']);
        $equity = floatval($this->getEquitySummary($period)['total']);
        return $liabilities + $equity;
    }

    /**
     * Get operating cash flow
     */
    private function getOperatingCashFlow(AccountingPeriod $period): float
    {
        // Sum of cash inflows from operations minus outflows
        // This would include sales, expenses, etc.
        $cashAccount = GlAccount::where('account_number', '1110')->first();
        if (!$cashAccount) {
            return 0;
        }

        $entries = GlEntry::where('gl_account_id', $cashAccount->id)
            ->where('accounting_period_id', $period->id)
            ->where('entry_type', 'sale')
            ->where('status', 'posted')
            ->get();

        return floatval($entries->sum('debit') - $entries->sum('credit'));
    }

    /**
     * Get investing cash flow
     */
    private function getInvestingCashFlow(AccountingPeriod $period): float
    {
        // Equipment purchases, asset sales, etc.
        return 0;
    }

    /**
     * Get financing cash flow
     */
    private function getFinancingCashFlow(AccountingPeriod $period): float
    {
        // Loans, equity injections, dividends
        return 0;
    }

    /**
     * Get net cash flow
     */
    private function getNetCashFlow(AccountingPeriod $period): float
    {
        return $this->getOperatingCashFlow($period) +
               $this->getInvestingCashFlow($period) +
               $this->getFinancingCashFlow($period);
    }

    /**
     * Format currency value for report display
     */
    public function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    /**
     * Get currency symbol for financial reports
     */
    public function getCurrencySymbol(string $currency = null): string
    {
        $service = new CurrencyFormattingService();
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        return $service->getSymbol($currency);
    }

    /**
     * Get accounting currency from settings
     */
    public function getAccountingCurrency(): string
    {
        return Settings::currencyLocalization('primary_currency', 'NGN');
    }
