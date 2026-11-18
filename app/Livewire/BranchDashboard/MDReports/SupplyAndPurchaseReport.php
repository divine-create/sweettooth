<?php

namespace App\Livewire\BranchDashboard\MDReports;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class SupplyAndPurchaseReport extends Component
{
    use ExportsCsv, Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public string $dateFilterMode = 'this_month'; // today, this_week, this_month, last_month, this_quarter, this_year, all_time, custom

    #[Url(keep: true)]
    public ?string $dateFrom = null;

    #[Url(keep: true)]
    public ?string $dateTo = null;

    #[Url(keep: true)]
    public string $selectedSupplierId = 'all';

    #[Url(keep: true)]
    public string $selectedPaymentStatus = 'all'; // all, paid, pending

    #[Url(keep: true)]
    public string $activeTab = 'suppliers'; // suppliers, items, purchases

    public string $search = '';

    // Modal state for supplier statement
    public bool $showSupplierModal = false;
    public ?string $selectedSupplierName = null;
    public ?int $modalSupplierId = null;
    public array $supplierStatement = [];

    // Modal state for purchase line items inspection
    public bool $showPurchaseModal = false;
    public ?Purchase $selectedPurchase = null;
    public array $purchaseModalItems = [];

    public function mount()
    {
        $this->b_id = request()->query('b_id', $this->b_id) ?? session('selected_branch_id') ?? current_branch_id();

        // If no explicit date preset was provided in the query string, check if the current month has purchases.
        // On the 1st or early days of a new month before new orders are entered,
        // automatically default to 'last_30_days' so users immediately see procurement metrics instead of zeros.
        if (!request()->has('dateFilterMode') && !request()->has('dateFrom')) {
            $currentMonthCount = Purchase::query()
                ->when($this->b_id && $this->b_id !== 'all', fn ($q) => $q->where('branch_id', $this->b_id))
                ->whereDate('purchase_date', '>=', Carbon::today()->startOfMonth())
                ->count();

            if ($currentMonthCount === 0) {
                $this->dateFilterMode = 'last_30_days';
            }
        }

        $this->applyDatePreset($this->dateFilterMode);
    }

    public function setDatePreset(string $preset): void
    {
        $this->dateFilterMode = $preset;
        $this->applyDatePreset($preset);
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->dateFilterMode = 'custom';
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->dateFilterMode = 'custom';
        $this->resetPage();
    }

    public function updatedSelectedSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    protected function applyDatePreset(string $preset): void
    {
        $today = Carbon::today();

        switch ($preset) {
            case 'today':
                $this->dateFrom = $today->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case 'this_week':
                $this->dateFrom = $today->copy()->startOfWeek()->toDateString();
                $this->dateTo = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->dateFrom = $today->copy()->startOfMonth()->toDateString();
                $this->dateTo = $today->copy()->endOfMonth()->toDateString();
                break;
            case 'last_30_days':
                $this->dateFrom = $today->copy()->subDays(30)->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case 'last_month':
                $this->dateFrom = $today->copy()->subMonth()->startOfMonth()->toDateString();
                $this->dateTo = $today->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_quarter':
                $this->dateFrom = $today->copy()->firstOfQuarter()->toDateString();
                $this->dateTo = $today->copy()->lastOfQuarter()->toDateString();
                break;
            case 'this_year':
                $this->dateFrom = $today->copy()->startOfYear()->toDateString();
                $this->dateTo = $today->copy()->endOfYear()->toDateString();
                break;
            case 'all_time':
                $this->dateFrom = null;
                $this->dateTo = null;
                break;
            case 'custom':
            default:
                if (!$this->dateFrom) {
                    $this->dateFrom = $today->copy()->startOfMonth()->toDateString();
                }
                if (!$this->dateTo) {
                    $this->dateTo = $today->copy()->endOfMonth()->toDateString();
                }
                break;
        }
    }

