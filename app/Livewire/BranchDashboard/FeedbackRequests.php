<?php

namespace App\Livewire\BranchDashboard;

use App\Models\AppraisalFeedback;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class FeedbackRequests extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showCreateModal = false;
    public $showProvideFeedbackModal = false;
    public $editingRequest;

    public $requestData = [
        'reviewee_id' => null,
        'reviewer_ids' => [],
        'reviewer_type' => 'peer', // peer, manager, subordinate
        'due_date' => '',
        'message' => ''
    ];

    public $feedbackData = [
        'rating' => null,
        'strengths' => '',
        'areas_for_improvement' => '',
        'comments' => '',
        'is_anonymous' => false
    ];

    protected $rules = [
        'requestData.reviewee_id' => 'required|exists:users,id',
        'requestData.reviewer_ids' => 'required|array|min:1',
        'requestData.reviewer_ids.*' => 'exists:users,id',
        'requestData.reviewer_type' => 'required|in:peer,manager,subordinate',
        'requestData.due_date' => 'required|date|after:today',
        'requestData.message' => 'nullable|string|max:1000'
    ];

    public function mount()
    {
        $this->requestData['due_date'] = now()->addDays(14)->format('Y-m-d');
    }

    public function createFeedbackRequest()
    {
        $this->validate();

        foreach ($this->requestData['reviewer_ids'] as $reviewerId) {
            AppraisalFeedback::create([
                'appraisal_id' => null, // Can be linked to appraisal later
                'reviewer_id' => $reviewerId,
                'reviewer_type' => $this->requestData['reviewer_type'],
                'feedback_data' => [
                    'reviewee_id' => $this->requestData['reviewee_id'],
                    'due_date' => $this->requestData['due_date'],
                    'message' => $this->requestData['message'],
                    'status' => 'pending'
                ]
            ]);
        }

        $this->reset('requestData', 'showCreateModal');
        session()->flash('message', 'Feedback requests sent successfully.');
    }

    public function provideFeedback(AppraisalFeedback $feedback)
    {
        $this->editingRequest = $feedback;
        $this->feedbackData = [
            'rating' => null,
            'strengths' => '',
            'areas_for_improvement' => '',
            'comments' => '',
            'is_anonymous' => false
        ];
        $this->showProvideFeedbackModal = true;
    }

    public function submitFeedback()
    {
        $this->validate([
            'feedbackData.rating' => 'required|integer|min:1|max:5',
            'feedbackData.strengths' => 'nullable|string|max:500',
            'feedbackData.areas_for_improvement' => 'nullable|string|max:500',
            'feedbackData.comments' => 'nullable|string|max:1000',
            'feedbackData.is_anonymous' => 'boolean'
        ]);

        $feedbackData = $this->editingRequest->feedback_data ?? [];
        $feedbackData['rating'] = $this->feedbackData['rating'];
        $feedbackData['strengths'] = $this->feedbackData['strengths'];
        $feedbackData['areas_for_improvement'] = $this->feedbackData['areas_for_improvement'];
        $feedbackData['comments'] = $this->feedbackData['comments'];
        $feedbackData['is_anonymous'] = $this->feedbackData['is_anonymous'];
        $feedbackData['status'] = 'completed';

        $this->editingRequest->update([
            'feedback_data' => $feedbackData,
            'submitted_at' => now()
        ]);

        $this->reset('feedbackData', 'showProvideFeedbackModal', 'editingRequest');
        session()->flash('message', 'Feedback submitted successfully.');
    }

    public function cancelFeedbackRequest(AppraisalFeedback $feedback)
    {
        $feedbackData = $feedback->feedback_data ?? [];
        $feedbackData['status'] = 'cancelled';

        $feedback->update(['feedback_data' => $feedbackData]);
        session()->flash('message', 'Feedback request cancelled.');
    }

    public function getFeedbackRequestsProperty()
    {
        $query = AppraisalFeedback::with(['reviewer']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('reviewer', function ($reviewerQuery) {
                    $reviewerQuery->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where(function ($q) {
                $status = $this->statusFilter;
                if ($status === 'pending') {
                    $q->whereNull('submitted_at');
                } elseif ($status === 'completed') {
                    $q->whereNotNull('submitted_at');
                }
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getEmployeesProperty()
    {
        $branchId = request()->query('b_id') ?? auth()->user()->branch_id ?? auth()->user()->last_accessed_branch_id;

        return User::where('branch_id', $branchId)->get();
    }

    public function getMyPendingFeedbackProperty()
    {
        return AppraisalFeedback::where('reviewer_id', auth()->id())
            ->whereNull('submitted_at')
            ->where('feedback_data->status', '!=', 'cancelled')
            ->get();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.feedback-requests');
    }
}