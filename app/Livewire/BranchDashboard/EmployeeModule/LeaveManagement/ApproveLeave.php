<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ApproveLeave extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    public ?string $status_filter = '';

    // Modals
    public $showApprovalModal = false;

    public $showRejectionModal = false;

    public $showDetailsModal = false;

    public $selectedLeaveId = null;

    public ?LeaveApplication $selectedLeave = null;

    public $approval_notes = '';

    public $rejection_reason = '';

    // Table headers
    public array $headers = [
        ['index' => 'application_number', 'label' => 'Application #'],
        ['index' => 'employee', 'label' => 'Employee'],
        ['index' => 'leave_type', 'label' => 'Leave Type'],
        ['index' => 'dates', 'label' => 'Leave Dates'],
        ['index' => 'total_days', 'label' => 'Days'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    protected function getModelClass(): string
    {
        return LeaveApplication::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return LeaveApplication::query();
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function getRowsProperty()
    {
        $query = LeaveApplication::with(['employee', 'leaveType', 'approvedBy', 'rejectedBy']);

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('application_number', 'like', '%'.$this->search.'%')
                    ->orWhere('reason', 'like', '%'.$this->search.'%')
                    ->orWhereHas('employee', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('leaveType', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->quantity);
    }

    public function viewDetails($id)
    {
        $this->selectedLeave = LeaveApplication::with(['employee', 'leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])->find($id);

        if (! $this->selectedLeave) {
            $this->toast()->error('Leave application not found.')->send();

            return;
        }

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedLeave = null;
    }

    public function openApprovalModal($id)
    {
        $leave = LeaveApplication::find($id);

        if (! $leave || $leave->status !== 'pending') {
            $this->toast()->error('Leave application not found or not pending.')->send();

            return;
        }

        $this->selectedLeaveId = $id;
        $this->approval_notes = '';
        $this->showApprovalModal = true;
    }

    public function closeApprovalModal()
    {
        $this->showApprovalModal = false;
        $this->selectedLeaveId = null;
        $this->approval_notes = '';
    }

    public function approveLeave()
    {
        $this->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $leave = LeaveApplication::find($this->selectedLeaveId);

            if (! $leave || $leave->status !== 'pending') {
                $this->toast()->error('Leave application not found or not pending.')->send();

                return;
            }

            // $approver = auth()->user();
            $approver = current_actor();
            $leave->approve($approver->id, get_class($approver), $this->approval_notes);

            $this->toast()->success("Leave application {$leave->application_number} approved successfully.")->send();
            $this->closeApprovalModal();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error approving leave: '.$e->getMessage())->send();
        }
    }

    public function openRejectionModal($id)
    {
        $leave = LeaveApplication::find($id);

        if (! $leave || $leave->status !== 'pending') {
            $this->toast()->error('Leave application not found or not pending.')->send();

            return;
        }

        $this->selectedLeaveId = $id;
        $this->rejection_reason = '';
        $this->showRejectionModal = true;
    }

    public function closeRejectionModal()
    {
        $this->showRejectionModal = false;
        $this->selectedLeaveId = null;
        $this->rejection_reason = '';
    }

    public function rejectLeave()
    {
        $this->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $leave = LeaveApplication::find($this->selectedLeaveId);

            if (! $leave || $leave->status !== 'pending') {
                $this->toast()->error('Leave application not found or not pending.')->send();

                return;
            }

            // $rejecter = auth()->user();
            $rejecter = current_actor();

            $leave->reject($rejecter->id, get_class($rejecter), $this->rejection_reason);

            $this->toast()->success("Leave application {$leave->application_number} rejected.")->send();
            $this->closeRejectionModal();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error rejecting leave: '.$e->getMessage())->send();
        }
    }

    public function viewDocument($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path);
        }

        $this->toast()->error('Document not found.')->send();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-module.leave-management.approve-leave', [
            'rows' => $this->rows,
        ]);
    }
}
