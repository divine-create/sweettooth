<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests;

use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\SalesProductionRequest;
use App\Models\SalesProductionRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Create extends Component
{
    use Interactions;
    use SalesDepartmentContext;

    public $selectedProductionDepartmentId = null;
    public $selectedRecipeId = null;
    public $quantityRequested = 1;
    public string $priority = 'normal';
    public string $notes = '';
    public string $submitError = '';
    public string $submitSuccess = '';

    /** @var array<int, array{id:int,name:string}> */
    public array $productionDepartments = [];

    /** @var array<int, array{id:string,name:string,sku:?string}> */
    public array $availableRecipes = [];

    /** @var array<int, array{
     *     production_department_id:int,
     *     production_department_name:string,
     *     recipe_id:int,
     *     recipe_name:string,
     *     product_id:?string,
     *     product_name:?string,
     *     sku:?string,
     *     yield_quantity:float,
     *     uom:?string,
     *     quantity_requested:float
     * }>
     */
    public array $cartItems = [];

    public function mount(): void
    {
        $this->initializeDepartmentContext();
        $this->loadProductionDepartments();

        if ($this->selectedProductionDepartmentId) {
            $this->loadRecipesForDepartment();
        }
    }

    public function updatedSelectedProductionDepartmentId(): void
    {
        if ($this->selectedProductionDepartmentId !== null && $this->selectedProductionDepartmentId !== '') {
            $this->selectedProductionDepartmentId = (int) $this->selectedProductionDepartmentId;
        }

        $this->selectedRecipeId = null;
        $this->loadRecipesForDepartment();
    }

    public function loadProductionDepartments(): void
    {
        $branchId = $this->getBranchId();

        $this->productionDepartments = Department::query()
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['production']);
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->toArray();
    }

    public function loadRecipesForDepartment(): void
    {
        if (! $this->selectedProductionDepartmentId) {
            $this->availableRecipes = [];

            return;
        }

        $departmentId = (int) $this->selectedProductionDepartmentId;
        $departmentIds = $this->resolveEquivalentProductionDepartmentIds($departmentId);
        $branchId = $this->getBranchId();
        $departmentProductIds = $this->resolveDepartmentProductIds($departmentIds, $branchId);

        // Strict sales ownership filter: only recipes tied to products owned by this sales department.
        if (empty($departmentProductIds)) {
            $this->availableRecipes = [];

            return;
        }

        $recipesQuery = Recipe::query()
            ->where('status', 'active')
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->whereIn('product_id', $departmentProductIds)
            ->with(['product:id,name,sku', 'unitOfMeasure:id,symbol']);

        $this->availableRecipes = $recipesQuery
            ->orderBy('product_name')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'product_id' => $recipe->product_id,
                'recipe_name' => $recipe->product_name,
                'product_name' => $recipe->product?->name,
                'sku' => $recipe->sku ?: $recipe->product?->sku,
                'yield_quantity' => (float) ($recipe->yield_quantity ?? 0),
                'uom' => $recipe->unitOfMeasure?->symbol,
            ])
            ->toArray();
    }

    /**
     * Resolve products that belong to the selected production department scope.
     *
     * @param  array<int>  $departmentIds
     * @return array<int, string>
     */
    protected function resolveDepartmentProductIds(array $departmentIds, ?string $branchId): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();
        if (empty($salesDepartmentIds)) {
            return [];
        }

        $query = Product::query()
            ->where('is_active', true)
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->where(function ($subQuery) use ($departmentIds) {
                $subQuery->whereHas('productType', function ($productTypeQuery) use ($departmentIds) {
                    $productTypeQuery->whereIn('department_id', $departmentIds);
                })->orWhereHas('departments', function ($departmentQuery) use ($departmentIds) {
                    $departmentQuery->whereIn('departments.id', $departmentIds)
                        ->where('department_product.is_available', true);
                });
            });

        if ($branchId) {
            $query->where(function ($subQuery) use ($branchId) {
                $subQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve equivalent sales department IDs from current slug in branch/global scope.
     *
     * @return array<int>
     */
    protected function resolveEquivalentSalesDepartmentIds(): array
    {
        if (! $this->departmentId && ! $this->salesDeptSlug) {
            return [];
        }

        $branchId = $this->getBranchId();
        $query = Department::query()
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->whereRaw('LOWER(name) = ?', ['sales']);
            });

        if ($this->salesDeptSlug) {
            $query->where('slug', $this->salesDeptSlug);
        } elseif ($this->departmentId) {
            $query->where('id', (int) $this->departmentId);
        }

        if ($branchId) {
            $query->where(function ($scopeQuery) use ($branchId) {
                $scopeQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $ids = $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($this->departmentId && ! in_array((int) $this->departmentId, $ids, true)) {
            $ids[] = (int) $this->departmentId;
        }

        return $ids;
    }

    /**
     * Resolve equivalent production department records across branch/global scopes.
     *
     * @return array<int>
     */
    protected function resolveEquivalentProductionDepartmentIds(int $departmentId): array
    {
        $department = Department::find($departmentId);
        if (! $department) {
            return [$departmentId];
        }

        $branchId = $this->getBranchId();
        $candidates = Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['production']);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get(['id', 'name', 'slug']);

        $targetNameKey = $this->normalizeDepartmentKey($department->name);
        $targetSlugKey = $this->normalizeDepartmentKey((string) $department->slug);

        $equivalentIds = $candidates
            ->filter(function (Department $candidate) use ($departmentId, $targetNameKey, $targetSlugKey): bool {
                if ((int) $candidate->id === $departmentId) {
                    return true;
                }

                $candidateNameKey = $this->normalizeDepartmentKey($candidate->name);
                $candidateSlugKey = $this->normalizeDepartmentKey((string) $candidate->slug);

                return ($targetNameKey !== '' && $candidateNameKey === $targetNameKey)
                    || ($targetSlugKey !== '' && $candidateSlugKey === $targetSlugKey)
                    || ($targetNameKey !== '' && $candidateSlugKey === $targetNameKey)
                    || ($targetSlugKey !== '' && $candidateNameKey === $targetSlugKey);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array($departmentId, $equivalentIds, true)) {
            $equivalentIds[] = $departmentId;
        }

        return $equivalentIds;
    }

    protected function normalizeDepartmentKey(string $value): string
    {
        $normalized = strtolower($value);
        $normalized = preg_replace('/[-_]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bproduction\b/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    public function addItem(): void
    {
        $this->submitError = '';
        $this->submitSuccess = '';

        $this->validate([
            'selectedProductionDepartmentId' => ['required', 'exists:departments,id'],
            'selectedRecipeId' => ['required', 'exists:recipes,id'],
            'quantityRequested' => ['required', 'numeric', 'min:0.01'],
        ]);

        $department = collect($this->productionDepartments)->firstWhere('id', $this->selectedProductionDepartmentId);
        $recipe = collect($this->availableRecipes)->firstWhere('id', $this->selectedRecipeId);

        // If options are stale in the browser, refresh source list once before failing.
        if (! $recipe && $this->selectedProductionDepartmentId) {
            $this->loadRecipesForDepartment();
            $recipe = collect($this->availableRecipes)->firstWhere('id', $this->selectedRecipeId);
        }

        if (! $department || ! $recipe) {
            $this->toast()->error('Please select a valid department and recipe.')->send();

            return;
        }

        $existingIndex = collect($this->cartItems)->search(function (array $item): bool {
            return (int) $item['production_department_id'] === (int) $this->selectedProductionDepartmentId
                && (int) $item['recipe_id'] === (int) $this->selectedRecipeId;
        });

        if ($existingIndex !== false) {
            $this->cartItems[$existingIndex]['quantity_requested'] =
                (float) $this->cartItems[$existingIndex]['quantity_requested'] + (float) $this->quantityRequested;
        } else {
            $this->cartItems[] = [
                'production_department_id' => (int) $department['id'],
                'production_department_name' => $department['name'],
                'recipe_id' => (int) $recipe['id'],
                'recipe_name' => (string) $recipe['recipe_name'],
                'product_id' => $recipe['product_id'] ? (string) $recipe['product_id'] : null,
                'product_name' => $recipe['product_name'] ?? null,
                'sku' => $recipe['sku'] ?? null,
                'yield_quantity' => (float) ($recipe['yield_quantity'] ?? 0),
                'uom' => $recipe['uom'] ?? null,
                'quantity_requested' => (float) $this->quantityRequested,
            ];
        }

        $this->selectedRecipeId = null;
        $this->quantityRequested = 1;
    }

    public function refreshAvailableRecipes(): void
    {
        if ($this->selectedProductionDepartmentId) {
            $this->loadRecipesForDepartment();
        }
    }

    public function removeItem(int $index): void
    {
        if (! isset($this->cartItems[$index])) {
            return;
        }

        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    public function submitRequest(): void
    {
        $this->submitError = '';
        $this->submitSuccess = '';

        $this->validate([
            'priority' => ['required', 'in:normal,urgent'],
            'notes' => ['nullable', 'string'],
        ]);

        // If the user selected an item but skipped clicking "Add", capture it here.
        if (
            empty($this->cartItems)
            && $this->selectedProductionDepartmentId
            && $this->selectedRecipeId
            && (float) $this->quantityRequested > 0
        ) {
            $this->addItem();
        }

        if (empty($this->cartItems)) {
            $this->toast()->error('Add at least one item before submitting.')->send();

            return;
        }

        if (! $this->departmentId) {
            $this->toast()->error('Sales department context is missing.')->send();

            return;
        }

        $branchId = $this->getBranchId();
        if (! $branchId) {
            $this->toast()->error('Branch context is missing.')->send();

            return;
        }

        if (! Schema::hasTable('sales_production_requests') || ! Schema::hasTable('sales_production_request_items')) {
            $this->toast()->error('Sales production tables are not ready. Run pending migrations and try again.')->send();

            return;
        }

        $salesDeptCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->departmentName) ?: 'SALES', 0, 4));
        $branchCode = strtoupper(substr(str_replace('-', '', $branchId), 0, 8));

        try {
            DB::transaction(function () use ($branchId, $salesDeptCode, $branchCode) {
                $request = SalesProductionRequest::create([
                    'branch_id' => $branchId,
                    'sales_department_id' => $this->departmentId,
                    'request_number' => SalesProductionRequest::generateRequestNumber($branchCode, $salesDeptCode),
                    'status' => 'pending',
                    'priority' => $this->priority,
                    'requested_by_id' => auth()->id(),
                    'requested_by_type' => auth()->user() ? get_class(auth()->user()) : null,
                    'notes' => $this->notes ?: null,
                ]);

                $hasRecipeIdColumn = Schema::hasColumn('sales_production_request_items', 'recipe_id');

                foreach ($this->cartItems as $item) {
                    $payload = [
                        'sales_production_request_id' => $request->id,
                        'production_department_id' => $item['production_department_id'],
                        'product_id' => $item['product_id'],
                        'quantity_requested' => $item['quantity_requested'],
                        'quantity_produced' => 0,
                        'status' => 'pending',
                    ];

                    if ($hasRecipeIdColumn) {
                        $payload['recipe_id'] = $item['recipe_id'];
                    }

                    SalesProductionRequestItem::create($payload);
                }
            });

            $this->toast()->success('Sales production request submitted successfully.')->send();
            $this->submitSuccess = 'Sales production request submitted successfully.';

            $this->redirectRoute('branch-dashboard.sales-dashboard.production-requests.index', [
                'salesDeptSlug' => $this->salesDeptSlug,
                'sales_dept_slug' => $this->salesDeptSlug,
                'b_id' => $this->getBranchId(),
                'page' => 'Production Requests' . '_' . $this->salesDeptSlug,
            ], navigate: true);
        } catch (\Throwable $e) {
            Log::error('Sales production request submit failed', [
                'sales_department_id' => $this->departmentId,
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            $this->submitError = 'Could not submit request: ' . $e->getMessage();
            $this->toast()->error('Could not submit request: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.production-requests.create', [
            'totalItems' => count($this->cartItems),
            'totalQuantity' => collect($this->cartItems)->sum('quantity_requested'),
        ]);
    }
}
