<?php

namespace App\Livewire\BranchDashboard\Production\Reports\PipelineStatus;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Pipeline Status Report')]
class Index extends Component
{
    use Interactions;

    public $periodFilter = 'week';
    public $customDateFrom;
    public $customDateTo;

    public function mount()
    {
        $this->customDateFrom = Carbon::now()->startOfWeek()->toDateString();
        $this->customDateTo = Carbon::now()->endOfWeek()->toDateString();
        $this->toast()->info('Pipeline Status Report - Coming Soon')->send();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.reports.placeholder');
    }
}
