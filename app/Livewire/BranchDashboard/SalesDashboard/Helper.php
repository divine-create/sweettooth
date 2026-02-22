<?php

namespace App\Livewire\BranchDashboard\SalesDashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Helper extends Component
{
    use Interactions;

    public function mount()
    {
        // Any initialization if needed
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.helper');
    }
}