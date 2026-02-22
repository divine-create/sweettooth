<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Enums\ProductionRequestSourceType;
use App\Models\ProductionRequest;
use App\Models\SalesProductionRequestItem;
use DomainException;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class ProductionRequestBoard extends Component
{
    use Interactions;
    use WithPagination;

    public $statusFilter = '';
    public $priorityFilter = '';
    public $userDepartmentId;
    public $selectedRequest = null;
    public $showDetails = false;
    public $showProgressForm = false;

    public function mount()
    {
        $user = auth()->user();
        if ($user) {
            // Check if user is super admin
            if (function_exists('is_super_admin') && is_super_admin()) {
                // Super admin can view all requests, but if they have a department, use that
                if ($user->department) {
                    $this->userDepartmentId = $user->department->id;
                } else {
                    // Super admin without department - can see all, but default to first production department
                    $firstProductionDept = \App\Models\Department::whereHas('category', function($q) {
                        $q->where('name', 'Production');
                    })->first();

                    if ($firstProductionDept) {
                        $this->userDepartmentId = $firstProductionDept->id;
                    } else {
                        $this->userDepartmentId = null; // Allow viewing all
                    }
                }
            } else {
                // Regular user - use their department
                if ($user->department) {
                    $this->userDepartmentId = $user->department->id;
                } else {
                    // User has no department assigned
                    $this->userDepartmentId = null;
                }
            }
        }
    }

    public function getRequestsProperty()
    {
        $query = ProductionRequest::query();

        // If user has a department, filter by it; otherwise, super admin can see all
        if ($this->userDepartmentId) {
            $query->where('production_department_id', $this->userDepartmentId);
        }

        $query->with(['productionDepartment', 'recipe', 'createdBy', 'progressFeedback']);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(20);
    }

    public function viewDetails($requestId)
    {
        $this->selectedRequest = ProductionRequest::with(['progressFeedback', 'dispatches'])->find($requestId);
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->selectedRequest = null;
        $this->showDetails = false;
        $this->showProgressForm = false;
    }

    public function startProduction($requestId)
    {
        try {
            $request = ProductionRequest::find($requestId);
            if ($request) {
                $this->assertMaterialsApprovedForSalesDemand($request);

                $request->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
                $this->dispatch('requestUpdated');
            }
        } catch (\Throwable $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function markCompleted($requestId)
    {
        $request = ProductionRequest::find($requestId);
        if ($request) {
            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->dispatch('requestUpdated');
        }
    }

    public function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'quality_check' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'dispatched' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300'
        };
    }

    public function getStatusLabel($status)
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'quality_check' => 'Quality Check',
            'completed' => 'Completed',
            'dispatched' => 'Dispatched',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.request.production-request-board', [
            'requests' => $this->requests,
        ]);
    }

    private function assertMaterialsApprovedForSalesDemand(ProductionRequest $request): void
    {
        if (
            ! Schema::hasColumn('production_requests', 'source_type')
            || ! Schema::hasColumn('production_requests', 'source_id')
        ) {
            return;
        }

        if ($request->source_type !== ProductionRequestSourceType::SALES_DEMAND->value) {
            return;
        }

        if (! $request->source_id) {
            throw new DomainException('Sales-demand request is missing source linkage.');
        }

        $salesItem = SalesProductionRequestItem::query()->find((int) $request->source_id);
        if (! $salesItem) {
            throw new DomainException('Linked sales request item was not found.');
        }

        $allowedStatuses = [
            'materials_approved',
            'processing',
            'completed',
            'dispatched',
            'received_by_sales',
        ];

        $salesStatus = is_string($salesItem->status) ? $salesItem->status : $salesItem->status->value;
        if (! in_array($salesStatus, $allowedStatuses, true)) {
            throw new DomainException('Cannot start production before inventory materials are approved.');
        }
    }
}
