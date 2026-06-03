<?php

namespace App\Livewire\BranchDashboard\AuditManagement;

use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Department;
use Livewire\WithPagination;
use App\Services\AuditService;
use App\Models\ApprovalAuditRequest;
use Livewire\Attributes\{Layout, On, Title, Url};
use App\Services\ApprovalExecutionService;
use App\Services\PurchaseAuditApprovalService;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title("Audit Mnagement")]
class Index extends Component
{
    use WithPagination;

    public $branchId;
    public $tab = 'logs';
    public $search = '';
    public $filterDepartment = '';
    public $filterAction = '';
    public $filterStatus = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $sortBy = 'logged_at';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        if (is_super_admin()) {
            $this->branchId = request()->query('b_id') ?? current_branch_id();
        } else {
            $this->branchId = request()->query('b_id');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function approveRequest($requestId)
    {
        try {
            \Log::info('🔵 [AUDIT APPROVAL] Starting approval process', [
                'request_id' => $requestId,
                'approver_id' => auth()->user()?->id ?? auth()->user()?->id,
            ]);

            $request = ApprovalAuditRequest::find($requestId);

            if (!$request) {
                throw new \Exception("Approval request #{$requestId} not found");
            }

            if ($request->status !== 'pending') {
                throw new \Exception("Request is no longer pending (current status: {$request->status})");
            }

            \Log::info('✅ [AUDIT APPROVAL] Request found and valid', [
                'action' => $request->action,
                'status' => $request->status,
            ]);

            // Execute the approved action - delegates to the shared execution service
            \Log::info('🔵 [AUDIT APPROVAL] Executing approved action', ['action' => $request->action]);
            $auditable = ApprovalExecutionService::execute($request, $this->getApprover());

            if (!$auditable) {
                throw new \Exception('Failed to execute approval action - auditable returned null');
            }

            \Log::info('✅ [AUDIT APPROVAL] Action executed successfully', [
                'auditable_type' => is_object($auditable) ? get_class($auditable) : gettype($auditable),
                'auditable_id' => $auditable->id ?? 'N/A',
            ]);

            $approver = auth()->user() ?? auth()->user();
            if (!$approver) {
                throw new \Exception('Could not determine current approver');
            }

            \Log::info('🔵 [AUDIT APPROVAL] Updating request status to approved', [
                'approver_id' => $approver->id,
                'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
            ]);

            $request->update([
                'status' => 'approved',
                'approver_id' => $approver->id,
                'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
                'approved_at' => now(),
            ]);

            \Log::info('✅ [AUDIT APPROVAL] Request status updated');

            // Log the approval action
            // Extract just the action type (e.g., "update:App\Models\Employee" -> "update")
            $baseAction = explode(':', $request->action)[0];

            \Log::info('🔵 [AUDIT APPROVAL] Logging approval action', ['base_action' => $baseAction]);
            AuditService::log(
                $approver,
                "approve_{$baseAction}",
                $auditable,
                'Approved by ' . $approver->name,
                'completed'
            );

            \Log::info('✅ [AUDIT APPROVAL] Approval completed successfully');
            $this->dispatch('toast', message: 'Request approved successfully', type: 'success');
            $this->resetPage();
        } catch (\Exception $e) {
            \Log::error('❌ [AUDIT APPROVAL] Approval failed', [
                'request_id' => $requestId ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log the error
            $approver = auth()->user() ?? auth()->user();
            if ($approver) {
                try {
                    AuditService::log(
                        $approver,
                        'approve_failed',
                        null,
                        'Approval failed: ' . $e->getMessage(),
                        'failed'
                    );
                } catch (\Exception $auditError) {
                    \Log::error('❌ [AUDIT APPROVAL] Failed to log approval error', [
                        'audit_error' => $auditError->getMessage(),
                    ]);
                }
            }

            $this->dispatch('toast', message: 'Error approving request: ' . $e->getMessage(), type: 'error');
            throw $e;
        }
    }

    /**
     * Get the current approver (user or employee)
     */
    private function getApprover()
    {
        return auth()->user() ?? auth()->user();
    }

    public function rejectRequest($requestId)
    {
        $request = ApprovalAuditRequest::find($requestId);

        if (!$request || $request->status !== 'pending') {
            $this->dispatch('toast', message: 'Request not found or already processed', type: 'error');
            return;
        }

        try {
            $approver = auth()->user() ?? auth()->user();

            // Handle purchase rejection - reset status back to draft
            $baseAction = explode(':', $request->action)[0];
            if ($baseAction === 'approve_purchase') {
                PurchaseAuditApprovalService::rejectPurchase($request, $approver, request('rejection_comment', ''));
            } else {
                $request->update([
                    'status' => 'rejected',
                    'approver_id' => $approver->id,
                    'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
                    'denied_at' => now(),
                ]);
            }

            // Log the rejection action
            AuditService::log(
                $approver,
                "reject_{$baseAction}",
                null,
                'Rejected by ' . $approver->name,
                'completed'
            );

            $this->dispatch('toast', message: 'Request rejected successfully', type: 'info');
            $this->resetPage();
        } catch (\Exception $e) {
            // Log the error
            $approver = auth()->user() ?? auth()->user();
            AuditService::log(
                $approver,
                'reject_failed',
                null,
                'Rejection failed: ' . $e->getMessage(),
                'failed'
            );

            $this->dispatch('toast', message: 'Error rejecting request: ' . $e->getMessage(), type: 'error');
        }
    }

    public function updatingTab()
    {
        $this->clearFilters();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterDepartment = '';
        $this->filterAction = '';
        $this->filterStatus = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $logsQuery = AuditLog::query();
        $approvalsQuery = ApprovalAuditRequest::query();
        $isSuperAdmin = is_super_admin();

        // Apply search filter
        if ($this->search) {
            $logsQuery->where('description', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%");
            $approvalsQuery->where('description', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%");
        }

        // Apply branch filter (from branchId) - show all if no branch specified for super admin
        if ($this->branchId) {
            $logsQuery->where('branch_id', $this->branchId);
            $approvalsQuery->where(function ($q) {
                $q->where('branch_id', $this->branchId)
                    ->orWhereNull('branch_id'); // Show records with null branch_id too
            });
        } else if (!$isSuperAdmin && current_branch_id()) {
            // Non-super admin: filter to their branch
            $logsQuery->where('branch_id', current_branch_id());
            $approvalsQuery->where(function ($q) {
                $q->where('branch_id', current_branch_id())
                    ->orWhereNull('branch_id');
            });
        }

        // Department filter removed (audit_logs table doesn't have department_id in prod)

        // Apply action filter (match base action or exact match)
        if ($this->filterAction) {
            $logsQuery->where(function ($q) {
                $q->where('action', $this->filterAction)
                    ->orWhere('action', 'like', $this->filterAction . ':%');
            });
            $approvalsQuery->where(function ($q) {
                $q->where('action', $this->filterAction)
                    ->orWhere('action', 'like', $this->filterAction . ':%');
            });
        }

        // Apply status filter
        if (!empty($this->filterStatus)) {
            $logsQuery->where('status', $this->filterStatus);
            $approvalsQuery->where('status', $this->filterStatus);
        }

        // Apply date filter
        if (!empty($this->filterDateFrom)) {
            $fromDate = \Carbon\Carbon::parse($this->filterDateFrom)->startOfDay();
            $logsQuery->where('logged_at', '>=', $fromDate);
            $approvalsQuery->where('created_at', '>=', $fromDate);
        }

        if (!empty($this->filterDateTo)) {
            $toDate = \Carbon\Carbon::parse($this->filterDateTo)->endOfDay();
            $logsQuery->where('logged_at', '<=', $toDate);
            $approvalsQuery->where('created_at', '<=', $toDate);
        }

        // Get departments for filter
        $departments = Department::orderBy('name')->get();

        // Get data based on tab
        if ($this->tab === 'logs') {
            $logs = $logsQuery
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(15);

            // Get unique base actions (without IDs) for the filter dropdown
            $rawActions = AuditLog::distinct('action')->pluck('action');
            $actions = $rawActions->map(function ($action) {
                // Extract base action: "delete_item:6" -> "delete_item", "update_item:123" -> "update_item"
                $parts = explode(':', $action);
                // If second part is numeric, it's an ID - return just the first part
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    return $parts[0];
                }
                return $action;
            })->unique()->sort()->values();

            $statuses = AuditLog::distinct('status')->pluck('status')->sort();

            return view('livewire.branch-dashboard.audit-management.index', [
                'logs' => $logs,
                'approvals' => null,
                'actions' => $actions,
                'statuses' => $statuses,
                'departments' => $departments,
                'isSuperAdmin' => $isSuperAdmin,
            ]);
        } else {
            $approvals = $approvalsQuery
                ->with('requester', 'approver')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Get unique base actions (without IDs) for the filter dropdown
            $rawActions = ApprovalAuditRequest::distinct('action')->pluck('action');
            $actions = $rawActions->map(function ($action) {
                // Extract base action: "delete_item:6" -> "delete_item", "update_item:123" -> "update_item"
                $parts = explode(':', $action);
                // If second part is numeric, it's an ID - return just the first part
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    return $parts[0];
                }
                return $action;
            })->unique()->sort()->values();

            $statuses = ApprovalAuditRequest::distinct('status')->pluck('status')->sort();

            return view('livewire.branch-dashboard.audit-management.index', [
                'logs' => null,
                'approvals' => $approvals,
                'actions' => $actions,
                'statuses' => $statuses,
                'departments' => $departments,
                'isSuperAdmin' => $isSuperAdmin,
            ]);
        }
    }
}
