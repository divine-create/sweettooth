<?php

namespace App\Livewire\BranchDashboard\Production\Store\MaterialClosing;

use App\Models\ProductionStore;
use App\Models\ProductionStoreDailyStock;
use App\Models\ProductionStoreStock;
use App\Models\Department;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $deptSlug;
    public $departmentId;
    public $branchId;
    public $storeId;

    public $stockDate;
    public $materialStocks = [];
    public $notes = '';

    public function mount($deptSlug = null)
    {
        $this->deptSlug = $deptSlug;
        $this->branchId = get_current_branch_id();

        // 1. Resolve Department
        if ($this->deptSlug) {
            $department = Department::where('slug', $this->deptSlug)->firstOrFail();
            $this->departmentId = $department->id;
        } else {
            // Default to Cornerstone (id 4) if not provided, or whatever is assigned
            $this->departmentId = 4;
            $department = Department::find(4);
            $this->deptSlug = $department?->slug ?? 'cornerstone';
        }

        // 2. Resolve Production Store
        $store = ProductionStore::where('department_id', $this->departmentId)
                                ->where('branch_id', $this->branchId)
                                ->first();

        if (!$store) {
            session()->flash('error', 'No production store found for this department.');
            return redirect()->route('production-dashboard.index');
        }

        $this->storeId = $store->id;
        $this->stockDate = Carbon::today()->toDateString();

        $this->loadStocks();
    }

    public function loadStocks()
    {
        $stocks = ProductionStoreDailyStock::with('item.unitOfMeasure')
            ->where('store_id', $this->storeId)
            ->where('stock_date', $this->stockDate)
            ->get();

        foreach ($stocks as $stock) {
            $expected = (float)$stock->opening_quantity + (float)$stock->addition_quantity - (float)$stock->quantity_used;
            $this->materialStocks[$stock->item_id] = [
                'id' => $stock->id,
                'item_name' => $stock->item->name ?? 'Unknown Item',
                'uom' => $stock->item->unitOfMeasure->symbol ?? 'units',
                'opening_quantity' => (float)$stock->opening_quantity,
                'addition_quantity' => (float)$stock->addition_quantity,
                'quantity_used' => (float)$stock->quantity_used, // Theoretical usage
                'expected_closing' => max(0, $expected),
                'closing_quantity' => $stock->workflow_step !== 'opening_pending' 
                                        ? (float)$stock->closing_quantity 
                                        : max(0, $expected),
                'notes' => $stock->notes,
            ];
        }
    }

    public function saveClosing()
    {
        $this->validate([
            'materialStocks.*.closing_quantity' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            foreach ($this->materialStocks as $itemId => $data) {
                $stock = ProductionStoreDailyStock::find($data['id']);
                if ($stock) {
                    $stock->closing_quantity = $data['closing_quantity'];
                    $stock->notes = $data['notes'];
                    $stock->workflow_step = 'closing_completed';
                    $stock->is_workflow_verified = true;
                    $stock->verified_at = now();
                    $stock->verified_by = auth()->id();
                    $stock->updateCalculatedFields();
                    $stock->save();

                    // Update the master production_store_stocks to match physical count
                    $masterStock = ProductionStoreStock::where('store_id', $this->storeId)
                        ->where('item_id', $itemId)
                        ->first();
                    
                    if ($masterStock) {
                        $masterStock->quantity_available = $data['closing_quantity'];
                        $masterStock->save();
                    }
                }
            }

            DB::commit();

            session()->flash('status', 'Material Shift Closed Successfully. Master stock adjusted to physical count.');
            return redirect()->route('production-dashboard.store.index', ['deptSlug' => $this->deptSlug]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Fallback for missing tallstack toast
            session()->flash('error', 'Error saving stock: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.store.material-closing.index')->layout('layouts.app');
    }
}
