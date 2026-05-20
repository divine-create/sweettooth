<?php

namespace App\Livewire\BranchDashboard\SalesDashboard;

use App\Events\ProductionRequest\DispatchAccepted;
use App\Events\ProductionRequest\DispatchRejected;
use App\Models\ProductDispatch;
use App\Models\ProductionRequest;
use Livewire\Component;

class DispatchVerification extends Component
{
    public $requestId;
    public $request;
    public $dispatches = [];
    public $rejectionReason = '';
    public $rejectionNotes = '';
    public $showRejectionForm = false;

    protected $listeners = [
        'refreshDispatches' => '$refresh',
    ];

    public function mount($requestId = null)
    {
        if ($requestId) {
            $this->loadRequest($requestId);
        }
    }

    public function loadRequest($requestId)
    {
        $this->requestId = $requestId;
        $this->request = ProductionRequest::with([
            'productionDepartment',
            'dispatches.product',
            'progressFeedback' => function($q) {
                $q->latest();
            }
        ])->whereHas('productionDepartment', function ($q) {
            $q->where('branch_id', current_branch_id());
        })->find($requestId);

        if ($this->request) {
            $this->dispatches = $this->request->dispatches->where('status', 'pending_verification');
        }
    }

    public function acceptDispatch($dispatchId)
    {
        $dispatch = ProductDispatch::find($dispatchId);

        if (!$dispatch || $dispatch->production_request_id != $this->requestId) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Dispatch not found or unauthorized'
            ]);
            return;
        }

        $dispatch->update(['status' => 'accepted']);
        $this->request->update(['status' => 'accepted']);

        broadcast(new DispatchAccepted($this->request))->toOthers();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dispatch accepted successfully'
        ]);

        $this->loadRequest($this->requestId);
    }

    public function rejectDispatch()
    {
        $this->validate([
            'rejectionReason' => 'required|in:quality,quantity,missing,other',
            'rejectionNotes' => 'required|min:10|max:500',
        ]);

        // Find the first pending dispatch for this request
        $dispatch = collect($this->dispatches)->first();

        if (!$dispatch) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No pending dispatch found'
            ]);
            return;
        }

        $dispatch->update(['status' => 'rejected']);
        $this->request->update(['status' => 'rejected']);

        // Create feedback record (you might need to create a feedback model/table)
        // For now, we'll just update with notes
        $this->request->update([
            'notes' => ($this->request->notes ? $this->request->notes . "\n\n" : '') .
                      "REJECTED: {$this->rejectionReason} - {$this->rejectionNotes}"
        ]);

        broadcast(new DispatchRejected($this->request))->toOthers();

        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Dispatch rejected. Feedback sent to production.'
        ]);

        $this->showRejectionForm = false;
        $this->rejectionReason = '';
        $this->rejectionNotes = '';

        $this->loadRequest($this->requestId);
    }

    public function showRejectionForm()
    {
        $this->showRejectionForm = true;
    }

    public function cancelRejection()
    {
        $this->showRejectionForm = false;
        $this->rejectionReason = '';
        $this->rejectionNotes = '';
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.dispatch-verification');
    }
}