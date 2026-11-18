<?php

namespace App\Livewire\BranchDashboard\MDReports;

use App\Models\Department;
use App\Models\ProductStock;
use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class ClosingStockReport extends Component
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

        $stockRecords = [];
        $shiftsInfo = [];
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
                    $query = ProductionStoreDailyStock::with(['item.unitOfMeasure', 'product.unitOfMeasure', 'shift.employee'])
                        ->where('store_id', $store->id)
                        ->whereDate('stock_date', $selectedDate);

                    if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                        $query->whereHas('shift', fn($q) => $q->where('shift_type', $this->selectedShiftType));
                    }

                    $stockRecords = $query->get();
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

            // Get shift info to show who entered it
            $shiftQuery = Shift::with('employee')
                ->where('department_id', $this->selectedDepartmentId)
                ->whereDate('shift_date', $selectedDate);

            if ($this->selectedShiftType && $this->selectedShiftType !== 'all') {
                $shiftQuery->where('shift_type', $this->selectedShiftType);
            }

            $shiftsInfo = $shiftQuery->get();
        }

        return view('livewire.branch-dashboard.m-d-reports.closing-stock-report', [
            'departments' => $departments,
            'selectedDept' => $selectedDept,
            'isProduction' => $isProduction,
            'stockRecords' => $stockRecords,
            'shiftsInfo' => $shiftsInfo,
        ]);
    }
}
