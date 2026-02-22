<?php

namespace App\Livewire\BranchDashboard\Organization;

use App\Models\UnitOfMeasure;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class UomUnits extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = 'all';

    public string $status = 'all';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = UnitOfMeasure::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('symbol', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        $units = $query
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);

        $categories = UnitOfMeasure::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('livewire.branch-dashboard.organization.uom.units', [
            'units' => $units,
            'categories' => $categories,
        ]);
    }
}

