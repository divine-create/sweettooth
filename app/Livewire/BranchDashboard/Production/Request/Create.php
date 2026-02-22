<?php
namespace App\Livewire\BranchDashboard\Production\Request;

use App\Models\Department;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductionRequest;
use App\Models\Recipe;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Create extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public $selectedProducts = [];
    public $currentShift = null;
    public $notes = '';

    public function mount($deptSlug = null)
    {
        $deptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        $this->dept_slug = $deptSlug;
        $this->department = $deptSlug ? $this->resolveProductionDepartment($deptSlug) : null;

        if (!$this->department) {
            abort(404, 'Department not found');
        }

        $this->determineCurrentShift();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    private function resolveProductionDepartment(string $deptSlug): ?Department
    {
        $branchId = $this->getBranchId();

        $query = Department::query()
            ->where('slug', $deptSlug)
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->whereRaw('LOWER(name) = ?', ['production']);
            });

        if ($branchId) {
            $query->where(function ($scopeQuery) use ($branchId) {
                $scopeQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })->orderByRaw('branch_id IS NULL');
        }

        return $query->first();
    }

    /**
     * Build product query constrained by this page's production department context.
     */
    private function getDepartmentProductsQuery()
    {
        $branchId = $this->getBranchId();
        $departmentId = $this->department?->id;

        $productsQuery = Product::query()
            ->where('is_active', 1)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            });

        if (! $departmentId) {
            return $productsQuery->whereRaw('1 = 0');
        }

        $recipesQuery = Recipe::query()
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            });

        $recipeProductIds = (clone $recipesQuery)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->map(static fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        $recipeProductNames = (clone $recipesQuery)
            ->pluck('product_name')
            ->filter(static fn ($name): bool => is_string($name) && trim($name) !== '')
            ->map(static fn ($name): string => trim((string) $name))
            ->unique()
            ->values()
            ->all();

        $departmentProductTypeIds = ProductType::query()
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $departmentTypeProductIds = empty($departmentProductTypeIds)
            ? []
            : (clone $productsQuery)
                ->whereIn('product_type_id', $departmentProductTypeIds)
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->all();

        $departmentPivotProductIds = (clone $productsQuery)
            ->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->where('is_available', true);
            })
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $recipeByNameProductIds = empty($recipeProductNames)
            ? []
            : (clone $productsQuery)
                ->whereIn('name', $recipeProductNames)
                ->where(function ($query) use ($departmentProductTypeIds, $departmentId) {
                    if (! empty($departmentProductTypeIds)) {
                        $query->whereIn('product_type_id', $departmentProductTypeIds)
                            ->orWhereHas('departments', function ($deptQuery) use ($departmentId) {
                                $deptQuery->where('department_id', $departmentId)
                                    ->where('is_available', true);
                            });
                    } else {
                        $query->whereHas('departments', function ($deptQuery) use ($departmentId) {
                            $deptQuery->where('department_id', $departmentId)
                                ->where('is_available', true);
                        });
                    }
                })
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->all();

        $allowedProductIds = array_values(array_unique(array_merge(
            $recipeProductIds,
            $departmentTypeProductIds,
            $departmentPivotProductIds,
            $recipeByNameProductIds
        )));

        if (empty($allowedProductIds)) {
            return $productsQuery->whereRaw('1 = 0');
        }

        return $productsQuery->whereIn('id', $allowedProductIds);
    }

    /**
     * Determine current shift based on time
     */
    public function determineCurrentShift()
    {
        $currentHour = now()->format('H');

        // Morning shift: 6:00 AM - 2:00 PM (06:00 - 14:00)
        // Afternoon shift: 2:00 PM - 10:00 PM (14:00 - 22:00)
        if ($currentHour >= 6 && $currentHour < 14) {
            $this->currentShift = 'morning';
        } elseif ($currentHour >= 14 && $currentHour < 22) {
            $this->currentShift = 'afternoon';
        } else {
            $this->currentShift = 'morning'; // Default to morning for night hours
        }
    }

    public function addProduct()
    {
        $this->selectedProducts[] = [
            'product_id'      => null,
            'quantity'        => 1,
            'product_details' => null,
            'recipe_id'       => null,
        ];
    }

    public function removeProduct($index)
    {
        unset($this->selectedProducts[$index]);
        $this->selectedProducts = array_values($this->selectedProducts);
    }

    /**
     * Load product and recipe details when product is selected
     */
    public function updatedSelectedProducts($value, $key)
    {
        // Extract index from key (e.g., "0.product_id" -> 0)
        if (str_contains($key, '.product_id')) {
            $index     = explode('.', $key)[0];
            $productId = $this->selectedProducts[$index]['product_id'];

            if ($productId) {
                $branchId = $this->getBranchId();
                $product = $this->getDepartmentProductsQuery()
                    ->where('id', $productId)
                    ->first();

                if (! $product) {
                    $this->selectedProducts[$index]['recipe_id'] = null;
                    $this->selectedProducts[$index]['product_details'] = null;

                    return;
                }

                // Find recipe for this product in the current department
                $recipe = Recipe::with('ingredients.item')
                    ->where('department_id', $this->department->id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($product) {
                        $query->where('product_id', $product->id)
                            ->orWhere(function ($fallbackQuery) use ($product) {
                                $fallbackQuery->whereNull('product_id')
                                    ->where('product_name', $product->name);
                            });
                    })
                    ->where(function ($query) use ($branchId) {
                        $query->whereNull('branch_id')
                            ->orWhere('branch_id', $branchId);
                    })
                    ->first();

                if ($recipe) {
                    $this->selectedProducts[$index]['recipe_id']       = $recipe->id;
                    $this->selectedProducts[$index]['product_details'] = [
                        'name'           => $product->name,
                        'recipe_name'    => $recipe->product_name,
                        'yield_quantity' => $recipe->yield_quantity,
                        'uom'            => $recipe->unitOfMeasure?->symbol ?? 'N/A',
                        'ingredients'    => $recipe->ingredients->map(function ($ing) {
                            return [
                                'item_id'            => $ing->item_id,
                                'item_name'          => $ing->item->name ?? 'N/A',
                                'quantity_per_batch' => $ing->quantity,
                                'uom'                => $ing->unitOfMeasure?->symbol ?? 'N/A',
                                'waste_percentage'   => $ing->waste_percentage,
                            ];
                        })->toArray(),
                    ];
                } else {
                    $this->selectedProducts[$index]['recipe_id']       = null;
                    $this->selectedProducts[$index]['product_details'] = null;
                }
            }
        }
    }

    public function save()
    {
        $allowedProductIds = $this->getDepartmentProductsQuery()
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $this->validate([
            'selectedProducts'              => 'required|array|min:1',
            'selectedProducts.*.product_id' => ['required', 'exists:products,id', Rule::in($allowedProductIds)],
            'selectedProducts.*.quantity'   => 'required|numeric|min:1',
        ], [
            'selectedProducts.required'              => 'Please add at least one product to request.',
            'selectedProducts.*.product_id.required' => 'Please select a product.',
            'selectedProducts.*.product_id.in'       => 'Selected product is not available for this department.',
            'selectedProducts.*.quantity.required'   => 'Please enter quantity.',
        ]);

        $employee     = is_super_admin() ? auth()->user() : Auth::guard('web')->user();
        $branchId     = $this->getBranchId();

        DB::transaction(function () use ($employee, $branchId) {
            // Get or create shift for today
            $departmentKey = $this->department->slug ?? (string) $this->department->id;
            if (is_super_admin() && $this->department->slug) {
                $departmentKey = $this->department->slug;
            }

            $shift = Shift::firstOrCreate([
                'branch_id'     => $branchId,
                'department_id' => $this->department->id,
                'shift_date'    => today(),
                'shift_type'    => $this->currentShift,
            ], [
                'employee_id'  => $employee->id,
                'shift_number' => Shift::generateShiftNumber(
                    (string) $departmentKey,
                    (string) $employee->id,
                    now(),
                    (string) $this->currentShift
                ),
                'status'       => 'active',
            ]);
          
            // Create Item Request
            $deptCode      = strtoupper(substr($this->department->name ?? 'DEPT', 0, 4));
            $requestNumber = ItemRequest::generateRequestNumber(
                substr($branchId, 0, 8),
                $deptCode
            );

            $itemRequest = ItemRequest::create([
                'branch_id'         => $branchId,
                'department_id'     => $this->department->id,
                'requested_by_id'   => $employee->id,
                'requested_by_type' => get_class($employee),
                'request_number'    => $requestNumber,
                'request_date'      => today(),
                'shift'             => $this->currentShift,
                'status'            => 'pending',
                'notes'             => $this->notes,
            ]);

            // Process each selected product
            foreach ($this->selectedProducts as $selectedProduct) {
                $unitsRequested = (float) $selectedProduct['quantity']; // Units requested by user

                // Check if there's a recipe for this product
                if ($selectedProduct['recipe_id']) {
                    // Get the recipe for this product
                    $recipe = Recipe::with('ingredients')->find($selectedProduct['recipe_id']);

                    if ($recipe) {
                        $recipeYield = (float) $recipe->yield_quantity; // Units per batch
                        if ($recipeYield <= 0) {
                            throw new \Exception("Recipe yield must be greater than 0 for {$recipe->product_name}.");
                        }
                        $batchCount = (int) ceil($unitsRequested / $recipeYield);
                        $plannedUnits = $batchCount * $recipeYield;

                        // Create Production Request (store actual units, not batches)
                        ProductionRequest::create([
                            'shift_id'                    => $shift->id, // Use shift even for super admin
                            'item_request_id'             => $itemRequest->id,
                            'recipe_id'                   => $recipe->id,
                            'planned_production_quantity' => $plannedUnits, // Batch-first planned units
                            'requested_units'             => $unitsRequested,
                        ]);

                        // Create Item Request Details for each ingredient
                        $ingredientsNeeded = $recipe->calculateIngredientsForBatch($batchCount);
                        foreach ($ingredientsNeeded as $ingredient) {
                            $totalQuantity = (float) $ingredient['quantity'];

                            ItemRequestDetail::create([
                                'request_id'          => $itemRequest->id,
                                'item_id'             => $ingredient['item_id'],
                                'quantity_requested'  => $totalQuantity,
                                'quantity_approved'   => 0,
                                'quantity_dispatched' => 0,
                                'uom_id'              => $ingredient['uom_id'],
                                'notes'               => "For {$recipe->product_name} production (requested {$unitsRequested} {$recipe->unitOfMeasure?->symbol}; plan {$batchCount} batch(es) = {$plannedUnits} {$recipe->unitOfMeasure?->symbol})",
                            ]);
                        }
                    } else {
                        // Recipe not found, handle as product without recipe
                        $product = Product::find($selectedProduct['product_id']);

                        ProductionRequest::create([
                            'shift_id'                    => $shift->id,
                            'item_request_id'             => $itemRequest->id,
                            'recipe_id'                   => null, // No recipe
                            'planned_production_quantity' => $unitsRequested, // Use quantity as units
                            'requested_units'             => $unitsRequested,
                        ]);

                        // For products without recipes, we can't create ingredient requests
                        // So we just create a note about the request
                        ItemRequestDetail::create([
                            'request_id'          => $itemRequest->id,
                            'item_id'             => null, // No specific item
                            'quantity_requested'  => $unitsRequested,
                            'quantity_approved'   => 0,
                            'quantity_dispatched' => 0,
                            'uom_id'              => null, // No specific UOM
                            'notes'               => "Request for {$product->name} without recipe ({$unitsRequested} units)",
                        ]);
                    }
                } else {
                    // No recipe_id means this product doesn't have a recipe
                    $product = Product::find($selectedProduct['product_id']);

                    ProductionRequest::create([
                        'shift_id'                    => $shift->id,
                        'item_request_id'             => $itemRequest->id,
                        'recipe_id'                   => null, // No recipe
                        'planned_production_quantity' => $unitsRequested, // Use quantity as units
                        'requested_units'             => $unitsRequested,
                    ]);

                    // For products without recipes, we can't create ingredient requests
                    // So we just create a note about the request
                    ItemRequestDetail::create([
                        'request_id'          => $itemRequest->id,
                        'item_id'             => null, // No specific item
                        'quantity_requested'  => $unitsRequested,
                        'quantity_approved'   => 0,
                        'quantity_dispatched' => 0,
                        'uom_id'              => null, // No specific UOM
                        'notes'               => "Request for {$product->name} without recipe ({$unitsRequested} units)",
                    ]);
                }
            }
        });

        $this->toast()->success('Production request created successfully!')->send();
        return $this->redirect(branch_route('branch-dashboard.production.request.index', [
            'deptSlug' => $this->dept_slug,
            'b_id' => $this->getBranchId()
        ]), navigate: true);
    }

    public function render()
    {
        $products = $this->getDepartmentProductsQuery()
            ->orderBy('name')
            ->get();

        return view('livewire.branch-dashboard.production.request.create', [
            'products' => $products,
        ]);
    }
}
