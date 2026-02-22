<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingPeriod;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class InventoryValuation extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    private function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public function render()
    {
        $period = AccountingPeriod::current()->first();
        $summary = $this->getSummary();

        return view('livewire.branch-dashboard.accounting.simple.inventory-valuation', [
            'period' => $period,
            'summary' => $summary,
        ]);
    }

    private function getSummary(): array
    {
        $branchId = $this->getBranchId();
        $totalValue = Stock::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->select(DB::raw('SUM(quantity_available * average_cost) as total_value'))
            ->value('total_value') ?? 0;

        $itemCount = Item::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->count();
        $stockCount = Stock::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('quantity_available', '>', 0)
            ->count();

        return [
            'total_value' => (float) $totalValue,
            'item_count' => $itemCount,
            'stock_count' => $stockCount,
        ];
    }
}
