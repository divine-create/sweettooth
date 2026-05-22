<?php

namespace App\Livewire\BranchDashboard\ReportingDepartment\ReviewReports;

use App\Models\DepartmentReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Review Reports')]
class Index extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?DepartmentReport $selectedReport = null;

    public $showReviewModal = false;

    public $reviewNotes = '';

    public $newAnnotation = '';

    public $annotations = [];

    public $filterCategory = 'all';

    public $filterDepartment = 'all';

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
    }

    public function openReviewModal($reportId)
    {
        $this->selectedReport = DepartmentReport::with(['department', 'generatedBy'])
            ->findOrFail($reportId);

        if (! $this->selectedReport->canBeReviewed()) {
            $this->toast()->error('This report cannot be reviewed')->send();

            return;
        }

        $this->reviewNotes = $this->selectedReport->review_notes ?? '';
        $this->annotations = $this->selectedReport->annotations()
            ->with('author')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($annotation) {
                return [
                    'id' => $annotation->id,
                    'body' => $annotation->body,
                    'author' => $annotation->author?->name ?? 'Unknown',
                    'created_at' => $annotation->created_at?->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
        $this->newAnnotation = '';
        $this->showReviewModal = true;
    }

    public function closeReviewModal()
    {
        $this->showReviewModal = false;
        $this->selectedReport = null;
        $this->reviewNotes = '';
        $this->newAnnotation = '';
        $this->annotations = [];
    }

    public function addAnnotation()
    {
        if (! $this->selectedReport) {
            $this->toast()->error('No report selected')->send();

            return;
        }

        $this->validate([
            'newAnnotation' => 'required|string|min:2|max:2000',
        ]);

        try {
            $actor = current_actor();

            $annotation = $this->selectedReport->annotations()->create([
                'author_id' => $actor?->getKey(),
                'author_type' => $actor ? get_class($actor) : null,
                'body' => $this->newAnnotation,
            ]);

            $this->annotations[] = [
                'id' => $annotation->id,
                'body' => $annotation->body,
                'author' => $actor?->name ?? 'Unknown',
                'created_at' => $annotation->created_at?->format('Y-m-d H:i'),
            ];

            $this->newAnnotation = '';
            $this->toast()->success('Annotation added.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error adding annotation: '.$e->getMessage())->send();
        }
    }

    public function submitForReview($reportId): void
    {
        try {
            $report = DepartmentReport::where('branch_id', $this->b_id ?? current_branch_id())
                ->findOrFail($reportId);

            if ($report->status !== 'draft') {
                $this->toast()->error('Only draft reports can be submitted for review.')->send();
                return;
            }

            $report->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function approveReport()
    {
        if (! $this->selectedReport) {
            $this->toast()->error('No report selected')->send();

            return;
        }

        try {
            $actor = current_actor();

            $this->selectedReport->markAsReviewed(
                $actor?->getKey(),
                $actor ? get_class($actor) : null,
                $this->reviewNotes
            );

            $this->toast()->success('Report approved successfully!')->send();
            $this->closeReviewModal();

        } catch (\Exception $e) {
            $this->toast()->error('Error approving report: '.$e->getMessage())->send();
        }
    }

    public function rejectReport()
    {
        if (! $this->selectedReport) {
            $this->toast()->error('No report selected')->send();

            return;
        }

        if (empty($this->reviewNotes)) {
            $this->reviewNotes = 'Rejected without notes.';
            $this->toast()->warning('No rejection notes provided; default note applied.')->send();
        }

        try {
            $actor = current_actor();

            $this->selectedReport->update([
                'status' => 'rejected',
                'reviewed_by_id' => $actor?->getKey(),
                'reviewed_by_type' => $actor ? get_class($actor) : null,
                'reviewed_at' => now(),
                'review_notes' => $this->reviewNotes,
            ]);

            $this->toast()->success('Report rejected. Notes saved.')->send();
            $this->closeReviewModal();

        } catch (\Exception $e) {
            $this->toast()->error('Error rejecting report: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        $branchId = $this->b_id ?? current_branch_id();

        $query = DepartmentReport::query()
            ->with(['department', 'generatedBy', 'reviewedBy'])
            ->forBranch($branchId)
            ->whereIn('status', $this->visibleStatuses());

        if ($this->filterCategory !== 'all') {
            $query->byCategory($this->filterCategory);
        }

        if ($this->filterDepartment !== 'all') {
            $query->forDepartment($this->filterDepartment);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(15);

        $departments = \App\Models\Department::where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        $statusCounts = [
            'draft' => DepartmentReport::forBranch($branchId)
                ->where('status', 'draft')
                ->count(),
            'pending_review' => DepartmentReport::forBranch($branchId)
                ->where('status', 'pending_review')
                ->count(),
            'reviewed' => DepartmentReport::forBranch($branchId)
                ->where('status', 'reviewed')
                ->count(),
            'rejected' => DepartmentReport::forBranch($branchId)
                ->where('status', 'rejected')
                ->count(),
        ];

        return view('livewire.branch-dashboard.reporting-department.review-reports.index', [
            'reports' => $reports,
            'departments' => $departments,
            'statusCounts' => $statusCounts,
        ]);
    }

    private function visibleStatuses(): array
    {
        return [
            'draft',
            'pending_review',
            'reviewed',
            'rejected',
            'compiled',
            'sent_to_md',
        ];
    }
}
