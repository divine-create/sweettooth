<?php

namespace App\Services\Reports;

use App\Models\DepartmentReport;
use App\Models\CompiledReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class ReportService
{
    protected ?\App\Services\Reports\Definitions\ReportDefinition $definition = null;
    protected string $reportCategory;
    protected string $reportType;
    protected $branchId;
    protected $departmentId;
    protected $periodFrom;
    protected $periodTo;
    protected int $cacheMinutes = 60;

    /**
     * Set branch ID for report generation.
     */
    public function forBranch($branchId): self
    {
        $this->branchId = $branchId;
        return $this;
    }

    /**
     * Set department ID for report generation.
     */
    public function forDepartment($departmentId): self
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    /**
     * Set period for report generation.
     */
    public function forPeriod($from, $to): self
    {
        $this->periodFrom = $from;
        $this->periodTo = $to;
        return $this;
    }

    /**
     * Generate and save report.
     */
    public function generate($employeeId = null): DepartmentReport
    {
        DB::beginTransaction();
        try {
            if ($this->definition) {
                $reportPayload = $this->generateReportPayloadFromDefinition($employeeId);
                $reportData = $reportPayload['report_data'];
                $summaryMetrics = $reportPayload['summary_metrics'];
                $chartsData = $reportPayload['charts_data'];
            } else {
                $reportData = $this->generateReportData();
                $summaryMetrics = $this->generateSummaryMetrics($reportData);
                $chartsData = $this->generateChartsData($reportData);
            }

            $actor = current_actor();
            $generatedById = $actor?->getKey() ?? $employeeId;
            $generatedByType = $actor ? get_class($actor) : null;

            $report = DepartmentReport::create([
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'generated_by_id' => $generatedById,
                'generated_by_type' => $generatedByType,
                'report_type' => $this->reportType,
                'report_category' => $this->reportCategory,
                'report_name' => $this->getReportName(),
                'report_date' => now()->toDateString(),
                'period_from' => $this->periodFrom,
                'period_to' => $this->periodTo,
                'report_data' => $reportData,
                'summary_metrics' => $summaryMetrics,
                'charts_data' => $chartsData,
                'system_data_hash' => DepartmentReport::computeSystemDataHash(
                    $reportData,
                    $summaryMetrics,
                    $chartsData
                ),
                'system_data_version' => 1,
                'system_data_locked_at' => now(),
                'status' => 'draft',
            ]);

            DB::commit();
            return $report;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get report data (cached).
     */
    public function getReportData(): array
    {
        $cacheKey = $this->getCacheKey();

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
            if ($this->definition) {
                return $this->generateReportPayloadFromDefinition();
            }

            return $this->generateReportData();
        });
    }

    /**
     * Use unified report definition (new pattern).
     */
    public function useDefinition(\App\Services\Reports\Definitions\ReportDefinition $definition): self
    {
        $this->definition = $definition;

        $meta = $definition->meta();
        $this->reportType = $meta['type'] ?? $this->reportType;
        $this->reportCategory = $meta['category'] ?? $this->reportCategory;

        return $this;
    }

    /**
     * Build payload using a report definition.
     */
    protected function generateReportPayloadFromDefinition($employeeId = null): array
    {
        $this->validateParameters();

        $meta = $this->definition->meta();
        $actor = current_actor();
        if (!empty($meta['requires_department']) && !$this->departmentId) {
            $this->departmentId = $actor?->department_id
                ?? session('selected_department_id')
                ?? request()?->attributes?->get('current_department_id')
                ?? $this->departmentId;
        }
        if (!empty($meta['requires_department']) && !$this->departmentId) {
            // Scope the fallback to a department in the REPORT'S category. Picking the
            // alphabetically-first department across ALL categories (the old behaviour)
            // could scope e.g. a Production report to an Accounting department, yielding
            // a permanently blank report. Mirror Generate.php's category→dept-category map.
            $category = strtolower((string) ($meta['category'] ?? $this->reportCategory ?? ''));
            $deptCategoryName = [
                'production' => 'Production',
                'sales'      => 'Sales',
                'inventory'  => 'Support',
                'accounting' => 'Support',
            ][$category] ?? null;

            $fallbackQuery = \App\Models\Department::query()
                ->where(function ($q) {
                    $q->where('branch_id', $this->branchId)
                        ->orWhereNull('branch_id');
                });
            if ($deptCategoryName) {
                $fallbackQuery->whereHas('category', fn ($q) => $q->where('name', $deptCategoryName));
            }
            $fallbackDeptId = $fallbackQuery->orderBy('name')->value('id');

            if ($fallbackDeptId) {
                $this->departmentId = $fallbackDeptId;
                session(['selected_department_id' => $fallbackDeptId]);
            }
        }
        if (!empty($meta['requires_department']) && !$this->departmentId) {
            throw new \InvalidArgumentException('Department ID is required for this report. Please select a department.');
        }

        $context = [
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
            'actor' => $actor,
            'employee_id' => $employeeId,
        ];

        $data = $this->definition->query($context);
        $summary = $this->definition->summary($data, $context);
        $charts = $this->definition->charts($data, $context);
        $tables = $this->definition->tables($data, $summary, $context);
        $narrative = $this->definition->narrative($data, $summary, $context);

        $reportData = [
            'report_data' => $data,
            'summary_metrics' => $summary,
            'charts_data' => $charts,
            'tables' => $tables,
            'narrative' => $narrative,
            'period_info' => [
                'from' => $this->periodFrom,
                'to' => $this->periodTo,
            ],
        ];

        return [
            'report_data' => $reportData,
            'summary_metrics' => $summary,
            'charts_data' => $charts,
        ];
    }

    /**
     * Generate cache key for report.
     */
    protected function getCacheKey(): string
    {
        return sprintf(
            'report:%s:%s:%s:%s:%s:%s',
            $this->reportCategory,
            $this->reportType,
            $this->branchId,
            $this->departmentId,
            $this->periodFrom,
            $this->periodTo
        );
    }

    /**
     * Clear report cache.
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Get report name.
     */
    abstract protected function getReportName(): string;

    /**
     * Generate the actual report data.
     * This must be implemented by child classes.
     */
    abstract protected function generateReportData(): array;

    /**
     * Generate summary metrics from report data.
     */
    abstract protected function generateSummaryMetrics(array $reportData): array;

    /**
     * Generate charts data from report data.
     */
    abstract protected function generateChartsData(array $reportData): array;

    /**
     * Validate required parameters before generation.
     */
    protected function validateParameters(): void
    {
        if (!$this->branchId) {
            throw new \InvalidArgumentException('Branch ID is required');
        }

        if (!$this->periodFrom || !$this->periodTo) {
            throw new \InvalidArgumentException('Period dates are required');
        }
    }

    /**
     * Format number for display.
     */
    protected function formatNumber($number, int $decimals = 2): string
    {
        return number_format($number, $decimals);
    }

    /**
     * Calculate percentage.
     */
    protected function calculatePercentage($value, $total): float
    {
        if ($total == 0) {
            return 0;
        }
        return round(($value / $total) * 100, 2);
    }

    /**
     * Calculate growth rate.
     */
    protected function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get date range label.
     */
    protected function getDateRangeLabel(): string
    {
        return sprintf(
            '%s to %s',
            \Carbon\Carbon::parse($this->periodFrom)->format('M d, Y'),
            \Carbon\Carbon::parse($this->periodTo)->format('M d, Y')
        );
    }
}
