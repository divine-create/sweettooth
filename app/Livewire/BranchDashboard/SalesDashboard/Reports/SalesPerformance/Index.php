<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Reports\SalesPerformance;

use App\Livewire\Traits\RequiresDepartmentSelection;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Services\Reports\Definitions\SalesPerformanceDefinition;
use App\Services\Reports\SalesPerformanceReportService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Sales Performance Report')]
class Index extends Component
{
    use Interactions, RequiresDepartmentSelection;

    public ?string $branchId = null;
    public ?string $salesDeptSlug = null;

    public string $periodFilter = 'week';
    public ?string $customDateFrom = null;
    public ?string $customDateTo = null;

    public ?string $departmentId = null;

    public $reportData = null;
    public $summaryMetrics = [];
    public $chartsData = [];
    public $tablesData = [];
    public $narrative = [];

    public bool $isLoading = false;
    public $generatedReport = null;
    public bool $showReportModal = false;
    public ?array $selectedSaleDetail = null;
    public bool $showSaleDetailModal = false;

    public function mount(?string $salesDeptSlug = null)
    {
        $this->branchId = current_branch_id();
        $this->salesDeptSlug = $salesDeptSlug;
        $this->initSalesDepartments($this->branchId);
        $this->setDateRange();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->branchId = $branchId;
        $this->initSalesDepartments($branchId);
        $this->generatePreview();
    }

    private function initSalesDepartments(string $branchId): void
    {
        $this->availableDepartments = Department::query()
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->whereHas('category', function ($q) {
                $q->where('name', 'Sales');
            })
            ->orderBy('name')
            ->get();

        if ($this->salesDeptSlug) {
            $dept = $this->availableDepartments->firstWhere('slug', $this->salesDeptSlug);
            if ($dept) {
                $this->departmentId = $dept->id;
                $this->selectedDepartmentId = $dept->id;
                session(['selected_department_id' => $dept->id]);
                return;
            }
            $this->toast()->error('Sales department not found for this URL.')->send();
        }

        $this->selectedDepartmentId = $this->departmentId
            ?? session('selected_department_id')
            ?? $this->availableDepartments->first()?->id;

        if ($this->selectedDepartmentId) {
            $this->departmentId = $this->selectedDepartmentId;
        }
    }

    public function setDateRange(): void
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

    public function updatedPeriodFilter(): void
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->generatePreview();
        }
    }

    public function generatePreview(): void
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
            $service = (new SalesPerformanceReportService())
                ->useDefinition(new SalesPerformanceDefinition());

            $service->forBranch($this->branchId)
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            $payload = $service->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->chartsData = $payload['charts_data'] ?? [];
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];

            $this->toast()->success('Sales performance report generated successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateReport(): void
    {
        if (! $this->ensureDepartmentSelected('generate')) {
            return;
        }

        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        try {
            $service = (new SalesPerformanceReportService())
                ->useDefinition(new SalesPerformanceDefinition());

            $this->generatedReport = $service
                ->forBranch($this->branchId)
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->generate(auth()->id());

            $this->showReportModal = true;
            $this->toast()->success('Sales performance report saved successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function submitForReview($reportId): void
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);
            $report->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review')->send();
            $this->showReportModal = false;
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function openSaleDetail(int $index): void
    {
        $salesDetails = data_get($this->reportData, 'report_data.sales_details')
            ?? data_get($this->reportData, 'sales_details', []);

        $this->selectedSaleDetail = $salesDetails[$index] ?? null;
        $this->showSaleDetailModal = $this->selectedSaleDetail !== null;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.reports.sales-performance.index');
    }
}
