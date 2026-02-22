<?php

namespace App\Livewire\BranchDashboard\Production\Reports\QualityMetrics;

use App\Models\DepartmentReport;
use App\Services\Reports\Definitions\ProductionQualityDefinition;
use App\Services\Reports\ProductionQualityReportService;
use App\Livewire\Traits\RequiresDepartmentSelection;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\{Layout, On, Title, Url};
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Quality Metrics Report')]
class Index extends Component
{
    use Interactions, RequiresDepartmentSelection;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $periodFilter = 'week';
    public $customDateFrom;
    public $customDateTo;
    public $departmentId;
    public $reportData = null;
    public $summaryMetrics = [];
    public $chartsData = [];
    public $tablesData = [];
    public $narrative = [];
    public $isLoading = false;
    public $generatedReport = null;
    public $showReportModal = false;

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->departmentId = session('selected_department_id');
        $this->initDepartments($this->b_id);
        $this->setDateRange();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->initDepartments($branchId);
    }

    public function setDateRange()
    {
        switch ($this->periodFilter) {
            case 'today':
                $this->customDateFrom = Carbon::today()->toDateString();
                $this->customDateTo = Carbon::today()->toDateString();
                break;
            case 'yesterday':
                $this->customDateFrom = Carbon::yesterday()->toDateString();
                $this->customDateTo = Carbon::yesterday()->toDateString();
                break;
            case 'week':
                $this->customDateFrom = Carbon::now()->startOfWeek()->toDateString();
                $this->customDateTo = Carbon::now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->customDateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->customDateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
        }
    }

    public function generatePreview()
    {
        if (! $this->ensureDepartmentSelected('preview')) {
            return;
        }

        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        $this->isLoading = true;

        try {
            $service = (new ProductionQualityReportService())
                ->useDefinition(new ProductionQualityDefinition());

            $service->forBranch($this->b_id ?? current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            $payload = $service->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->chartsData = $payload['charts_data'] ?? [];
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];

            $this->toast()->success('Quality report generated successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateReport()
    {
        if (! $this->ensureDepartmentSelected('generate')) {
            return;
        }

        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        try {
            $service = (new ProductionQualityReportService())
                ->useDefinition(new ProductionQualityDefinition());

            $this->generatedReport = $service
                ->forBranch($this->b_id ?? current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->generate(auth()->id());

            $this->showReportModal = true;
            $this->toast()->success('Quality report saved successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function submitForReview($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);
            $report->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review')->send();
            $this->showReportModal = false;
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function updatedPeriodFilter()
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->generatePreview();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.reports.quality-metrics.index');
    }
}
