<?php

namespace App\Livewire\BranchDashboard\MDReports\Dashboard;

use App\Models\CompiledReport;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class Index extends Component
{
    use Interactions, WithPagination;

    public $filterStatus = 'all';
    public $filterBranch = 'all';
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can access MD Reports');
        }

        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
    }

    public function markAsReviewed($reportId)
    {
        try {
            $report = CompiledReport::findOrFail($reportId);
            $report->markAsReviewedByMD();

            $this->toast()->success('Report marked as reviewed')->send();

        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        $query = CompiledReport::query()
            ->with(['branch', 'compiledBy', 'mdUser'])
            ->whereIn('status', ['sent_to_md', 'reviewed_by_md'])
            ->whereBetween('compilation_date', [$this->dateFrom, $this->dateTo]);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterBranch !== 'all') {
            $query->where('branch_id', $this->filterBranch);
        }

        $compiledReports = $query->orderBy('sent_to_md_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total_reports' => CompiledReport::whereIn('status', ['sent_to_md', 'reviewed_by_md'])->count(),
            'pending_review' => CompiledReport::where('status', 'sent_to_md')->count(),
            'reviewed' => CompiledReport::where('status', 'reviewed_by_md')->count(),
            'this_month' => CompiledReport::whereIn('status', ['sent_to_md', 'reviewed_by_md'])
                ->whereBetween('sent_to_md_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count(),
        ];

        return view('livewire.branch-dashboard.md-reports.dashboard.index', [
            'compiledReports' => $compiledReports,
            'stats' => $stats,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
