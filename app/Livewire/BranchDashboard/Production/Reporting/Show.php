<?php

namespace App\Livewire\BranchDashboard\Production\Reporting;

use App\Models\DepartmentReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Production Report')]
class Show extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?DepartmentReport $report = null;

    public function mount(string $id): void
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->report = DepartmentReport::query()
            ->with(['department', 'generatedBy'])
            ->forBranch($this->b_id ?? current_branch_id())
            ->byCategory('production')
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.reporting.show');
    }
}
