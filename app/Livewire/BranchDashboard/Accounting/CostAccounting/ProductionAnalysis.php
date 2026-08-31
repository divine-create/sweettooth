<?php

namespace App\Livewire\BranchDashboard\Accounting\CostAccounting;

use App\Models\Department;
use App\Models\ProductionRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class ProductionAnalysis extends Component
{
    public $dateFilter = 'monthly';
    public $customStartDate;
    public $customEndDate;
    public $selectedDepartment = 'all';

    public function render()
    {
        $startDate = match ($this->dateFilter) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'custom' => $this->customStartDate ? Carbon::parse($this->customStartDate)->startOfDay() : Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfMonth(),
        };

        $endDate = match ($this->dateFilter) {
            'custom' => $this->customEndDate ? Carbon::parse($this->customEndDate)->endOfDay() : Carbon::now()->endOfDay(),
            default => Carbon::now()->endOfDay(),
        };

        // Fetch production departments
        $departments = Department::whereHas('category', function($q) {
            $q->where('name', 'like', '%Production%')
              ->orWhere('name', 'like', '%Kitchen%')
              ->orWhere('name', 'like', '%Pastry%')
              ->orWhere('name', 'like', '%Gelato%');
        })->get();

        if ($departments->isEmpty()) {
            $departments = Department::all();
        }

        // Production Query
        $productionQuery = ProductionRecord::with(['recipe.product', 'dailyProduce.shift.department'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->selectedDepartment !== 'all') {
            $productionQuery->whereHas('dailyProduce.shift', function($q) {
                $q->where('department_id', $this->selectedDepartment);
            });
        }

        $records = $productionQuery->latest()->get();

        $productionData = $records->map(function($record) {
            return [
                'department' => optional(optional(optional($record->dailyProduce)->shift)->department)->name ?? 'N/A',
                'product' => optional(optional($record->recipe)->product)->name ?? 'Unknown',
                'quantity_produced' => $record->quantity_produced,
                'quantity_approved' => $record->quantity_approved,
                'quantity_rejected' => $record->quantity_rejected,
                'unit_cost' => $record->unit_cost,
                'total_cost' => $record->quantity_produced * $record->unit_cost,
                'date' => $record->created_at->format('Y-m-d')
            ];
        });

        // Summary metrics
        $totalCost = $productionData->sum('total_cost');
        $totalProduced = $productionData->sum('quantity_produced');
        $totalRejected = $productionData->sum('quantity_rejected');

        return view('livewire.branch-dashboard.accounting.cost-accounting.production-analysis', [
            'departments' => $departments,
            'productionData' => $productionData,
            'totalCost' => $totalCost,
            'totalProduced' => $totalProduced,
            'totalRejected' => $totalRejected,
        ]);
    }
}
