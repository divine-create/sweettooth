<?php

namespace App\Livewire\BranchDashboard\Production\Store;

use App\Models\Department;
use App\Models\ProductionStore;
use App\Models\ProductionStoreStock;
use App\Services\ProductionStoreService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Stock extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public ?ProductionStore $store = null;

    public string $search = '';

    public string $stockFilter = 'all';

    public int $quantity = 15;

    public function mount($deptSlug)
    {
        $this->dept_slug = $deptSlug;
        $this->b_id = request()->query('b_id');
        $this->department = Department::where('slug', $deptSlug)->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        $this->loadStore();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    protected function loadStore()
    {
        $branchId = $this->getBranchId();

        $this->store = ProductionStore::forBranch($branchId)
            ->forDepartment($this->department->id)
            ->active()
            ->first();
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $store = ProductionStore::forBranch($branchId)
            ->forDepartment($this->department->id)
            ->active()
            ->first();

        $stocks = collect();
        $stockCounts = ['all' => 0, 'wip' => 0, 'raw' => 0, 'finished' => 0];
        $wipProductIds = [];

        // Raw materials have numeric item_id (Items); products (WIP / finished
        // goods) have UUID item_id. A product is "finished goods" when it is held
        // in the store but is NOT a WIP recipe output.
        $numericRegex = "item_id REGEXP '^[0-9]+$'";

        if ($store) {
            $wipProductIds = \App\Models\Recipe::where('is_wip', true)
                ->whereNotNull('product_id')
                ->pluck('product_id')
                ->toArray();

            $stocks = ProductionStoreStock::where('store_id', $store->id)
                ->with('item.category', 'item.unitOfMeasure')
                ->when($this->search, function ($query) {
                    $query->whereHas('item', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('sku', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->stockFilter === 'wip', function ($query) use ($wipProductIds) {
                    $query->whereIn('item_id', $wipProductIds);
                })
                ->when($this->stockFilter === 'raw', function ($query) use ($numericRegex) {
                    $query->whereRaw($numericRegex);
                })
                ->when($this->stockFilter === 'finished', function ($query) use ($wipProductIds, $numericRegex) {
                    $query->whereRaw("NOT ($numericRegex)")
                        ->whereNotIn('item_id', $wipProductIds);
                })
                ->orderBy('quantity_available', 'desc')
                ->get();

            // Load product information for WIP items
            $wipProducts = \App\Models\Product::whereIn('id', $stocks->pluck('item_id')->toArray())->get()->keyBy('id');
            $stocks->each(function ($stock) use ($wipProducts) {
                if (isset($wipProducts[$stock->item_id])) {
                    $stock->displayItem = $wipProducts[$stock->item_id];
                } else {
                    $stock->displayItem = $stock->item;
                }
            });

            $baseQuery = ProductionStoreStock::where('store_id', $store->id)
                ->where('quantity_available', '>', 0);

            $stockCounts['wip'] = (clone $baseQuery)
                ->whereIn('item_id', $wipProductIds)
                ->count();

            $stockCounts['raw'] = (clone $baseQuery)
                ->whereRaw($numericRegex)
                ->count();

            $stockCounts['finished'] = (clone $baseQuery)
                ->whereRaw("NOT ($numericRegex)")
                ->whereNotIn('item_id', $wipProductIds)
                ->count();

            $stockCounts['all'] = $stockCounts['wip'] + $stockCounts['raw'] + $stockCounts['finished'];
        }

        $totalValue = $stocks->sum(function ($stock) {
            return ($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0);
        });

        return view('livewire.branch-dashboard.production.store.stock', [
            'stocks' => $stocks,
            'store' => $store,
            'totalValue' => $totalValue,
            'stockCounts' => $stockCounts,
            'wipProductIds' => $wipProductIds,
        ]);
    }

    public function getStoreName(): string
    {
        return $this->store?->name ?? 'Production Store';
    }
}