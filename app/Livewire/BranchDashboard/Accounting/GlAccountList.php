<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\GlAccount;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class GlAccountList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public string $sortBy = 'account_number';

    public string $sortDirection = 'asc';

    protected $queryString = ['search', 'filterType', 'filterStatus', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function accounts()
    {
        return GlAccount::query()
            ->when($this->search, function ($q) {
                return $q->where('account_number', 'like', "%{$this->search}%")
                    ->orWhere('account_name', 'like', "%{$this->search}%");
            })
            ->when($this->filterType, function ($q) {
                return $q->where('account_type', $this->filterType);
            })
            ->when($this->filterStatus, function ($q) {
                return $q->where('is_active', $this->filterStatus === 'active');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function toggleActive(GlAccount $account)
    {
        $account->update(['is_active' => ! $account->is_active]);
        session()->flash('success', "Account {$account->account_number} status updated");
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.gl-account-list');
    }
}
