<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\Branch;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ProductionRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class CostAccountantDashboard extends Component
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

        // 1. Top Selling Items & Profitability
        $salesQuery = SaleItem::with('product')
            ->select('product_id', 
                DB::raw('SUM(quantity) as total_quantity'), 
                DB::raw('SUM(subtotal) as total_revenue'), 
                DB::raw('SUM(line_cost) as total_cogs')
            )
            ->whereHas('sale', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
                if ($this->selectedBranch !== 'all') {
                    $q->where('branch_id', $this->selectedBranch);
                }
            })
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->take(10)
            ->get();

        // 2. Production Wastage
        $productionQuery = ProductionRecord::with('recipe.product')
            ->where('created_at', '>=', $startDate)
            ->where('quantity_rejected', '>', 0);
            
        $productionWastage = $productionQuery->get()->map(function($record) {
            return [
                'item' => optional(optional($record->recipe)->product)->name ?? 'Unknown',
                'quantity' => $record->quantity_rejected,
                'reason' => $record->rejection_reason ?? 'Wastage',
                'cost' => $record->quantity_rejected * $record->unit_cost,
                'date' => $record->created_at->format('Y-m-d')
            ];
        });

        // 3. Inventory Shortages
        $stockQuery = StockMovement::with('stock.item')
            ->where('movement_date', '>=', $startDate)
            ->where('type', 'out')
            ->whereIn('adjustment_reason', ['wastage', 'shortage', 'spoilage', 'expired', 'loss']);
            
        if ($this->selectedBranch !== 'all') {
            $stockQuery->where('branch_id', $this->selectedBranch);
        }
            
        $inventoryShortages = $stockQuery->get()->map(function($movement) {
            return [
                'item' => optional(optional($movement->stock)->item)->name ?? 'Unknown',
                'quantity' => abs($movement->quantity),
                'reason' => $movement->adjustment_reason,
                'cost' => abs($movement->cost_impact),
                'date' => Carbon::parse($movement->movement_date)->format('Y-m-d')
            ];
        });

        return view('livewire.branch-dashboard.accounting.cost-accountant-dashboard', [
            'branches' => $branches,
            'topSales' => $salesQuery,
            'productionWastage' => $productionWastage,
            'inventoryShortages' => $inventoryShortages,
        ]);
    }
}
