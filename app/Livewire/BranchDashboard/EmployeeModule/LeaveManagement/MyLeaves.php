<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\LeaveApplication;
use App\Services\AuditService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class MyLeaves extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    public ?string $status_filter = null;

    // Modal for cancellation
    public $showCancelModal = false;

    public $selectedLeaveId = null;

    public $cancellation_reason = '';

    // Table headers
    public array $headers = [
        ['index' => 'application_number', 'label' => 'Application #'],
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
        $employee = auth()->user();

        return LeaveApplication::where('employee_id', $employee->id);
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function getRowsProperty()
    {
        $employee = auth()->user();
        $query = LeaveApplication::where('employee_id', $employee->id)
            ->with(['leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy']);

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('application_number', 'like', '%'.$this->search.'%')
                    ->orWhere('reason', 'like', '%'.$this->search.'%')
                    ->orWhereHas('leaveType', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->quantity);
    }

    public function viewDocument($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path);
        }

        $this->toast()->error('Document not found.')->send();
    }

    public function openCancelModal($id)
    {
        $leave = LeaveApplication::find($id);

        if (! $leave || $leave->employee_id !== auth()->id()) {
            $this->toast()->error('Leave application not found.')->send();

            return;
        }

        if (! in_array($leave->status, ['pending', 'approved'])) {
            $this->toast()->error('Only pending or approved leaves can be cancelled.')->send();

            return;
        }

        $this->selectedLeaveId = $id;
        $this->cancellation_reason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->selectedLeaveId = null;
        $this->cancellation_reason = '';
    }

    public function cancelLeave()
    {
        $this->validate([
            'cancellation_reason' => 'required|string|min:10',
        ]);

        try {
            $leave = LeaveApplication::find($this->selectedLeaveId);

            if (! $leave || $leave->employee_id !== auth()->id()) {
                $this->toast()->error('Leave application not found.')->send();

                return;
            }

            $employee = auth()->user();
            $leave->cancel($employee->id, $this->cancellation_reason);

            // Log the cancellation
            AuditService::log(
                $employee,
                'cancel',
                $leave,
                "Cancelled leave application {$leave->application_number}. Reason: {$this->cancellation_reason}",
                'completed'
            );

            $this->toast()->success('Leave application cancelled successfully.')->send();
            $this->closeCancelModal();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error cancelling leave: '.$e->getMessage())->send();
        }
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
        return view('livewire.branch-dashboard.employee-module.leave-management.my-leaves', [
            'rows' => $this->rows,
        ]);
    }
}
