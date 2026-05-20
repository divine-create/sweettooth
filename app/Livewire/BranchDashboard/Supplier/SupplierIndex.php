<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class SupplierIndex extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $searchTerm = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public ?Supplier $selectedSupplier = null;
    public bool $showDetails = false;
    public bool $showCreateForm = false;

    protected $queryString = ['searchTerm', 'statusFilter', 'sortBy', 'sortDirection'];

    public function mount(): void
    {
        $this->b_id = current_branch_id();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId): void
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

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
            ->where('branch_id', current_branch_id())
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
        ])->where('branch_id', current_branch_id())->find($supplierId);

        if (! $this->selectedSupplier) {
            $this->toast()->error('Supplier not found.')->send();
            return;
        }

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

    #[On('supplierCreated')]
    public function handleSupplierCreated(): void
    {
        $this->showCreateForm = false;
        $this->toast()->success('Supplier created successfully.')->send();
    }

    public function getStatusBadgeClass($status): string
    {
        return match($status) {
            'active'    => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'inactive'  => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            default     => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'active'    => 'Active',
            'inactive'  => 'Inactive',
            'suspended' => 'Suspended',
            'pending'   => 'Pending Verification',
            default     => ucfirst($status),
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
