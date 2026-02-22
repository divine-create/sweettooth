<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\Shifts;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\EmployeeShift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    public function mount()
    {
        // Set b_id from current branch context
        $this->b_id = current_branch_id();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    protected function getModelClass(): string
    {
        return EmployeeShift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-module.shifts.index');
    }
}