    /**
     * Build base query for purchases matching filters.
     */
    protected function getBasePurchasesQuery()
    {
        $branchId = $this->b_id;

        return Purchase::query()
            ->when($branchId && $branchId !== 'all', fn ($q) => $q->where('purchases.branch_id', $branchId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $this->dateTo))
            ->when($this->selectedSupplierId !== 'all', function ($q) {
                if (is_numeric($this->selectedSupplierId)) {
                    $q->where('purchases.supplier_id', (int) $this->selectedSupplierId);
                } else {
                    $q->where('purchases.supplier_name', $this->selectedSupplierId);
                }
            })
            ->when($this->selectedPaymentStatus !== 'all', fn ($q) => $q->where('purchases.payment_status', $this->selectedPaymentStatus));
    }

    /**
     * Compute KPI cards summary metrics.
     */
    public function getKpiSummary(): array
    {
        $base = $this->getBasePurchasesQuery();

        $purchases = (clone $base)
            ->selectRaw('
                count(id) as total_orders,
                sum(coalesce(landing_cost, total_cost, 0)) as total_spend,
                sum(case when payment_status = "paid" then coalesce(landing_cost, total_cost, 0) else 0 end) as total_paid,
                sum(case when payment_status != "paid" then coalesce(landing_cost, total_cost, 0) else 0 end) as total_pending,
                count(distinct coalesce(supplier_id, supplier_name)) as active_suppliers_count
            ')
            ->first();

        $totalOrders = (int) ($purchases->total_orders ?? 0);
        $totalSpend = (float) ($purchases->total_spend ?? 0);
        $totalPaid = (float) ($purchases->total_paid ?? 0);
        $totalPending = (float) ($purchases->total_pending ?? 0);
        $activeSuppliers = (int) ($purchases->active_suppliers_count ?? 0);
        $avgOrder = $totalOrders > 0 ? ($totalSpend / $totalOrders) : 0;
        $paidPercentage = $totalSpend > 0 ? round(($totalPaid / $totalSpend) * 100, 1) : 0;
        $pendingPercentage = $totalSpend > 0 ? round(($totalPending / $totalSpend) * 100, 1) : 0;

        return [
            'total_spend' => $totalSpend,
            'total_orders' => $totalOrders,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'active_suppliers' => $activeSuppliers,
            'avg_order_value' => $avgOrder,
            'paid_percentage' => $paidPercentage,
            'pending_percentage' => $pendingPercentage,
        ];
    }

    /**
     * Query suppliers summary & vendor debt balances.
     */
    public function getSuppliersSummary(): array
    {
        $branchId = $this->b_id;
        $search = trim($this->search);

        $query = Purchase::query()
            ->when($branchId && $branchId !== 'all', fn ($q) => $q->where('purchases.branch_id', $branchId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $this->dateTo))
            ->when($this->selectedSupplierId !== 'all', function ($q) {
                if (is_numeric($this->selectedSupplierId)) {
                    $q->where('purchases.supplier_id', (int) $this->selectedSupplierId);
                } else {
                    $q->where('purchases.supplier_name', $this->selectedSupplierId);
                }
            })
            ->when($this->selectedPaymentStatus !== 'all', fn ($q) => $q->where('purchases.payment_status', $this->selectedPaymentStatus))
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->selectRaw('
                coalesce(suppliers.id, purchases.supplier_id) as supplier_id,
                coalesce(suppliers.name, purchases.supplier_name, "Unknown Vendor") as supplier_name,
                coalesce(suppliers.phone, purchases.supplier_contact, "N/A") as phone,
                coalesce(suppliers.code, "N/A") as code,
                count(purchases.id) as orders_count,
                sum(coalesce(purchases.landing_cost, purchases.total_cost, 0)) as total_supplied,
                sum(case when purchases.payment_status = "paid" then coalesce(purchases.landing_cost, purchases.total_cost, 0) else 0 end) as amount_paid,
                sum(case when purchases.payment_status != "paid" then coalesce(purchases.landing_cost, purchases.total_cost, 0) else 0 end) as balance_due
            ')
            ->groupBy('purchases.supplier_id', 'purchases.supplier_name', 'suppliers.id', 'suppliers.name', 'suppliers.phone', 'purchases.supplier_contact', 'suppliers.code');

        if ($search !== '') {
            $query->havingRaw('supplier_name LIKE ? OR phone LIKE ? OR code LIKE ?', ["%{$search}%", "%{$search}%", "%{$search}%"]);
        }

        $results = $query->orderByDesc('total_supplied')->get();

        return $results->map(function ($row) {
            $supplied = (float) $row->total_supplied;
            $paid = (float) $row->amount_paid;
            $balance = (float) $row->balance_due;
            $paidPct = $supplied > 0 ? round(($paid / $supplied) * 100, 1) : 0;

            return [
                'supplier_id' => $row->supplier_id,
                'supplier_name' => $row->supplier_name,
                'code' => $row->code,
                'phone' => $row->phone,
                'orders_count' => (int) $row->orders_count,
                'total_supplied' => $supplied,
                'amount_paid' => $paid,
                'balance_due' => $balance,
                'paid_percentage' => $paidPct,
            ];
        })->toArray();
    }

    /**
     * Query top purchased items & ingredients with price variance.
     */
    public function getItemsSummary(): array
    {
        $branchId = $this->b_id;
        $search = trim($this->search);

        $query = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->leftJoin('items', 'items.id', '=', 'purchase_items.item_id')
            ->when($branchId && $branchId !== 'all', fn ($q) => $q->where('purchases.branch_id', $branchId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $this->dateTo))
            ->when($this->selectedSupplierId !== 'all', function ($q) {
                if (is_numeric($this->selectedSupplierId)) {
                    $q->where('purchases.supplier_id', (int) $this->selectedSupplierId);
                } else {
                    $q->where('purchases.supplier_name', $this->selectedSupplierId);
                }
            })
            ->when($this->selectedPaymentStatus !== 'all', fn ($q) => $q->where('purchases.payment_status', $this->selectedPaymentStatus))
            ->selectRaw('
                purchase_items.item_id,
                coalesce(items.name, concat("Item #", purchase_items.item_id)) as item_name,
                coalesce(items.sku, "N/A") as sku,
                coalesce(purchase_items.uom, "units") as uom,
                sum(purchase_items.quantity) as total_qty,
                sum(coalesce(purchase_items.total_cost, 0)) as total_spend,
                avg(case when purchase_items.quantity > 0 then (purchase_items.total_cost / purchase_items.quantity) else 0 end) as avg_unit_cost,
                min(case when purchase_items.quantity > 0 then (purchase_items.total_cost / purchase_items.quantity) else null end) as min_unit_cost,
                max(case when purchase_items.quantity > 0 then (purchase_items.total_cost / purchase_items.quantity) else null end) as max_unit_cost,
                count(distinct purchases.id) as purchases_count
            ')
            ->groupBy('purchase_items.item_id', 'items.name', 'items.sku', 'purchase_items.uom');

        if ($search !== '') {
            $query->havingRaw('item_name LIKE ? OR sku LIKE ?', ["%{$search}%", "%{$search}%"]);
        }

        $items = $query->orderByDesc('total_spend')->get();
        $totalSpendAll = $items->sum('total_spend');

        return $items->map(function ($row) use ($totalSpendAll) {
            $spend = (float) $row->total_spend;
            $share = $totalSpendAll > 0 ? round(($spend / $totalSpendAll) * 100, 1) : 0;

            return [
                'item_id' => $row->item_id,
                'item_name' => $row->item_name,
                'sku' => $row->sku,
                'uom' => $row->uom,
                'total_qty' => (float) $row->total_qty,
                'total_spend' => $spend,
                'avg_unit_cost' => (float) $row->avg_unit_cost,
                'min_unit_cost' => (float) $row->min_unit_cost,
                'max_unit_cost' => (float) $row->max_unit_cost,
                'purchases_count' => (int) $row->purchases_count,
                'spend_share' => $share,
            ];
        })->toArray();
    }

    /**
     * Inspect purchase items modal.
     */
    public function openPurchaseDetail(int $purchaseId): void
    {
        $this->selectedPurchase = Purchase::with(['purchaseItems.item', 'supplier'])->find($purchaseId);

        if (!$this->selectedPurchase) {
            $this->toast()->error('Purchase record not found.')->send();
            return;
        }

        $this->purchaseModalItems = $this->selectedPurchase->purchaseItems->map(function ($row) {
            return [
                'item_name' => $row->item?->name ?? 'N/A',
                'sku' => $row->item?->sku ?? 'N/A',
                'quantity' => (float) $row->quantity,
                'uom' => $row->uom ?? 'units',
                'unit_price' => (float) ($row->unit_fob_ngn ?? 0),
                'other_costs' => (float) ($row->allocated_other_costs ?? 0),
                'total_cost' => (float) ($row->total_cost ?? 0),
                'cost_per_unit' => (float) ($row->cost_per_unit ?? 0),
                'batch_number' => $row->batch_number ?? 'N/A',
                'expiry_date' => $row->expiry_date ? $row->expiry_date->format('Y-m-d') : 'N/A',
            ];
        })->toArray();

        $this->showPurchaseModal = true;
    }

    public function closePurchaseModal(): void
    {
        $this->showPurchaseModal = false;
        $this->selectedPurchase = null;
        $this->purchaseModalItems = [];
    }

    /**
     * View supplier statement modal.
     */
    public function openSupplierStatement($supplierId, string $supplierName): void
    {
        $this->modalSupplierId = $supplierId ? (int) $supplierId : null;
        $this->selectedSupplierName = $supplierName;

        $purchases = Purchase::query()
            ->when($this->b_id && $this->b_id !== 'all', fn ($q) => $q->where('branch_id', $this->b_id))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->where(function ($q) use ($supplierId, $supplierName) {
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId)
                      ->orWhere('supplier_name', $supplierName);
                } else {
                    $q->where('supplier_name', $supplierName);
                }
            })
            ->withCount('purchaseItems')
            ->orderBy('purchase_date', 'desc')
            ->get();

        $this->supplierStatement = $purchases->map(function ($p) {
            $cost = (float) ($p->landing_cost ?: ($p->total_cost ?: $p->calculateTotalCost()));
            return [
                'id' => $p->id,
                'purchase_number' => $p->purchase_number,
                'purchase_date' => $p->purchase_date ? $p->purchase_date->format('M d, Y') : 'N/A',
                'items_count' => $p->purchase_items_count,
                'amount' => $cost,
                'payment_status' => $p->payment_status,
                'notes' => $p->notes,
            ];
        })->toArray();

        $this->showSupplierModal = true;
    }

    public function closeSupplierModal(): void
    {
        $this->showSupplierModal = false;
        $this->modalSupplierId = null;
        $this->selectedSupplierName = null;
        $this->supplierStatement = [];
    }

    /**
     * Export active tab to CSV.
     */
    public function exportToCsv()
    {
        $dateLabel = ($this->dateFrom && $this->dateTo) ? "{$this->dateFrom}_to_{$this->dateTo}" : 'all_time';

        if ($this->activeTab === 'suppliers') {
            $data = $this->getSuppliersSummary();
            $header = ['Supplier Code', 'Supplier Name', 'Phone', 'Total Orders', 'Total Supplied (NGN)', 'Amount Paid (NGN)', 'Balance Due (NGN)', 'Paid %'];
            $rows = array_map(function ($r) {
                return [
                    $r['code'],
                    $r['supplier_name'],
                    $r['phone'],
                    $r['orders_count'],
                    number_format($r['total_supplied'], 2, '.', ''),
                    number_format($r['amount_paid'], 2, '.', ''),
                    number_format($r['balance_due'], 2, '.', ''),
                    $r['paid_percentage'] . '%',
                ];
            }, $data);

            return $this->streamCsv("suppliers_summary_{$dateLabel}", $header, $rows);
        }

        if ($this->activeTab === 'items') {
            $data = $this->getItemsSummary();
            $header = ['Item Name', 'SKU', 'UOM', 'Total Qty', 'Avg Unit Cost (NGN)', 'Min Unit Cost', 'Max Unit Cost', 'Total Spend (NGN)', 'Spend Share %'];
            $rows = array_map(function ($r) {
                return [
                    $r['item_name'],
                    $r['sku'],
                    $r['uom'],
                    number_format($r['total_qty'], 2, '.', ''),
                    number_format($r['avg_unit_cost'], 2, '.', ''),
                    number_format($r['min_unit_cost'], 2, '.', ''),
                    number_format($r['max_unit_cost'], 2, '.', ''),
                    number_format($r['total_spend'], 2, '.', ''),
                    $r['spend_share'] . '%',
                ];
            }, $data);

            return $this->streamCsv("materials_procurement_{$dateLabel}", $header, $rows);
        }

        // Tab: purchases
        $base = $this->getBasePurchasesQuery();
        $purchases = $base->with(['supplier', 'purchaseItems'])->latest('purchase_date')->get();
        $header = ['Purchase #', 'Date', 'Supplier', 'Items Count', 'FOB (NGN)', 'Other Costs (NGN)', 'Landing Cost (NGN)', 'Payment Status', 'Notes'];
        $rows = $purchases->map(function ($p) {
            return [
                $p->purchase_number,
                optional($p->purchase_date)->toDateString(),
                $p->supplier?->name ?? $p->supplier_name,
                $p->purchaseItems->count(),
                number_format($p->total_fob_ngn ?? 0, 2, '.', ''),
                number_format($p->other_costs ?? 0, 2, '.', ''),
                number_format($p->landing_cost ?: ($p->total_cost ?: 0), 2, '.', ''),
                ucfirst((string) $p->payment_status),
                $p->notes ?? '',
            ];
        })->toArray();

        return $this->streamCsv("purchases_log_{$dateLabel}", $header, $rows);
    }

    public function render()
    {
        $branchId = $this->b_id;
        $search = trim($this->search);

        // All suppliers for dropdown filter
        $suppliersList = Supplier::query()
            ->when($branchId && $branchId !== 'all', fn ($q) => $q->byBranch($branchId))
            ->orderBy('name')
            ->get();

        // Branches for context
        $branches = Branch::orderBy('name')->get();
        $currentBranch = Branch::find($branchId);

        // Detailed purchases pagination
        $purchasesQuery = $this->getBasePurchasesQuery()
            ->with(['supplier', 'purchaseItems.item'])
            ->withCount('purchaseItems');

        if ($search !== '') {
            $purchasesQuery->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('purchaseItems.item', fn ($it) => $it->where('name', 'like', "%{$search}%"));
            });
        }

        $purchases = $purchasesQuery->latest('purchase_date')->latest('id')->paginate(15);

        return view('livewire.branch-dashboard.m-d-reports.supply-and-purchase-report', [
            'kpi' => $this->getKpiSummary(),
            'suppliersSummary' => $this->activeTab === 'suppliers' ? $this->getSuppliersSummary() : [],
            'itemsSummary' => $this->activeTab === 'items' ? $this->getItemsSummary() : [],
            'purchases' => $purchases,
            'suppliersList' => $suppliersList,
            'branches' => $branches,
            'currentBranch' => $currentBranch,
        ]);
    }
}
