<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests;

use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
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
    public $selectedProductId = null;
    public $quantityRequested = 1;
    public string $productSearch = '';
    public string $priority = 'normal';
    public string $notes = '';
    public string $submitError = '';
    public string $submitSuccess = '';

    /** @var array<int, array{id:int,name:string}> */
    public array $productionDepartments = [];

    /** @var array<int, array{id:string,name:string,sku:?string,uom:?string}> */
    public array $availableProducts = [];

    /** @var array<int, array{
     *     production_department_id:int,
     *     production_department_name:string,
     *     recipe_id:int|null,
     *     recipe_name:?string,
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
            $this->loadProductsForDepartment();
        }
    }

    public function updatedSelectedProductionDepartmentId(): void
    {
        if ($this->selectedProductionDepartmentId !== null && $this->selectedProductionDepartmentId !== '') {
            $this->selectedProductionDepartmentId = (int) $this->selectedProductionDepartmentId;
        }

        $this->selectedProductId = null;
        $this->productSearch = '';
        $this->loadProductsForDepartment();
    }

    public function updatedProductSearch(): void
    {
        $this->loadProductsForDepartment();
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

    public function loadProductsForDepartment(): void
    {
        if (! $this->selectedProductionDepartmentId) {
            $this->availableProducts = [];

            return;
        }

        $branchId = $this->getBranchId();
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds)) {
            $this->availableProducts = [];

            return;
        }

        $productionDepartmentIds = $this->resolveEquivalentProductionDepartmentIds((int) $this->selectedProductionDepartmentId);

        $productsQuery = Product::query()
            ->active()
            ->available()
            ->whereIn('sales_department_id', $salesDepartmentIds)
            ->whereHas('productType', function ($query) use ($productionDepartmentIds) {
                $query->whereIn('department_id', $productionDepartmentIds);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->with(['unitOfMeasure:id,symbol', 'salesUom:id,symbol']);

        $this->availableProducts = $productsQuery
            ->when($this->productSearch !== '', function ($query) {
                $term = '%' . $this->productSearch . '%';
                $query->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn (Product $product) => [
                'id' => (string) $product->id,
                'product_id' => (string) $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'uom' => $product->salesUom?->symbol ?? $product->unitOfMeasure?->symbol,
            ])
            ->toArray();
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
            'selectedProductId' => ['required', 'exists:products,id'],
            'quantityRequested' => ['required', 'numeric', 'min:0.01'],
        ]);

        $department = collect($this->productionDepartments)->firstWhere('id', $this->selectedProductionDepartmentId);
        $product = collect($this->availableProducts)->firstWhere('id', (string) $this->selectedProductId);

        // If options are stale in the browser, refresh source list once before failing.
        if (! $product && $this->selectedProductionDepartmentId) {
            $this->loadProductsForDepartment();
            $product = collect($this->availableProducts)->firstWhere('id', (string) $this->selectedProductId);
        }

        if (! $department || ! $product) {
            $this->toast()->error('Please select a valid department and product.')->send();

            return;
        }

        $existingIndex = collect($this->cartItems)->search(function (array $item): bool {
            return (int) $item['production_department_id'] === (int) $this->selectedProductionDepartmentId
                && (string) $item['product_id'] === (string) $this->selectedProductId;
        });

        if ($existingIndex !== false) {
            $this->cartItems[$existingIndex]['quantity_requested'] =
                (float) $this->cartItems[$existingIndex]['quantity_requested'] + (float) $this->quantityRequested;
        } else {
            $this->cartItems[] = [
                'production_department_id' => (int) $department['id'],
                'production_department_name' => $department['name'],
                'recipe_id' => null,
                'recipe_name' => null,
                'product_id' => $product['product_id'] ? (string) $product['product_id'] : null,
                'product_name' => $product['product_name'] ?? null,
                'sku' => $product['sku'] ?? null,
                'yield_quantity' => 0,
                'uom' => $product['uom'] ?? null,
                'quantity_requested' => (float) $this->quantityRequested,
            ];
        }

        $this->selectedProductId = null;
        $this->quantityRequested = 1;
    }

    public function refreshAvailableRecipes(): void
    {
        if ($this->selectedProductionDepartmentId) {
            $this->loadProductsForDepartment();
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
            && $this->selectedProductId
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
