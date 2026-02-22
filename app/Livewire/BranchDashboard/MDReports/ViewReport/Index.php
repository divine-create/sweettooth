<?php

namespace App\Livewire\BranchDashboard\MDReports\ViewReport;

use App\Models\CompiledReport;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

class Index extends Component
{
    use Interactions;

    public $reportId;
    public $report;

    public function mount($id)
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can view MD Reports');
        }

        $this->reportId = $id;
        $this->report = CompiledReport::with([
            'branch',
            'compiledBy',
            'mdUser',
            'departmentReports.department',
            'departmentReports.generatedBy',
            'annotations.author',
            'departmentReports.annotations.author',
        ])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.md-reports.view-report.index', [
            'report' => $this->report,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
