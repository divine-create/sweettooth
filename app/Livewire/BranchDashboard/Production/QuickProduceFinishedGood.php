<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BranchDashboard\Production\Concerns\QuickProduceTrait;
use App\Models\Recipe;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class QuickProduceFinishedGood extends Component
{
    use Interactions, QuickProduceTrait;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public function getBranchId(): ?string
    {
        return $this->b_id ?? request()->query('b_id');
    }

    public function getRecipes()
    {
        return Recipe::where('branch_id', $this->getBranchId())
            ->where('department_id', $this->department->id)
            ->where('is_active', true)
            ->where('is_wip', false)
            ->with('productType', 'unitOfMeasure')
            ->orderBy('product_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.quick-produce-finished-good');
    }
}
