<?php

namespace App\Livewire\BranchDashboard\MDReports;

use App\Models\DailyProduce;
use App\Models\Department;
use App\Models\ProductStock;
use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\Shift;
use App\Services\ProductionStockService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class OpeningStockReport extends Component
{
    public $dateFilter;
    public $selectedDepartmentId = null;
    public $selectedShiftType = 'all';

    public function mount()
    {
        $this->dateFilter = Carbon::today()->format('Y-m-d');

        // Find default department (prefer Till Sales, then any Sales or Production)
        $defaultDept = Department::where('name', 'like', '%Till%')
            ->whereHas('category', function($q) {
                $q->where('name', 'Sales');
            })->first()
            ?? Department::whereHas('category', function($q) {
                $q->whereIn('name', ['Sales', 'Production']);
            })->first();

        if ($defaultDept) {
            $this->selectedDepartmentId = $defaultDept->id;
        }
    }

    public function render()
    {
        // Get all Sales & Production departments
        $departments = Department::with('category')
            ->whereHas('category', function($q) {
                $q->whereIn('name', ['Sales', 'Production']);
            })
            ->orderBy('name')
            ->get();

        if (!$this->selectedDepartmentId || !$departments->contains('id', $this->selectedDepartmentId)) {
            $this->selectedDepartmentId = $departments->first()?->id;
        }

        $stockRecords = collect();
        $dailyProduceRecords = collect();
        $shiftsInfo = collect();
        $isProduction = false;

        $selectedDept = $departments->firstWhere('id', $this->selectedDepartmentId);
        if ($selectedDept && strtolower($selectedDept->category?->name ?? '') === 'production') {
            $isProduction = true;
        }

        if ($this->selectedDepartmentId && $this->dateFilter) {
            $selectedDate = Carbon::parse($this->dateFilter);

            if ($isProduction) {
                $store = ProductionStore::where('department_id', $this->selectedDepartmentId)->first();
                if ($store) {
                    $query = ProductionStoreDailyStock::with(['item.unitOfMeasure', 'product.unitOfMeasure', 'shift.employee', 'verifier'])
                        ->where('store_id', $store->id)
                        ->whereDate('stock_date', $selectedDate);

                    if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                        $query->where(function ($q) {
                            $q->whereHas('shift', fn($sq) => $sq->where('shift_type', $this->selectedShiftType))
                              ->orWhereNull('shift_id');
                        });
                    }

                    $stockRecords = $query->get();

                    // If no daily stock records exist yet for this production store today, load baseline expected opening stock
                    if ($stockRecords->isEmpty() && $selectedDate->isToday()) {
                        $stockService = app(ProductionStockService::class);
                        $stockService->ensureBaselineDailyStock($store, $selectedDept, null, $selectedDate->toDateString());
                        $stockRecords = ProductionStoreDailyStock::with(['item.unitOfMeasure', 'product.unitOfMeasure', 'shift.employee', 'verifier'])
                            ->where('store_id', $store->id)
                            ->whereDate('stock_date', $selectedDate)
                            ->get();
                    }
                }

                // Query DailyProduce (recipes, batches, finished goods produced/opened for this department)
                $dpQuery = DailyProduce::with(['recipe.product.unitOfMeasure', 'shift.employee'])
                    ->whereDate('produce_date', $selectedDate)
                    ->whereHas('recipe', fn($rq) => $rq->where('department_id', $this->selectedDepartmentId)
                        ->orWhereHas('product', fn($pq) => $pq->where('department_id', $this->selectedDepartmentId)
                            ->orWhereHas('departments', fn($dq) => $dq->where('department_id', $this->selectedDepartmentId))
                        )
                    );

                if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                    $dpQuery->where(function ($q) {
                        $q->where('shift_type', $this->selectedShiftType)
                          ->orWhereHas('shift', fn($sq) => $sq->where('shift_type', $this->selectedShiftType));
                    });
                }

                $dailyProduceRecords = $dpQuery->get();

                // If any DailyProduce has opening_quantity <= 0, sync from stockRecords if physical count was recorded
                if ($dailyProduceRecords->isNotEmpty() && $stockRecords->isNotEmpty()) {
                    foreach ($dailyProduceRecords as $dp) {
                        if ((float) $dp->opening_quantity <= 0 && $dp->recipe?->product_id) {
                            $matchingStock = $stockRecords->firstWhere('item_id', (string) $dp->recipe->product_id);
                            if ($matchingStock && (float) $matchingStock->opening_quantity > 0) {
                                $dp->opening_quantity = (float) $matchingStock->opening_quantity;
                            }
                        }
                    }
                }

            } else {
                // Get the stock entries for sales department and date
                $query = ProductStock::with(['product.unitOfMeasure', 'shift.employee'])
                    ->where('department_id', $this->selectedDepartmentId)
                    ->whereDate('stock_date', $selectedDate);

                if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                    $query->where('shift_type', $this->selectedShiftType);
                }

                $stockRecords = $query->orderBy('shift_type')->get();
            }

            // Get shift info to show who entered / verified it
            $shiftQuery = Shift::with('employee')
                ->where('department_id', $this->selectedDepartmentId)
                ->whereDate('shift_date', $selectedDate);

            if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                $shiftQuery->where('shift_type', $this->selectedShiftType);
            }

            $shiftsInfo = $shiftQuery->get();

            // If shiftsInfo is empty for production, check verifiers recorded on stock records
            if ($shiftsInfo->isEmpty() && $isProduction && $stockRecords->isNotEmpty()) {
                $verifierIds = $stockRecords->pluck('verified_by')->filter()->unique()->values()->all();
                if (!empty($verifierIds)) {
                    $verifiers = \App\Models\User::whereIn('id', $verifierIds)->get();
                    foreach ($verifiers as $v) {
                        $shiftsInfo->push((object)[
                            'shift_type' => 'verified',
                            'employee' => $v,
                            'stock_verified_at' => $stockRecords->firstWhere('verified_by', $v->id)?->verified_at ?? now(),
                        ]);
                    }
                }
            }
        }

        return view('livewire.branch-dashboard.m-d-reports.opening-stock-report', [
            'departments' => $departments,
            'selectedDepartmentId' => $this->selectedDepartmentId,
            'selectedDept' => $selectedDept,
            'isProduction' => $isProduction,
            'stockRecords' => $stockRecords,
            'dailyProduceRecords' => $dailyProduceRecords,
            'shiftsInfo' => $shiftsInfo,
            'dateFilter' => $this->dateFilter,
            'selectedShiftType' => $this->selectedShiftType,
        ]);
    }
}
