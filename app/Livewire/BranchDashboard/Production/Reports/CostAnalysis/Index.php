<?php

namespace App\Livewire\BranchDashboard\Production\Reports\CostAnalysis;

use App\Models\DepartmentReport;
use App\Services\Reports\CostAnalysisReportService;
use App\Services\Reports\Definitions\ProductionCostAnalysisDefinition;
use App\Livewire\Traits\RequiresDepartmentSelection;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Cost Analysis Report')]
class Index extends Component
{
    use Interactions, RequiresDepartmentSelection;


    public $periodFilter = 'week';
    public $customDateFrom;
    public $customDateTo;
    public $departmentId;

    // Report data
    public $reportData = null;
    public $summaryMetrics = [];
    public $chartsData = [];
    public $tablesData = [];
    public $narrative = [];
    public $isLoading = false;

    // Generated report
    public $generatedReport = null;
    public $showReportModal = false;

    // Saved reports
    public $savedReports = [];
    public $showMetricModal = false;
    public $currentMetric = null;

    public function mount()
    {
        $this->departmentCategoryFilter = 'Production';
        $this->customDateFrom = Carbon::now()->startOfWeek()->toDateString();
        $this->customDateTo = Carbon::now()->endOfWeek()->toDateString();
        $this->departmentId = session('selected_department_id');
        $this->initDepartments(current_branch_id());
    }

    public function updatedPeriodFilter()
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->generatePreview();
        }
    }

    public function setDateRange()
    {
        switch ($this->periodFilter) {
            case 'today':
                $this->customDateFrom = $this->customDateTo = Carbon::today()->toDateString();
                break;
            case 'yesterday':
                $this->customDateFrom = $this->customDateTo = Carbon::yesterday()->toDateString();
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
            $service = (new CostAnalysisReportService())
                ->useDefinition(new ProductionCostAnalysisDefinition());

            $service->forBranch(current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            $payload = $service->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->chartsData = $payload['charts_data'] ?? [];
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];

            $this->toast()->success('Cost analysis report generated successfully')->send();
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
            $service = (new CostAnalysisReportService())
                ->useDefinition(new ProductionCostAnalysisDefinition());

            $this->generatedReport = $service
                ->forBranch(current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->generate(auth()->id());

            $this->showReportModal = true;
            $this->toast()->success('Report generated and saved successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error generating report: ' . $e->getMessage())->send();
        }
    }

    public function viewReport($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);

            // Load the report data into the preview
            $payload = $report->report_data ?? [];
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $report->summary_metrics ?? ($payload['summary_metrics'] ?? []);
            $this->chartsData = $report->charts_data ?? ($payload['charts_data'] ?? []);
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];
            $this->customDateFrom = Carbon::parse($report->period_from)->toDateString();
            $this->customDateTo = Carbon::parse($report->period_to)->toDateString();
            $this->periodFilter = 'custom';

            $this->toast()->success('Report loaded for preview')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error loading report: ' . $e->getMessage())->send();
        }
    }

    public function submitForReview($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);

            if (!$report->isEditable()) {
                $this->toast()->error('Report cannot be edited in its current state')->send();
                return;
            }

            $report->update(['status' => 'pending_review']);

            $this->toast()->success('Report submitted for review')->send();
            $this->showReportModal = false;
            $this->generatedReport = null;
        } catch (\Exception $e) {
            $this->toast()->error('Error submitting report: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        // Load saved reports for this department and report type
        $this->savedReports = DepartmentReport::where('branch_id', current_branch_id())
            ->where('department_id', $this->departmentId)
            ->where('report_type', 'cost_analysis')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('livewire.branch-dashboard.production.reports.cost-analysis.index', [
            'savedReports' => $this->savedReports,
        ]);
    }

    public function openMetricModal(string $metric): void
    {
        $this->currentMetric = $metric;
        $this->showMetricModal = true;
    }

    public function downloadReport($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);

            // For now, return JSON data - you can implement CSV/PDF export
            return response()->json($report->report_data, 200, [
                'Content-Disposition' => 'attachment; filename="cost-analysis-report-' . $report->id . '.json"'
            ]);
        } catch (\Exception $e) {
            $this->toast()->error('Error downloading report: ' . $e->getMessage())->send();
        }
    }
}
