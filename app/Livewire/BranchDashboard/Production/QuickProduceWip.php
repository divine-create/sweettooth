<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BranchDashboard\Production\Concerns\QuickProduceTrait;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Recipe;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class QuickProduceWip extends Component
{
    use Interactions, QuickProduceTrait;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?string $selectedProductId = null;

    public function getBranchId(): ?string
    {
        return $this->b_id ?? $this->department->branch_id ?? request()->query('b_id');
    }

    public function getProducts()
    {
        $wipType = ProductType::where('code', 'WIP')
            ->where('department_id', $this->department->id)
            ->first();

        if (! $wipType) {
            return collect();
        }

        return Product::where('product_type_id', $wipType->id)
            ->where('branch_id', $this->department->branch_id ?? $this->getBranchId())
            ->where('department_id', $this->department->id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->orderBy('name')
            ->get();
    }

    public function selectProduct($productId)
    {
        $this->selectedProductId = $productId;

        if (empty($productId)) {
            $this->selectRecipe(null);

            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            $this->selectRecipe(null);

            return;
        }

        // Find associated WIP recipe
        $recipe = Recipe::where('product_id', $product->id)
            ->where('is_wip', true)
            ->first();

        if (! $recipe) {
            $this->toast()->error('No WIP recipe found for this product. Please create a recipe first.')->send();

            return;
        }

        // Use the trait's selectRecipe method
        $this->selectRecipe($recipe->id);
    }

    public function getRecipes()
    {
        return collect();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.quick-produce-wip');
    }
}
