<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Bill;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\MaterialRequestDispatch;
use App\Models\Product;
use App\Services\CurrencyFormattingService;
use Livewire\Attributes\{Computed, Layout, Url};
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
    public string $departmentName = 'Bill';
    public string $branchName = '';

    public string $search = '';

    /**
     * Selected lines keyed by 'p_<id>' (products) or 'item_<id>' (inventory).
     * Each line: ['type','name','price','qty','uom'] (+ 'product_id'|'item_id').
     */
    public array $billItems = [];
    public $billDiscount = 0;

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

            if (! $department) {
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

    /**
     * Resolve equivalent sales department IDs for the slug across branch/global scope.
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

    public function getModelClass(): string
    {
        return Product::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    /**
     * Format currency value for display (mirrors POS).
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();

        return $service->format($amount);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * All products sellable in this sales department, with set prices, regardless
     * of today's stock. Mirrors the POS product filter without the stock join.
     */
    #[Computed]
    public function products()
    {
        if (! $this->departmentId) {
            return Product::query()->whereRaw('1 = 0')->paginate(24);
        }

        $departmentIds = ! empty($this->departmentIds) ? $this->departmentIds : [$this->departmentId];

        $wipTypeIds = \DB::table('product_types')->where('code', 'WIP')->pluck('id');

        return Product::query()
            ->active()
            ->available()
            ->whereNotIn('product_type_id', $wipTypeIds)
            ->where(function ($q) use ($departmentIds) {
                $q->whereIn('sales_department_id', $departmentIds)
                    ->orWhereHas('departments', function ($dq) use ($departmentIds) {
                        $dq->whereIn('department_id', $departmentIds);
                    });
            })
            ->when(strlen($this->search), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->with(['unitOfMeasure:id,symbol', 'salesUom:id,symbol'])
            ->orderBy('name')
            ->paginate(24);
    }

    /**
     * Inventory items dispatched to this sales dept that have a sell price.
     * A bill ignores availability, so only name/price/uom are needed.
     *
     * @return array<int, array{item_id:int,name:string,price:float,uom:string}>
     */
    public function dispatchedItems(): array
    {
        if (! $this->departmentId) {
            return [];
        }

        $dispatched = MaterialRequestDispatch::query()
            ->whereHas('request', function ($q) {
                $q->where('department_id', $this->departmentId)
                    ->where('branch_id', $this->branchId)
                    ->whereNotIn('status', ['cancelled']);
            })
            ->whereHas('item', fn ($q) => $q->whereNotNull('sell_price')->where('status', 'active'))
            ->with(['item.unitOfMeasure:id,symbol'])
            ->get();

        if ($dispatched->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($dispatched->groupBy('item_id') as $itemId => $rows) {
            $item = $rows->first()->item;
            if (! $item || ! $item->isSellable()) {
                continue;
            }

            $result[] = [
                'item_id' => (int) $itemId,
                'name' => $item->name,
                'price' => (float) $item->sell_price,
                'uom' => $item->unitOfMeasure?->symbol ?? 'unit',
            ];
        }

        return $result;
    }

    public function addProductToBill(string $productId): void
    {
        $product = Product::query()
            ->with(['unitOfMeasure', 'salesUom'])
            ->whereKey($productId)
            ->first();

        if (! $product) {
            $this->toast()->error('Product not found.')->send();

            return;
        }

        $key = 'p_' . $productId;
        $current = (float) ($this->billItems[$key]['qty'] ?? 0);

        $this->billItems[$key] = [
            'type' => 'product',
            'product_id' => $productId,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'qty' => $current + 1,
            'uom' => $product->effectiveSalesUomSymbol ?: ($product->uomSymbol ?: ''),
        ];
    }

    public function addItemToBill(int $itemId): void
    {
        $item = Item::with('unitOfMeasure')
            ->whereNotNull('sell_price')
            ->where('status', 'active')
            ->find($itemId);

        if (! $item) {
            $this->toast()->error('Item not available.')->send();

            return;
        }

        $key = 'item_' . $itemId;
        $current = (float) ($this->billItems[$key]['qty'] ?? 0);

        $this->billItems[$key] = [
            'type' => 'item',
            'item_id' => $itemId,
            'name' => $item->name,
            'price' => (float) $item->sell_price,
            'qty' => $current + 1,
            'uom' => $item->unitOfMeasure?->symbol ?? 'unit',
        ];
    }

    public function incrementBill(string $key): void
    {
        if (! isset($this->billItems[$key])) {
            return;
        }

        $this->billItems[$key]['qty'] = (float) $this->billItems[$key]['qty'] + 1;
    }

    public function decrementBill(string $key): void
    {
        if (! isset($this->billItems[$key])) {
            return;
        }

        $this->billItems[$key]['qty'] = max(1, (float) $this->billItems[$key]['qty'] - 1);
    }

    public function updateBillQty(string $key, $quantity): void
    {
        if (! isset($this->billItems[$key])) {
            return;
        }

        $this->billItems[$key]['qty'] = max(1, (float) $quantity);
    }

    public function removeFromBill(string $key): void
    {
        unset($this->billItems[$key]);
    }

    public function clearBill(): void
    {
        $this->billItems = [];
        $this->billDiscount = 0;
    }

    #[Computed]
    public function billSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->billItems as $line) {
            $subtotal += (float) $line['price'] * (float) $line['qty'];
        }

        return $subtotal;
    }

    #[Computed]
    public function billTotal(): float
    {
        return max(0, $this->billSubtotal() - (float) ($this->billDiscount ?: 0));
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.bill.index', [
            'dispatchedItems' => $this->dispatchedItems(),
        ]);
    }
}
