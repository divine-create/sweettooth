<?php

namespace App\Livewire\BranchDashboard\HR\Reports;

use App\Models\DepartmentReport;
use App\Services\Reports\GenericDefinitionReportService;
use App\Services\Reports\Definitions\HRAppraisalRatingsDefinition;
use App\Services\Reports\Definitions\HRAttendancePunctualityDefinition;
use App\Services\Reports\Definitions\HRDepartmentStaffingWorkloadDefinition;
use App\Services\Reports\Definitions\HRLeaveBalanceAccrualDefinition;
use App\Services\Reports\Definitions\HRLeaveUtilizationDefinition;
use App\Services\Reports\Definitions\HRPayrollCompensationDefinition;
use App\Services\Reports\Definitions\HRTurnoverRetentionDefinition;
use App\Services\Reports\Definitions\HRWorkforceOverviewDefinition;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('HR Reports')]
class Index extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $reportType = 'workforce_overview';

    public string $periodFilter = 'month';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $isLoading = false;

    public ?array $reportData = null;

    public array $summaryMetrics = [];

    public array $tablesData = [];

    public array $narrative = [];

    public bool $showSaveModal = false;

    public string|int|null $savedReportId = null;

    public function mount(): void
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->setDateRange();
    }

    public static function reportTypes(): array
    {
        return [
            'workforce_overview'       => ['name' => 'Workforce Overview',             'class' => HRWorkforceOverviewDefinition::class],
            'leave_utilization'        => ['name' => 'Leave Utilization',              'class' => HRLeaveUtilizationDefinition::class],
            'hr_attendance_punctuality'=> ['name' => 'Attendance & Punctuality',       'class' => HRAttendancePunctualityDefinition::class],
            'hr_payroll_compensation'  => ['name' => 'Payroll & Compensation',         'class' => HRPayrollCompensationDefinition::class],
            'hr_turnover_retention'    => ['name' => 'Turnover & Retention',           'class' => HRTurnoverRetentionDefinition::class],
            'hr_appraisal_ratings'     => ['name' => 'Appraisal & Performance Ratings','class' => HRAppraisalRatingsDefinition::class],
            'hr_leave_balance_accrual' => ['name' => 'Leave Balance & Accrual',        'class' => HRLeaveBalanceAccrualDefinition::class],
            'hr_department_staffing'   => ['name' => 'Department Staffing & Workload', 'class' => HRDepartmentStaffingWorkloadDefinition::class],
        ];
    }

    public function updatedReportType(): void
    {
        $this->resetReport();
    }

    public function updatedPeriodFilter(): void
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->resetReport();
        }
    }

    public function setDateRange(): void
    {
        switch ($this->periodFilter) {
            case 'today':
                $this->dateFrom = Carbon::today()->toDateString();
                $this->dateTo   = Carbon::today()->toDateString();
                break;
            case 'yesterday':
                $this->dateFrom = Carbon::yesterday()->toDateString();
                $this->dateTo   = Carbon::yesterday()->toDateString();
                break;
            case 'week':
                $this->dateFrom = Carbon::now()->startOfWeek()->toDateString();
                $this->dateTo   = Carbon::now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->dateTo   = Carbon::now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->dateTo   = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
        }
    }

    public function generatePreview(): void
    {
        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo'   => 'required|date|after_or_equal:dateFrom',
        ]);

        $this->isLoading = true;
        $this->resetReport();

        try {
            $payload = $this->runDefinition();

            $this->reportData     = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->tablesData     = $payload['tables'] ?? ($this->reportData['tables'] ?? []);
            $this->narrative      = $payload['narrative'] ?? ($this->reportData['narrative'] ?? []);

            $this->toast()->success('Report generated.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function saveReport(): void
    {
        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo'   => 'required|date|after_or_equal:dateFrom',
        ]);

        try {
            $definitionClass = self::reportTypes()[$this->reportType]['class'];
            $definition = new $definitionClass();

            $service = (new GenericDefinitionReportService())
                ->useDefinition($definition)
                ->forBranch($this->b_id ?? current_branch_id())
                ->forPeriod($this->dateFrom, $this->dateTo);

            $report = $service->generate(auth()->id());

            $this->savedReportId = $report->id;
            $this->showSaveModal = true;
            $this->toast()->success('Report saved successfully.')->send();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->toast()->error('Save failed: ' . $e->getMessage())->send();
        }
    }

    public function submitForReview(): void
    {
        if (! $this->savedReportId) {
            return;
        }

        try {
            DepartmentReport::findOrFail($this->savedReportId)->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review.')->send();
            $this->showSaveModal = false;
            $this->savedReportId = null;
            $this->resetPage();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    private function runDefinition(): array
    {
        $types = self::reportTypes();
        $definitionClass = $types[$this->reportType]['class'] ?? null;

        if (! $definitionClass) {
            throw new \InvalidArgumentException('Unknown report type.');
        }

        $definition = new $definitionClass();

        $service = (new GenericDefinitionReportService())
            ->useDefinition($definition)
            ->forBranch($this->b_id ?? current_branch_id())
            ->forPeriod($this->dateFrom, $this->dateTo);

        return $service->getReportData();
    }

    private function resetReport(): void
    {
        $this->reportData     = null;
        $this->summaryMetrics = [];
        $this->tablesData     = [];
        $this->narrative      = [];
    }

    public function render()
    {
        $branchId = $this->b_id ?? current_branch_id();

        $savedReports = DepartmentReport::query()
            ->forBranch($branchId)
            ->byCategory('hr')
            ->when($this->reportType, fn ($q) => $q->byType($this->reportType))
            ->orderByDesc('report_date')
            ->paginate(8);

        return view('livewire.branch-dashboard.hr.reports.index', [
            'savedReports' => $savedReports,
            'reportTypes'  => self::reportTypes(),
        ]);
    }
}
