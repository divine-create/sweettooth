<?php

namespace App\Livewire\BranchDashboard\Reporting;

use App\Models\Department;
use App\Models\DepartmentReport;
use App\Services\Reports\ReportRegistry;
use App\Services\SidebarVisibilityService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Report Generator')]
class Generate extends Component
{
    use Interactions;

    public $branchId;
    public $periodFilter = 'week';
    public $customDateFrom;
    public $customDateTo;
    public $shiftType = null;

    public $reportCategory;
    public $reportKey;
    public $reportData = null;
    public $summaryMetrics = [];
    public $tablesData = [];
    public $narrative = [];
    public $isLoading = false;
    public $generatedReport = null;

    public $departmentId;
    public $availableDepartments = [];

    public $reportsByCategory = [];
    public $savedReports = [];

    public function mount()
    {
        $this->branchId = current_branch_id();
        $this->loadReports();
        $this->loadDepartments($this->reportCategory);
        $this->setDateRange();
        $this->refreshSavedReports();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->branchId = $branchId;
        $this->loadDepartments($this->reportCategory);
        $this->clearReportData();
        $this->refreshSavedReports();
    }

    private function loadDepartments(?string $category = null): void
    {
        $user = auth()->user();
        $branchId = $this->branchId ?? current_branch_id();
        $category = strtolower((string) $category);

        // Category → department category name mapping
        $categoryNameMap = [
            'production' => 'Production',
            'sales'      => 'Sales',
            'inventory'  => 'Support',
            'accounting' => 'Support',
        ];

        if (isset($categoryNameMap[$category])) {
            $categoryName = $categoryNameMap[$category];
            $this->availableDepartments = Department::whereHas('category', fn ($q) => $q->where('name', $categoryName))
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                })
                ->where('is_active', true)
                ->with('category')
                ->orderBy('name')
                ->get()
                ->values()
                ->all();
        } else {
            $departments = SidebarVisibilityService::getAccessibleDepartments($user);
            $this->availableDepartments = $departments->values()->all();
        }

        $selected = $this->departmentId
            ?? session('selected_department_id')
            ?? $user?->department_id;

        $selectedIsValid = collect($this->availableDepartments)->firstWhere('id', $selected);

        if ($selectedIsValid) {
            $this->departmentId = $selected;
            return;
        }

        // Prefer the department that actually has recent production activity over a
        // blind alphabetical pick — otherwise the generator opens on an empty
        // department (e.g. CORNERSTONE) and every production report renders blank.
        $this->departmentId = $this->mostActiveDepartmentId($this->availableDepartments, $category)
            ?? collect($this->availableDepartments)->first()?->id
            ?? null;
    }

    /**
     * For production, return the available department with the most recent
     * production record so the report lands on real data by default.
     */
    private function mostActiveDepartmentId(array $departments, ?string $category): mixed
    {
        if (strtolower((string) $category) !== 'production' || empty($departments)) {
            return null;
        }

        $ids = collect($departments)->pluck('id')->filter()->all();
        if (empty($ids)) {
            return null;
        }

        $branchId = $this->branchId ?? current_branch_id();

        return \App\Models\ProductionRecord::query()
            ->join('daily_produces', 'production_records.daily_produce_id', '=', 'daily_produces.id')
            ->join('shifts', 'daily_produces.shift_id', '=', 'shifts.id')
            ->whereIn('shifts.department_id', $ids)
            ->where('shifts.branch_id', $branchId)
            ->orderByDesc('production_records.production_time')
            ->value('shifts.department_id');
    }

    private function loadReports(): void
    {
        $this->reportsByCategory = ReportRegistry::groupedByCategory(auth()->user());

        if (empty($this->reportsByCategory)) {
            $this->reportCategory = null;
            $this->reportKey = null;
            return;
        }

        if (!$this->reportCategory || !isset($this->reportsByCategory[$this->reportCategory])) {
            $this->reportCategory = array_key_first($this->reportsByCategory);
        }

        $currentCategoryReports = $this->reportsByCategory[$this->reportCategory] ?? [];
        $this->reportKey = $this->reportKey ?? ($currentCategoryReports[0]['key'] ?? null);
    }

    public function updatedReportCategory()
    {
        if (empty($this->reportsByCategory)) {
            $this->reportKey = null;
            $this->clearReportData();
            $this->refreshSavedReports();
            return;
        }

        if (!$this->reportCategory || empty($this->reportsByCategory[$this->reportCategory])) {
            $this->reportKey = null;
        } else {
            $this->reportKey = $this->reportsByCategory[$this->reportCategory][0]['key'] ?? null;
        }
        $this->loadDepartments($this->reportCategory);
        $this->clearReportData();
        $this->refreshSavedReports();
    }

    public function updatedReportKey()
    {
        $this->clearReportData();
        $this->refreshSavedReports();
    }

    public function updatedPeriodFilter()
    {
        $this->setDateRange();
    }

    public function updatedShiftType()
    {
        // Optional: trigger refresh if needed
    }

    public function updatedDepartmentId($value)
    {
        if ($value) {
            session(['selected_department_id' => $value]);
        }
        $this->refreshSavedReports();
    }

    private function isSalesDepartment($department): bool
    {
        if (!$department) {
            return false;
        }

        return strtolower($department->category?->name ?? '') === 'sales';
    }

    private function filterDepartmentsForCategory($departments, ?string $category)
    {
        if (!$category) {
            return $departments;
        }

        $category = strtolower($category);

        if ($category === 'sales') {
            return $departments->filter(fn($department) => $this->isSalesDepartment($department));
        }

        if ($category === 'production') {
            return $departments->filter(function ($department) {
                $categoryName = strtolower($department->category?->name ?? '');
                return $categoryName === 'production';
            });
        }

        if ($category === 'inventory') {
            return $departments->filter(function ($department) {
                $categoryName = strtolower($department->category?->name ?? '');
                return $categoryName === 'support';
            });
        }

        if ($category === 'accounting') {
            return $departments->filter(function ($department) {
                $categoryName = strtolower($department->category?->name ?? '');
                return $categoryName === 'support';
            });
        }

        return $departments;
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
        $resolved = $this->resolveReport();
        if (!$resolved) {
            return;
        }

        if (!$this->validateContext($resolved['meta'])) {
            return;
        }

        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        $this->isLoading = true;

        try {
            $serviceClass = $resolved['service'];
            $definitionClass = $resolved['definition'];

            $service = (new $serviceClass())->useDefinition(new $definitionClass());
            $service->forBranch($this->branchId)
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            if ($this->shiftType && method_exists($service, 'forShiftType')) {
                $service->forShiftType($this->shiftType);
            }

            $payload = $service->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->tablesData = $payload['tables'] ?? ($this->reportData['tables'] ?? []);
            $this->narrative = $payload['narrative'] ?? ($this->reportData['narrative'] ?? []);

            $this->toast()->success('Report preview generated successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateReport()
    {
        $resolved = $this->resolveReport();
        if (!$resolved) {
            return;
        }

        if (!$this->validateContext($resolved['meta'])) {
            return;
        }

        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        try {
            $serviceClass = $resolved['service'];
            $definitionClass = $resolved['definition'];

            $service = (new $serviceClass())->useDefinition(new $definitionClass());
            
            $service->forBranch($this->branchId)
                ->forDepartment($this->departmentId)
                ->forPeriod($this->customDateFrom, $this->customDateTo);

            if ($this->shiftType && method_exists($service, 'forShiftType')) {
                $service->forShiftType($this->shiftType);
            }

            $this->generatedReport = $service->generate(auth()->id());

            $this->toast()->success('Report saved successfully')->send();
            $this->refreshSavedReports();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    public function loadSavedReport(string $reportId)
    {
        $report = DepartmentReport::query()
            ->where('branch_id', $this->branchId)
            ->findOrFail($reportId);

        $payload = $report->report_data ?? [];
        $this->reportData = $payload;
        $this->summaryMetrics = $report->summary_metrics ?? ($payload['summary_metrics'] ?? []);
        $this->tablesData = $payload['tables'] ?? ($payload['report_data']['tables'] ?? []);
        $this->narrative = $payload['narrative'] ?? ($payload['report_data']['narrative'] ?? []);
        $this->generatedReport = $report;
    }

    public function submitForReview(string $reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);
            $report->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review')->send();
            $this->refreshSavedReports();
        } catch (\Exception $e) {
            $this->toast()->error('Error: ' . $e->getMessage())->send();
        }
    }

    private function refreshSavedReports(): void
    {
        if (!$this->reportKey) {
            $this->savedReports = [];
            return;
        }

        $resolved = ReportRegistry::resolve($this->reportKey, auth()->user());
        if (!$resolved) {
            $this->savedReports = [];
            return;
        }

        $meta = $resolved['meta'] ?? [];
        $query = DepartmentReport::query()
            ->where('branch_id', $this->branchId)
            ->where('report_category', $meta['category'] ?? null)
            ->where('report_type', $meta['type'] ?? null)
            ->orderBy('report_date', 'desc')
            ->limit(10);

        if (!empty($meta['requires_department']) && $this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        $this->savedReports = $query->get();
    }

    private function resolveReport(): ?array
    {
        if (!$this->reportKey) {
            $this->toast()->warning('Please select a report type.')->send();
            return null;
        }

        $resolved = ReportRegistry::resolve($this->reportKey, auth()->user());
        if (!$resolved) {
            $this->toast()->error('Selected report is not available.')->send();
            return null;
        }

        return $resolved;
    }

    private function validateContext(array $meta): bool
    {
        if (!empty($meta['requires_department']) && !$this->departmentId) {
            $this->toast()->warning('Please select a department for this report.')->send();
            return false;
        }

        return true;
    }

    private function clearReportData(): void
    {
        $this->reportData = null;
        $this->summaryMetrics = [];
        $this->tablesData = [];
        $this->narrative = [];
        $this->generatedReport = null;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.reporting.generate');
    }
}
