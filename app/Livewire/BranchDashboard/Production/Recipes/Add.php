<?php

namespace App\Livewire\BranchDashboard\Production\Recipes;

use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ApprovalAuditRequest;
use App\Models\UnitOfMeasure;
use App\Services\ProductionAuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Component;
use TallStackUi\Traits\Interactions;


#[Layout('components.layouts.app.branch-dashboard')]
class Add extends Component
{
    use Interactions;
    
    #[Url(keep: true)]
    public ?string $b_id = null;

    // Form fields
    /** @var string|int Product ID selected by user */
    public string|int $product_id = '';

    /** @var string Auto-generated SKU (recipe identifier) */
    public string $sku = '';

    /** @var int|null Department ID - set during mount() */
    public ?int $department_id = null;

    /** @var string Product type in slug format (e.g., "baked_good") */
    public string $product_type = '';

    /** @var float Cost per unit of finished product - calculated from ingredients */
    public $cost_per_unit = 0;

    /** @var string Unit of Measure - auto-filled from selected product (grams, kg, liters, ml, pcs, units) */
    public string $uom = 'grams';

    /** @var float Number of units produced per batch (e.g., 55 units). User enters this directly. */
    public $yield_quantity = 1;

    /** @var int|null Preparation time in minutes for one batch */
    public ?int $preparation_time = null;

    /** @var array Preparation instructions as array of strings (converted to JSON on save) */
    public array $instructions = [];

    /** @var string Recipe status: active, inactive, or testing */
    public string $status = 'active';

    // Recipe Ingredients
    /** @var array Array of ingredients with item_id, quantity, uom, cost_per_unit, waste_percentage, notes */
    public array $ingredients = [];

    #[Url(keep:true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    // Audit modal for approval requests
    public bool $showAuditModal = false;
    public ?string $auditAction = null;          // create|edit|delete
    public string $auditReason = '';              // User-provided reason
    public ?int $pendingItemId = null;            // Recipe ID pending action
    public array $pendingItemData = [];           // Data to save on approval

    public bool $hasExistingRecipe = false;

    public ?int $existingRecipeId = null;

