<?php

namespace App\Services\Reports;

use App\Models\ProductionRecord;
use App\Models\ProductionCallback;
use Illuminate\Support\Facades\DB;

class WasteAnalysisReportService extends ReportService
{
    protected string $reportCategory = 'production';
    protected string $reportType = 'waste_analysis';

    /**
     * Get report name.
     */
    protected function getReportName(): string
    {
        return 'Waste Analysis Report';
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
     * Generate the waste analysis report data.
     */
    protected function generateReportData(): array
    {
        $this->validateParameters();

        // Get production rejections (waste during production)
        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->where('quantity_rejected', '>', 0)
            ->whereBetween('production_time', [$this->periodFrom, $this->periodTo])
            ->get();

        // Get callbacks (waste after production - raw materials and finished products)
        $callbacks = ProductionCallback::query()
            ->with(['shift', 'item', 'product', 'recordedBy'])
            ->whereHas('shift', function ($q) {
                $q->where('branch_id', $this->branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
            })
            ->whereBetween('callback_time', [$this->periodFrom, $this->periodTo])
            ->get();

        $reportData = [
            'waste_overview' => $this->generateWasteOverview($productionRecords, $callbacks),
            'production_waste' => $this->generateProductionWaste($productionRecords),
            'callback_waste' => $this->generateCallbackWaste($callbacks),
            'waste_by_reason' => $this->generateWasteByReason($productionRecords, $callbacks),
            'waste_by_product' => $this->generateWasteByProduct($productionRecords, $callbacks),
            'daily_waste_trends' => $this->generateDailyWasteTrends($productionRecords, $callbacks),
            'employee_waste_analysis' => $this->generateEmployeeWasteAnalysis($productionRecords),
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
     * Generate waste overview.
     */
    private function generateWasteOverview($productionRecords, $callbacks): array
    {
        $totalProductionWaste = $productionRecords->sum('quantity_rejected');
        $totalCallbackWaste = $callbacks->sum('quantity');
        $totalWaste = $totalProductionWaste + $totalCallbackWaste;

        $rawMaterialCallbacks = $callbacks->where('source_type', 'raw_material_from_stock');
        $finishedProductCallbacks = $callbacks->where('source_type', 'finished_product_reject');

        return [
            'total_waste_quantity' => $totalWaste,
            'production_rejection_waste' => $totalProductionWaste,
            'callback_waste' => $totalCallbackWaste,
            'raw_material_waste' => $rawMaterialCallbacks->sum('quantity'),
            'finished_product_waste' => $finishedProductCallbacks->sum('quantity') + $totalProductionWaste,
            'production_waste_percentage' => $totalWaste > 0
                ? round(($totalProductionWaste / $totalWaste) * 100, 2)
                : 0,
            'callback_waste_percentage' => $totalWaste > 0
                ? round(($totalCallbackWaste / $totalWaste) * 100, 2)
                : 0,
            'total_waste_incidents' => $productionRecords->count() + $callbacks->count(),
            'production_rejection_incidents' => $productionRecords->count(),
            'callback_incidents' => $callbacks->count(),
        ];
    }

    /**
     * Generate production waste details.
     */
    private function generateProductionWaste($productionRecords): array
    {
        return $productionRecords->map(function ($record) {
            return [
                'date' => \Carbon\Carbon::parse($record->production_time)->format('Y-m-d'),
                'time' => \Carbon\Carbon::parse($record->production_time)->format('H:i'),
                'product' => $record->recipe->name ?? 'Unknown',
                'quantity_rejected' => $record->quantity_rejected,
                'reason' => $record->rejection_reason ?? 'Not specified',
                'quality_status' => $record->quality_status,
                'produced_by' => $record->producedBy->name ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Generate callback waste details.
     */
    private function generateCallbackWaste($callbacks): array
    {
        return $callbacks->map(function ($callback) {
            return [
                'date' => \Carbon\Carbon::parse($callback->callback_time)->format('Y-m-d'),
                'time' => \Carbon\Carbon::parse($callback->callback_time)->format('H:i'),
                'type' => $callback->source_type,
                'item' => $callback->item_name,
                'quantity' => $callback->quantity,
                'uom' => $callback->uom,
                'reason' => $callback->formatted_reason,
                'status' => $callback->formatted_status,
                'recorded_by' => $callback->recordedBy->name ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Generate waste analysis by reason.
     */
    private function generateWasteByReason($productionRecords, $callbacks): array
    {
        $reasons = [];

        // Group production rejections by reason
        foreach ($productionRecords->groupBy('rejection_reason') as $reason => $records) {
            $reasonKey = $reason ?: 'Not specified';
            if (!isset($reasons[$reasonKey])) {
                $reasons[$reasonKey] = [
                    'reason' => $reasonKey,
                    'count' => 0,
                    'quantity' => 0,
                ];
            }
            $reasons[$reasonKey]['count'] += $records->count();
            $reasons[$reasonKey]['quantity'] += $records->sum('quantity_rejected');
        }

        // Group callbacks by reason
        foreach ($callbacks->groupBy('reason') as $reason => $cbRecords) {
            $reasonKey = ucwords(str_replace('_', ' ', $reason ?: 'Not specified'));
            if (!isset($reasons[$reasonKey])) {
                $reasons[$reasonKey] = [
                    'reason' => $reasonKey,
                    'count' => 0,
                    'quantity' => 0,
                ];
            }
            $reasons[$reasonKey]['count'] += $cbRecords->count();
            $reasons[$reasonKey]['quantity'] += $cbRecords->sum('quantity');
        }

        // Sort by quantity descending
        usort($reasons, function ($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });

        return $reasons;
    }

    /**
     * Generate waste by product.
     */
    private function generateWasteByProduct($productionRecords, $callbacks): array
    {
        $products = [];

        // Group production rejections by product
        foreach ($productionRecords->groupBy('recipe_id') as $recipeId => $records) {
            $recipe = $records->first()->recipe;
            $productName = $recipe->product_name ?? 'Unknown';

            if (!isset($products[$productName])) {
                $products[$productName] = [
                    'product' => $productName,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }

            $products[$productName]['production_waste'] += $records->sum('quantity_rejected');
            $products[$productName]['incidents'] += $records->count();
        }

        // Group callbacks by product (finished products only)
        $finishedProductCallbacks = $callbacks->where('source_type', 'finished_product_reject');
        foreach ($finishedProductCallbacks->groupBy('product_id') as $productId => $cbRecords) {
            $product = $cbRecords->first()->product;
            $productName = $product->name ?? 'Unknown';

            if (!isset($products[$productName])) {
                $products[$productName] = [
                    'product' => $productName,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }

            $products[$productName]['callback_waste'] += $cbRecords->sum('quantity');
            $products[$productName]['incidents'] += $cbRecords->count();
        }

        // Calculate totals and sort
        $result = array_values($products);
        foreach ($result as &$product) {
            $product['total_waste'] = $product['production_waste'] + $product['callback_waste'];
        }

        usort($result, function ($a, $b) {
            return $b['total_waste'] <=> $a['total_waste'];
        });

        return $result;
    }

    /**
     * Generate daily waste trends.
     */
    private function generateDailyWasteTrends($productionRecords, $callbacks): array
    {
        $dailyData = [];

        // Group production waste by date
        foreach ($productionRecords->groupBy(function ($record) {
            return \Carbon\Carbon::parse($record->production_time)->format('Y-m-d');
        }) as $date => $records) {
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }
            $dailyData[$date]['production_waste'] += $records->sum('quantity_rejected');
            $dailyData[$date]['incidents'] += $records->count();
        }

        // Group callbacks by date
        foreach ($callbacks->groupBy(function ($callback) {
            return \Carbon\Carbon::parse($callback->callback_time)->format('Y-m-d');
        }) as $date => $cbRecords) {
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'production_waste' => 0,
                    'callback_waste' => 0,
                    'total_waste' => 0,
                    'incidents' => 0,
                ];
            }
            $dailyData[$date]['callback_waste'] += $cbRecords->sum('quantity');
            $dailyData[$date]['incidents'] += $cbRecords->count();
        }

        // Calculate totals and sort by date
        $result = array_values($dailyData);
        foreach ($result as &$day) {
            $day['total_waste'] = $day['production_waste'] + $day['callback_waste'];
        }

        usort($result, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $result;
    }

    /**
     * Generate employee waste analysis.
     */
    private function generateEmployeeWasteAnalysis($productionRecords): array
    {
        return $productionRecords->groupBy('produced_by')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->producedBy;
            $totalProduced = $employeeRecords->sum('quantity_produced');
            $totalRejected = $employeeRecords->sum('quantity_rejected');

            return [
                'employee_id' => $employee->id ?? null,
                'employee_name' => $employee->name ?? 'Unknown',
                'total_produced' => $totalProduced,
                'total_rejected' => $totalRejected,
                'rejection_rate' => $totalProduced > 0
                    ? round(($totalRejected / $totalProduced) * 100, 2)
                    : 0,
                'incidents' => $employeeRecords->count(),
            ];
        })->sortByDesc('total_rejected')->values()->toArray();
    }

    /**
     * Generate summary metrics.
     */
    protected function generateSummaryMetrics(array $reportData): array
    {
        $overview = $reportData['waste_overview'];
        $trends = collect($reportData['daily_waste_trends']);

        return [
            'total_waste' => $overview['total_waste_quantity'],
            'production_waste' => $overview['production_rejection_waste'],
            'callback_waste' => $overview['callback_waste'],
            'total_incidents' => $overview['total_waste_incidents'],
            'average_daily_waste' => $trends->avg('total_waste'),
            'highest_waste_day' => $trends->sortByDesc('total_waste')->first(),
            'lowest_waste_day' => $trends->sortBy('total_waste')->where('total_waste', '>', 0)->first(),
            'top_waste_reason' => !empty($reportData['waste_by_reason'])
                ? $reportData['waste_by_reason'][0]['reason']
                : 'N/A',
            'top_waste_product' => !empty($reportData['waste_by_product'])
                ? $reportData['waste_by_product'][0]['product']
                : 'N/A',
        ];
    }

    /**
     * Generate charts data.
     */
    protected function generateChartsData(array $reportData): array
    {
        $trends = $reportData['daily_waste_trends'];
        $byReason = array_slice($reportData['waste_by_reason'], 0, 5);
        $byProduct = array_slice($reportData['waste_by_product'], 0, 10);

        return [
            'daily_waste_chart' => [
                'type' => 'line',
                'labels' => array_column($trends, 'date'),
                'datasets' => [
                    [
                        'label' => 'Production Waste',
                        'data' => array_column($trends, 'production_waste'),
                        'color' => '#ef4444',
                    ],
                    [
                        'label' => 'Callback Waste',
                        'data' => array_column($trends, 'callback_waste'),
                        'color' => '#f59e0b',
                    ],
                ],
            ],
            'waste_by_reason_chart' => [
                'type' => 'pie',
                'labels' => array_column($byReason, 'reason'),
                'data' => array_column($byReason, 'quantity'),
                'colors' => ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e'],
            ],
            'waste_by_product_chart' => [
                'type' => 'bar',
                'labels' => array_column($byProduct, 'product'),
                'datasets' => [
                    [
                        'label' => 'Total Waste',
                        'data' => array_column($byProduct, 'total_waste'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
            'waste_type_distribution' => [
                'type' => 'doughnut',
                'labels' => ['Production Rejection', 'Raw Material Callback', 'Finished Product Callback'],
                'data' => [
                    $reportData['waste_overview']['production_rejection_waste'],
                    $reportData['waste_overview']['raw_material_waste'],
                    $reportData['waste_overview']['callback_waste'] - $reportData['waste_overview']['raw_material_waste'],
                ],
                'colors' => ['#ef4444', '#f59e0b', '#eab308'],
            ],
        ];
    }
}
