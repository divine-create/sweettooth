<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class WipProductRecipe extends Component
{
    use Interactions;

    public ?string $productId = null;

    public ?Department $department = null;

    public ?Product $product = null;

    public ?Recipe $recipe = null;

    public string $productName = '';

    public string $productSku = '';

    public int $productUomId = 2;

    public int $shelfLifeDays = 0;

    public float $productPrice = 0;

    public float $productCost = 0;

    public float $yieldQuantity = 1;

    public string $recipeStatus = 'active';

    public array $ingredients = [];

    public bool $isEditing = false;

    protected $queryString = ['productId'];

    public function mount()
    {
        $deptSlug = request()->route('deptSlug');
        $this->department = Department::where('slug', $deptSlug)->first();

        if (! $this->department) {
            session()->flash('error', 'Department not found.');

            return;
        }

        $this->loadProduct();
    }

    public function getBranchId(): ?string
    {
        return request()->query('b_id') ?? current_branch_id();
    }

    protected function loadProduct()
    {
        if (! $this->productId) {
            $this->productId = request()->route('productId');
        }

        if (! $this->productId) {
            session()->flash('error', 'Product not specified.');

            return redirect()->route('branch-dashboard.production.wip-products', [
                'deptSlug' => $this->department?->slug,
                'b_id' => $this->getBranchId(),
            ]);
        }

        $this->product = Product::with('unitOfMeasure')->find($this->productId);

        if (! $this->product) {
            session()->flash('error', 'Product not found.');

            return redirect()->route('branch-dashboard.production.wip-products', [
                'deptSlug' => $this->department?->slug,
                'b_id' => $this->getBranchId(),
            ]);
        }

        $this->productName = $this->product->name;
        $this->productSku = $this->product->sku;
        $this->productUomId = $this->product->uom_id;
        $this->shelfLifeDays = $this->product->shelf_life_days ?? 0;
        $this->productPrice = $this->product->price ?? 0;
        $this->productCost = $this->product->cost ?? 0;

        $this->loadRecipe();
    }

    protected function loadRecipe()
    {
        $this->recipe = Recipe::where('product_id', $this->productId)
            ->where('department_id', $this->department->id)
            ->first();

        if ($this->recipe) {
            $this->isEditing = true;
            $this->yieldQuantity = $this->recipe->yield_quantity ?? 1;
            $this->recipeStatus = $this->recipe->status ?? 'active';
            $this->ingredients = $this->recipe->ingredients->map(function ($ing) {
                return [
                    'item_id' => $ing->item_id,
                    'item_name' => $ing->item?->name ?? $ing->item_name ?? 'Unknown',
                    'quantity' => $ing->quantity,
                    'uom' => $ing->uom ?? 'units',
                    'cost_per_unit' => $ing->cost_per_unit ?? 0,
                    'waste_percentage' => $ing->waste_percentage ?? 0,
                    'is_wip' => $ing->is_wip ?? false,
                ];
            })->toArray();
        } else {
            $this->ingredients = [
                [
                    'item_id' => '',
                    'item_name' => '',
                    'quantity' => 1,
                    'uom' => '',
                    'cost_per_unit' => 0,
                    'waste_percentage' => 0,
                    'is_wip' => false,
                ],
            ];
        }
    }

    public function render()
    {
        $wipType = ProductType::where('code', 'WIP')->first();

        $wipProducts = collect();
        if ($wipType) {
            $wipProducts = Product::with(['productType', 'unitOfMeasure'])
                ->where('product_type_id', $wipType->id)
                ->where('branch_id', $this->getBranchId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $wipProductIds = Recipe::where('is_wip', true)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        $rawItems = Item::where('branch_id', $this->getBranchId())
            ->orderBy('name')
            ->get();

        $unitOfMeasures = UnitOfMeasure::orderBy('symbol')->get();

        return view('livewire.branch-dashboard.production.wip-product-recipe', [
            'wipProducts' => $wipProducts,
            'wipProductIds' => $wipProductIds,
            'rawItems' => $rawItems,
            'unitOfMeasures' => $unitOfMeasures,
            'deptSlug' => $this->department?->slug,
        ]);
    }

    public function addRawIngredient()
    {
        $this->ingredients[] = [
            'item_id' => '',
            'item_name' => '',
            'quantity' => 1,
            'uom' => '',
            'cost_per_unit' => 0,
            'waste_percentage' => 0,
            'is_wip' => false,
        ];
    }

    public function addWipIngredient()
    {
        $this->ingredients[] = [
            'item_id' => '',
            'item_name' => '',
            'quantity' => 1,
            'uom' => '',
            'cost_per_unit' => 0,
            'waste_percentage' => 0,
            'is_wip' => true,
        ];
    }

    public function removeIngredient($index)
    {
        if (isset($this->ingredients[$index])) {
            unset($this->ingredients[$index]);
            $this->ingredients = array_values($this->ingredients);
        }
    }

    public function onIngredientItemChanged($index, $value)
    {
        if (empty($value)) {
            return;
        }

        $isWip = $this->ingredients[$index]['is_wip'] ?? false;

        if ($isWip) {
            $item = Product::with('unitOfMeasure')->find($value);
            if ($item) {
                $this->ingredients[$index]['item_name'] = $item->name;
                $this->ingredients[$index]['uom'] = $item->unitOfMeasure->symbol ?? 'units';
                $this->ingredients[$index]['cost_per_unit'] = $item->price ?? 0;
            }
        } else {
            $item = Item::with('unitOfMeasure')->find($value);
            if ($item) {
                $this->ingredients[$index]['item_name'] = $item->name;
                $this->ingredients[$index]['uom'] = $item->unitOfMeasure->symbol ?? 'units';
                $this->ingredients[$index]['cost_per_unit'] = $item->unit_price ?? 0;
            }
        }
    }

    public function saveRecipe()
    {
        $this->validate([
            'yieldQuantity' => 'required|numeric|min:0.01',
            'recipeStatus' => 'required|in:active,inactive,testing',
        ]);

        $hasAtLeastOneIngredient = collect($this->ingredients)->contains(function ($ing) {
            return ! empty($ing['item_id']);
        });

        if (! $hasAtLeastOneIngredient) {
            session()->flash('error', 'Add at least one ingredient.');

            return;
        }

        $product = $this->product;

        if ($this->isEditing && $this->recipe) {
            $this->recipe->update([
                'yield_quantity' => $this->yieldQuantity,
                'status' => $this->recipeStatus,
            ]);

            $this->recipe->ingredients()->delete();

            $this->saveIngredients($this->recipe);

            $this->toast()->success('Recipe updated.')->send();
        } else {
            $recipe = Recipe::create([
                'branch_id' => $this->getBranchId(),
                'department_id' => $this->department->id,
                'product_id' => $this->productId,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'product_type_id' => $product->product_type_id,
                'uom_id' => $product->uom_id,
                'yield_quantity' => $this->yieldQuantity,
                'status' => $this->recipeStatus,
                'is_wip' => true,
                'is_active' => true,
                'created_by_id' => Auth::id(),
                'created_by_type' => get_class(Auth::user()),
            ]);

            // Attach department to the WIP product
            $product->department_id = $this->department->id;
            $product->save();

            $product->departments()->attach($this->department->id, [
                'is_available' => true,
                'sort_order' => 0,
            ]);

            $this->saveIngredients($recipe);

            $this->toast()->success("Recipe created for {$product->name}")->send();
        }

        return redirect()->route('branch-dashboard.production.wip-products', [
            'deptSlug' => $this->department?->slug ?? request()->route('deptSlug'),
            'b_id' => $this->getBranchId(),
        ]);
    }

    protected function saveIngredients(Recipe $recipe)
    {
        foreach ($this->ingredients as $ing) {
            if (empty($ing['item_id'])) {
                continue;
            }

            $isWip = $ing['is_wip'] ?? false;

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'item_id' => $ing['item_id'],
                'item_name' => $ing['item_name'],
                'quantity' => $ing['quantity'],
                'uom' => $ing['uom'],
                'cost_per_unit' => $ing['cost_per_unit'] ?? 0,
                'waste_percentage' => $ing['waste_percentage'] ?? 0,
                'is_wip' => $isWip,
            ]);
        }
    }
}
