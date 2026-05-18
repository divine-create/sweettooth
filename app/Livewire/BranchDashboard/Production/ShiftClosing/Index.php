<?php

namespace App\Livewire\BranchDashboard\Production\ShiftClosing;

use App\Livewire\BaseComponent;
use App\Models\Shift;
use App\Models\ProductionRecord;
use App\Models\DailyProduce;
use App\Models\Branch;
use App\Models\Department;
use App\Models\ItemRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Carbon\Carbon;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    public string $departmentName = '';
    public string $branchName = '';
    public ?string $currentShiftId = null;
    public $shiftDate;
    public $shiftType = 'morning';

    // Closing data
    public array $productionSummary = [];
    public array $rawMaterialUsage = [];
    public array $materialVariances = [];
    public bool $isVerified = false;
    public $notes = '';
    public $totalMaterialEfficiency = 0;

    protected function getModelClass(): string
    {
        return Shift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function mount()
    {
        $this->mountBase();
        $this->loadBranchAndDepartment();
        $this->shiftDate = Carbon::today()->format('Y-m-d');
        $this->loadCurrentShift();
        $this->loadProductionClosingData();
    }

    protected function loadBranchAndDepartment(): void
    {
        $this->branchId = request('b_id');
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        if ($this->deptSlug) {
            $department = Department::where('slug', $this->deptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            if (!$department) {
                $department = Department::where('slug', $this->deptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
                $this->departmentName = $department->name;
            }
        }
    }

    protected function loadCurrentShift()
    {
        $employee = auth()->user();

        // Get active shift for this production department
        $activeShift = Shift::where('employee_id', $employee->id)
            ->where('department_id', $this->departmentId)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->first();

        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
            $this->shiftType = $activeShift->shift_type ?? 'morning';
        }
    }

    /**
     * Load production closing data
     *
     * TODO: Enhance this method to:
     * - Summarize all production batches created during shift
     * - Calculate total yield produced vs expected
     * - Track all raw materials used vs planned
     * - Show wastage/callbacks recorded
     * - Calculate production efficiency percentage
     * - Show products dispatched to sales departments
     * - Display quality control results
     */
    public function loadProductionClosingData()
    {
        if (!$this->currentShiftId) {
            $this->productionSummary = [];
            return;
        }

        // BASIC IMPLEMENTATION: Get production records for this shift
        $dailyProduces = DailyProduce::where('shift_id', $this->currentShiftId)
            ->with(['recipe', 'productionRecords'])
            ->get();

        $summary = [];
        foreach ($dailyProduces as $produce) {
            $totalProduced = $produce->productionRecords->sum('quantity_produced');
            $totalApproved = $produce->productionRecords->sum('quantity_approved');
            $totalRejected = $produce->productionRecords->sum('quantity_rejected');
            $totalSent = $produce->productionRecords->sum('quantity_sent_out');

            $summary[] = [
                'recipe_name' => $produce->recipe->name ?? 'N/A',
                'batches_planned' => $produce->batches_to_produce,
                'batches_produced' => $produce->productionRecords->count(),
                'total_produced' => $totalProduced,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'total_sent' => $totalSent,
                'yield_percentage' => $totalProduced > 0 ? ($totalApproved / $totalProduced) * 100 : 0,
            ];
        }

        $this->productionSummary = $summary;

        // Load raw material usage data
        $this->loadRawMaterialUsage();
    }

    /**
     * Load raw material usage data with planned vs actual comparison
     */
    public function loadRawMaterialUsage()
    {
        if (!$this->currentShiftId) {
            $this->rawMaterialUsage = [];
            $this->materialVariances = [];
            $this->totalMaterialEfficiency = 0;
            return;
        }

        $shift = Shift::find($this->currentShiftId);
        if (!$shift) return;

        // Get all daily produces for this shift
        $dailyProduces = DailyProduce::where('shift_id', $this->currentShiftId)
            ->with(['recipe.ingredients.item', 'productionRecords'])
            ->get();

        $materialUsageByItem = [];
        $totalPlannedCost = 0;
        $totalActualCost = 0;

        foreach ($dailyProduces as $produce) {
            $recipe = $produce->recipe;
            if (!$recipe) continue;

            // Calculate total batches produced
            $totalProduced = $produce->productionRecords->sum('quantity_produced');
            $batchesProduced = $totalProduced > 0 ? ceil($totalProduced / ($recipe->yield_quantity ?: 1)) : 0;

            // Calculate expected material usage based on recipe
            foreach ($recipe->ingredients as $ingredient) {
                $item = $ingredient->item;
                if (!$item) continue;

                $itemId = $item->id;
                $plannedQuantity = $ingredient->getQuantityForBatchSize($batchesProduced);
                $plannedCost = $plannedQuantity * $ingredient->cost_per_unit;

                if (!isset($materialUsageByItem[$itemId])) {
                    $materialUsageByItem[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $item->name,
                        'item_code' => $item->item_code ?? 'N/A',
                        'uom' => $ingredient->unitOfMeasure?->symbol ?? 'N/A',
                        'planned_quantity' => 0,
                        'actual_quantity' => 0,
                        'variance' => 0,
                        'planned_cost' => 0,
                        'actual_cost' => 0,
                        'cost_variance' => 0,
                        'efficiency_percentage' => 0,
                        'recipes_used_in' => [],
                    ];
                }

                $materialUsageByItem[$itemId]['planned_quantity'] += $plannedQuantity;
                $materialUsageByItem[$itemId]['planned_cost'] += $plannedCost;
                $materialUsageByItem[$itemId]['recipes_used_in'][] = $recipe->product_name;

                $totalPlannedCost += $plannedCost;
            }
        }

        // Get actual material usage from item requests for this shift
        $itemRequests = ItemRequest::where('department_id', $this->departmentId)
            ->where('shift', $this->shiftType)
            ->whereDate('request_date', $this->shiftDate)
            ->where('status', 'dispatched')
            ->with(['requestDetails.item', 'itemDispatches.dispatchDetails'])
            ->get();

        foreach ($itemRequests as $request) {
            foreach ($request->requestDetails as $detail) {
                $item = $detail->item;
                if (!$item) continue;

                $itemId = $item->id;
                $actualQuantity = $detail->quantity_dispatched ?? 0;
                $actualCost = $actualQuantity * ((float) ($item->unit_price ?? $detail->cost_per_unit ?? 0));

                if (isset($materialUsageByItem[$itemId])) {
                    $materialUsageByItem[$itemId]['actual_quantity'] += $actualQuantity;
                    $materialUsageByItem[$itemId]['actual_cost'] += $actualCost;
                    $totalActualCost += $actualCost;
                } else {
                    // Material was used but not planned (variance alert!)
                    $materialUsageByItem[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $item->name,
                        'item_code' => $item->item_code ?? 'N/A',
                        'uom' => $detail->unitOfMeasure?->symbol ?? 'N/A',
                        'planned_quantity' => 0,
                        'actual_quantity' => $actualQuantity,
                        'variance' => $actualQuantity,
                        'planned_cost' => 0,
                        'actual_cost' => $actualCost,
                        'cost_variance' => $actualCost,
                        'efficiency_percentage' => 0,
                        'recipes_used_in' => ['Unplanned Usage'],
                    ];
                    $totalActualCost += $actualCost;
                }
            }
        }

        // Calculate variances and efficiency for each item
        $significantVariances = [];
        foreach ($materialUsageByItem as $itemId => &$usage) {
            $usage['variance'] = $usage['actual_quantity'] - $usage['planned_quantity'];
            $usage['cost_variance'] = $usage['actual_cost'] - $usage['planned_cost'];

            // Calculate efficiency (100% = perfect match, >100% = waste, <100% = under-usage)
            if ($usage['planned_quantity'] > 0) {
                $usage['efficiency_percentage'] = ($usage['actual_quantity'] / $usage['planned_quantity']) * 100;
            } else {
                $usage['efficiency_percentage'] = $usage['actual_quantity'] > 0 ? 999 : 100;
            }

            // Flag significant variances (>5% or >$50 cost variance)
            $variancePercentage = $usage['planned_quantity'] > 0
                ? abs($usage['variance'] / $usage['planned_quantity']) * 100
                : 0;

            if ($variancePercentage > 5 || abs($usage['cost_variance']) > 50) {
                $significantVariances[] = [
                    'item_name' => $usage['item_name'],
                    'item_code' => $usage['item_code'],
                    'variance_quantity' => $usage['variance'],
                    'variance_percentage' => round($variancePercentage, 2),
                    'cost_variance' => $usage['cost_variance'],
                    'severity' => abs($usage['cost_variance']) > 100 ? 'high' : 'medium',
                    'type' => $usage['variance'] > 0 ? 'overuse' : 'underuse',
                ];
            }

            // Round for display
            $usage['variance'] = round($usage['variance'], 2);
            $usage['efficiency_percentage'] = round($usage['efficiency_percentage'], 2);
            $usage['planned_quantity'] = round($usage['planned_quantity'], 2);
            $usage['actual_quantity'] = round($usage['actual_quantity'], 2);
            $usage['recipes_used_in'] = array_unique($usage['recipes_used_in']);
        }

        // Calculate overall material efficiency
        $this->totalMaterialEfficiency = $totalPlannedCost > 0
            ? round(($totalActualCost / $totalPlannedCost) * 100, 2)
            : 100;

        // Sort by cost variance (highest first)
        usort($materialUsageByItem, function($a, $b) {
            return abs($b['cost_variance']) <=> abs($a['cost_variance']);
        });

        // Sort variances by severity
        usort($significantVariances, function($a, $b) {
            if ($a['severity'] !== $b['severity']) {
                return $a['severity'] === 'high' ? -1 : 1;
            }
            return abs($b['cost_variance']) <=> abs($a['cost_variance']);
        });

        $this->rawMaterialUsage = array_values($materialUsageByItem);
        $this->materialVariances = $significantVariances;
    }

    /**
     * Save production shift closing
     *
     * TODO: Implement full closing logic:
     * - Finalize all production records (lock from editing)
     * - Update raw material stocks based on actual usage
     * - Calculate shift production metrics
     * - Generate shift performance report
     * - Close the active shift
     * - Create production_shift_summary record
     * - Notify production manager
     * - Calculate employee productivity metrics
     */
    public function saveShiftClosing()
    {
        if (!$this->currentShiftId) {
            $this->toast()->error('No active shift found.')->send();
            return;
        }

        DB::beginTransaction();
        try {
            $shift = Shift::find($this->currentShiftId);
            if (!$shift) {
                throw new \Exception('Shift not found');
            }

            // 1. Prepare shift summary data
            $shiftSummary = [
                'production_summary' => $this->productionSummary,
                'raw_material_usage' => $this->rawMaterialUsage,
                'material_variances' => $this->materialVariances,
                'material_efficiency' => $this->totalMaterialEfficiency,
                'total_batches_produced' => collect($this->productionSummary)->sum('batches_produced'),
                'total_quantity_produced' => collect($this->productionSummary)->sum('total_produced'),
                'total_quantity_approved' => collect($this->productionSummary)->sum('total_approved'),
                'total_quantity_rejected' => collect($this->productionSummary)->sum('total_rejected'),
                'user_notes' => $this->notes,
                'closed_at' => now()->toDateTimeString(),
            ];

            // 2. Update shift status and store summary
            $shift->status = 'closed';
            $shift->clock_out = now();
            $shift->notes = json_encode($shiftSummary);
            $shift->save();

            // 3. Lock all ProductionRecord entries for this shift
            ProductionRecord::whereHas('dailyProduce', function($q) {
                $q->where('shift_id', $this->currentShiftId);
            })->update(['is_locked' => true]);

            // 4. Finalize DailyProduce records
            DailyProduce::where('shift_id', $this->currentShiftId)
                ->update(['status' => 'finalized']);

            // 5. Update ItemRequest status to completed
            ItemRequest::where('department_id', $this->departmentId)
                ->where('shift', $this->shiftType)
                ->whereDate('request_date', $this->shiftDate)
                ->where('status', 'dispatched')
                ->update(['status' => 'completed']);

            // TODO for next iteration:
            // - Update inventory stocks based on actual material usage
            // - Create inventory adjustment records for significant variances
            // - Generate PDF report
            // - Send notifications to production manager
            // - Calculate employee productivity metrics

            DB::commit();
            $this->toast()->success('Production shift closed successfully!')->send();
            $this->isVerified = true;

            // Reload data to reflect closed status
            $this->loadProductionClosingData();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error closing shift: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.shift-closing.index');
    }
}
