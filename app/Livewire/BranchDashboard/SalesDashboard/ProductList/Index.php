<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\ProductList;

use App\Helpers\Settings;
use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use App\Services\CurrencyFormattingService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\{Layout, Url, Computed};
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    /** @var array<int> */
    public array $departmentIds = [];
    public string $departmentName = 'Product List';
    public string $branchName = '';

    public string $search = '';
    public bool $showAddProductsModal = false;
    public array $selectedProducts = [];
    public array $availableProducts = [];

    // Edit product settings
    public bool $showEditModal = false;
    public ?string $editingProductId = null;
    public ?float $departmentPrice = null;
    public bool $isAvailable = true;
    public int $sortOrder = 0;

    public function mount(): void
    {
        $this->mountBase();
        if (! $this->salesDeptSlug) {
            $this->salesDeptSlug = request()->route('salesDeptSlug')
                ?? request()->query('sales_dept_slug')
                ?? request()->query('salesDeptSlug');
        }
        $this->loadBranchAndDepartment();
    }

    protected function loadBranchAndDepartment(): void
    {
        $this->branchId = request('b_id');
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        if ($this->salesDeptSlug) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            if (!$department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
                $this->departmentName = $department->name;
                $this->departmentIds = $this->resolveEquivalentSalesDepartmentIds($department);
            }
        }
    }

    public function getModelClass(): string
    {
        return Product::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        if (!$this->departmentId || !$this->branchId) {
            return collect([]);
        }

        $department = Department::find($this->departmentId);

        $query = $department->products()
            ->where(function ($q) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $this->branchId);
            });

        if (! empty($this->departmentIds)) {
            $query->whereIn('products.sales_department_id', $this->departmentIds);
        } else {
            $query->where('products.sales_department_id', $this->departmentId);
        }

        if (strlen($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(20);
    }

    public function openAddProductsModal(): void
    {
        $this->loadAvailableProducts();
        $this->selectedProducts = [];
        $this->showAddProductsModal = true;
    }

    protected function loadAvailableProducts(): void
    {
        if (!$this->departmentId || !$this->branchId) {
            $this->availableProducts = [];
            return;
        }

        $department = Department::find($this->departmentId);
        $currentProductIds = $department->products()->pluck('products.id')->toArray();

        $this->availableProducts = Product::where(function ($q) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $this->branchId);
            })
            ->when(! empty($this->departmentIds), fn ($q) => $q->whereIn('sales_department_id', $this->departmentIds))
            ->when(empty($this->departmentIds), fn ($q) => $q->where('sales_department_id', $this->departmentId))
            ->whereNotIn('id', $currentProductIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Resolve equivalent sales department IDs for slug across branch/global scope.
     *
     * @return array<int>
     */
    private function resolveEquivalentSalesDepartmentIds(Department $department): array
    {
        $branchId = $this->branchId;

        $query = Department::query()
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->where('slug', $department->slug);

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

        if (! in_array((int) $department->id, $ids, true)) {
            $ids[] = (int) $department->id;
        }

        return $ids;
    }

    public function addProducts(): void
    {
        if (empty($this->selectedProducts)) {
            $this->toast()->warning('Please select at least one product.')->send();
            return;
        }

        if (!$this->departmentId) {
            $this->toast()->error('Department not found.')->send();
            return;
        }

        $department = Department::find($this->departmentId);

        DB::transaction(function () use ($department) {
            foreach ($this->selectedProducts as $productId) {
                $department->products()->attach($productId, [
                    'is_available' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->toast()->success(count($this->selectedProducts) . ' product(s) added to ' . $department->name)->send();
        $this->showAddProductsModal = false;
        $this->selectedProducts = [];
        unset($this->products);
    }

    public function openEditModal(string $productId): void
    {
        $department = Department::find($this->departmentId);
        $product = $department->products()->where('products.id', $productId)->first();

        if (!$product) {
            $this->toast()->error('Product not found.')->send();
            return;
        }

        $this->editingProductId = $productId;
        $this->departmentPrice = $product->pivot->department_price;
        $this->isAvailable = $product->pivot->is_available;
        $this->sortOrder = $product->pivot->sort_order;
        $this->showEditModal = true;
    }

    public function updateProduct(): void
    {
        if (!$this->editingProductId || !$this->departmentId) {
            $this->toast()->error('Invalid request.')->send();
            return;
        }

        $department = Department::find($this->departmentId);

        $department->products()->updateExistingPivot($this->editingProductId, [
            'department_price' => $this->departmentPrice,
            'is_available' => $this->isAvailable,
            'sort_order' => $this->sortOrder,
            'updated_at' => now(),
        ]);

        $this->toast()->success('Product settings updated.')->send();
        $this->showEditModal = false;
        unset($this->products);
    }

    public function removeProduct(string $productId): void
    {
        if (!$this->departmentId) {
            $this->toast()->error('Department not found.')->send();
            return;
        }

        $department = Department::find($this->departmentId);
        $department->products()->detach($productId);

        $this->toast()->success('Product removed from ' . $department->name)->send();
        unset($this->products);
    }

    public function toggleAvailability(string $productId): void
    {
        $department = Department::find($this->departmentId);
        $product = $department->products()->where('products.id', $productId)->first();

        if (!$product) {
            $this->toast()->error('Product not found.')->send();
            return;
        }

        $newStatus = !$product->pivot->is_available;
        $department->products()->updateExistingPivot($productId, [
            'is_available' => $newStatus,
            'updated_at' => now(),
        ]);

        $message = $newStatus ? 'Product enabled' : 'Product disabled';
        $this->toast()->success($message)->send();
        unset($this->products);
    }

    /**
     * Format price for display
     */
    public function formatPrice(float $price): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($price);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(string $currency = null): string
    {
        $service = new CurrencyFormattingService();
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');
        return $service->getSymbol($currency);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.product-list.index');
    }
}
