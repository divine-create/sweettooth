<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierIndex extends Component
{
    use WithPagination;

    public string $searchTerm = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public ?Supplier $selectedSupplier = null;
    public bool $showDetails = false;
    public bool $showCreateForm = false;

    protected $queryString = ['searchTerm', 'statusFilter', 'sortBy', 'sortDirection'];

    public function updatedSearchTerm(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function getSuppliersProperty()
    {
        return Supplier::query()
            ->when($this->searchTerm, fn ($q) => $q->search($this->searchTerm))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function viewDetails($supplierId): void
    {
        $this->selectedSupplier = Supplier::with([
            'contacts',
            'bankAccounts',
            'documents',
            'performanceHistory',
            'purchases',
        ])->find($supplierId);
        $this->showDetails = true;
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedSupplier = null;
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    public function getStatusBadgeClass($status): string
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending' => 'Pending Verification',
            default => ucfirst($status),
        };
    }

    public function sortBy($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.supplier-index', [
            'suppliers' => $this->suppliers,
        ]);
    }
}
