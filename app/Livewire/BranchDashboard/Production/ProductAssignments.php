<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\ApprovalAuditRequest;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Recipe;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use function is_super_admin;

#[Layout('components.layouts.app.branch-dashboard')]
class ProductAssignments extends BaseComponent
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public ?int $quantity = 10;

    public ?string $search = null;

    public ?int $filterProductType = null;

    public array $selectedProductIds = [];

    public ?int $selectedSalesDepartmentId = null;

    public ?string $selectedProductIdForRecipe = null;

    public ?int $selectedRecipeId = null;

    public string $activeTab = 'sales';

    public bool $showAuditModal = false;

    public ?string $auditAction = null;

    public string $auditReason = '';

    public array $pendingPayload = [];

    private int $cacheTtlMinutes = 5;

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public function mount($deptSlug = null)
    {
        $this->mountBase();

        $requestedDeptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        if ($requestedDeptSlug) {
            $this->department = Department::select('id', 'name', 'slug')
                ->where('slug', $requestedDeptSlug)
                ->first();

            if ($this->department) {
                $this->dept_slug = $this->department->slug;
            }
        }
    }

    protected function getModelClass(): string
    {
        return Product::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterProductType()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterProductType = null;
        $this->resetPage();
    }

    public function updatedSelectedSalesDepartmentId($value)
    {
        if ($value === '' || $value === null) {
            $this->selectedSalesDepartmentId = null;
        }
    }

    public function updatedSelectedRecipeId($value)
    {
        if ($value === '' || $value === null) {
            $this->selectedRecipeId = null;
        }
    }

    public function applySalesDepartmentAssignment(): void
    {
        if (empty($this->selectedProductIds)) {
            $this->toast()->warning('Please select at least one product')->send();
            return;
        }

        if (! $this->selectedSalesDepartmentId) {
            $this->toast()->warning('Select a sales department to assign')->send();
            return;
        }

        if (! $this->isValidSalesDepartment($this->selectedSalesDepartmentId)) {
            $this->toast()->error('Please select a valid sales department for this branch.')->send();
            return;
        }

        $payload = [
            'type' => 'sales_department',
            'product_ids' => $this->selectedProductIds,
            'sales_department_id' => $this->selectedSalesDepartmentId,
            'department_id' => $this->department?->id,
        ];

        if (should_bypass_approval()) {
            $this->executeSalesDepartmentAssignment($payload);
            return;
        }

        $this->auditAction = 'assignments';
        $this->pendingPayload = $payload;
        $this->showAuditModal = true;
    }

    public function applyRecipeAssignment(): void
    {
        if (! $this->selectedProductIdForRecipe) {
            $this->toast()->warning('Please select a product')->send();
            return;
        }

        if (! $this->selectedRecipeId) {
            $this->toast()->warning('Please select a recipe')->send();
            return;
        }

        if (! $this->isValidRecipe($this->selectedRecipeId)) {
            $this->toast()->error('Please select a valid recipe for this department.')->send();
            return;
        }

        $payload = [
            'type' => 'recipe',
            'product_id' => $this->selectedProductIdForRecipe,
            'recipe_id' => $this->selectedRecipeId,
            'department_id' => $this->department?->id,
        ];

        if (should_bypass_approval()) {
            $this->executeRecipeAssignment($payload);
            return;
        }

        $this->auditAction = 'assignments';
        $this->pendingPayload = $payload;
        $this->showAuditModal = true;
    }

    private function executeSalesDepartmentAssignment(array $payload): void
    {
        try {
            $productIds = $payload['product_ids'] ?? [];
            $salesDepartmentId = $payload['sales_department_id'] ?? null;

            Product::whereIn('id', $productIds)->update([
                'sales_department_id' => $salesDepartmentId,
            ]);

            $this->selectedProductIds = [];
            $this->selectedSalesDepartmentId = null;
            $this->toast()->success('Sales department assigned successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Failed to assign sales department: '.$e->getMessage())->send();
        }
    }

    private function executeRecipeAssignment(array $payload): void
    {
        try {
            $productId = $payload['product_id'] ?? null;
            $recipeId = $payload['recipe_id'] ?? null;

            $product = Product::findOrFail($productId);
            $recipe = Recipe::findOrFail($recipeId);

            $recipe->update([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'product_type_id' => $product->product_type_id,
            ]);

            $this->selectedProductIdForRecipe = null;
            $this->selectedRecipeId = null;
            $this->toast()->success('Recipe assigned successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Failed to assign recipe: '.$e->getMessage())->send();
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
            'action' => 'product:assignments',
            'description' => $this->auditReason,
            'payload' => $this->pendingPayload,
            'status' => 'pending',
            'branch_id' => $this->getBranchId(),
        ]);

        $this->closeAuditModal();
        $this->toast()->success('Approval request submitted for review')->send();
        $this->dispatch('refresh');
    }

    public function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;
        $this->pendingPayload = [];
    }

    private function salesDepartmentsQuery()
    {
        $branchId = $this->getBranchId();

        return Department::query()
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            });
    }

    private function isValidSalesDepartment(int $departmentId): bool
    {
        return $this->salesDepartmentsQuery()
            ->where('id', $departmentId)
            ->exists();
    }

    private function isValidRecipe(int $recipeId): bool
    {
        $branchId = $this->getBranchId();

        return Recipe::query()
            ->where('id', $recipeId)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($this->department, function ($q) {
                $q->where('department_id', $this->department->id);
            })
            ->exists();
    }

    public function render()
    {
        $productTypes = collect();
        $recipes = collect();
        $products = collect();
        if ($this->department) {
            $productTypes = ProductType::where('department_id', $this->department->id)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();

            $recipes = Recipe::where('department_id', $this->department->id)
                ->when($this->getBranchId(), function ($q) {
                    $q->where('branch_id', $this->getBranchId());
                })
                ->orderBy('product_name')
                ->select('id', 'product_name', 'sku')
                ->get();

            $products = Product::query()
                ->select('id', 'name', 'sku', 'product_type_id', 'branch_id')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('products.name', 'like', '%'.$this->search.'%')
                            ->orWhere('products.sku', 'like', '%'.$this->search.'%')
                            ->orWhere('products.description', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->filterProductType, function ($query) {
                    $query->where('product_type_id', $this->filterProductType);
                })
                ->whereHas('productType', function ($q) {
                    $q->where('department_id', $this->department->id);
                })
                ->where(function ($query) {
                    $query->whereNull('branch_id')
                        ->orWhere('branch_id', $this->getBranchId());
                })
                ->orderBy('name')
                ->get();
        }

        $salesDepartments = $this->salesDepartmentsQuery()
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $productOptions = $products->map(function ($product) {
            return [
                'value' => $product->id,
                'label' => $product->name.' ('.$product->sku.')',
            ];
        })->values()->all();

        $recipeOptions = $recipes->map(function ($recipe) {
            return [
                'value' => $recipe->id,
                'label' => $recipe->product_name.' ('.$recipe->sku.')',
            ];
        })->values()->all();

        $salesDepartmentOptions = $salesDepartments->map(function ($dept) {
            return [
                'value' => $dept->id,
                'label' => $dept->name,
            ];
        })->values()->all();

        return view('livewire.branch-dashboard.production.product-assignments', [
            'products' => $products,
            'productTypes' => $productTypes,
            'recipes' => $recipes,
            'salesDepartments' => $salesDepartments,
            'productOptions' => $productOptions,
            'recipeOptions' => $recipeOptions,
            'salesDepartmentOptions' => $salesDepartmentOptions,
            'department' => $this->department,
            'dept_slug' => $this->dept_slug,
        ]);
    }
}
