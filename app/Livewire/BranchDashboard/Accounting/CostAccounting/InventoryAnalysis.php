<?php

namespace App\Livewire\BranchDashboard\Accounting\CostAccounting;

use App\Models\Branch;
use App\Models\Stock;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class InventoryAnalysis extends Component
{
    use WithPagination;

    public $selectedBranch = 'all';
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedBranch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $branches = Branch::all();

        $stockQuery = Stock::with(['item', 'branch']);

        if ($this->selectedBranch !== 'all') {
            $stockQuery->where('branch_id', $this->selectedBranch);
        }

        if (!empty($this->search)) {
            $stockQuery->whereHas('item', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        $stocks = $stockQuery->paginate(20);

        // Overall summary (ignoring pagination)
        $summaryQuery = Stock::query();
        if ($this->selectedBranch !== 'all') {
            $summaryQuery->where('branch_id', $this->selectedBranch);
        }

        $totalInventoryValue = $summaryQuery->get()->sum(function($stock) {
            return $stock->quantity_available * $stock->average_cost;
        });

        $totalItems = $summaryQuery->sum('quantity_available');

        return view('livewire.branch-dashboard.accounting.cost-accounting.inventory-analysis', [
            'branches' => $branches,
            'stocks' => $stocks,
            'totalInventoryValue' => $totalInventoryValue,
            'totalItems' => $totalItems,
        ]);
    }
}
