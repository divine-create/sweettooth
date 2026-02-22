<?php

namespace App\Services\Reports;

use App\Models\CompiledReport;
use App\Models\DepartmentReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportCompilationService
{
    /**
     * Compile multiple department reports into a single compiled report.
     */
    public function compile(
        array $departmentReportIds,
        string $title,
        string $description,
        $branchId,
        $employeeId,
        $periodFrom,
        $periodTo
    ): CompiledReport {
        DB::beginTransaction();
        try {
            $departmentReports = DepartmentReport::with(['department', 'generatedBy'])
                ->whereIn('id', $departmentReportIds)
                ->where('branch_id', $branchId)
                ->get();

            // Validate all reports can be compiled
            $this->validateReportsForCompilation($departmentReports);

            // Generate executive summary
            $executiveSummary = $this->generateExecutiveSummary($departmentReports);

            // Generate key metrics
            $keyMetrics = $this->generateKeyMetrics($departmentReports);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($departmentReports);

            // Get the current actor to determine polymorphic type
            $actor = current_actor();
            if ($actor) {
                $employeeId = $employeeId ?? $actor->getKey();
                $compiledByType = get_class($actor);
            } else {
                $user = auth()->user();
                $compiledByType = $user instanceof \App\Models\Employee ? \App\Models\Employee::class : \App\Models\User::class;
            }

            // Create compiled report
            $compiledReport = CompiledReport::create([
                'branch_id' => $branchId,
                'compiled_by_id' => $employeeId,
                'compiled_by_type' => $compiledByType,
                'compilation_title' => $title,
                'compilation_description' => $description,
                'compilation_date' => now()->toDateString(),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'included_reports' => $departmentReportIds,
                'executive_summary' => $executiveSummary,
                'key_metrics' => $keyMetrics,
                'recommendations' => $recommendations,
                'status' => 'draft',
            ]);

            // Attach department reports
            $compiledReport->departmentReports()->attach($departmentReportIds);

            // Mark department reports as compiled
            DepartmentReport::whereIn('id', $departmentReportIds)
                ->update(['status' => 'compiled']);

            DB::commit();
            return $compiledReport;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate reports can be compiled together.
     */
    private function validateReportsForCompilation(Collection $reports): void
    {
        if ($reports->isEmpty()) {
            throw new \InvalidArgumentException('No reports provided for compilation');
        }

        // Check if all reports are from the same branch
        if ($reports->pluck('branch_id')->unique()->count() > 1) {
            throw new \InvalidArgumentException('All reports must be from the same branch');
        }

        // Check if reports can be compiled (must be reviewed)
        $invalidReports = $reports->filter(fn($report) => !$report->canBeCompiled());
        if ($invalidReports->isNotEmpty()) {
            throw new \InvalidArgumentException('Some reports cannot be compiled. They must be reviewed first.');
        }
    }

    /**
     * Generate executive summary from department reports.
     */
    private function generateExecutiveSummary(Collection $reports): array
    {
        $production = $reports->where('report_category', 'production');
        $sales = $reports->where('report_category', 'sales');
        $inventory = $reports->where('report_category', 'inventory');
        $accounting = $reports->where('report_category', 'accounting');
        $hr = $reports->where('report_category', 'hr');

        return [
            'overview' => $this->generateOverview($reports),
            'production_summary' => $this->generateProductionSummary($production),
            'sales_summary' => $this->generateSalesSummary($sales),
            'inventory_summary' => $this->generateInventorySummary($inventory),
            'accounting_summary' => $this->generateAccountingSummary($accounting),
            'hr_summary' => $this->generateHRSummary($hr),
            'highlights' => $this->generateHighlights($reports),
            'concerns' => $this->generateConcerns($reports),
        ];
    }

    /**
     * Generate overview section.
     */
    private function generateOverview(Collection $reports): array
    {
        return [
            'total_reports' => $reports->count(),
            'production_reports' => $reports->where('report_category', 'production')->count(),
            'sales_reports' => $reports->where('report_category', 'sales')->count(),
            'inventory_reports' => $reports->where('report_category', 'inventory')->count(),
            'accounting_reports' => $reports->where('report_category', 'accounting')->count(),
            'hr_reports' => $reports->where('report_category', 'hr')->count(),
            'departments_covered' => $reports->pluck('department_id')->unique()->count(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate production summary.
     */
    private function generateProductionSummary(Collection $productionReports): array
    {
        if ($productionReports->isEmpty()) {
            return ['status' => 'No production reports included'];
        }

        $summary = [];

        foreach ($productionReports as $report) {
            $metrics = $report->summary_metrics ?? [];

            if ($report->report_type === 'production_efficiency') {
                $summary['efficiency'] = [
                    'overall_efficiency' => $metrics['overall_efficiency'] ?? 0,
                    'total_planned' => $metrics['total_planned'] ?? 0,
                    'total_actual' => $metrics['total_actual'] ?? 0,
                ];
            }

            if ($report->report_type === 'quality_metrics') {
                $summary['quality'] = [
                    'approval_rate' => $metrics['overall_approval_rate'] ?? 0,
                    'rejection_rate' => $metrics['overall_rejection_rate'] ?? 0,
                    'total_batches' => $metrics['total_batches'] ?? 0,
                ];
            }
        }

        return $summary;
    }

    /**
     * Generate sales summary.
     */
    private function generateSalesSummary(Collection $salesReports): array
    {
        if ($salesReports->isEmpty()) {
            return ['status' => 'No sales reports included'];
        }

        // Extract key sales metrics from reports
        return [
            'reports_count' => $salesReports->count(),
            'report_types' => $salesReports->pluck('report_type')->unique()->values()->toArray(),
        ];
    }

    /**
     * Generate inventory summary.
     */
    private function generateInventorySummary(Collection $inventoryReports): array
    {
        if ($inventoryReports->isEmpty()) {
            return ['status' => 'No inventory reports included'];
        }

        return [
            'reports_count' => $inventoryReports->count(),
            'report_types' => $inventoryReports->pluck('report_type')->unique()->values()->toArray(),
        ];
    }

    /**
     * Generate accounting summary.
     */
    private function generateAccountingSummary(Collection $accountingReports): array
    {
        if ($accountingReports->isEmpty()) {
            return ['status' => 'No accounting reports included'];
        }

        return [
            'reports_count' => $accountingReports->count(),
            'report_types' => $accountingReports->pluck('report_type')->unique()->values()->toArray(),
        ];
    }

    /**
     * Generate HR summary.
     */
    private function generateHRSummary(Collection $hrReports): array
    {
        if ($hrReports->isEmpty()) {
            return ['status' => 'No HR reports included'];
        }

        return [
            'reports_count' => $hrReports->count(),
            'report_types' => $hrReports->pluck('report_type')->unique()->values()->toArray(),
        ];
    }

    /**
     * Generate highlights.
     */
    private function generateHighlights(Collection $reports): array
    {
        $highlights = [];

        foreach ($reports as $report) {
            $metrics = $report->summary_metrics ?? [];

            // Production efficiency highlights
            if ($report->report_type === 'production_efficiency') {
                $efficiency = $metrics['overall_efficiency'] ?? 0;
                if ($efficiency >= 95) {
                    $highlights[] = [
                        'type' => 'positive',
                        'category' => 'production',
                        'message' => "Excellent production efficiency of {$efficiency}% achieved",
                        'department' => $report->department->name ?? 'Unknown',
                    ];
                }
            }

            // Quality highlights
            if ($report->report_type === 'quality_metrics') {
                $approvalRate = $metrics['overall_approval_rate'] ?? 0;
                if ($approvalRate >= 98) {
                    $highlights[] = [
                        'type' => 'positive',
                        'category' => 'production',
                        'message' => "Outstanding quality approval rate of {$approvalRate}%",
                        'department' => $report->department->name ?? 'Unknown',
                    ];
                }
            }

            // Accounting highlights
            if ($report->report_type === 'income_statement') {
                $netIncome = $metrics['net_income'] ?? 0;
                if ($netIncome > 0) {
                    $highlights[] = [
                        'type' => 'positive',
                        'category' => 'accounting',
                        'message' => 'Positive net income recorded for the period',
                        'department' => $report->department->name ?? 'N/A',
                    ];
                }
            }
        }

        return $highlights;
    }

    /**
     * Generate concerns.
     */
    private function generateConcerns(Collection $reports): array
    {
        $concerns = [];

        foreach ($reports as $report) {
            $metrics = $report->summary_metrics ?? [];

            // Production efficiency concerns
            if ($report->report_type === 'production_efficiency') {
                $efficiency = $metrics['overall_efficiency'] ?? 0;
                if ($efficiency < 80) {
                    $concerns[] = [
                        'type' => 'warning',
                        'severity' => 'high',
                        'category' => 'production',
                        'message' => "Low production efficiency of {$efficiency}% - requires attention",
                        'department' => $report->department->name ?? 'Unknown',
                    ];
                }
            }

            // Quality concerns
            if ($report->report_type === 'quality_metrics') {
                $rejectionRate = $metrics['overall_rejection_rate'] ?? 0;
                if ($rejectionRate > 10) {
                    $concerns[] = [
                        'type' => 'warning',
                        'severity' => 'high',
                        'category' => 'production',
                        'message' => "High rejection rate of {$rejectionRate}% - quality issues detected",
                        'department' => $report->department->name ?? 'Unknown',
                    ];
                }
            }

            // Accounting concerns
            if ($report->report_type === 'income_statement') {
                $netIncome = $metrics['net_income'] ?? 0;
                if ($netIncome < 0) {
                    $concerns[] = [
                        'type' => 'warning',
                        'severity' => 'high',
                        'category' => 'accounting',
                        'message' => 'Net income is negative for the period',
                        'department' => $report->department->name ?? 'N/A',
                    ];
                }
            }

            if ($report->report_type === 'balance_sheet') {
                $isBalanced = $metrics['is_balanced'] ?? true;
                if (!$isBalanced) {
                    $concerns[] = [
                        'type' => 'warning',
                        'severity' => 'high',
                        'category' => 'accounting',
                        'message' => 'Balance sheet is out of balance',
                        'department' => $report->department->name ?? 'N/A',
                    ];
                }
            }

            if ($report->report_type === 'trial_balance') {
                $isBalanced = $metrics['is_balanced'] ?? true;
                if (!$isBalanced) {
                    $concerns[] = [
                        'type' => 'warning',
                        'severity' => 'high',
                        'category' => 'accounting',
                        'message' => 'Trial balance is not balanced',
                        'department' => $report->department->name ?? 'N/A',
                    ];
                }
            }
        }

        return $concerns;
    }

    /**
     * Generate key metrics across all departments.
     */
    private function generateKeyMetrics(Collection $reports): array
    {
        return [
            'production_metrics' => $this->extractProductionMetrics($reports),
            'sales_metrics' => $this->extractSalesMetrics($reports),
            'inventory_metrics' => $this->extractInventoryMetrics($reports),
            'accounting_metrics' => $this->extractAccountingMetrics($reports),
            'hr_metrics' => $this->extractHRMetrics($reports),
            'cross_department_insights' => $this->generateCrossDepartmentInsights($reports),
        ];
    }

    /**
     * Extract production metrics.
     */
    private function extractProductionMetrics(Collection $reports): array
    {
        $productionReports = $reports->where('report_category', 'production');

        $metrics = [
            'total_reports' => $productionReports->count(),
            'departments' => $productionReports->pluck('department.name')->unique()->values()->toArray(),
        ];

        foreach ($productionReports as $report) {
            $summary = $report->summary_metrics ?? [];
            $metrics[$report->report_type] = $summary;
        }

        return $metrics;
    }

    /**
     * Extract sales metrics.
     */
    private function extractSalesMetrics(Collection $reports): array
    {
        $salesReports = $reports->where('report_category', 'sales');

        return [
            'total_reports' => $salesReports->count(),
            'departments' => $salesReports->pluck('department.name')->unique()->values()->toArray(),
        ];
    }

    /**
     * Extract inventory metrics.
     */
    private function extractInventoryMetrics(Collection $reports): array
    {
        $inventoryReports = $reports->where('report_category', 'inventory');

        return [
            'total_reports' => $inventoryReports->count(),
        ];
    }

    /**
     * Extract accounting metrics.
     */
    private function extractAccountingMetrics(Collection $reports): array
    {
        $accountingReports = $reports->where('report_category', 'accounting');

        $metrics = [
            'total_reports' => $accountingReports->count(),
            'report_types' => $accountingReports->pluck('report_type')->unique()->values()->toArray(),
        ];

        foreach ($accountingReports as $report) {
            $summary = $report->summary_metrics ?? [];
            $metrics[$report->report_type] = $summary;
        }

        return $metrics;
    }

    /**
     * Extract HR metrics.
     */
    private function extractHRMetrics(Collection $reports): array
    {
        $hrReports = $reports->where('report_category', 'hr');

        $metrics = [
            'total_reports' => $hrReports->count(),
            'report_types' => $hrReports->pluck('report_type')->unique()->values()->toArray(),
        ];

        foreach ($hrReports as $report) {
            $summary = $report->summary_metrics ?? [];
            $metrics[$report->report_type] = $summary;
        }

        return $metrics;
    }

    /**
     * Generate cross-department insights.
     */
    private function generateCrossDepartmentInsights(Collection $reports): array
    {
        // This would analyze relationships between departments
        // e.g., production efficiency vs sales performance

        return [
            'total_departments_analyzed' => $reports->pluck('department_id')->unique()->count(),
            'report_categories' => $reports->pluck('report_category')->unique()->values()->toArray(),
            'analysis_period' => [
                'earliest_from' => $reports->min('period_from'),
                'latest_to' => $reports->max('period_to'),
            ],
        ];
    }

    /**
     * Generate recommendations based on compiled data.
     */
    private function generateRecommendations(Collection $reports): array
    {
        $recommendations = [];

        // Analyze production efficiency
        $productionEfficiency = $reports->firstWhere('report_type', 'production_efficiency');
        if ($productionEfficiency) {
            $efficiency = $productionEfficiency->summary_metrics['overall_efficiency'] ?? 0;

            if ($efficiency < 85) {
                $recommendations[] = [
                    'priority' => 'high',
                    'category' => 'production',
                    'title' => 'Improve Production Efficiency',
                    'description' => 'Current efficiency of ' . $efficiency . '% is below target. Review production processes and staff training.',
                    'action_items' => [
                        'Conduct efficiency audit',
                        'Review staff scheduling',
                        'Assess equipment performance',
                    ],
                ];
            }
        }

        // Analyze quality metrics
        $qualityMetrics = $reports->firstWhere('report_type', 'quality_metrics');
        if ($qualityMetrics) {
            $rejectionRate = $qualityMetrics->summary_metrics['overall_rejection_rate'] ?? 0;

            if ($rejectionRate > 5) {
                $recommendations[] = [
                    'priority' => 'high',
                    'category' => 'production',
                    'title' => 'Address Quality Issues',
                    'description' => 'Rejection rate of ' . $rejectionRate . '% indicates quality control problems.',
                    'action_items' => [
                        'Enhance quality control procedures',
                        'Provide additional staff training',
                        'Review ingredient/material quality',
                    ],
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Send compiled report to MD.
     */
    public function sendToMD(CompiledReport $compiledReport, $mdUserId): CompiledReport
    {
        if (!$compiledReport->canBeSentToMD()) {
            throw new \InvalidArgumentException('Report must be approved before sending to MD');
        }

        $compiledReport->sendToMD($mdUserId);

        // Here you could trigger notifications, emails, etc.

        return $compiledReport->fresh();
    }

    /**
     * Approve compiled report.
     */
    public function approve(CompiledReport $compiledReport, $employeeId): CompiledReport
    {
        if (!$compiledReport->canBeApproved()) {
            throw new \InvalidArgumentException('Report cannot be approved in its current state');
        }

        // Determine the morph type based on authenticated user
        $user = auth()->user();
        $employeeType = $user instanceof \App\Models\Employee ? \App\Models\Employee::class : \App\Models\User::class;

        $compiledReport->markAsApproved($employeeId, $employeeType);

        return $compiledReport->fresh();
    }
}
