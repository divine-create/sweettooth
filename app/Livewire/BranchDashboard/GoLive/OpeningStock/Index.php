<?php

namespace App\Livewire\BranchDashboard\GoLive\OpeningStock;

use App\Models\Department;
use App\Models\GlobalBusinessConfiguration;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProductionStore;
use App\Models\ProductionStoreStock;
use App\Models\ProductStock;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Temporary go-live tool: managers enter opening (yesterday's closing) stock for
 * production stores, sales, and main inventory. Disabled permanently once an MD
 * clicks "Lock Go-Live" (see opening_stock_entry_enabled()).
 */
#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $tab = 'production';

    public int $perPage = 50;

    public ?int $prodDeptId = null;

    public string $prodType = 'raw';

    public string $prodSearch = '';

    public ?int $salesDeptId = null;

    public string $salesSearch = '';

    public string $invSearch = '';

    public bool $showLockModal = false;

    // Reset pagination whenever the active list, scope, or filter changes.
    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingProdDeptId(): void
    {
        $this->resetPage();
    }

    public function updatingProdType(): void
    {
        $this->resetPage();
    }

    public function updatingProdSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSalesDeptId(): void
    {
        $this->resetPage();
    }

    public function updatingSalesSearch(): void
    {
        $this->resetPage();
    }

    public function updatingInvSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        abort_unless(opening_stock_entry_enabled(), 404);

        $access = $this->access();
        abort_unless($access['production'] || $access['sales'] || $access['inventory'], 403);

        $this->tab = $access['production'] ? 'production' : ($access['sales'] ? 'sales' : 'inventory');
        $this->prodDeptId = $this->productionDepartments()->keys()->first();
        $this->salesDeptId = $this->salesDepartments()->keys()->first();
    }

    /** Access map for the three tabs + lock, by role level / department category. */
    protected function access(): array
    {
        $user = current_actor();
        $level = (int) ($user?->roles()->max('level') ?? 0);
        $canAll = is_super_admin() || $level >= 4;
        $catName = optional(optional($user?->department)->category)->name;

        return [
            'production' => $canAll || $catName === 'Production',
            'sales' => $canAll || $catName === 'Sales',
            'inventory' => $canAll || (bool) ($user?->can('manage-inventory') ?? false),
            'all' => $canAll,
            'lock' => is_super_admin() || $level >= 5,
        ];
    }

    protected function canAllDepts(): bool
    {
        return $this->access()['all'];
    }

    protected function productionDepartments()
    {
        return $this->departmentsForCategory('Production');
    }

    protected function salesDepartments()
    {
        return $this->departmentsForCategory('Sales');
    }

    /** Departments of a category the user may enter for (id => name). */
    protected function departmentsForCategory(string $category)
    {
        $branchId = current_branch_id();

        $query = Department::query()
            ->whereHas('category', fn ($q) => $q->where('name', $category))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));

        if (! $this->canAllDepts()) {
            $query->where('id', current_actor()?->department_id);
        }

        return $query->orderBy('name')->pluck('name', 'id');
    }

    protected function canUseProductionDept(?int $deptId): bool
    {
        return $deptId !== null && $this->productionDepartments()->keys()->contains($deptId);
    }

    protected function canUseSalesDept(?int $deptId): bool
    {
        return $deptId !== null && $this->salesDepartments()->keys()->contains($deptId);
    }

    // ---- Saves (idempotent upserts) -------------------------------------

    public function saveProduction(string $itemId, $quantity, $cost = 0): void
    {
        if (! $this->access()['production'] || ! $this->canUseProductionDept($this->prodDeptId)) {
            $this->toast()->error('Not allowed for this department.')->send();

            return;
        }

        $data = validator(
            ['quantity' => $quantity, 'cost' => $cost],
            ['quantity' => 'required|numeric|min:0', 'cost' => 'nullable|numeric|min:0']
        )->validate();

        $store = ProductionStore::getOrCreateForDepartment((int) $this->prodDeptId);

        ProductionStoreStock::updateOrCreate(
            ['store_id' => $store->id, 'item_id' => (string) $itemId],
            [
                'quantity_available' => (float) $data['quantity'],
                'quantity_reserved' => 0,
                'average_cost' => (float) ($data['cost'] ?? 0),
                'last_stock_take_date' => Carbon::today(),
            ]
        );

        $this->toast()->success('Saved.')->send();
    }

    public function saveSales(string $productId, $quantity): void
    {
        if (! $this->access()['sales'] || ! $this->canUseSalesDept($this->salesDeptId)) {
            $this->toast()->error('Not allowed for this department.')->send();

            return;
        }

        $data = validator(
            ['quantity' => $quantity],
            ['quantity' => 'required|numeric|min:0']
        )->validate();

        $qty = (float) $data['quantity'];

        ProductStock::updateOrCreate(
            [
                'stock_date' => Carbon::today()->toDateString(),
                'department_id' => $this->salesDeptId,
                'product_id' => $productId,
                'shift_type' => 'morning',
            ],
            [
                'opening_quantity' => $qty,
                'addition_quantity' => 0,
                'quantity_sold' => 0,
                'total_available' => $qty,
                'closing_quantity' => $qty,
            ]
        );

        $this->toast()->success('Saved.')->send();
    }

    public function saveInventory($itemId, $quantity, $cost = 0): void
    {
        if (! $this->access()['inventory']) {
            $this->toast()->error('Not allowed.')->send();

            return;
        }

        $data = validator(
            ['quantity' => $quantity, 'cost' => $cost],
            ['quantity' => 'required|numeric|min:0', 'cost' => 'nullable|numeric|min:0']
        )->validate();

        Stock::updateOrCreate(
            ['branch_id' => current_branch_id(), 'item_id' => (int) $itemId],
            [
                'quantity_available' => (float) $data['quantity'],
                'average_cost' => (float) ($data['cost'] ?? 0),
                'health_status' => 'good',
                'last_stock_take_date' => Carbon::today(),
            ]
        );

        $this->toast()->success('Saved.')->send();
    }

    public function lockGoLive(): void
    {
        if (! $this->access()['lock']) {
            $this->toast()->error('Only an MD / super admin can lock go-live.')->send();

            return;
        }

        $config = GlobalBusinessConfiguration::first();
        if ($config) {
            $config->update(['opening_stock_entry_enabled' => false]);
        } else {
            GlobalBusinessConfiguration::create(['opening_stock_entry_enabled' => false]);
        }

        $this->showLockModal = false;
        $this->toast()->success('Go-live opening-stock entry has been locked.')->send();
        $this->redirect(branch_route('branch-dashboard.dashboards.router'), navigate: true);
    }

    // ---- List builders (browsable + filtered by search) -----------------

    protected function productionResults()
    {
        if (! $this->prodDeptId) {
            return Item::query()->whereRaw('1 = 0')->paginate($this->perPage);
        }

        $branchId = current_branch_id();
        $store = ProductionStore::forBranch($branchId)->forDepartment($this->prodDeptId)->first();
        $uoms = $this->uomMap();
        $search = trim($this->prodSearch);

        if ($this->prodType === 'product') {
            $paginator = Product::where('department_id', $this->prodDeptId)
                ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')->paginate($this->perPage);

            $ids = collect($paginator->items())->map(fn ($p) => (string) $p->id)->all();
            $existing = $store ? ProductionStoreStock::where('store_id', $store->id)->whereIn('item_id', $ids)->get()->keyBy('item_id') : collect();

            $paginator->setCollection($paginator->getCollection()->map(function (Product $p) use ($existing, $uoms) {
                $cur = $existing[(string) $p->id] ?? null;

                return [
                    'type' => 'Product',
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'uom' => $uoms[$p->uom_id] ?? 'pcs',
                    'current' => $cur ? (float) $cur->quantity_available : 0,
                    'cost' => $cur ? (float) $cur->average_cost : (float) ($p->cost ?? 0),
                ];
            }));

            return $paginator;
        }

        $paginator = Item::where('branch_id', $branchId)
            ->when($search !== '', fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate($this->perPage);

        $ids = collect($paginator->items())->map(fn ($i) => (string) $i->id)->all();
        $existing = $store ? ProductionStoreStock::where('store_id', $store->id)->whereIn('item_id', $ids)->get()->keyBy('item_id') : collect();
        $costFallback = Stock::where('branch_id', $branchId)->whereIn('item_id', $ids)->pluck('average_cost', 'item_id');

        $paginator->setCollection($paginator->getCollection()->map(function (Item $it) use ($existing, $costFallback, $uoms) {
            $cur = $existing[(string) $it->id] ?? null;

            return [
                'type' => 'Raw',
                'id' => (string) $it->id,
                'name' => $it->name,
                'uom' => $uoms[$it->uom_id] ?? '',
                'current' => $cur ? (float) $cur->quantity_available : 0,
                'cost' => $cur ? (float) $cur->average_cost : (float) ($costFallback[$it->id] ?? 0),
            ];
        }));

        return $paginator;
    }

    protected function salesResults()
    {
        if (! $this->salesDeptId) {
            return Product::query()->whereRaw('1 = 0')->paginate($this->perPage);
        }

        $uoms = $this->uomMap();
        $search = trim($this->salesSearch);

        $paginator = Product::where('sales_department_id', $this->salesDeptId)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate($this->perPage);

        $ids = collect($paginator->items())->map(fn ($p) => $p->id)->all();
        $existing = ProductStock::where('stock_date', Carbon::today()->toDateString())
            ->where('department_id', $this->salesDeptId)
            ->where('shift_type', 'morning')
            ->whereIn('product_id', $ids)
            ->pluck('opening_quantity', 'product_id');

        $paginator->setCollection($paginator->getCollection()->map(fn (Product $p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'uom' => $uoms[$p->sales_uom_id] ?? ($uoms[$p->uom_id] ?? 'pcs'),
            'current' => (float) ($existing[$p->id] ?? 0),
        ]));

        return $paginator;
    }

    protected function inventoryResults()
    {
        $branchId = current_branch_id();
        $uoms = $this->uomMap();
        $search = trim($this->invSearch);

        $paginator = Item::where('branch_id', $branchId)
            ->when($search !== '', fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate($this->perPage);

        $ids = collect($paginator->items())->map(fn ($i) => $i->id)->all();
        $existing = Stock::where('branch_id', $branchId)->whereIn('item_id', $ids)->get()->keyBy('item_id');

        $paginator->setCollection($paginator->getCollection()->map(function (Item $it) use ($existing, $uoms) {
            $cur = $existing[$it->id] ?? null;

            return [
                'id' => (string) $it->id,
                'name' => $it->name,
                'uom' => $uoms[$it->uom_id] ?? '',
                'current' => $cur ? (float) $cur->quantity_available : 0,
                'cost' => $cur ? (float) $cur->average_cost : 0,
            ];
        }));

        return $paginator;
    }

    protected function uomMap(): array
    {
        return UnitOfMeasure::pluck('symbol', 'id')->all();
    }

    public function render(): View
    {
        $access = $this->access();

        return view('livewire.branch-dashboard.go-live.opening-stock.index', [
            'access' => $access,
            'productionDepartments' => $access['production'] ? $this->productionDepartments() : collect(),
            'salesDepartments' => $access['sales'] ? $this->salesDepartments() : collect(),
            'prodResults' => $this->tab === 'production' ? $this->productionResults() : collect(),
            'salesProducts' => $this->tab === 'sales' ? $this->salesResults() : collect(),
            'invResults' => $this->tab === 'inventory' ? $this->inventoryResults() : collect(),
        ]);
    }
}
