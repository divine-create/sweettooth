<?php

namespace App\Livewire\BranchDashboard\Production\Reports\ProductionActivities;

use App\Livewire\Traits\RequiresDepartmentSelection;
use App\Models\DailyProduce;
use App\Models\DepartmentReport;
use App\Models\ProductionRecord;
use App\Models\ProductionRequest;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Production Activities')]
class Index extends Component
{
    use RequiresDepartmentSelection;
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $periodFilter = 'week';
    public ?string $customDateFrom = null;
    public ?string $customDateTo = null;
    public ?string $shiftType = 'all';
    public ?string $requestStatus = 'all';
    public $departmentId;

    public array $reportData = [];
    public bool $isViewingSaved = false;

    public $generatedReport = null;
    public bool $showReportModal = false;
    public $savedReports = [];

    #[On('branch-changed')]
    public function handleBranchChange($branchId): void
    {
        $this->b_id = $branchId;
        $this->initDepartments($branchId);
        $this->setDateRange();
        $this->isViewingSaved = false;
    }

    public function mount(): void
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->departmentId = session('selected_department_id');
        $this->initDepartments($this->b_id);
        $this->setDateRange();
    }

    public function updatedPeriodFilter(): void
    {
        $this->setDateRange();
        $this->isViewingSaved = false;
    }

    public function updatedCustomDateFrom(): void
    {
        $this->isViewingSaved = false;
    }

    public function updatedCustomDateTo(): void
    {
        $this->isViewingSaved = false;
    }

    public function updatedShiftType(): void
    {
        $this->isViewingSaved = false;
    }

    public function updatedRequestStatus(): void
    {
        $this->isViewingSaved = false;
    }

    public function updatedSelectedDepartmentId($value): void
    {
        $this->departmentId = $value;
        session(['selected_department_id' => $value]);
        $this->isViewingSaved = false;
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
            case 'custom':
                break;
        }
    }

    private function dailyProducesQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        return DailyProduce::query()
            ->with(['recipe.product', 'shift'])
            ->whereHas('shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
            })
            ->whereBetween('produce_date', [$this->customDateFrom, $this->customDateTo]);
    }

    private function productionRecordsQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        return ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
            })
            ->whereBetween('production_time', [
                Carbon::parse($this->customDateFrom)->startOfDay(),
                Carbon::parse($this->customDateTo)->endOfDay()
            ]);
    }

    private function productionRequestsQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        $query = ProductionRequest::query()
            ->with(['recipe', 'shift', 'itemRequest'])
            ->whereHas('shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
                if ($this->customDateFrom && $this->customDateTo) {
                    $q->whereBetween('shift_date', [
                        Carbon::parse($this->customDateFrom)->startOfDay(),
                        Carbon::parse($this->customDateTo)->endOfDay()
                    ]);
                }
            });

        if ($this->requestStatus && $this->requestStatus !== 'all') {
            $query->where('fulfillment_status', $this->requestStatus);
        }

        return $query;
    }

    private function buildReportPayload(): array
    {
        $records = $this->productionRecordsQuery()
            ->orderByDesc('production_time')
            ->get();
        $requests = $this->productionRequestsQuery()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $totalProduced = $records->sum('quantity_produced');
        $totalRequested = $records->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
        $totalSentOut = $records->sum('quantity_sent_out');
        $totalVariance = $totalProduced - $totalRequested;

        $productionValue = $records->sum(function ($record) {
            $price = $record->recipe?->product?->price;
            $fallback = $record->recipe?->cost_per_unit;
            $unitValue = $price ?? $fallback ?? 0;

            return (float) $record->quantity_produced * (float) $unitValue;
        });

        $unfulfilledQuantity = $records->unique('daily_produce_id')->sum(function ($record) {
            $requested = (float) $record->dailyProduce->requested_quantity;
            $produced = (float) $record->dailyProduce->produced_quantity;
            return max(0, $requested - $produced);
        });

        $recordsPreview = $records->take(50)->map(function ($record) {
            return [
                'time' => optional($record->production_time)->format('Y-m-d H:i') ?? '-',
                'product' => $record->recipe?->product_name ?? 'Unknown',
                'produced' => (float) ($record->quantity_produced ?? 0),
                'sent_out' => (float) ($record->quantity_sent_out ?? 0),
            ];
        })->values()->toArray();

        $requestsData = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'product' => $request->recipe?->product_name ?? 'Unknown',
                'planned' => (float) ($request->planned_production_quantity ?? 0),
                'status' => $request->fulfillment_status ?? 'unknown',
            ];
        })->values()->toArray();

        $dailySummary = $records->groupBy(function ($r) {
            return $r->production_time->format('Y-m-d') . '|' . $r->recipe_id;
        })->map(function ($rows) {
            $recipe = $rows->first()->recipe;
            $planned = $rows->unique('daily_produce_id')->sum(fn($r) => $r->dailyProduce->requested_quantity);
            $produced = $rows->sum('quantity_produced');
            $sentOut = $rows->sum('quantity_sent_out');

            return [
                'date' => $rows->first()->production_time->format('Y-m-d'),
                'product' => $recipe->product_name ?? 'Unknown',
                'requested' => (float) $planned,
                'produced' => (float) $produced,
                'sent_out' => (float) $sentOut,
                'variance' => (float) ($produced - $planned),
                'is_wip' => (bool) ($recipe->is_wip ?? false),
            ];
        })->values();

        $finishedGoodsSummary = $dailySummary->where('is_wip', false)->values()->toArray();
        $wipGoodsSummary = $dailySummary->where('is_wip', true)->values()->toArray();

        $summary = [
            'total_produced' => $totalProduced,
            'total_requested' => $totalRequested,
            'total_sent_out' => $totalSentOut,
            'total_variance' => $totalVariance,
            'production_value' => $productionValue,
            'unfulfilled_quantity' => $unfulfilledQuantity,
        ];

        return [
            'report_data' => [
                'summary' => $summary,
                'production_records' => $recordsPreview,
                'production_requests' => $requestsData,
                'finished_goods_summary' => $finishedGoodsSummary,
                'wip_goods_summary' => $wipGoodsSummary,
            ],
            'summary_metrics' => $summary,
            'tables' => [
                'production_records' => [
                    'headers' => ['Time', 'Product', 'Produced', 'Sent Out'],
                    'rows' => array_map(function ($row) {
                        return [
                            $row['time'] ?? '-',
                            $row['product'] ?? 'Unknown',
                            $row['produced'] ?? 0,
                            $row['sent_out'] ?? 0,
                        ];
                    }, $recordsPreview),
                ],
                'production_requests' => [
                    'headers' => ['Request', 'Product', 'Planned', 'Status'],
                    'rows' => array_map(function ($row) {
                        return [
                            $row['id'] ?? '-',
                            $row['product'] ?? 'Unknown',
                            $row['planned'] ?? 0,
                            $row['status'] ?? 'unknown',
                        ];
                    }, $requestsData),
                ],
                'finished_goods_summary' => [
                    'headers' => ['Date', 'Produce Finished Good', 'Requested', 'Produced', 'Sent Out', 'Variance'],
                    'rows' => array_map(function ($row) {
                        return [
                            $row['date'] ?? '-',
                            $row['product'] ?? 'Unknown',
                            $row['requested'] ?? 0,
                            $row['produced'] ?? 0,
                            $row['sent_out'] ?? 0,
                            $row['variance'] ?? 0,
                        ];
                    }, $finishedGoodsSummary),
                ],
                'wip_goods_summary' => [
                    'headers' => ['Date', 'WIP Produce', 'Requested', 'Produced', 'Sent Out', 'Variance'],
                    'rows' => array_map(function ($row) {
                        return [
                            $row['date'] ?? '-',
                            $row['product'] ?? 'Unknown',
                            $row['requested'] ?? 0,
                            $row['produced'] ?? 0,
                            $row['sent_out'] ?? 0,
                            $row['variance'] ?? 0,
                        ];
                    }, $wipGoodsSummary),
                ],
            ],
            'period' => [
                'from' => $this->customDateFrom,
                'to' => $this->customDateTo,
            ],
        ];
    }


    public function generateReport(): void
    {
        if (! $this->ensureDepartmentSelected('generate')) {
            return;
        }

        $payload = $this->buildReportPayload();

        $actor = current_actor();
        $generatedById = $actor?->getKey() ?? auth()->id();
        $generatedByType = $actor ? get_class($actor) : null;
        $reportData = $payload;
        $summaryMetrics = $payload['summary_metrics'] ?? [];
        $chartsData = [];

        $this->generatedReport = DepartmentReport::create([
            'branch_id' => $this->b_id ?? current_branch_id(),
            'department_id' => $this->departmentId,
            'generated_by_id' => $generatedById,
            'generated_by_type' => $generatedByType,
            'report_type' => 'production_activities',
            'report_category' => 'production',
            'report_name' => 'Production Activities Report',
            'report_date' => now()->toDateString(),
            'period_from' => $this->customDateFrom,
            'period_to' => $this->customDateTo,
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

        $this->showReportModal = true;
        $this->toast()->success('Report generated and saved successfully')->send();
    }

    public function viewReport($reportId): void
    {
        $report = DepartmentReport::findOrFail($reportId);
        $payload = $report->report_data ?? [];

        $this->reportData = $payload['report_data'] ?? [];
        $this->customDateFrom = Carbon::parse($report->period_from)->toDateString();
        $this->customDateTo = Carbon::parse($report->period_to)->toDateString();
        $this->periodFilter = 'custom';
        $this->isViewingSaved = true;

        $this->toast()->success('Report loaded for preview')->send();
    }

    public function downloadReport($reportId)
    {
        $report = DepartmentReport::findOrFail($reportId);

        return response()->json($report->report_data, 200, [
            'Content-Disposition' => 'attachment; filename="production-activities-report-' . $report->id . '.json"'
        ]);
    }

    public function render()
    {
        $this->savedReports = DepartmentReport::where('branch_id', $this->b_id ?? current_branch_id())
            ->where('department_id', $this->departmentId)
            ->where('report_type', 'production_activities')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        if (! $this->isViewingSaved) {
            $payload = $this->buildReportPayload();
            $this->reportData = $payload['report_data'] ?? [];
        }

        return view('livewire.branch-dashboard.production.reports.production-activities.index', [
            'summary' => $this->reportData['summary'] ?? [],
            'productionRecords' => $this->reportData['production_records'] ?? [],
            'productionRequests' => $this->reportData['production_requests'] ?? [],
            'finishedGoodsSummary' => $this->reportData['finished_goods_summary'] ?? [],
            'wipGoodsSummary' => $this->reportData['wip_goods_summary'] ?? [],
        ]);
    }
}
