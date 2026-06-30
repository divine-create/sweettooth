<?php

namespace App\Livewire\BranchDashboard\Production\Store;

use App\Models\Department;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductionStore;
use App\Models\ProductionStoreStock;
use App\Models\Recipe;
use App\Models\Shift;
use App\Services\ProductionStoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Stock extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public ?ProductionStore $store = null;

    public string $search = '';

    public string $stockFilter = 'all';

    public int $perPage = 50;

    public int $quantity = 15;

    // Reset to the first page when the filter/search changes, so pagination
    // doesn't land on an out-of-range page.
    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // --- Send to Sales modal state ---
    public bool $showSendModal = false;

    public ?string $sendProductId = null;

    public string $sendProductName = '';

    public string $sendUom = '';

    // Sales-side unit the dispatch is entered in. Falls back to the production
    // UOM ($sendUom) when the product has no distinct sales unit configured.
    public string $sendSalesUom = '';

    public float $sendAvailable = 0.0;

    // Held balance expressed in the sales UOM (= $sendAvailable when no conversion).
    public float $sendAvailableSales = 0.0;

    public $sendQuantity = null;

    public ?int $sendSalesDepartmentId = null;

    /** @var array<int,string> sales departments the product may be sent to (id => name) */
    public array $sendDepartments = [];

    // The "Transfer to another store" modal is opened client-side (Alpine) for an
    // instant response; only the actual transfer (transferStock) hits the server.

    public function mount($deptSlug)
    {
        $this->dept_slug = $deptSlug;
        $this->b_id = request()->query('b_id');
        $this->department = Department::where('slug', $deptSlug)->first();

        if (! $this->department) {
            abort(404, 'Department not found');
        }

        $this->loadStore();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    protected function loadStore()
    {
        $branchId = $this->getBranchId();

        $this->store = ProductionStore::forBranch($branchId)
            ->forDepartment($this->department->id)
            ->active()
            ->first();
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $store = ProductionStore::forBranch($branchId)
            ->forDepartment($this->department->id)
            ->active()
            ->first();

        $stocks = collect();
        $stockCounts = ['all' => 0, 'wip' => 0, 'raw' => 0, 'finished' => 0];
        $wipProductIds = [];

        // Raw materials have numeric item_id (Items); products (WIP / finished
        // goods) have UUID item_id. A product is "finished goods" when it is held
        // in the store but is NOT a WIP recipe output.
        $numericRegex = "item_id REGEXP '^[0-9]+$'";

        $totalValue = 0;

        if ($store) {
            $wipProductIds = Recipe::where('is_wip', true)
                ->whereNotNull('product_id')
                ->pluck('product_id')
                ->toArray();

            // Single filtered query, reused for the page, the total value, and the
            // row count — so we only build the WHERE clause once.
            $filtered = ProductionStoreStock::where('store_id', $store->id)
                ->when($this->search, function ($query) {
                    $query->whereHas('item', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('sku', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->stockFilter === 'wip', function ($query) use ($wipProductIds) {
                    $query->whereIn('item_id', $wipProductIds);
                })
                ->when($this->stockFilter === 'raw', function ($query) use ($numericRegex) {
                    $query->whereRaw($numericRegex);
                })
                ->when($this->stockFilter === 'finished', function ($query) use ($wipProductIds, $numericRegex) {
                    $query->whereRaw("NOT ($numericRegex)")
                        ->whereNotIn('item_id', $wipProductIds);
                });

            // Total value across the whole filtered set (not just the current page).
            $totalValue = (float) (clone $filtered)->sum(DB::raw('quantity_available * average_cost'));

            // Only load + render one page of rows.
            $stocks = (clone $filtered)
                ->with('item.category', 'item.unitOfMeasure')
                ->orderBy('quantity_available', 'desc')
                ->paginate($this->perPage);

            // Enrich only the product (UUID) rows on this page; raw rows resolve to
            // their Item, so there is no Product lookup for the Raw Materials tab.
            $productIds = collect($stocks->items())
                ->reject(fn ($s) => ctype_digit((string) $s->item_id))
                ->pluck('item_id')
                ->all();

            $wipProducts = empty($productIds)
                ? collect()
                : Product::whereIn('id', $productIds)->get()->keyBy('id');

            $stocks->getCollection()->each(function ($stock) use ($wipProducts) {
                $stock->displayItem = $wipProducts[$stock->item_id] ?? $stock->item;
            });

            $baseQuery = ProductionStoreStock::where('store_id', $store->id)
                ->where('quantity_available', '>', 0);

            $stockCounts['wip'] = (clone $baseQuery)
                ->whereIn('item_id', $wipProductIds)
                ->count();

            $stockCounts['raw'] = (clone $baseQuery)
                ->whereRaw($numericRegex)
                ->count();

            $stockCounts['finished'] = (clone $baseQuery)
                ->whereRaw("NOT ($numericRegex)")
                ->whereNotIn('item_id', $wipProductIds)
                ->count();

            $stockCounts['all'] = $stockCounts['wip'] + $stockCounts['raw'] + $stockCounts['finished'];
        }

        return view('livewire.branch-dashboard.production.store.stock', [
            'stocks' => $stocks,
            'store' => $store,
            'totalValue' => $totalValue,
            'stockCounts' => $stockCounts,
            'wipProductIds' => $wipProductIds,
            'transferDepartments' => $this->resolveTransferDepartments(),
        ]);
    }

    public function getStoreName(): string
    {
        return $this->store?->name ?? 'Production Store';
    }

    /**
     * Open the "Send to Sales" modal for one finished-goods product, defaulting
     * the quantity to its currently held balance in this store.
     */
    public function openSendModal(string $productId): void
    {
        $store = $this->store ?? $this->getStoreModel();

        if (! $store) {
            $this->toast()->error('No production store found.')->send();

            return;
        }

        $stock = ProductionStoreStock::where('store_id', $store->id)
            ->where('item_id', $productId)
            ->first();

        $available = (float) ($stock->quantity_available ?? 0);

        if ($available <= 0) {
            $this->toast()->warning('No held stock available to send for this product.')->send();

            return;
        }

        $product = Product::with(['unitOfMeasure', 'salesUom'])->find($productId);

        if (! $product) {
            $this->toast()->error('Product not found.')->send();

            return;
        }

        // The held balance is in the base/production UOM (e.g. grams). The operator
        // enters the dispatch amount in the sales UOM; we convert back to base on
        // submit. effectiveSalesUomSymbol / convertBaseToSalesQuantity fall back to
        // the production UOM when no sales unit is configured.
        $this->sendProductId = $productId;
        $this->sendProductName = $product->name;
        $this->sendUom = $product->unitOfMeasure?->symbol ?? '';
        $this->sendSalesUom = $product->effectiveSalesUomSymbol;
        $this->sendAvailable = $available;
        $this->sendAvailableSales = $product->convertBaseToSalesQuantity($available);
        $this->sendQuantity = $this->sendAvailableSales;
        $this->sendSalesDepartmentId = null;
        $this->sendDepartments = $this->resolveSalesDepartmentsForProduct($productId);
        $this->showSendModal = true;
    }

    /**
     * Live preview of how much will actually leave the store (base/production UOM)
     * for the sales-UOM quantity currently entered.
     *
     * @return array{base: float, base_uom: string, converts: bool}
     */
    public function sendBasePreview(): array
    {
        $product = $this->sendProductId ? Product::find($this->sendProductId) : null;
        $sales = (float) ($this->sendQuantity ?: 0);
        $base = $product ? $product->convertSalesToBaseQuantity($sales) : $sales;

        return [
            'base' => $base,
            'base_uom' => $this->sendUom,
            'converts' => $this->sendSalesUom !== $this->sendUom,
        ];
    }

    public function closeSendModal(): void
    {
        $this->showSendModal = false;
        $this->sendProductId = null;
        $this->sendProductName = '';
        $this->sendUom = '';
        $this->sendSalesUom = '';
        $this->sendAvailable = 0.0;
        $this->sendAvailableSales = 0.0;
        $this->sendQuantity = null;
        $this->sendSalesDepartmentId = null;
        $this->sendDepartments = [];
    }

    /**
     * Sales departments a product may be dispatched to — limited to the sales-
     * category departments the product is assigned to (mirrors the Finished
     * Goods sheet / Quick Produce dispatch scoping).
     *
     * @return array<int,string>
     */
    protected function resolveSalesDepartmentsForProduct(string $productId): array
    {
        $product = Product::with('departments:id')->find($productId);

        if (! $product) {
            return [];
        }

        $allowedIds = $product->departments->pluck('id')->all();
        if ($product->sales_department_id) {
            $allowedIds[] = (int) $product->sales_department_id;
        }
        $allowedIds = array_values(array_unique($allowedIds));

        if (empty($allowedIds)) {
            return [];
        }

        return Department::with('category')
            ->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['sales']))
            ->where(fn ($q) => $q->where('branch_id', $this->getBranchId())->orWhereNull('branch_id'))
            ->where('is_active', true)
            ->whereIn('id', $allowedIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Dispatch held finished goods to a sales department. Creates a
     * ProductDispatch (pending_verification) and draws the held balance down via
     * a transfer movement, so the sales Dispatches screen can confirm receipt.
     */
    public function sendToSales(): void
    {
        $store = $this->store ?? $this->getStoreModel();

        if (! $store || ! $this->sendProductId) {
            $this->toast()->error('No production store or product selected.')->send();

            return;
        }

        // Quantity is entered in the sales UOM; validate against the held balance
        // expressed in that same unit.
        $this->validate([
            'sendQuantity' => 'required|numeric|min:0.01|max:'.$this->sendAvailableSales,
            'sendSalesDepartmentId' => 'required|integer',
        ], [], [
            'sendQuantity' => 'quantity',
            'sendSalesDepartmentId' => 'sales department',
        ]);

        $product = Product::with(['unitOfMeasure', 'salesUom'])->find($this->sendProductId);

        if (! $product) {
            $this->toast()->error('Product not found.')->send();

            return;
        }

        $actor = current_actor();
        $shift = $actor
            ? Shift::where('employee_id', $actor->id)->where('status', 'active')->first()
            : null;

        $salesQuantity = (float) $this->sendQuantity;

        // Convert the sales-UOM amount back to the base/production UOM that the
        // store holds and the dispatch is recorded in. Clamp to the held balance
        // to absorb any rounding in the round-trip conversion.
        $quantity = $product->convertSalesToBaseQuantity($salesQuantity);
        if ($quantity > $this->sendAvailable) {
            $quantity = $this->sendAvailable;
        }

        if ($quantity <= 0) {
            $this->toast()->error('Quantity to send must be greater than zero.')->send();

            return;
        }

        DB::transaction(function () use ($store, $product, $actor, $shift, $quantity) {
            $dispatch = ProductDispatch::create([
                'branch_id' => $this->getBranchId(),
                'production_shift_id' => $shift?->id,
                'shift_type' => $shift?->shift_type,
                'product_id' => $product->id,
                'sales_department_id' => $this->sendSalesDepartmentId,
                'quantity' => $quantity,
                'uom' => $this->sendUom ?: ($product->unitOfMeasure->symbol ?? 'units'),
                'status' => 'pending_verification',
                'dispatched_by_id' => $actor?->id,
                'dispatched_by_type' => $actor ? get_class($actor) : null,
                'dispatch_date' => now()->toDateString(),
                'notes' => 'Sent to sales from Production Store stock',
            ]);

            app(ProductionStoreService::class)->recordDispatch(
                $store,
                $product,
                $quantity,
                'transfer',
                $dispatch,
                $actor,
                'Dispatched to sales from Production Store stock (pending verification)'
            );
        });

        $salesLabel = rtrim(rtrim(number_format($salesQuantity, 2), '0'), '.');
        $baseLabel = rtrim(rtrim(number_format($quantity, 2), '0'), '.');
        $sentText = $this->sendSalesUom !== $this->sendUom
            ? "{$salesLabel} {$this->sendSalesUom} ({$baseLabel} {$this->sendUom})"
            : "{$baseLabel} {$this->sendUom}";

        $this->toast()->success("Sent {$sentText} of {$this->sendProductName} to sales (pending confirmation).")->send();
        $this->closeSendModal();
    }

    /**
     * Active Production-category departments in this branch, excluding the current
     * one — the stores a raw material may be transferred to. Computed once per
     * render and passed to the view so the transfer modal (opened client-side)
     * already has its destination list.
     *
     * @return array<int,string>
     */
    protected function resolveTransferDepartments(): array
    {
        return Department::with('category')
            ->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['production']))
            ->where(fn ($q) => $q->where('branch_id', $this->getBranchId())->orWhereNull('branch_id'))
            ->where('is_active', true)
            ->where('id', '!=', $this->department->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Send a raw material to another department's production store (in-transit;
     * the destination credits it on receipt). Called directly from the client
     * modal with the row's values, so opening the modal needs no server round-trip.
     */
    public function transferStock(string $itemId, $quantity, $toDepartmentId, ?string $notes = null): void
    {
        $store = $this->store ?? $this->getStoreModel();

        if (! $store) {
            $this->toast()->error('No production store found.')->send();

            return;
        }

        if (! ctype_digit($itemId)) {
            $this->toast()->warning('Only raw materials can be transferred between stores.')->send();

            return;
        }

        $allowedDepartmentIds = array_keys($this->resolveTransferDepartments());

        $validated = validator(
            [
                'quantity' => $quantity,
                'toDepartmentId' => $toDepartmentId,
                'notes' => $notes,
            ],
            [
                'quantity' => 'required|numeric|min:0.01',
                'toDepartmentId' => ['required', 'integer', Rule::in($allowedDepartmentIds)],
                'notes' => 'nullable|string|max:500',
            ],
            [
                'toDepartmentId.in' => 'Choose a valid destination department.',
            ],
            [
                'quantity' => 'quantity',
                'toDepartmentId' => 'destination department',
            ]
        )->validate();

        try {
            $destStore = ProductionStore::getOrCreateForDepartment((int) $validated['toDepartmentId']);

            $transfer = app(ProductionStoreService::class)->sendStoreTransfer(
                $store,
                $destStore,
                $itemId,
                (float) $validated['quantity'],
                $notes ?: null,
                current_actor()
            );
        } catch (\InvalidArgumentException $e) {
            $this->toast()->error($e->getMessage())->send();

            return;
        }

        $qty = (float) $validated['quantity'];
        $this->toast()->success("Sent {$qty} {$transfer->uom} of {$transfer->item_name} (awaiting receipt).")->send();
    }

    /**
     * Resolve this store model on demand (the public $store is also set in
     * mount/render, but action calls may run before render).
     */
    protected function getStoreModel(): ?ProductionStore
    {
        return ProductionStore::forBranch($this->getBranchId())
            ->forDepartment($this->department->id)
            ->active()
            ->first();
    }
}
