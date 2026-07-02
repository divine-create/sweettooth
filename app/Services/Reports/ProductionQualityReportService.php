<?php

namespace App\Services\Reports;

use App\Models\ProductionRecord;
use App\Models\ProductionCallback;
use Illuminate\Support\Facades\DB;

class ProductionQualityReportService extends ReportService
{
    protected string $reportCategory = 'production';
    protected string $reportType = 'quality_metrics';

    /**
     * Get report name.
     */
    protected function getReportName(): string
    {
        return 'Production Quality Metrics Report';
    }

    /**
     * Get summary metrics for report data.
     */
    public function getSummaryMetrics(array $reportData): array
    {
        return $this->generateSummaryMetrics($reportData);
    }

    /**
     * Get charts data for report data.
     */
    public function getChartsData(array $reportData): array
    {
        return $this->generateChartsData($reportData);
    }

    /**
     * Generate the quality metrics report data.
     */
    protected function generateReportData(): array
    {
        $this->validateParameters();

        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->whereBetween('production_time', [$this->periodFrom, $this->periodTo])
            ->get();

        $callbacks = ProductionCallback::query()
            ->with(['recipe', 'createdBy'])
            ->whereHas('shift', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->whereBetween('created_at', [$this->periodFrom, $this->periodTo])
            ->get();

        $reportData = [
            'quality_overview' => $this->generateQualityOverview($productionRecords),
            'rejection_analysis' => $this->generateRejectionAnalysis($productionRecords),
            'product_quality' => $this->generateProductQuality($productionRecords),
            'callback_analysis' => $this->generateCallbackAnalysis($callbacks),
            'employee_quality' => $this->generateEmployeeQuality($productionRecords),
            'trends' => $this->generateQualityTrends($productionRecords),
            'period_info' => [
                'from' => $this->periodFrom,
                'to' => $this->periodTo,
                'total_days' => \Carbon\Carbon::parse($this->periodFrom)
                    ->diffInDays(\Carbon\Carbon::parse($this->periodTo)) + 1,
            ],
        ];

        // Generate summary metrics
        $reportData['summary_metrics'] = $this->generateSummaryMetrics($reportData);

        return $reportData;
    }

    /**
     * Generate quality overview.
     */
    private function generateQualityOverview($productionRecords): array
    {
        $totalProduced = $productionRecords->sum('quantity_produced');
        $totalApproved = $productionRecords->sum('quantity_approved');
        $totalRejected = $productionRecords->sum('quantity_rejected');

        $approvedRecords = $productionRecords->where('quality_status', 'approved');
        $rejectedRecords = $productionRecords->where('quality_status', 'rejected');
        $pendingRecords = $productionRecords->where('quality_status', 'pending');

        return [
            'total_batches' => $productionRecords->count(),
            'total_produced' => $totalProduced,
            'total_approved' => $totalApproved,
            'total_rejected' => $totalRejected,
            'approval_rate' => $totalProduced > 0 ? round(($totalApproved / $totalProduced) * 100, 2) : 0,
            'rejection_rate' => $totalProduced > 0 ? round(($totalRejected / $totalProduced) * 100, 2) : 0,
            'batches_approved' => $approvedRecords->count(),
            'batches_rejected' => $rejectedRecords->count(),
            'batches_pending' => $pendingRecords->count(),
            'average_batch_size' => $productionRecords->count() > 0
                ? round($totalProduced / $productionRecords->count(), 2)
                : 0,
        ];
    }

    /**
     * Generate rejection analysis.
     */
    private function generateRejectionAnalysis($productionRecords): array
    {
        $rejectedRecords = $productionRecords->where('quantity_rejected', '>', 0);

        $rejectionReasons = $rejectedRecords->groupBy('rejection_reason')->map(function ($records, $reason) {
            return [
                'reason' => $reason ?: 'Not specified',
                'count' => $records->count(),
                'quantity_rejected' => $records->sum('quantity_rejected'),
                'percentage' => $this->calculatePercentage(
                    $records->count(),
                    $rejectedRecords->count()
                ),
            ];
        })->sortByDesc('count')->values()->toArray();

        return [
            'total_rejections' => $rejectedRecords->count(),
            'rejection_reasons' => $rejectionReasons,
            'top_rejection_reason' => !empty($rejectionReasons) ? $rejectionReasons[0] : null,
        ];
    }

    /**
     * Generate product quality analysis.
     */
    private function generateProductQuality($productionRecords): array
    {
        return $productionRecords->groupBy('recipe_id')->map(function ($recipeRecords) {
            $recipe = $recipeRecords->first()->recipe;
            $totalProduced = $recipeRecords->sum('quantity_produced');
            $totalApproved = $recipeRecords->sum('quantity_approved');
            $totalRejected = $recipeRecords->sum('quantity_rejected');

            return [
                'product_id' => $recipe->id ?? null,
                'product_name' => $recipe->product_name ?? 'Unknown Recipe',
                'total_batches' => $recipeRecords->count(),
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'approval_rate' => $totalProduced > 0 ? round(($totalApproved / $totalProduced) * 100, 2) : 0,
                'rejection_rate' => $totalProduced > 0 ? round(($totalRejected / $totalProduced) * 100, 2) : 0,
            ];
        })->sortBy('approval_rate')->values()->toArray();
    }

    /**
     * Generate callback analysis.
     */
    private function generateCallbackAnalysis($callbacks): array
    {
        $totalCallbacks = $callbacks->sum('quantity');

        $callbacksByReason = $callbacks->groupBy('reason')->map(function ($reasonCallbacks, $reason) use ($totalCallbacks) {
            return [
                'reason' => $reason ?: 'Not specified',
                'count' => $reasonCallbacks->count(),
                'quantity' => $reasonCallbacks->sum('quantity'),
                'percentage' => $this->calculatePercentage($reasonCallbacks->count(), $callbacks->count()),
            ];
        })->sortByDesc('count')->values()->toArray();

        $callbacksByRecipe = $callbacks->groupBy('recipe_id')->map(function ($recipeCallbacks) {
            $recipe = $recipeCallbacks->first()->recipe;

            return [
                'product_id' => $recipe->id ?? null,
                'product_name' => $recipe->product_name ?? 'Unknown Recipe',
                'count' => $recipeCallbacks->count(),
                'quantity' => $recipeCallbacks->sum('quantity'),
            ];
        })->sortByDesc('quantity')->take(10)->values()->toArray();

        return [
            'total_callbacks' => $callbacks->count(),
            'total_callback_quantity' => $totalCallbacks,
            'callbacks_by_reason' => $callbacksByReason,
            'top_callback_products' => $callbacksByRecipe,
        ];
    }

    /**
     * Generate employee quality performance.
     */
    private function generateEmployeeQuality($productionRecords): array
    {
        return $productionRecords->groupBy('produced_by')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->producedBy;
            $totalProduced = $employeeRecords->sum('quantity_produced');
            $totalApproved = $employeeRecords->sum('quantity_approved');
            $totalRejected = $employeeRecords->sum('quantity_rejected');

            return [
                'employee_id' => $employee->id ?? null,
                'employee_name' => $employee->name ?? 'Unknown',
                'total_batches' => $employeeRecords->count(),
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'approval_rate' => $totalProduced > 0 ? round(($totalApproved / $totalProduced) * 100, 2) : 0,
                'rejection_rate' => $totalProduced > 0 ? round(($totalRejected / $totalProduced) * 100, 2) : 0,
            ];
        })->sortByDesc('approval_rate')->values()->toArray();
    }

    /**
     * Generate quality trends.
     */
    private function generateQualityTrends($productionRecords): array
    {
        $dailyTrends = $productionRecords->groupBy(function ($record) {
            return \Carbon\Carbon::parse($record->production_time)->format('Y-m-d');
        })->map(function ($dayRecords, $date) {
            $totalProduced = $dayRecords->sum('quantity_produced');
            $totalApproved = $dayRecords->sum('quantity_approved');
            $totalRejected = $dayRecords->sum('quantity_rejected');

            return [
                'date' => $date,
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'approval_rate' => $totalProduced > 0 ? round(($totalApproved / $totalProduced) * 100, 2) : 0,
            ];
        })->values()->toArray();

        return ['daily' => $dailyTrends];
    }

    /**
     * Generate summary metrics.
     */
    protected function generateSummaryMetrics(array $reportData): array
    {
        $overview = $reportData['quality_overview'];

        return [
            'total_batches' => $overview['total_batches'],
            'overall_approval_rate' => $overview['approval_rate'],
            'overall_rejection_rate' => $overview['rejection_rate'],
            'total_callbacks' => $reportData['callback_analysis']['total_callbacks'],
            'products_analyzed' => count($reportData['product_quality']),
            'employees_tracked' => count($reportData['employee_quality']),
            'top_rejection_reason' => $reportData['rejection_analysis']['top_rejection_reason']['reason'] ?? 'N/A',
            'best_performer' => !empty($reportData['employee_quality'])
                ? $reportData['employee_quality'][0]['employee_name']
                : 'N/A',
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateChartsData(array $reportData): array
    {
        return [
            'quality_distribution' => [
                'type' => 'pie',
                'labels' => ['Approved', 'Rejected', 'Pending'],
                'data' => [
                    $reportData['quality_overview']['batches_approved'],
                    $reportData['quality_overview']['batches_rejected'],
                    $reportData['quality_overview']['batches_pending'],
                ],
                'colors' => ['#10b981', '#ef4444', '#f59e0b'],
            ],
            'rejection_reasons_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($reportData['rejection_analysis']['rejection_reasons'], 0, 5), 'reason'),
                'datasets' => [
                    [
                        'label' => 'Rejection Count',
                        'data' => array_column(array_slice($reportData['rejection_analysis']['rejection_reasons'], 0, 5), 'count'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
            'daily_approval_trend' => [
                'type' => 'line',
                'labels' => array_column($reportData['trends']['daily'], 'date'),
                'datasets' => [
                    [
                        'label' => 'Approval Rate %',
                        'data' => array_column($reportData['trends']['daily'], 'approval_rate'),
                        'color' => '#10b981',
                    ],
                ],
            ],
        ];
    }
}
