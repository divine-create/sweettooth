<?php

namespace App\Livewire\BranchDashboard\ReportingDepartment\ReportDetail;

use App\Models\DepartmentReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Report Detail')]
class Index extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?DepartmentReport $report = null;

    public function mount(string $id): void
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->report = DepartmentReport::with(['department', 'generatedBy'])
            ->where('branch_id', $this->b_id ?? current_branch_id())
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.reporting-department.report-detail.index');
    }
}
