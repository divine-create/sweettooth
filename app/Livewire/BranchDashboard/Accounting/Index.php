<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\AccountingPeriod;
use App\Models\GlEntry;
use App\Services\BalanceSheetService;
use App\Services\GeneralLedgerService;
use App\Services\IncomeStatementService;
use App\Services\TrialBalanceService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    protected GeneralLedgerService $glService;

    protected TrialBalanceService $tbService;

    protected IncomeStatementService $isService;

    protected BalanceSheetService $bsService;

    public ?int $currentPeriodId = null;

    public string $activeTab = 'overview';

    public function mount()
    {
        $this->glService = app(GeneralLedgerService::class);
        $this->tbService = app(TrialBalanceService::class);
        $this->isService = app(IncomeStatementService::class);
        $this->bsService = app(BalanceSheetService::class);

        // Set current period
        $currentPeriod = AccountingPeriod::current()->first();
        if ($currentPeriod) {
            $this->currentPeriodId = $currentPeriod->id;
        }
    }

    public function render()
    {
        // Get current period
        $currentPeriod = AccountingPeriod::find($this->currentPeriodId);

        // GL Summary
        $totalEntries = GlEntry::where('status', 'posted')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->count();

        $totalDebits = GlEntry::where('status', 'posted')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->sum('debit');

        $totalCredits = GlEntry::where('status', 'posted')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->sum('credit');

        // Trial Balance
        $trialBalance = $this->tbService->getTrialBalance($this->currentPeriodId);

        // Income Statement
        $incomeStatement = $this->isService->getIncomeStatement($this->currentPeriodId);

        // Balance Sheet
        $balanceSheet = $this->bsService->getBalanceSheet($this->currentPeriodId);

        // Recent GL Entries
        $recentEntries = GlEntry::where('status', 'posted')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->with(['glAccount', 'period'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.branch-dashboard.accounting.index', [
            'currentPeriod' => $currentPeriod,
            'periods' => AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'totalEntries' => $totalEntries,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'trialBalance' => $trialBalance,
            'incomeStatement' => $incomeStatement,
            'balanceSheet' => $balanceSheet,
            'recentEntries' => $recentEntries,
            'activeTab' => $this->activeTab,
        ]);
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function changePeriod(int $periodId)
    {
        $this->currentPeriodId = $periodId;
    }
}
