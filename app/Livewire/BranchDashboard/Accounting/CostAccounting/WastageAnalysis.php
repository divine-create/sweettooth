<?php

namespace App\Livewire\BranchDashboard\Accounting\CostAccounting;

use App\Models\Branch;
use App\Models\StockMovement;
use App\Models\ProductionRecord;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class WastageAnalysis extends Component
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

        // 2. Production Wastage & Rejections
        $productionQuery = ProductionRecord::with('recipe.product')
            ->where('created_at', '>=', $startDate)
            ->where('quantity_rejected', '>', 0);
            
        if ($this->selectedBranch !== 'all') {
            // Check if ProductionRecord has branch_id or relates to a branch.
            // Assuming ProductionRecord has branch_id or we filter out at display if needed.
            if (\Illuminate\Support\Facades\Schema::hasColumn('production_records', 'branch_id')) {
                $productionQuery->where('branch_id', $this->selectedBranch);
            }
        }

        $productionWastage = $productionQuery->get()->map(function($record) {
            return [
                'item' => optional(optional($record->recipe)->product)->name ?? 'Unknown',
                'quantity' => $record->quantity_rejected,
                'reason' => $record->rejection_reason ?? 'Wastage',
                'cost' => $record->quantity_rejected * $record->unit_cost,
                'date' => $record->created_at->format('Y-m-d')
            ];
        });

        // 3. Inventory Shortages & Damages
        $stockQuery = StockMovement::with('stock.item')
            ->where('movement_date', '>=', $startDate)
            ->where('type', 'out')
            ->whereIn('movement_type', ['adjustment_out', 'spoilage', 'damage', 'expired']);
            
        if ($this->selectedBranch !== 'all') {
            if (\Illuminate\Support\Facades\Schema::hasColumn('stock_movements', 'branch_id')) {
                $stockQuery->where('branch_id', $this->selectedBranch);
            }
        }

        $inventoryShortages = $stockQuery->get()->map(function($movement) {
            return [
                'item' => optional(optional($movement->stock)->item)->name ?? 'Unknown',
                'quantity' => abs($movement->quantity),
                'reason' => $movement->adjustment_reason ?? $movement->movement_type,
                'cost' => abs($movement->cost_impact),
                'date' => Carbon::parse($movement->movement_date)->format('Y-m-d')
            ];
        });

        return view('livewire.branch-dashboard.accounting.cost-accounting.wastage-analysis', [
            'branches' => $branches,
            'productionWastage' => $productionWastage,
            'inventoryShortages' => $inventoryShortages,
        ]);
    }
}
