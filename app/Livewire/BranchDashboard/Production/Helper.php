<?php

namespace App\Livewire\BranchDashboard\Production;

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
        return view('livewire.branch-dashboard.production.helper');
    }
}