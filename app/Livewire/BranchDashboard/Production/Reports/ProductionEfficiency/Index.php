<?php

namespace App\Livewire\BranchDashboard\Production\Reports\ProductionEfficiency;

use App\Models\DepartmentReport;
use App\Services\Reports\Definitions\ProductionEfficiencyDefinition;
use App\Services\Reports\ProductionEfficiencyReportService;
use App\Livewire\Traits\RequiresDepartmentSelection;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\{Layout, On, Title, Url};
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Production Efficiency Report')]
class Index extends Component
{
    use Interactions, RequiresDepartmentSelection;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Filters
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
    public $savedReports = [];

    // Metric explanation modal
    public $showMetricModal = false;
    public $currentMetric = null;

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

    /**
     * Set date range based on filter.
     */
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
            case 'custom':
                // Keep existing custom dates
                break;
        }
    }

    /**
     * Generate report preview.
     */
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
            $service = (new ProductionEfficiencyReportService())
                ->useDefinition(new ProductionEfficiencyDefinition());

            $service->forBranch($this->b_id ?? current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            $payload = $service->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->chartsData = $payload['charts_data'] ?? [];
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];

            $this->toast()->success('Report generated successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error generating report: ' . $e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate and save report.
     */
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
            $service = (new ProductionEfficiencyReportService())
                ->useDefinition(new ProductionEfficiencyDefinition());

            $this->generatedReport = $service
                ->forBranch($this->b_id ?? current_branch_id())
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->generate(auth()->id());

            $this->showReportModal = true;
            $this->toast()->success('Report generated and saved successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error generating report: ' . $e->getMessage())->send();
        }
    }

    /**
     * Submit report for review.
     */
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





    /**
     * Refresh report data.
     */
    public function refresh()
    {
        $this->generatePreview();
    }

    /**
     * Update period filter.
     */
    public function updatedPeriodFilter()
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->generatePreview();
        }
    }

    /**
     * Show metric explanation modal
     */
    public function showMetricExplanation($metric)
    {
        $this->currentMetric = $metric;
        $this->showMetricModal = true;
    }

    /**
     * View a saved report
     */
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

    /**
     * Download/export a report
     */
    public function downloadReport($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);

            // For now, return JSON data - you can implement CSV/PDF export
            return response()->json($report->report_data, 200, [
                'Content-Disposition' => 'attachment; filename="production-efficiency-report-' . $report->id . '.json"'
            ]);
        } catch (\Exception $e) {
            $this->toast()->error('Error downloading report: ' . $e->getMessage())->send();
        }
    }

    /**
     * Export current preview as CSV
     */
    public function exportCsv()
    {
        if (!$this->reportData) {
            $this->toast()->error('No report data to export')->send();
            return;
        }

        // Basic CSV export of daily summary
        $csvData = "Date,Planned,Actual,Variance,Efficiency,Products Count\n";

        foreach ($this->reportData['daily_summary'] ?? [] as $day) {
            $csvData .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $day['date'],
                $day['planned'],
                $day['actual'],
                $day['variance'],
                $day['efficiency_percentage'],
                $day['products_count']
            );
        }

        return response($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="production-efficiency-' . now()->format('Y-m-d') . '.csv"'
        ]);
    }

    public function render()
    {
        // Load saved reports for this department and report type
        $this->savedReports = DepartmentReport::where('branch_id', $this->b_id ?? current_branch_id())
            ->where('department_id', $this->departmentId)
            ->where('report_type', 'production_efficiency')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('livewire.branch-dashboard.production.reports.production-efficiency.index', [
            'savedReports' => $this->savedReports,
        ]);
    }
}
