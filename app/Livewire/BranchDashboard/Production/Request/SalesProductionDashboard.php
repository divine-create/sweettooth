<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Models\ProductionRequest;
use Livewire\Component;
use Livewire\WithPagination;

class SalesProductionDashboard extends Component
{
    use WithPagination;

    public $statusFilter = '';
    public $priorityFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $searchTerm = '';
    public $selectedRequest = null;
    public $showDetails = false;

    public function mount()
    {
        $this->resetPage();
    }

    public function getRequestsProperty()
    {
        $query = ProductionRequest::where('created_by_id', auth()->id())
            ->with(['productionDepartment', 'progressFeedback', 'dispatches']);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->whereHas('productionDepartment', function ($subQ) {
                    $subQ->where('name', 'like', '%' . $this->searchTerm . '%');
                })
                ->orWhere('notes', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function viewDetails($requestId)
    {
        $this->selectedRequest = ProductionRequest::find($requestId);
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->selectedRequest = null;
        $this->showDetails = false;
    }

    public function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'approved' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'quality_check' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'dispatched' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
            'accepted' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'cancelled' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300',
            default => 'bg-zinc-100 text-zinc-800'
        };
    }

    public function getStatusLabel($status)
    {
        return match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'in_progress' => 'In Progress',
            'quality_check' => 'Quality Check',
            'completed' => 'Completed',
            'dispatched' => 'Dispatched',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.request.sales-production-dashboard', [
            'requests' => $this->requests,
        ]);
    }
}
