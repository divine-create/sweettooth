<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Select;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use SalesDepartmentContext, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public array $selectedProductIds = [];

    public int $productsPerPage = 20;

    protected string $pageName = 'stock_opening_select_page';

    public ?string $search = null;

    public ?int $filterProductType = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProductType' => ['except' => ''],
    ];

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->initializeDepartmentContext();

        // Get previous shift products (yesterday's closing stock > 0)
        $previousProductIds = $this->getPreviousShiftProductIds();

        // If no session selection exists, use previous stock as default
        $sessionSelection = session()->get('stock_opening_selected_products', []);

        if (empty($sessionSelection) && ! empty($previousProductIds)) {
            // Default to previous stock products - will be saved to session on confirm
            $this->selectedProductIds = $previousProductIds;
        } else {
            $this->selectedProductIds = $sessionSelection;
        }
    }

    /**
     * Get products with closing stock from previous shifts (yesterday and earlier)
     */
    protected function getPreviousShiftProductIds(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds)) {
            return [];
        }

        // Get IDs of today's existing stock records to exclude them from "previous" search
        $todayStockIds = ProductStock::where('stock_date', $today)
            ->whereIn('department_id', $salesDepartmentIds)
            ->pluck('id')
            ->toArray();

        // Get products with closing_quantity > 0 from previous shift records chronologically
        $query = ProductStock::whereIn('department_id', $salesDepartmentIds)
            ->whereNotNull('closing_quantity')
            ->where('closing_quantity', '>', 0)
            ->orderByDesc('id');

        if (! empty($todayStockIds)) {
            $minId = min($todayStockIds);
            $query->where('id', '<', $minId);
        }

        return $query->distinct()
            ->pluck('product_id')
            ->toArray();
    }

    /**
     * Get equivalent sales department IDs for the current context
     */
    protected function resolveEquivalentSalesDepartmentIds(): array
    {
        if (! $this->departmentId && ! $this->salesDeptSlug) {
            return [];
        }

        $branchId = $this->getBranchId();

        // First try branch-specific department
        $department = Department::where('slug', $this->salesDeptSlug)
            ->where('branch_id', $branchId)
            ->first();

        // Then try global department
        if (! $department) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->whereNull('branch_id')
                ->first();
        }

        if (! $department) {
            return [];
        }

        // Return the department ID and any related sales departments
        return [$department->id];
    }

    public function updatedSearch()
    {
        $this->resetPage($this->pageName);
    }

    public function updatedFilterProductType()
    {
        $this->resetPage($this->pageName);
    }

    public function toggleProductSelection(string $productId)
    {
        $key = array_search($productId, $this->selectedProductIds);
        if ($key !== false) {
            unset($this->selectedProductIds[$key]);
            $this->selectedProductIds = array_values($this->selectedProductIds);
        } else {
            $this->selectedProductIds[] = $productId;
        }

        // Persist to session
        session()->put('stock_opening_selected_products', $this->selectedProductIds);
    }

    public function selectAllCurrentPage()
    {
        $products = $this->getProductsPaginator()->getCollection();
        foreach ($products as $product) {
            if (! in_array($product->id, $this->selectedProductIds)) {
                $this->selectedProductIds[] = $product->id;
            }
        }
        session()->put('stock_opening_selected_products', $this->selectedProductIds);
    }

    public function deselectAllCurrentPage()
    {
        $products = $this->getProductsPaginator()->getCollection();
        $currentIds = $products->pluck('id')->toArray();
        $this->selectedProductIds = array_values(array_diff($this->selectedProductIds, $currentIds));
        session()->put('stock_opening_selected_products', $this->selectedProductIds);
    }

    public function clearSelection()
    {
        $this->selectedProductIds = [];
        session()->forget('stock_opening_selected_products');
    }

    public function proceedToEntry()
    {
        if (empty($this->selectedProductIds)) {
            $this->toast()->error('Please select at least one product')->send();

            return;
        }

        // Persist selection and redirect to entry
        session()->put('stock_opening_selected_products', $this->selectedProductIds);

        return $this->redirect(route('branch-dashboard.sales-dashboard.stock-opening.entry.index', [
            'salesDeptSlug' => $this->salesDeptSlug,
            'b_id' => $this->b_id,
        ]));
    }

    public function getProductsPaginator(): LengthAwarePaginator
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        if (empty($salesDepartmentIds)) {
            return new LengthAwarePaginator(collect(), 0, $this->productsPerPage, 1);
        }

        $search = trim((string) $this->search);

        return Product::query()
            ->active()
            ->available()
            ->where(function($q) use ($salesDepartmentIds) {
                $q->whereIn('sales_department_id', $salesDepartmentIds)
                  ->orWhereHas('departments', function($dq) use ($salesDepartmentIds) {
                      $dq->whereIn('department_id', $salesDepartmentIds);
                  });
            })
            ->when($this->filterProductType, function ($query) {
                $query->where('product_type_id', $this->filterProductType);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->productsPerPage, ['id', 'name', 'sku', 'uom_id', 'product_type_id'], $this->pageName);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.stock-opening.select.index', [
            'products' => $this->getProductsPaginator(),
            'selectedCount' => count($this->selectedProductIds),
            'salesDeptSlug' => $this->salesDeptSlug,
        ]);
    }

    protected function getModelClass(): string
    {
        return Product::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->selectedProductIds;
    }
}
