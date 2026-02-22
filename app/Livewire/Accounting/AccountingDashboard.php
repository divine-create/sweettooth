<?php

namespace App\Livewire\Accounting;

use App\Helpers\Settings;
use App\Models\AccountingPeriod;
use App\Models\GlEntry;
use App\Services\AccountingReportService;
use App\Services\CurrencyFormattingService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class AccountingDashboard extends Component
{
    public ?AccountingPeriod $selectedPeriod = null;
    public string $reportType = 'balance_sheet'; // balance_sheet, income_statement, trial_balance
    public array $reportData = [];

    protected AccountingReportService $reportService;

    public function mount()
    {
        $this->reportService = app(AccountingReportService::class);

        // Get current period with branch context if needed
        $query = AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now());

        $this->selectedPeriod = $query->first();

        if ($this->selectedPeriod) {
            $this->loadReport();
        }
    }

    public function loadReport()
    {
        if (!$this->selectedPeriod) {
            return;
        }

        $this->reportData = match ($this->reportType) {
            'balance_sheet' => $this->reportService->generateBalanceSheet($this->selectedPeriod),
            'income_statement' => $this->reportService->generateIncomeStatement($this->selectedPeriod),
            'trial_balance' => $this->reportService->generateTrialBalance($this->selectedPeriod),
            'cash_flow' => $this->reportService->generateCashFlowStatement($this->selectedPeriod),
            default => [],
        };
    }

    #[Computed]
    public function periods()
    {
        return AccountingPeriod::orderBy('period_end', 'desc')->limit(24)->get();
    }

    #[Computed]
    public function totalEntries()
    {
        if (!$this->selectedPeriod) {
            return 0;
        }

        return GlEntry::where('accounting_period_id', $this->selectedPeriod->id)
            ->where('status', 'posted')
            ->count();
    }

    #[Computed]
    public function totalDebits()
    {
        if (!$this->selectedPeriod) {
            return 0;
        }

        return GlEntry::where('accounting_period_id', $this->selectedPeriod->id)
            ->where('status', 'posted')
            ->sum('debit');
    }

    #[Computed]
    public function totalCredits()
    {
        if (!$this->selectedPeriod) {
            return 0;
        }

        return GlEntry::where('accounting_period_id', $this->selectedPeriod->id)
            ->where('status', 'posted')
            ->sum('credit');
    }

    /**
     * Format currency value for accounting display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    /**
     * Get currency symbol for reports
     */
    protected function getCurrencySymbol(string $currency = null): string
    {
        $service = new CurrencyFormattingService();
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        return $service->getSymbol($currency);
    }

    /**
     * Format percentage for financial metrics
     */
    protected function formatPercentage(float $value, int $decimals = 2): string
    {
        $service = new CurrencyFormattingService();
        return $service->formatPercentage($value, $decimals);
    }

    public function render()
    {
        return view('livewire.accounting.accounting-dashboard');
    }
}
