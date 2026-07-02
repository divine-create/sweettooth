<?php

namespace App\Services\Reports\Definitions;

use App\Models\ProductionRecord;
use App\Models\ProductionCallback;
use Carbon\Carbon;

class ProductionQualityDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Production Quality Metrics Report',
            'type' => 'quality_metrics',
            'category' => 'production',
            'order' => 3,
            'requires_department' => true,
            'permissions' => ['view-reports', 'generate-reports'],
        ];
    }

    public function query(array $context): array
    {
        $productionRecords = ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('production_time', [
                Carbon::parse($context['period_from'])->startOfDay(),
                Carbon::parse($context['period_to'])->endOfDay()
            ])
            ->get();

        $callbacks = ProductionCallback::query()
            ->with(['recipe']) // 'createdBy' does not exist on ProductionCallback (throws RelationNotFound); it was never used downstream anyway
            ->whereHas('shift', function ($q) use ($context) {
                $q->where('branch_id', $context['branch_id']);
                if ($context['department_id']) {
                    $q->where('department_id', $context['department_id']);
                }
            })
            ->whereBetween('created_at', [
                Carbon::parse($context['period_from'])->startOfDay(),
                Carbon::parse($context['period_to'])->endOfDay()
            ])
            ->get();

        $periodDays = Carbon::parse($context['period_from'])
            ->diffInDays(Carbon::parse($context['period_to'])) + 1;

        $finishedProduces = $productionRecords->filter(fn($p) => !($p->recipe?->is_wip ?? false));
        $wipProduces = $productionRecords->filter(fn($p) => (bool)($p->recipe?->is_wip ?? false));

        return [
            'quality_overview' => $this->buildQualityOverview($productionRecords),
            'rejection_analysis' => $this->buildRejectionAnalysis($productionRecords),
            'finished_quality' => $this->buildProductQuality($finishedProduces),
            'wip_quality' => $this->buildProductQuality($wipProduces),
            'callback_analysis' => $this->buildCallbackAnalysis($callbacks),
            'employee_quality' => $this->buildEmployeeQuality($productionRecords),
            'trends' => $this->buildQualityTrends($productionRecords),
            'period_info' => [
                'from' => $context['period_from'],
                'to' => $context['period_to'],
                'total_days' => $periodDays,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $overview = $data['quality_overview'] ?? [];

        return [
            'total_batches' => $overview['total_batches'] ?? 0,
            'overall_approval_rate' => $overview['approval_rate'] ?? 0,
            'overall_rejection_rate' => $overview['rejection_rate'] ?? 0,
            'total_callbacks' => $data['callback_analysis']['total_callbacks'] ?? 0,
            'finished_analyzed' => count($data['finished_quality'] ?? []),
            'wip_analyzed' => count($data['wip_quality'] ?? []),
            'employees_tracked' => count($data['employee_quality'] ?? []),
            'top_rejection_reason' => $data['rejection_analysis']['top_rejection_reason']['reason'] ?? 'N/A',
            'best_performer' => !empty($data['employee_quality'])
                ? $data['employee_quality'][0]['employee_name']
                : 'N/A',
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [
            'quality_distribution' => [
                'type' => 'pie',
                'labels' => ['Approved', 'Rejected', 'Pending'],
                'data' => [
                    $data['quality_overview']['batches_approved'] ?? 0,
                    $data['quality_overview']['batches_rejected'] ?? 0,
                    $data['quality_overview']['batches_pending'] ?? 0,
                ],
                'colors' => ['#10b981', '#ef4444', '#f59e0b'],
            ],
            'rejection_reasons_chart' => [
                'type' => 'bar',
                'labels' => array_column(array_slice($data['rejection_analysis']['rejection_reasons'] ?? [], 0, 5), 'reason'),
                'datasets' => [
                    [
                        'label' => 'Rejection Count',
                        'data' => array_column(array_slice($data['rejection_analysis']['rejection_reasons'] ?? [], 0, 5), 'count'),
                        'color' => '#ef4444',
                    ],
                ],
            ],
            'daily_approval_trend' => [
                'type' => 'line',
                'labels' => array_column($data['trends']['daily'] ?? [], 'date'),
                'datasets' => [
                    [
                        'label' => 'Approval Rate %',
                        'data' => array_column($data['trends']['daily'] ?? [], 'approval_rate'),
                        'color' => '#10b981',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'finished_quality' => [
                'headers' => ['Produce Finished Good', 'Batches', 'Produced', 'Approved', 'Rejected', 'Approval %', 'Rejection %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product_name'] ?? '-',
                        $row['total_batches'] ?? 0,
                        $row['total_produced'] ?? 0,
                        $row['total_approved'] ?? 0,
                        $row['total_rejected'] ?? 0,
                        $row['approval_rate'] ?? 0,
                        $row['rejection_rate'] ?? 0,
                    ];
                }, $data['finished_quality'] ?? []),
            ],
            'wip_quality' => [
                'headers' => ['WIP Produce', 'Batches', 'Produced', 'Approved', 'Rejected', 'Approval %', 'Rejection %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['product_name'] ?? '-',
                        $row['total_batches'] ?? 0,
                        $row['total_produced'] ?? 0,
                        $row['total_approved'] ?? 0,
                        $row['total_rejected'] ?? 0,
                        $row['approval_rate'] ?? 0,
                        $row['rejection_rate'] ?? 0,
                    ];
                }, $data['wip_quality'] ?? []),
            ],
            'employee_quality' => [
                'headers' => ['Employee', 'Batches', 'Produced', 'Approved', 'Rejected', 'Approval %', 'Rejection %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['employee_name'] ?? '-',
                        $row['total_batches'] ?? 0,
                        $row['total_produced'] ?? 0,
                        $row['total_approved'] ?? 0,
                        $row['total_rejected'] ?? 0,
                        $row['approval_rate'] ?? 0,
                        $row['rejection_rate'] ?? 0,
                    ];
                }, $data['employee_quality'] ?? []),
            ],
            'rejection_reasons' => [
                'headers' => ['Reason', 'Count', 'Qty Rejected', 'Share %'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['reason'] ?? '-',
                        $row['count'] ?? 0,
                        $row['quantity_rejected'] ?? 0,
                        $row['percentage'] ?? 0,
                    ];
                }, $data['rejection_analysis']['rejection_reasons'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        $approval = $summary['overall_approval_rate'] ?? 0;
        $rejection = $summary['overall_rejection_rate'] ?? 0;

        if ($approval >= 98) {
            $highlights[] = "Excellent approval rate at {$approval}%.";
        } elseif ($approval < 90) {
            $concerns[] = "Approval rate is low at {$approval}%.";
        }

        if ($rejection > 5) {
            $concerns[] = "Rejection rate is high at {$rejection}% - quality issues detected.";
        }

        $topReason = $summary['top_rejection_reason'] ?? null;
        if ($topReason && $topReason !== 'N/A') {
            $concerns[] = "Top rejection reason: {$topReason}.";
        }

        return [
            'overview' => "Quality approval averaged {$approval}% from {$context['period_from']} to {$context['period_to']}.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $this->buildRecommendations($summary),
        ];
    }

    private function buildRecommendations(array $summary): array
    {
        $recommendations = [];

        if (($summary['overall_approval_rate'] ?? 0) < 95) {
            $recommendations[] = 'Review quality checkpoints and retrain staff on critical steps.';
        }

        if (($summary['overall_rejection_rate'] ?? 0) > 5) {
            $recommendations[] = 'Investigate rejection causes and tighten QA inspections.';
        }

        return $recommendations;
    }

    private function buildQualityOverview($productionRecords): array
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

    private function buildRejectionAnalysis($productionRecords): array
    {
        $rejectedRecords = $productionRecords->where('quantity_rejected', '>', 0);

        $rejectionReasons = $rejectedRecords->groupBy('rejection_reason')->map(function ($records, $reason) use ($rejectedRecords) {
            return [
                'reason' => $reason ?: 'Not specified',
                'count' => $records->count(),
                'quantity_rejected' => $records->sum('quantity_rejected'),
                'percentage' => $this->percent($records->count(), $rejectedRecords->count()),
            ];
        })->sortByDesc('count')->values()->toArray();

        return [
            'total_rejections' => $rejectedRecords->count(),
            'rejection_reasons' => $rejectionReasons,
            'top_rejection_reason' => !empty($rejectionReasons) ? $rejectionReasons[0] : null,
        ];
    }

    private function buildProductQuality($productionRecords): array
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

    private function buildCallbackAnalysis($callbacks): array
    {
        $totalCallbacks = $callbacks->sum('quantity');

        $callbacksByReason = $callbacks->groupBy('reason')->map(function ($reasonCallbacks, $reason) use ($callbacks) {
            return [
                'reason' => $reason ?: 'Not specified',
                'count' => $reasonCallbacks->count(),
                'quantity' => $reasonCallbacks->sum('quantity'),
                'percentage' => $this->percent($reasonCallbacks->count(), $callbacks->count()),
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

    private function buildEmployeeQuality($productionRecords): array
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

    private function buildQualityTrends($productionRecords): array
    {
        $dailyTrends = $productionRecords->groupBy(function ($record) {
            return Carbon::parse($record->production_time)->format('Y-m-d');
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

    private function percent($part, $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }
}
