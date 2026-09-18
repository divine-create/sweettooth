<?php

namespace App\Livewire\BranchDashboard\MDReports;

use App\Models\Department;
use App\Models\ProductStock;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class OpeningStockReport extends Component
{
    public $dateFilter;
    public $selectedDepartmentId = null;

    public function mount()
    {
        $this->dateFilter = Carbon::today()->format('Y-m-d');
        
        // Find first sales department to default to
        $firstSalesDept = Department::whereHas('category', function($q) {
            $q->where('name', 'Sales');
        })->first();
        
        if ($firstSalesDept) {
            $this->selectedDepartmentId = $firstSalesDept->id;
        }
    }

    public function render()
    {
        // Get all Sales departments
        $salesDepartments = Department::whereHas('category', function($q) {
            $q->where('name', 'Sales');
        })->get();

        $stockRecords = [];
        $shiftInfo = null;
        
        if ($this->selectedDepartmentId && $this->dateFilter) {
            $selectedDate = Carbon::parse($this->dateFilter);
            
            // Get the stock entries for this department and date
            $stockRecords = ProductStock::with(['product.salesUom'])
                ->where('department_id', $this->selectedDepartmentId)
                ->whereDate('stock_date', $selectedDate)
                ->get();
                
            // Get shift info to show who entered it
            $shiftInfo = Shift::with('employee')
                ->where('department_id', $this->selectedDepartmentId)
                ->whereDate('shift_date', $selectedDate)
                ->first();
        }

        return view('livewire.branch-dashboard.m-d-reports.opening-stock-report', [
            'salesDepartments' => $salesDepartments,
            'stockRecords' => $stockRecords,
            'shiftInfo' => $shiftInfo,
        ]);
    }
}
