<?php

namespace App\Livewire\BranchDashboard\Production\Reports;

use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Url};

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Performance Reports')]
class PerformanceReports extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    // Active report tab
    public $activeReport = 'ingredient-utilization'; // 'ingredient-utilization', 'production-activities'

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
    }

    public function switchReport($reportType)
    {
        $this->activeReport = $reportType;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.reports.performance-reports');
    }
}
