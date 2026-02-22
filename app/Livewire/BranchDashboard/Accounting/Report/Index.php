<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.branch-dashboard.accounting.report.index');
    }
}
