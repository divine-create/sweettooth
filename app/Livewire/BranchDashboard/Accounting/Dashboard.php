<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\AccountingPeriod;
use App\Models\GlEntry;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\BalanceSheetService;
use App\Services\GeneralLedgerService;
use App\Services\IncomeStatementService;
use App\Services\TrialBalanceService;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Dashboard extends Component
{
    protected GeneralLedgerService $glService;

    protected TrialBalanceService $tbService;

    protected IncomeStatementService $isService;

    protected BalanceSheetService $bsService;

    public ?int $currentPeriodId = null;

    public string $activeTab = 'financial'; // financial, status, recent

    public ?string $branchId = null;

    public function mount()
    {
        $this->glService = app(GeneralLedgerService::class);
        $this->tbService = app(TrialBalanceService::class);
        $this->isService = app(IncomeStatementService::class);
        $this->bsService = app(BalanceSheetService::class);

        // Get branch from request
        $this->branchId = Request::query('b_id');

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

        // ====== FINANCIAL SUMMARY (from Index) ======
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

        // ====== ACCOUNTING STATUS SUMMARY (from Overview) ======
        $postedEntries = GlEntry::where('status', 'posted')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->count();

        $draftEntries = GlEntry::where('status', 'draft')
            ->when($this->currentPeriodId, fn ($q) => $q->where('accounting_period_id', $this->currentPeriodId))
            ->count();

        $pendingPostings = Sale::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'pending')->count()
                           + Purchase::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'pending')->count()
                           + Payment::where('gl_posting_status', 'pending')->count()
                           + StockMovement::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'pending')->count();

        $failedPostings = Sale::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'failed')->count()
                        + Purchase::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'failed')->count()
                        + Payment::where('gl_posting_status', 'failed')->count()
                        + StockMovement::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'failed')->count();

        // Transaction Stats
        $totalSales = Sale::count();
        $completedSales = Sale::where('status', 'completed')->count();

        $totalPurchases = Purchase::count();
        $approvedPurchases = Purchase::where('status', 'approved')->count();

        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();

        // GL Balancing
        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        // Failed Postings
        $failedSales = Sale::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'failed')->limit(5)->get();
        $failedPurchases = Purchase::when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('gl_posting_status', 'failed')->limit(5)->get();
        $failedPayments = Payment::where('gl_posting_status', 'failed')->limit(5)->get();

        return view('livewire.branch-dashboard.accounting.dashboard', [
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
            'postedEntries' => $postedEntries,
            'draftEntries' => $draftEntries,
            'pendingPostings' => $pendingPostings,
            'failedPostings' => $failedPostings,
            'totalSales' => $totalSales,
            'completedSales' => $completedSales,
            'totalPurchases' => $totalPurchases,
            'approvedPurchases' => $approvedPurchases,
            'totalPayments' => $totalPayments,
            'completedPayments' => $completedPayments,
            'isBalanced' => $isBalanced,
            'failedSales' => $failedSales,
            'failedPurchases' => $failedPurchases,
            'failedPayments' => $failedPayments,
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
