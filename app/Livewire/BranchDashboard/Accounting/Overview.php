<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\AccountingPeriod;
use App\Models\GlEntry;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Overview extends Component
{
    public ?int $periodId = null;

    public function render()
    {
        // Get current period if not set
        if (! $this->periodId) {
            $period = AccountingPeriod::current()->first();
            $this->periodId = $period?->id;
        }

        // GL Status
        $postedEntries = GlEntry::where('status', 'posted')
            ->when($this->periodId, fn ($q) => $q->where('accounting_period_id', $this->periodId))
            ->count();

        $draftEntries = GlEntry::where('status', 'draft')
            ->when($this->periodId, fn ($q) => $q->where('accounting_period_id', $this->periodId))
            ->count();

        $pendingPostings = Sale::where('gl_posting_status', 'pending')->count()
                          + Purchase::where('gl_posting_status', 'pending')->count()
                          + Payment::where('gl_posting_status', 'pending')->count()
                          + StockMovement::where('gl_posting_status', 'pending')->count();

        $failedPostings = Sale::where('gl_posting_status', 'failed')->count()
                        + Purchase::where('gl_posting_status', 'failed')->count()
                        + Payment::where('gl_posting_status', 'failed')->count()
                        + StockMovement::where('gl_posting_status', 'failed')->count();

        // Transaction Stats
        $totalSales = Sale::count();
        $completedSales = Sale::where('status', 'completed')->count();

        $totalPurchases = Purchase::count();
        $approvedPurchases = Purchase::where('status', 'approved')->count();

        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();

        // GL Balancing
        $glBalance = GlEntry::where('status', 'posted')
            ->when($this->periodId, fn ($q) => $q->where('accounting_period_id', $this->periodId));

        $totalDebits = $glBalance->sum('debit');
        $totalCredits = $glBalance->sum('credit');
        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        // Recently Posted
        $recentPostedEntries = GlEntry::where('status', 'posted')
            ->when($this->periodId, fn ($q) => $q->where('accounting_period_id', $this->periodId))
            ->with(['glAccount', 'period'])
            ->orderBy('entry_date', 'desc')
            ->limit(5)
            ->get();

        // Failed Postings
        $failedSales = Sale::where('gl_posting_status', 'failed')->limit(5)->get();
        $failedPurchases = Purchase::where('gl_posting_status', 'failed')->limit(5)->get();
        $failedPayments = Payment::where('gl_posting_status', 'failed')->limit(5)->get();

        return view('livewire.branch-dashboard.accounting.overview', [
            'periods' => AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'currentPeriod' => AccountingPeriod::find($this->periodId),
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
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'isBalanced' => $isBalanced,
            'recentPostedEntries' => $recentPostedEntries,
            'failedSales' => $failedSales,
            'failedPurchases' => $failedPurchases,
            'failedPayments' => $failedPayments,
        ]);
    }

    public function changePeriod(int $periodId)
    {
        $this->periodId = $periodId;
    }
}