    /**
     * Initialize the component with department context
     * Validates that the department exists before allowing recipe creation
     */
    public function mount($deptSlug){
        $this->dept_slug = $deptSlug;
        $this->department = Department::where('slug', $deptSlug)->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        // Auto-set department_id based on dept_slug for filtering products
        $this->department_id = $this->department->id;
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    /**
     * Auto-generate a unique SKU for the recipe
     * Format: RCP-{branchId}-{productNameCode}-{randomNumber}
     * Example: RCP-1-CHOC-342
     */
    private function generateSku()
    {
        $branchId = $this->getBranchId();
        $product = Product::find($this->product_id);
        // Extract first 4 letters of product name (removing non-alphabetic characters)
        $nameCode = $product ? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product->name), 0, 4)) : 'PROD';
        // Generate a random 3-digit code padded with zeros
        $randomCode = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $this->sku = 'RCP-'.$branchId.'-'.$nameCode.'-'.$randomCode;
    }

    public function addIngredient()
    {
        array_unshift($this->ingredients, [
            'item_id' => null,
            'quantity' => null,
            'uom' => null,
            'cost_per_unit' => null,
            'waste_percentage' => 0,
            'notes' => '',
            'preparation_notes' => '',
        ]);

        $this->dispatch('added-ingredient');
    }

    /**
     * When an ingredient item is selected, populate its details from Stock
     */
    public function updatedIngredients($value, $key)
    {
        // Parse the key to get index (e.g., "0.item_id" -> 0)
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'item_id' && $value) {
            $index = $parts[0];
            $itemId = $value;

            // Get item with stock details
            $item = Item::where('branch_id', $this->getBranchId())
                ->with(['stocks' => function ($q) {
                    $q->where('branch_id', $this->getBranchId());
                }])->find($itemId);

            if ($item) {
                // Get stock for this branch
                $stock = $item->stocks->first();
                $itemUnitPrice = (float) ($item->unit_price ?? 0);
                $stockAverageCost = (float) ($stock?->average_cost ?? 0);
                
                // Auto-populate ingredient fields from item
                $this->ingredients[$index]['quantity'] = null; // User must enter this
                $this->ingredients[$index]['uom'] = $item->uom ?? 'grams';
                $this->ingredients[$index]['cost_per_unit'] = $itemUnitPrice > 0 ? $itemUnitPrice : $stockAverageCost;
                $this->ingredients[$index]['waste_percentage'] = 0;
            }
        }
    }

    /**
     * When product_id is updated, populate recipe fields from product
     */
    public function updatedProductId()
    {
        if ($this->product_id) {
            $product = Product::with('productType')->find($this->product_id);
            if ($product) {
                $existingRecipe = $product->recipes()
                    ->when($this->getBranchId(), function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNull('branch_id')
                                ->orWhere('branch_id', $this->getBranchId());
                        });
                    })
                    ->first();

                if ($existingRecipe) {
                    $this->hasExistingRecipe = true;
                    $this->existingRecipeId = $existingRecipe->id;
                    return;
                }

                $this->hasExistingRecipe = false;
                $this->existingRecipeId = null;

                $this->yield_quantity = $product->recipe_yield ?? 1;
                $this->uom = $product->unitOfMeasure?->symbol ?? 'grams';
                
                // Get preparation time from the first recipe associated with the product
                $primaryRecipe = $product->recipes()->first();
                $this->preparation_time = $primaryRecipe?->preparation_time ?? null;

                // Auto-generate SKU
                $this->generateSku();

                // Auto-populate product type from product's actual ProductType relationship
                if ($product->productType) {
                    // Store the product_type_id for later use in validation
                    $this->product_type = $product->product_type_id ?? '';
                    \Log::info('✅ [RECIPE ADD] Product type set', [
                        'product_type_id' => $product->product_type_id,
                        'product_type_name' => $product->productType->name,
                    ]);
                }
            }
        }
    }



    public function addInstruction()
    {
        array_unshift($this->instructions, '');
        $this->dispatch('added-instruction');
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
        try {
            \Log::info('🔵 [RECIPE ADD] Save method started', [
                'product_id' => $this->product_id,
                'department_id' => $this->department_id,
                'ingredients_count' => count($this->ingredients),
                'is_super_admin' => is_super_admin(),
            ]);

            // Basic validation only - skip expensive database checks
            $rules = [
                'product_id' => 'required|string', // UUID format
                'sku' => 'required|string|max:255',
                'department_id' => 'required|integer',
                'product_type' => 'required', // Validate dynamically below
                'uom' => 'required|exists:units_of_measure,symbol',
                'yield_quantity' => 'required|numeric|min:0.01',
                'preparation_time' => 'nullable|integer|min:0',
                'status' => 'required|in:active,inactive,testing',
                'ingredients' => 'required|array|min:1',
                'ingredients.*.item_id' => 'required|string', // UUID format
                'ingredients.*.quantity' => 'required|numeric|min:0.01',
                'ingredients.*.uom' => 'required|exists:units_of_measure,symbol',
                'ingredients.*.cost_per_unit' => 'required|numeric|min:0',
                'ingredients.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
            ];

            \Log::info('🔵 [RECIPE ADD] Validating form data...');
            $this->validate($rules);
            \Log::info('✅ [RECIPE ADD] Form validation passed');

            // Validate product_type exists in database for this department
            \Log::info('🔵 [RECIPE ADD] Validating product type...');
            $productType = \App\Models\ProductType::where('id', $this->product_type)
                ->where('department_id', $this->department_id)
                ->first();
            
            if (!$productType) {
                throw new \Exception("Invalid product type for this department");
            }
            \Log::info('✅ [RECIPE ADD] Product type valid', [
                'product_type_id' => $this->product_type,
                'product_type_name' => $productType->name,
            ]);



            // Get product name
            \Log::info('🔵 [RECIPE ADD] Fetching product details...');
            $product = Product::find($this->product_id);
            if (!$product) {
                throw new \Exception("Product #{$this->product_id} not found");
            }
            $productName = $product->name;
            \Log::info('✅ [RECIPE ADD] Product found: ' . $productName);

            // Calculate cost for preview
            \Log::info('🔵 [RECIPE ADD] Calculating total cost...');
            $totalCost = collect($this->ingredients)->sum(function ($ing) {
                $quantity = (float) $ing['quantity'];
                $costPerUnit = (float) $ing['cost_per_unit'];
                $wastePercent = (float) ($ing['waste_percentage'] ?? 0);
                $actualQuantity = $quantity * (1 + ($wastePercent / 100));
                return $actualQuantity * $costPerUnit;
            });
            $costPerUnit = $totalCost / max((float) $this->yield_quantity, 1);
            \Log::info('✅ [RECIPE ADD] Cost calculated', [
                'total_cost' => $totalCost,
                'cost_per_unit' => $costPerUnit,
            ]);

            // Convert UOM symbol to ID
            $uomId = UnitOfMeasure::where('symbol', $this->uom)->first()?->id;
            if (!$uomId) {
                throw new \Exception("Invalid unit of measure: {$this->uom}");
            }

            // Convert ingredient UOM strings to IDs
            $ingredientsWithUomIds = array_map(function ($ingredient) {
                $ingredientUomId = UnitOfMeasure::where('symbol', $ingredient['uom'])->first()?->id;
                if (!$ingredientUomId) {
                    throw new \Exception("Invalid unit of measure for ingredient: {$ingredient['uom']}");
                }
                $ingredient['uom_id'] = $ingredientUomId;
                return $ingredient;
            }, $this->ingredients);

            $recipeData = [
                'product_id' => $this->product_id,
                'product_name' => $productName,
                'sku' => strtoupper($this->sku),
                'department_id' => $this->department_id,
                'product_type_id' => $this->product_type,  // Store the ID
                'cost_per_unit' => $costPerUnit,
                'uom_id' => $uomId,
                'yield_quantity' => $this->yield_quantity,
                'preparation_time' => $this->preparation_time,
                'instructions' => !empty($this->instructions) ? json_encode(array_values($this->instructions)) : null,
                'status' => $this->status,
                'ingredients' => $ingredientsWithUomIds,
            ];

            \Log::info('🔵 [RECIPE ADD] Checking admin status...');
            // Super admin bypass - save directly without audit
            if (is_super_admin()) {
                \Log::info('✅ [RECIPE ADD] Super admin detected - saving directly without audit');
                $this->saveRecipe($recipeData);
                return;
            }

            // Non-super-admin: show audit modal
            \Log::info('✅ [RECIPE ADD] Non-admin user - showing audit modal');
            $this->auditAction = 'create_recipe';
            $this->pendingItemId = null;
            $this->pendingItemData = $recipeData;
            $this->showAuditModal = true;
            \Log::info('✅ [RECIPE ADD] Audit modal displayed successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ [RECIPE ADD] Validation error', [
                'errors' => $e->errors(),
                'failed_message' => $e->getMessage(),
            ]);
            $this->toast()->error('Validation failed: Check form fields')->send();
            throw $e;
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE ADD] Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Error: ' . $e->getMessage())->send();
            throw $e;
        }
    }

    private function saveRecipe(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                // Validate SKU uniqueness just before save
                if (Recipe::where('sku', $data['sku'])->exists()) {
                    throw new \Exception('SKU already exists');
                }

                // Verify product exists
                if (!Product::where('id', $data['product_id'])->exists()) {
                    throw new \Exception('Selected product not found');
                }

                $ingredients = $data['ingredients'];
                unset($data['ingredients']);

                // Verify all items exist
                $itemIds = array_column($ingredients, 'item_id');
                $existingItems = Item::whereIn('id', $itemIds)->pluck('id')->toArray();
                if (count($existingItems) !== count($itemIds)) {
                    throw new \Exception('One or more selected items not found');
                }

                $requester = current_actor();
                $data['created_by_id'] = $requester->id;
                $data['created_by_type'] = get_class($requester);
                $data['branch_id'] = $this->getBranchId();

                $recipe = Recipe::create($data);

                // Save ingredients
                foreach ($ingredients as $ingredient) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'item_id' => $ingredient['item_id'],
                        'quantity' => $ingredient['quantity'],
                        'uom_id' => $ingredient['uom_id'],
                        'cost_per_unit' => $ingredient['cost_per_unit'],
                        'waste_percentage' => $ingredient['waste_percentage'] ?? 0,
                        'notes' => $ingredient['notes'] ?? null,
                        'preparation_notes' => $ingredient['preparation_notes'] ?? null,
                    ]);
                }
            });

            \Log::info('✅ [RECIPE ADD] Recipe created successfully');
            $this->toast()->success('Recipe created successfully')->send();
            return $this->redirect(branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $this->dept_slug]), navigate: true);
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE ADD] Failed to save recipe', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Failed to create recipe: ' . $e->getMessage())->send();
            throw $e;
        }
    }



    /**
     * Submit audit request for recipe creation
     * Validates reason (min 10, max 500 chars) and creates ApprovalAuditRequest
     */
    public function submitAuditRequest()
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ]);

        $requester = current_actor();
        
        ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'recipe:' . $this->auditAction,
            'description' => $this->auditReason,
            'payload' => $this->pendingItemData,
            'status' => 'pending',
            'branch_id' => $this->getBranchId(),
        ]);

        $this->closeAuditModal();
        $this->toast()->success('Approval request submitted')->send();
        return $this->redirect(branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $this->dept_slug]), navigate: true);
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

        // Simplified: Load all active products for the department without checking recipes (performance optimization)
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

        // Filter items by current branch
        $items = Item::where('branch_id', $branchId)
            ->orderBy('name')
            ->get();
        
        // Get product types for the department
        $productTypes = \App\Models\ProductType::where('department_id', $this->department->id)
            ->whereHas('products', function ($q) use ($branchId) {
                $q->where('is_active', 1)
                    ->where(function ($query) use ($branchId) {
                        $query->whereNull('branch_id')
                            ->orWhere('branch_id', $branchId);
                    });
            })
            ->orderBy('sort_order')
            ->get();

        // Get all units of measure
        $unitsOfMeasure = UnitOfMeasure::active();

        return view('livewire.branch-dashboard.production.recipes.add', [
            'products' => $products,
            'items' => $items,
            'department' => $this->department,
            'dept_slug'=>$this->dept_slug,
            'productTypes' => $productTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ]);
    }
}
