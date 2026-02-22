<?php

namespace App\Livewire\BranchDashboard\SalesDashboard;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Helper extends BaseComponent
{
    use Interactions, SalesDepartmentContext;

    public function mount()
    {
        $this->mountBase();
        $this->initializeDepartmentContext();
    }

    protected function getModelClass(): string
    {
        return \App\Models\Sale::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.helper');
    }
}
