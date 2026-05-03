<?php

namespace App\Livewire\BranchDashboard\AuditManagement;

use App\Models\ApprovalAuditRequest;
use App\Services\InventoryApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class InventoryApprovals extends Component
{
    use WithPagination;
    
    public $filterStatus = 'pending'; // pending, approved, rejected
    public $filterAction = ''; // stock_adjustment, create_item, update_item, delete_item
    public $quantity = 15;
    public $search = '';
    
    // Modal state
    public $showDetailsModal = false;
    public $selectedRequestId = null;
    public $rejectionComment = '';
    public $showRejectionForm = false;
    
    public function render()
    {
        $branchId = Auth::guard('web')->user()->branch_id;
        
        $query = ApprovalAuditRequest::where('branch_id', $branchId)
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterAction, fn($q) => $q->where('action', 'like', $this->filterAction . '%'))
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%')
                ->orWhereHas('requester', fn($q) => $q->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')))
            ->orderBy('created_at', 'desc');
        
        $requests = $query->paginate((int) $this->quantity);
        
        return view('livewire.branch-dashboard.audit-management.inventory-approvals', [
            'requests' => $requests,
        ]);
    }
    
    public function openDetailsModal($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->showDetailsModal = true;
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedRequestId = null;
        $this->rejectionComment = '';
        $this->resetValidation();
    }
    
    public function approveRequest($requestId)
    {
        try {
            $auditable = null;
            $branchId = Auth::guard('web')->user()->branch_id;
            
            $request = ApprovalAuditRequest::where('id', $requestId)
                ->where('branch_id', $branchId)
                ->firstOrFail();
            
            $approver = Auth::guard('web')->user();
            list($action, $id) = explode(':', $request->action);

            if (str_contains($request->action, 'stock_adjustment')) {
                InventoryApprovalService::executeStockAdjustment($request, $approver);
                $this->toast()->success('Stock adjustment approved!')->send();
            } elseif (str_contains($request->action, 'create_item')) {
                InventoryApprovalService::executeItemCreation($request, $approver);
                $this->toast()->success('Item creation approved!')->send();
            } elseif (str_contains($request->action, 'update_item')) {
                InventoryApprovalService::executeItemUpdate($request, $approver);
                $this->toast()->success('Item update approved!')->send();
            } elseif (str_contains($request->action, 'delete_item')) {
                InventoryApprovalService::executeItemDeletion($request, $approver);
                $this->toast()->success('Item deletion approved!')->send();
            } elseif (str_contains($request->action, 'create_purchase')) {
                InventoryApprovalService::executePurchaseCreation($request, $approver);
                $this->toast()->success('Purchase creation approved!')->send();
            } elseif (str_contains($request->action, 'delete_purchase')) {
                InventoryApprovalService::executePurchaseDeletion($request, $approver);
                $this->toast()->success('Purchase deletion approved!')->send();
            }
            
            $this->closeDetailsModal();
            $this->resetPage();

            return $auditable;
        } catch (\Exception $e) {
            $this->toast()->error('Failed to approve: ' . $e->getMessage())->send();
        }
    }
    
    public function rejectRequest($requestId)
    {
        $this->validate(['rejectionComment' => 'required|string|min:5|max:500'], [
            'rejectionComment.required' => 'Please provide a reason for rejection.',
            'rejectionComment.min' => 'Reason must be at least 5 characters.',
        ]);
        
        try {
            $branchId = Auth::guard('web')->user()->branch_id;
            
            $request = ApprovalAuditRequest::where('id', $requestId)
                ->where('branch_id', $branchId)
                ->firstOrFail();
            
            $approver = Auth::guard('web')->user();
            
            InventoryApprovalService::rejectStockAdjustment($request, $approver, $this->rejectionComment);
            
            $this->toast()->success('Request rejected!')->send();
            $this->closeDetailsModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->toast()->error('Failed to reject: ' . $e->getMessage())->send();
        }
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedFilterStatus()
    {
        $this->resetPage();
    }
    
    public function updatedFilterAction()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = 'pending';
        $this->filterAction = '';
        $this->resetPage();
    }
}
