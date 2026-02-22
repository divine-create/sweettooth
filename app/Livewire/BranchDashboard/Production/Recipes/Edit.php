<?php

namespace App\Livewire\BranchDashboard\Production\Recipes;

use App\Models\ApprovalAuditRequest;
use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Edit extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Recipe ID being edited
    public ?int $recipe_id = null;

    // Form fields
    public ?string $product_id = null;

    public string $productName = '';

    public string $sku = '';

    public ?int $department_id = null;

    public ?int $product_type_id = null;

    public $cost_per_unit = 0;

    public string $uom = 'grams';

    public $yield_quantity = 1;

    public ?int $preparation_time = null;

    public array $instructions = [];

    public string $status = 'active';

    // Recipe Ingredients
    public array $ingredients = [];

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    // Audit modal for approval requests
    public bool $showAuditModal = false;

    public ?string $auditAction = null;          // edit|delete

    public string $auditReason = '';              // User-provided reason

    public ?int $pendingItemId = null;            // Recipe ID pending action

    public array $pendingItemData = [];           // Data to save on approval

    public function mount($id, $deptSlug)
    {
        $this->recipe_id = $id;
        $this->dept_slug = $deptSlug;
        $this->department = Department::where('slug', $deptSlug)->first();

        if (! $this->department) {
            abort(404, 'Department not found');
        }

        // Load recipe data with department check
        $recipe = Recipe::with('ingredients.item')
            ->where('id', $id)
            ->where('department_id', $this->department->id)
            ->first();

        if (!$recipe) {
            abort(403, 'Unauthorized access to recipe or recipe not found in this department');
        }

        // Set department_id based on dept_slug
        $this->department_id = $this->department->id;

        // Populate form fields from recipe
        $this->product_id = $recipe->product_id;
        $this->productName = $recipe->product_name;
        $this->sku = $recipe->sku;
        $this->product_type_id = $recipe->product_type_id;
        $this->cost_per_unit = $recipe->cost_per_unit;
        $this->uom = $recipe->unitOfMeasure?->symbol ?? 'grams';
        $this->yield_quantity = $recipe->yield_quantity ?? 1;
        $this->preparation_time = $recipe->preparation_time;
        $this->status = $recipe->status ?? 'active';

        // Load instructions
        $this->instructions = json_decode($recipe->instructions, true) ?? [];

        // Load ingredients
        $this->ingredients = $recipe->ingredients->map(function ($ingredient) {
            return [
                'id' => $ingredient->id,
                'item_id' => $ingredient->item_id,
                'quantity' => $ingredient->quantity,
                'uom' => $ingredient->unitOfMeasure?->symbol ?? 'grams',
                'cost_per_unit' => $ingredient->cost_per_unit,
                'waste_percentage' => $ingredient->waste_percentage,
                'notes' => $ingredient->notes,
                'preparation_notes' => $ingredient->preparation_notes,
            ];
        })->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function updatedProductId()
    {
        if ($this->product_id) {
            $product = Product::with('productType')->find($this->product_id);
            if ($product) {
                $this->productName = $product->name;
                $this->sku = $product->sku;
                $this->yield_quantity = $product->recipe_yield ?? 1;
                $this->uom = $product->unitOfMeasure?->symbol ?? 'grams';

                // Auto-populate product type from product's actual ProductType relationship
                if ($product->productType) {
                    // Store the product_type_id for later use in validation
                    $this->product_type_id = $product->product_type_id ?? null;
                }
            }
        } else {
            $this->productName = '';
            $this->sku = '';
            $this->product_type_id = null;
        }
    }

    /**
     * When ingredient item changes, sync uom and default cost from item pricing.
     */
    public function updatedIngredients($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2 || $parts[1] !== 'item_id' || empty($value)) {
            return;
        }

        $index = (int) $parts[0];
        $item = Item::where('branch_id', $this->getBranchId())
            ->with(['stocks' => function ($query) {
                $query->where('branch_id', $this->getBranchId());
            }])->find($value);

        if (! $item) {
            return;
        }

        $stock = $item->stocks->first();
        $itemUnitPrice = (float) ($item->unit_price ?? 0);
        $stockAverageCost = (float) ($stock?->average_cost ?? 0);

        $this->ingredients[$index]['uom'] = $item->uom ?? 'grams';
        $this->ingredients[$index]['cost_per_unit'] = $itemUnitPrice > 0 ? $itemUnitPrice : $stockAverageCost;
    }

    public function addIngredient()
    {
        $this->ingredients[] = [
            'item_id' => null,
            'quantity' => 0,
            'uom' => 'grams',
            'cost_per_unit' => 0,
            'waste_percentage' => 0,
            'notes' => '',
            'preparation_notes' => '',
        ];
    }

    public function addInstruction()
    {
        $this->instructions[] = '';
    }

    public function removeInstruction($index)
    {
        unset($this->instructions[$index]);
        $this->instructions = array_values($this->instructions);
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients);
    }

    public function save()
    {
        $rules = [
            'productName' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:recipes,sku,'.$this->recipe_id,
            'department_id' => 'required|exists:departments,id',
            'product_type_id' => 'required|exists:product_types,id',
            'cost_per_unit' => 'required|numeric|min:0.0001',
            'uom' => 'required|exists:units_of_measure,symbol',
            'yield_quantity' => 'required|numeric|min:0.01',
            'preparation_time' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,testing',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.item_id' => 'required|exists:items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.uom' => 'required|exists:units_of_measure,symbol',
            'ingredients.*.cost_per_unit' => 'required|numeric|min:0.0001',
            'ingredients.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
        ];

        $this->validate($rules);

        // Calculate cost for preview
        $totalCost = collect($this->ingredients)->sum(function ($ing) {
            $quantity = (float) $ing['quantity'];
            $costPerUnit = (float) $ing['cost_per_unit'];
            $wastePercent = (float) ($ing['waste_percentage'] ?? 0);
            $actualQuantity = $quantity * (1 + ($wastePercent / 100));

            return $actualQuantity * $costPerUnit;
        });
        $costPerUnit = $totalCost / max((float) $this->yield_quantity, 1);

        $recipeData = [
            'id' => $this->recipe_id,
            'product_id' => $this->product_id,
            'product_name' => $this->productName,
            'sku' => strtoupper($this->sku),
            'department_id' => $this->department_id,
            'product_type_id' => $this->product_type_id,
            'cost_per_unit' => $costPerUnit,
            'uom' => $this->uom,
            'yield_quantity' => $this->yield_quantity,
            'preparation_time' => $this->preparation_time,
            'instructions' => ! empty($this->instructions) ? json_encode(array_values($this->instructions)) : null,
            'status' => $this->status,
            'ingredients' => $this->ingredients,
        ];

        // Super admin bypass - save directly without audit
        if (is_super_admin()) {
            $this->saveRecipe($recipeData);

            return;
        }

        // Non-super-admin: show audit modal
        $this->auditAction = 'edit_recipe';
        $this->pendingItemId = $this->recipe_id;
        $this->pendingItemData = $recipeData;
        $this->showAuditModal = true;
    }

    private function saveRecipe(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $recipeId = $data['id'];
                $ingredients = $data['ingredients'];
                unset($data['id']);
                unset($data['ingredients']);

                $recipe = Recipe::findOrFail($recipeId);
                $recipe->update($data);

                // Delete old ingredients and create new ones
                $recipe->ingredients()->delete();
                foreach ($ingredients as $ingredient) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'item_id' => $ingredient['item_id'],
                        'quantity' => $ingredient['quantity'],
                        'uom' => $ingredient['uom'],
                        'cost_per_unit' => $ingredient['cost_per_unit'],
                        'waste_percentage' => $ingredient['waste_percentage'] ?? 0,
                        'notes' => $ingredient['notes'] ?? null,
                        'preparation_notes' => $ingredient['preparation_notes'] ?? null,
                    ]);
                }
            });

            $this->toast()->success('Recipe updated successfully')->send();

            return $this->redirect(branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $this->dept_slug]), navigate: true);
        } catch (\Exception $e) {
            $this->toast()->error('Failed to update recipe: '.$e->getMessage())->send();
        }
    }

    public function submitAuditRequest()
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ]);

        $requester = current_actor();

        ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'recipe:'.$this->auditAction,
            'description' => $this->auditReason,
            'payload' => $this->pendingItemData,
            'status' => 'pending',
            'branch_id' => $this->getBranchId(),
        ]);

        $this->closeAuditModal();
        $this->toast()->success('Approval request submitted')->send();

        // For delete requests, redirect to recipes index
        if ($this->auditAction === 'delete_recipe') {
            return $this->redirect(branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $this->dept_slug]), navigate: true);
        }

        // For edit requests, stay on edit page

    }

    /**
     * Delete recipe - triggers audit modal for non-super-admins
     */
    public function delete()
    {
        $recipe = Recipe::findOrFail($this->recipe_id);

        // Super admin bypass - delete directly without audit
        if (is_super_admin()) {
            $this->deleteRecipe($recipe);

            return;
        }

        // Non-super-admin: show audit modal for deletion
        $this->auditAction = 'delete_recipe';
        $this->pendingItemId = $this->recipe_id;
        $this->pendingItemData = [
            'recipe_id' => $recipe->id,
            'recipe_name' => $recipe->product_name,
            'sku' => $recipe->sku,
        ];
        $this->showAuditModal = true;
    }

    private function deleteRecipe(Recipe $recipe)
    {
        try {
            DB::transaction(function () use ($recipe) {
                $recipeName = $recipe->product_name;
                $recipe->delete();
            });

            $this->toast()->success('Recipe deleted successfully')->send();

            return $this->redirect(branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $this->dept_slug]), navigate: true);
        } catch (\Exception $e) {
            $this->toast()->error('Failed to delete recipe: '.$e->getMessage())->send();
        }
    }

    private function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;
        $this->pendingItemId = null;
        $this->pendingItemData = [];
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        // Filter products by department
        $products = Product::where('is_active', 1)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->whereHas('productType', function ($q) {
                $q->where('department_id', $this->department->id);
            })
            ->orderBy('name')
            ->get();

        $items = Item::where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        // Get product types for the department
        $productTypes = \App\Models\ProductType::where('department_id', $this->department->id)
            ->orderBy('sort_order')
            ->get();

        // Get all units of measure
        $unitsOfMeasure = UnitOfMeasure::active();

        return view('livewire.branch-dashboard.production.recipes.edit', [
            'products' => $products,
            'items' => $items,
            'department' => $this->department,
            'dept_slug' => $this->dept_slug,
            'productTypes' => $productTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ]);
    }
}
