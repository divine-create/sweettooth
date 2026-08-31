<?php

namespace App\Livewire\BranchDashboard\Accounting\CostAccounting;

use App\Models\Branch;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ProductionRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class Dashboard extends Component
{
    public $dateFilter = 'monthly'; // daily, weekly, monthly
    public $selectedBranch = 'all';

    public function render()
    {
        $startDate = match ($this->dateFilter) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfMonth(),
        };

        $branches = Branch::all();

        // High Level Summaries
        $salesQuery = SaleItem::whereHas('sale', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
                if ($this->selectedBranch !== 'all') {
                    $q->where('branch_id', $this->selectedBranch);
                }
            })
            ->select(
                DB::raw('SUM(subtotal) as total_revenue'), 
                DB::raw('SUM(line_cost) as total_cogs')
            )->first();
            
        $totalRevenue = $salesQuery->total_revenue ?? 0;
        $totalCogs = $salesQuery->total_cogs ?? 0;
        $grossProfit = $totalRevenue - $totalCogs;

        $productionWastageTotal = ProductionRecord::where('created_at', '>=', $startDate)
            ->where('quantity_rejected', '>', 0)
            ->when($this->selectedBranch !== 'all' && \Illuminate\Support\Facades\Schema::hasColumn('production_records', 'branch_id'), function ($q) {
                $q->where('branch_id', $this->selectedBranch);
            })
            ->get()
            ->sum(function($record) {
                return $record->quantity_rejected * $record->unit_cost;
            });

        $inventoryDamagesTotal = StockMovement::where('movement_date', '>=', $startDate)
            ->where('type', 'out')
            ->whereIn('movement_type', ['adjustment_out', 'spoilage', 'damage', 'expired'])
            ->when($this->selectedBranch !== 'all' && \Illuminate\Support\Facades\Schema::hasColumn('stock_movements', 'branch_id'), function ($q) {
                $q->where('branch_id', $this->selectedBranch);
            })
            ->sum('cost_impact');

        return view('livewire.branch-dashboard.accounting.cost-accounting.dashboard', [
            'branches' => $branches,
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'productionWastageTotal' => $productionWastageTotal,
            'inventoryDamagesTotal' => abs($inventoryDamagesTotal),
        ]);
    }
}
