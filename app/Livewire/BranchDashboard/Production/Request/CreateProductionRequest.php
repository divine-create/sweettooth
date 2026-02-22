<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Events\ProductionRequest\RequestCreated;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductionRequest;
use App\Models\Recipe;
use Livewire\Component;

class CreateProductionRequest extends Component
{
    public $productionDepartmentId;
    public $priority = 'normal';
    public $notes = '';
    public $selectedProducts = []; // Array of ['product_id' => id, 'batches' => qty]
    public $availableProducts = [];
    public $productionDepartments = [];
    public $showSuccessMessage = false;
    public $showModal = false;

    public function mount()
    {
        $this->loadDepartments();
    }

    public function loadDepartments()
    {
        $this->productionDepartments = Department::where('type', 'production')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
            ])
            ->toArray();
    }

    public function updatedProductionDepartmentId()
    {
        $this->selectedProducts = [];
        $this->loadAvailableProducts();
    }

    public function loadAvailableProducts()
    {
        if (!$this->productionDepartmentId) {
            $this->availableProducts = [];
            return;
        }

        // Get recipes for this production department
        $recipes = Recipe::where('department_id', $this->productionDepartmentId)
            ->where('status', 'active')
            ->with(['product', 'unitOfMeasure'])
            ->get();

        // Map recipes to available products (recipes are what production can make)
        $this->availableProducts = $recipes->map(fn($recipe) => [
            'id' => $recipe->product_id ?? $recipe->id,
            'recipe_id' => $recipe->id,
            'name' => $recipe->product_name,
            'sku' => $recipe->sku ?? $recipe->product?->sku,
            'yield_quantity' => $recipe->yield_quantity,
            'uom' => $recipe->unitOfMeasure?->symbol ?? 'unit',
            'has_recipe' => true,
        ])->toArray();

        // If no recipes found, fall back to products in department
        if (empty($this->availableProducts)) {
            $this->availableProducts = Product::whereHas('productType', function ($query) {
                    $query->where('department_id', $this->productionDepartmentId);
                })
                ->where('is_active', true)
                ->with('unit')
                ->get()
                ->map(fn($product) => [
                    'id' => $product->id,
                    'recipe_id' => null,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'yield_quantity' => 1,
                    'uom' => $product->unit?->name ?? 'unit',
                    'has_recipe' => false,
                ])
                ->toArray();
        }
    }

    public function addProduct($productId, $recipeId = null)
    {
        // Check if product already selected
        if (collect($this->selectedProducts)->contains('product_id', $productId)) {
            return;
        }

        // Find the product/recipe info from available products
        $productInfo = collect($this->availableProducts)->firstWhere('id', $productId);

        $this->selectedProducts[] = [
            'product_id' => $productId,
            'recipe_id' => $recipeId ?? ($productInfo['recipe_id'] ?? null),
            'batches' => 1,
            'yield_per_batch' => $productInfo['yield_quantity'] ?? 1,
        ];
    }

    public function removeProduct($index)
    {
        unset($this->selectedProducts[$index]);
        $this->selectedProducts = array_values($this->selectedProducts); // Re-index
    }

    public function updateBatches($index, $value)
    {
        if (isset($this->selectedProducts[$index])) {
            $this->selectedProducts[$index]['batches'] = max(0, (int)$value);
        }
    }

    public function getSelectedProductsWithDetails()
    {
        return collect($this->selectedProducts)->map(function ($item) {
            // Find the product/recipe in available products
            $productInfo = collect($this->availableProducts)->firstWhere('id', $item['product_id']);

            // If we have a recipe_id, get the recipe details
            if ($item['recipe_id'] ?? null) {
                $recipe = Recipe::with('unitOfMeasure')->find($item['recipe_id']);
                return [
                    'id' => $item['product_id'],
                    'recipe_id' => $item['recipe_id'],
                    'name' => $recipe?->product_name ?? $productInfo['name'] ?? 'Unknown',
                    'sku' => $recipe?->sku ?? $productInfo['sku'] ?? null,
                    'batches' => $item['batches'],
                    'yield_per_batch' => $recipe?->yield_quantity ?? $item['yield_per_batch'] ?? 1,
                    'uom' => $recipe?->unitOfMeasure?->symbol ?? $productInfo['uom'] ?? 'units',
                    'has_recipe' => true,
                ];
            }

            // Fallback to product info
            $product = Product::find($item['product_id']);
            return [
                'id' => $item['product_id'],
                'recipe_id' => null,
                'name' => $product?->name ?? $productInfo['name'] ?? 'Unknown',
                'sku' => $product?->sku ?? $productInfo['sku'] ?? null,
                'batches' => $item['batches'],
                'yield_per_batch' => $item['yield_per_batch'] ?? 1,
                'uom' => $productInfo['uom'] ?? 'units',
                'has_recipe' => false,
            ];
        })->toArray();
    }

    public function createRequest()
    {
        $this->validate([
            'productionDepartmentId' => 'required|exists:departments,id',
            'priority' => 'required|in:normal,urgent',
            'selectedProducts' => 'required|array|min:1',
            'selectedProducts.*.batches' => 'required|numeric|min:1',
        ]);

        try {
            $createdRequests = [];

            // Create a separate production request for each product/recipe
            foreach ($this->selectedProducts as $item) {
                $batches = (int) $item['batches'];
                $yieldPerBatch = (float) ($item['yield_per_batch'] ?? 1);
                $totalQuantity = $batches * $yieldPerBatch;

                // Get recipe info for notes
                $recipeName = null;
                if ($item['recipe_id']) {
                    $recipe = Recipe::find($item['recipe_id']);
                    $recipeName = $recipe?->product_name;
                }

                $request = ProductionRequest::create([
                    'production_department_id' => $this->productionDepartmentId,
                    'recipe_id' => $item['recipe_id'] ?? null,
                    'priority' => $this->priority,
                    'planned_production_quantity' => $totalQuantity,
                    'requested_units' => $totalQuantity,
                    'notes' => $this->notes . ($recipeName ? " | Product: {$recipeName} ({$batches} batches)" : ''),
                    'created_by_id' => auth()->id(),
                    'status' => 'pending',
                ]);

                $createdRequests[] = $request;

                // Broadcast event for real-time notification
                if (class_exists(RequestCreated::class)) {
                    broadcast(new RequestCreated($request))->toOthers();
                }
            }

            $this->showSuccessMessage = true;
            $this->resetForm();

            $this->dispatch('requestCreated', ['count' => count($createdRequests)]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create production request: ' . $e->getMessage());
        }
    }

    private function resetForm()
    {
        $this->priority = 'normal';
        $this->notes = '';
        $this->selectedProducts = [];
        $this->productionDepartmentId = null;
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.request.create-production-request', [
            'selectedProductsDetails' => $this->getSelectedProductsWithDetails(),
        ]);
    }
}
