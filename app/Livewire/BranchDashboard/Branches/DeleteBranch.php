<?php

namespace App\Livewire\BranchDashboard\Branches;

use App\Livewire\BaseComponent;
use Livewire\Component;
use App\Models\Branch;
use App\Models\User;

class DeleteBranch extends BaseComponent
{
    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $advancedSearch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Filter fields
    public ?string $filterStatus = null;
    public ?string $filterCountry = null;
    public ?string $filterCity = null;
    public ?string $filterManager = null;

    // Modal states
    public bool $showBranchModal = false;
    public bool $showDeleteModal = false;
    public ?string $selectedBranchId = null;
    public bool $isEditing = false;

    public function mount()
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can manage deleted branches');
        }
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return Branch::query()->onlyTrashed()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->advancedSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('code', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('location', 'like', '%' . $this->advancedSearch . '%')
                      ->orWhere('city', 'like', '%' . $this->advancedSearch . '%');
                });
            })
            ->when($this->filterStatus !== null && $this->filterStatus !== '', function ($query) {
                $query->where('is_active', $this->filterStatus === 'active');
            })
            ->when($this->filterCountry, function ($query) {
                $query->where('country', 'like', '%' . $this->filterCountry . '%');
            })
            ->when($this->filterCity, function ($query) {
                $query->where('city', 'like', '%' . $this->filterCity . '%');
            })
            ->when($this->filterManager, function ($query) {
                $query->where('manager_user_id', $this->filterManager);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            });
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->advancedSearch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->filterStatus = null;
        $this->filterCountry = null;
        $this->filterCity = null;
        $this->filterManager = null;
        $this->resetPage();
    }

    // Export methods


    public function exportCSV()
    {
        $branches = $this->getFilteredQuery()->get();

        $csv = "ID,Name,Code,Location,Phone,Email,City,Country,Active,Created At\n";
        foreach ($branches as $branch) {
            $csv .= "{$branch->id},{$branch->name},{$branch->code},{$branch->location},{$branch->phone},{$branch->email},{$branch->city},{$branch->country}," . ($branch->is_active ? 'Yes' : 'No') . ",{$branch->created_at}\n";
        }

        return response()->streamDownload(function() use ($csv) {
            echo $csv;
        }, 'branches-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // Restore methods
    public function confirmRestore($branchId): void
    {
        $this->selectedBranchId = $branchId;

        $this->dialog()
            ->question('Restore Branch', 'Are you sure you want to restore this branch?')
            ->confirm('Restore', 'restoreBranch', 'Branch restored successfully!')
            ->cancel('Cancel', 'cancelledRestore', 'Restore cancelled')
            ->send();
    }

    public function restoreBranch(string $message): void
    {
        if ($this->selectedBranchId) {
            Branch::onlyTrashed()->findOrFail($this->selectedBranchId)->restore();
            $this->dialog()->success('Success', $message)->send();
            $this->selectedBranchId = null;
        }
    }

    public function cancelledRestore(string $message): void
    {
        $this->selectedBranchId = null;
        $this->dialog()->info('Cancelled', $message)->send();
    }

    // Permanent Delete methods
    public function confirmDelete($branchId): void
    {
        $this->selectedBranchId = $branchId;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to permanently delete this branch? This action cannot be undone.')
            ->confirm('Confirm Delete', 'deleteBranch', 'Branch permanently deleted!')
            ->cancel('Cancel', 'cancelledDelete', 'Delete cancelled')
            ->send();
    }

    public function deleteBranch(string $message): void
    {
        if ($this->selectedBranchId) {
            Branch::onlyTrashed()->findOrFail($this->selectedBranchId)->forceDelete();
            $this->dialog()->success('Success', $message)->send();
            $this->selectedBranchId = null;
        }
    }

    public function cancelledDelete(string $message): void
    {
        $this->selectedBranchId = null;
        $this->dialog()->info('Cancelled', $message)->send();
    }

    // Bulk Restore
    public function confirmBulkRestore(): void
    {
        $this->dialog()
            ->question('Restore Multiple Branches', 'Are you sure you want to restore ' . count($this->selectedIds) . ' branch(es)?')
            ->confirm('Restore All', 'bulkRestore', count($this->selectedIds) . ' branch(es) restored successfully!')
            ->cancel('Cancel', 'cancelledBulkRestore', 'Bulk restore cancelled')
            ->send();
    }

    public function bulkRestore(string $message): void
    {
        Branch::onlyTrashed()->whereIn('id', $this->selectedIds)->restore();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function cancelledBulkRestore(string $message): void
    {
        $this->dialog()->info('Cancelled', $message)->send();
    }

    // Bulk Permanent Delete
    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to permanently delete ' . count($this->selectedIds) . ' branch(es)? This action cannot be undone.')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' branch(es) permanently deleted!')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Bulk delete cancelled')
            ->send();
    }

    public function bulkDelete(string $message): void
    {
        Branch::onlyTrashed()->whereIn('id', $this->selectedIds)->forceDelete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->info('Cancelled', $message)->send();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->orderBy('created_at', 'DESC')->paginate((int) ($this->quantity ?? 10));
        $users = User::all();

        return view('livewire.branch-dashboard.branches.delete-branch', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'name', 'label' => 'Branch Name'],
                ['index' => 'code', 'label' => 'Code'],
                ['index' => 'location', 'label' => 'Location'],
                ['index' => 'city', 'label' => 'City'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'users' => $users,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
