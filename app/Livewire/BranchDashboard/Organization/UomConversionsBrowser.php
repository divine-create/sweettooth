<?php

namespace App\Livewire\BranchDashboard\Organization;

use App\Models\UomConversion;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class UomConversionsBrowser extends Component
{
    use WithPagination;

    public string $search = '';

    public string $scope = 'all';

    public string $status = 'active';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedScope(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = UomConversion::query()
            ->with([
                'fromUnit:id,name,code,symbol',
                'toUnit:id,name,code,symbol',
                'branch:id,name,code',
                'item:id,name,sku',
                'product:id,name,sku',
            ]);

        if ($this->scope !== 'all') {
            if ($this->scope === 'global') {
                $query->whereNull('branch_id')
                    ->whereNull('item_id')
                    ->whereNull('product_id');
            } elseif ($this->scope === 'branch') {
                $query->whereNotNull('branch_id')
                    ->whereNull('item_id')
                    ->whereNull('product_id');
            } elseif ($this->scope === 'item') {
                $query->whereNotNull('item_id');
            } elseif ($this->scope === 'product') {
                $query->whereNotNull('product_id');
            }
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('notes', 'like', $term)
                    ->orWhereHas('fromUnit', function ($unitQuery) use ($term): void {
                        $unitQuery->where('name', 'like', $term)
                            ->orWhere('symbol', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('toUnit', function ($unitQuery) use ($term): void {
                        $unitQuery->where('name', 'like', $term)
                            ->orWhere('symbol', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('name', 'like', $term))
                    ->orWhereHas('item', function ($itemQuery) use ($term): void {
                        $itemQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    })
                    ->orWhereHas('product', function ($productQuery) use ($term): void {
                        $productQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    });
            });
        }

        $conversions = $query
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.branch-dashboard.organization.uom.conversions', [
            'conversions' => $conversions,
        ]);
    }
}

