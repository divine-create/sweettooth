<?php

namespace App\Livewire\BranchDashboard\Production\Reports\IngredientUtilization;

use App\Livewire\Traits\RequiresDepartmentSelection;
use App\Models\DepartmentReport;
use App\Models\RawMaterialUtilization;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Ingredient Utilization Report')]
class Index extends Component
{
    use RequiresDepartmentSelection;
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $periodFilter = 'week';
    public ?string $customDateFrom = null;
    public ?string $customDateTo = null;
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
        $this->departmentCategoryFilter = 'Production';
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

    private function utilizationQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        return RawMaterialUtilization::query()
            ->with(['item', 'recipe', 'shift'])
            ->whereHas('shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->customDateFrom && $this->customDateTo) {
                    $q->whereBetween('shift_date', [
                        Carbon::parse($this->customDateFrom)->startOfDay(),
                        Carbon::parse($this->customDateTo)->endOfDay()
                    ]);
                }
            });
    }

    private function buildReportPayload(): array
    {
        $utilizations = $this->utilizationQuery()->get();

        $allRows = $utilizations
            ->groupBy(function ($row) {
                return ($row->item_id ?? 'unknown') . '|' . ($row->recipe_id ?? 'unknown');
            })
            ->map(function ($items) {
                $recipe = $items->first()->recipe;
                $required = $items->sum('quantity_required');
                $used = $items->sum('quantity_used');
                $variance = $items->sum('variance');
                $unitsProduced = $items->sum('units_produced');
                $costImpact = $items->sum('cost_impact');
                $types = $items->pluck('variance_type')->filter()->unique()->values();

                return [
                    'item' => $items->first()->item?->name ?? 'Unknown',
                    'recipe' => $recipe?->product_name ?? 'Unknown',
                    'required' => $required,
                    'used' => $used,
                    'variance' => $variance,
                    'variance_type' => $types->count() === 1 ? $types->first() : ($types->isEmpty() ? 'n/a' : 'mixed'),
                    'units_produced' => $unitsProduced,
                    'cost_impact' => $costImpact,
                    'is_wip' => (bool) ($recipe?->is_wip ?? false),
                ];
            })
            ->sortByDesc('cost_impact')
            ->values();

        $finishedRows = $allRows->where('is_wip', false)->values();
        $wipRows = $allRows->where('is_wip', true)->values();

        $summary = [
            'total_required' => $allRows->sum('required'),
            'total_used' => $allRows->sum('used'),
            'total_variance' => $allRows->sum('variance'),
            'total_cost_impact' => $allRows->sum('cost_impact'),
        ];

        return [
            'report_data' => [
                'finished_rows' => $finishedRows->toArray(),
                'wip_rows' => $wipRows->toArray(),
                'summary' => $summary,
            ],
            'summary_metrics' => $summary,
            'tables' => [
                'finished_utilization' => [
                    'headers' => [
                        'Produce Finished Good',
                        'Ingredient',
                        'Required',
                        'Used',
                        'Variance',
                        'Variance Type',
                        'Units Produced',
                        'Cost Impact',
                    ],
                    'rows' => $finishedRows->map(function ($row) {
                        return [
                            $row['recipe'],
                            $row['item'],
                            $row['required'],
                            $row['used'],
                            $row['variance'],
                            $row['variance_type'],
                            $row['units_produced'],
                            $row['cost_impact'],
                        ];
                    })->values()->toArray(),
                ],
                'wip_utilization' => [
                    'headers' => [
                        'WIP Produce',
                        'Ingredient',
                        'Required',
                        'Used',
                        'Variance',
                        'Variance Type',
                        'Units Produced',
                        'Cost Impact',
                    ],
                    'rows' => $wipRows->map(function ($row) {
                        return [
                            $row['recipe'],
                            $row['item'],
                            $row['required'],
                            $row['used'],
                            $row['variance'],
                            $row['variance_type'],
                            $row['units_produced'],
                            $row['cost_impact'],
                        ];
                    })->values()->toArray(),
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
            'report_type' => 'ingredient_utilization',
            'report_category' => 'production',
            'report_name' => 'Ingredient Utilization Report',
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
            'Content-Disposition' => 'attachment; filename="ingredient-utilization-report-' . $report->id . '.json"'
        ]);
    }

    public function render()
    {
        $this->savedReports = DepartmentReport::where('branch_id', $this->b_id ?? current_branch_id())
            ->where('department_id', $this->departmentId)
            ->where('report_type', 'ingredient_utilization')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        if (! $this->isViewingSaved) {
            $payload = $this->buildReportPayload();
            $this->reportData = $payload['report_data'] ?? [];
        }

        return view('livewire.branch-dashboard.production.reports.ingredient-utilization.index', [
            'rows' => $this->reportData['rows'] ?? [],
            'summary' => $this->reportData['summary'] ?? [],
        ]);
    }
}
