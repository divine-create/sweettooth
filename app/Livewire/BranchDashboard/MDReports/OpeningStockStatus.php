<?php

namespace App\Livewire\BranchDashboard\MDReports;

use App\Models\Department;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class OpeningStockStatus extends Component
{
    public $dateFilter;
    public $branchId; // optional filter for branch if needed, but keeping simple for now.

    public function mount()
    {
        $this->dateFilter = Carbon::today()->format('Y-m-d');
    }

    public function render()
    {
        $selectedDate = Carbon::parse($this->dateFilter);

        // Get all active departments mapped to sales, production, inventory
        $departments = Department::with('category')
            ->whereHas('category', function($q) {
                $q->whereIn('name', ['Sales', 'Production', 'Support']);
            })
            ->get();

        // Get all shifts for this date
        $shifts = Shift::with(['employee', 'department'])
            ->whereDate('shift_date', $selectedDate)
            ->get();

        // Group the data
        $salesUnits = [];
        $productionUnits = [];
        $inventoryUnits = [];

        foreach ($departments as $dept) {
            $cat = strtolower($dept->category->name);
            
            // Find shift for this department
            $shift = $shifts->where('department_id', $dept->id)->first();
            
            $status = 'not_started';
            if ($shift) {
                if ($shift->stock_verified_at) {
                    $status = 'verified';
                } else {
                    $status = 'in_progress';
                }
            }

            $unitData = [
                'department_name' => $dept->name,
                'staff_name' => $shift?->employee?->name ?? 'No Staff',
                'status' => $status,
                'verified_at' => $shift?->stock_verified_at ? Carbon::parse($shift->stock_verified_at)->format('h:i A') : null,
                'shift_id' => $shift?->id,
            ];

            if (str_contains($cat, 'sale')) {
                $salesUnits[] = $unitData;
            } elseif (str_contains($cat, 'production')) {
                $productionUnits[] = $unitData;
            } elseif (str_contains(strtolower($dept->name), 'inventory') || str_contains(strtolower($dept->name), 'store')) {
                $inventoryUnits[] = $unitData;
            }
        }

        return view('livewire.branch-dashboard.m-d-reports.opening-stock-status', [
            'salesUnits' => $salesUnits,
            'productionUnits' => $productionUnits,
            'inventoryUnits' => $inventoryUnits,
        ]);
    }
}
