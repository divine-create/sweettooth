<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Pos;

use App\Helpers\Settings;
use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\BranchPosConfiguration;
use App\Models\Department;
use App\Models\Item;
use App\Models\MaterialRequestDispatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductStock;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesShift;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\Table;
use App\Models\User;
use App\Notifications\SalesReceiptNotification;
use App\Services\CurrencyFormattingService;
use App\Services\PosDocumentService;
use App\Services\UomConversionService;
use Carbon\Carbon;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use SalesDepartmentContext;

    // Note: salesDeptSlug, branchId, departmentId, departmentName, branchName
    // are now provided by SalesDepartmentContext trait

    public string $search = '';

    public string $stockFilter = 'all'; // 'all' | 'in_stock' | 'out_of_stock'

    public array $cart = [];

    public float $subtotal = 0.0;

    public $discount = 0.0;

    public float $tax = 0.0;

    public float $total = 0.0;

    public ?int $activeShiftId = null;

    public float $cashReceived = 0.0;

    public float $changeDue = 0.0;

    public ?int $currentSaleId = null;

    public string $orderType = 'dine-in';

    public array $payments = [];

    public float $paymentTotal = 0.0;

    public float $paymentRemaining = 0.0;

    // How to handle an overpayment (paymentTotal > total):
    //   'change'  = hand the difference back as change (only the bill stays in the drawer)
    //   'overage' = customer kept it; the difference is booked as overage income
    public string $overageDisposition = 'change';

    public string $shiftType = 'morning';

    protected ?CursorPaginator $productsForView = null;

    protected ?array $productAvailabilityForView = null;

    protected ?array $productsPayloadForView = null;

    public int $productsPerPage = 50;

    public function __set(string $name, mixed $value): void
    {
        // Ensure float properties stay as floats
        if (in_array($name, ['subtotal', 'discount', 'tax', 'total', 'cashReceived', 'changeDue', 'paymentTotal', 'paymentRemaining'])) {
            $value = (float) $value;
        }
        parent::__set($name, $value);
    }

    // Quotations / draft orders
    public bool $showQuotationModal = false;

    public string $quotationCustomerName = '';

    public string $quotationCustomerPhone = '';

    public ?string $quotationValidUntil = null;

    public string $quotationNotes = '';

    // Set when the current cart was loaded from a quotation; on successful sale
    // completion the quotation is marked converted and linked to the new sale.
    public ?int $convertingQuotationId = null;

    // Table Management
    public ?int $selectedTableId = null;

    public bool $showTableManagement = false;

    public bool $showTableModal = false;

    public string $newTableNumber = '';

    public string $newTableName = '';

    public int $newTableCapacity = 4;

    protected $rules = [
        'discount' => 'numeric|min:0',
        'cashReceived' => 'numeric|min:0',
        'orderType' => 'in:dine-in,takeaway,delivery',
        'payments.*.method' => 'required|in:cash,transfer,pos',
        'payments.*.amount' => 'numeric|min:0',
        'payments.*.bank_account_id' => 'nullable|exists:bank_accounts,id',
        'payments.*.payer_bank' => 'nullable|string|max:255',
    ];

    /**
     * Format currency value for POS display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    public function mount(): void
    {
        $this->mountBase();
        $this->initializeDepartmentContext();
        $this->departmentName = $this->departmentName ?: 'POS';
        $this->loadActiveShift();

        $this->payments = [['method' => 'cash', 'amount' => 0.0]];
        $this->recalculateTotals();
        $this->recalcPayments();
        $this->checkTableManagement();

        // Arriving from the Quotations list with ?quotation=ID loads it into the cart.
        $quotationId = (int) (request()->query('quotation') ?? 0);
        if ($quotationId > 0) {
            $this->loadQuotationToCart($quotationId);
        }
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->branchId = $branchId;
        $this->initializeDepartmentContext(); // Using trait method
        $this->loadActiveShift();
        $this->resetCart();
    }

    // loadBranchAndDepartment is now handled by SalesDepartmentContext trait

    protected function checkTableManagement(): void
    {
        // Check if current department has table management enabled
        if ($this->departmentId) {
            $department = Department::find($this->departmentId);
            $this->showTableManagement = $department?->enable_table_management ?? false;
        } else {
            $this->showTableManagement = false;
        }
    }

    public function toggleTableManagement(): void
    {
        if (! $this->departmentId) {
            $this->toast()->error('Department not found.')->send();

            return;
        }

        $department = Department::find($this->departmentId);
        if (! $department) {
            $this->toast()->error('Department not found.')->send();

            return;
        }

        $department->enable_table_management = ! $department->enable_table_management;
        $department->save();

        $this->showTableManagement = $department->enable_table_management;

        // If enabling for the first time and no tables exist for this branch+department, create 8 default tables
        $existingTablesCount = Table::where('branch_id', $this->branchId)
            ->where('department_id', $this->departmentId)
            ->count();

        if ($department->enable_table_management && $existingTablesCount === 0) {
            for ($i = 1; $i <= 8; $i++) {
                // Double-check this specific table doesn't exist before creating
                $tableExists = Table::where('branch_id', $this->branchId)
                    ->where('table_number', (string) $i)
                    ->exists();

                if (! $tableExists) {
                    Table::create([
                        'branch_id' => $this->branchId,
                        'department_id' => $this->departmentId,
                        'table_number' => (string) $i,
                        'table_name' => 'Table '.$i,
                        'status' => 'available',
                        'capacity' => 4,
                        'is_active' => true,
                    ]);
                }
            }
            $this->toast()->success('Table management enabled! Default tables created for '.$department->name.'.')->send();
        } else {
            $message = $department->enable_table_management
                ? 'Table management enabled for '.$department->name.'.'
                : 'Table management disabled for '.$department->name.'.';
            $this->toast()->success($message)->send();
        }

        unset($this->tables);
    }

    protected function loadActiveShift(): void
    {
        $shift = Shift::where('employee_id', auth()->id())
            ->where('status', 'active')
            ->whereDate('shift_date', Carbon::today())
            ->first();

        if ($shift) {
            $this->activeShiftId = $shift->id;
            $this->shiftType = (string) ($shift->shift_type ?: 'morning');
        } else {
            $this->activeShiftId = null;
        }
    }

    #[Computed]
    public function activeShift()
    {
        // return $this->activeShiftId ? SalesShift::find($this->activeShiftId) : null;

        return $this->activeShiftId ? Shift::find($this->activeShiftId) : null;
    }

    public function hasActiveShift(): bool
    {
        return $this->activeShiftId !== null;
    }

    public function getModelClass(): string
    {
        return Sale::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage('products_cursor');
        $this->productsForView = null;
        $this->productAvailabilityForView = null;
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage('products_cursor');
        $this->productsForView = null;
        $this->productAvailabilityForView = null;
    }

    public function updatedProductsCursor(): void
    {
        $this->productsForView = null;
        $this->productAvailabilityForView = null;
    }

    public function addToCart(string $productId): void
    {
        if (! $this->departmentId) {
            $this->toast()->error('Sales department context is missing.')->send();

            return;
        }

        $productQuery = Product::query()
            ->with(['unitOfMeasure', 'salesUom'])
            ->active()
            ->available()
            ->whereKey($productId);

        $departmentIds = $this->scopedSalesDepartmentIds();
        if (! empty($departmentIds)) {
            $productQuery->whereIn('sales_department_id', $departmentIds);
        }

        $product = $productQuery->first();
        if (! $product) {
            $this->toast()->error('Product is not available for this sales department.')->send();

            return;
        }

        // Block products with no selling price: a zero-price line collects no
        // revenue and is almost always a data-entry mistake. The manager must set
        // the product's selling price before it can be sold. (A zero COST is
        // allowed — cost is an internal figure and may legitimately be unset.)
        if ((float) ($product->price ?? 0) <= 0) {
            $this->toast()->error($product->name.' has no selling price set and cannot be sold. Please set the price before selling.')->send();

            return;
        }

        $stock = $this->getTodayStockForProduct($productId);
        $available = $this->availableQuantity($stock);

        $lineKey = (string) $productId;
        $currentQty = $this->cart[$lineKey]['qty'] ?? 0;
        $newQty = $currentQty + 1;

        // Convert sales quantity to base quantity for stock checking
        $baseQuantity = $product->hasSalesUomConversion()
            ? $product->convertSalesToBaseQuantity($newQty)
            : $newQty;

        // Strict stock enforcement: cannot add if out of stock
        if ($available <= 0) {
            $this->toast()->error('Out of stock for '.$product->name.'. Cannot add to cart.')->send();

            return;
        }

        // Strict stock enforcement: cannot exceed available quantity (in base UOM)
        if ($baseQuantity > $available) {
            $this->toast()->warning('Cannot add more than available stock ('.$available.' '.$product->uomSymbol.') for '.$product->name)->send();

            return;
        }

        $this->cart[$lineKey] = [
            'product_id' => $productId,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'qty' => $newQty,
            'sales_uom' => $product->effectiveSalesUomSymbol,
            'base_uom' => $product->uomSymbol,
            'has_conversion' => $product->hasSalesUomConversion(),
            'base_quantity' => $baseQuantity,
            'low_stock' => $available < 10,
            'available' => $available,
            'available_sales_qty' => $product->hasSalesUomConversion()
                ? floor($product->convertBaseToSalesQuantity($available))
                : $available,
        ];

        $this->recalculateTotals();
    }

    public function increment(string $lineKey): void
    {
        if (! isset($this->cart[$lineKey])) {
            return;
        }

        $line = $this->cart[$lineKey];
        $newQty = $line['qty'] + 1;

        // $available must be expressed in the SAME unit as the cart qty. For products
        // with a sales-UOM conversion the stock is in base units (grams) while qty is
        // in sales units (scoops), so convert availability to sales units — otherwise
        // the guard never fires and the cart can be built past real stock.
        if (isset($line['item_id'])) {
            $available = (float) ($line['available'] ?? 0);
        } else {
            $stock = $this->getTodayStockForProduct((string) $line['product_id']);
            $availableBase = $this->availableQuantity($stock);
            $this->cart[$lineKey]['available'] = $availableBase;
            $available = $this->availableInSalesUnits((string) $line['product_id'], $availableBase);
            $this->cart[$lineKey]['available_sales_qty'] = $available;
        }

        if ($newQty > $available) {
            $this->toast()->warning('Cannot exceed available stock ('.$available.' '.($line['sales_uom'] ?? '').') for '.$line['name'])->send();

            return;
        }

        $this->cart[$lineKey]['qty'] = $newQty;
        $this->recalculateTotals();
    }

    /**
     * Available stock expressed in the product's SALES units (e.g. scoops), given
     * an availability already computed in base units (e.g. grams). Non-conversion
     * products return the base figure unchanged.
     */
    private function availableInSalesUnits(string $productId, float $availableBase): float
    {
        $product = \App\Models\Product::find($productId);

        if ($product && $product->hasSalesUomConversion()) {
            return floor($product->convertBaseToSalesQuantity($availableBase));
        }

        return $availableBase;
    }

    public function decrement(string $lineKey): void
    {
        if (! isset($this->cart[$lineKey])) {
            return;
        }
        $newQty = max(1, $this->cart[$lineKey]['qty'] - 1);
        $this->cart[$lineKey]['qty'] = $newQty;
        $this->recalculateTotals();
    }

    public function updateQuantity(string $lineKey, $quantity): void
    {
        if (! isset($this->cart[$lineKey])) {
            return;
        }

        $line = $this->cart[$lineKey];
        $qty = max(1, (float) $quantity);

        // $available must be in the SAME unit as $qty (sales units for conversion
        // products). The old code compared sales-unit $qty against base-unit stock and
        // then "capped" $qty to the base (gram) figure — e.g. capping 600 scoops to
        // "500" actually meant 500 scoops = 50,000 g, a huge oversell. Convert first.
        if (isset($line['item_id'])) {
            $available = (float) ($line['available'] ?? 0);
        } else {
            $stock = $this->getTodayStockForProduct((string) $line['product_id']);
            $availableBase = $this->availableQuantity($stock);
            $this->cart[$lineKey]['available'] = $availableBase;
            $available = $this->availableInSalesUnits((string) $line['product_id'], $availableBase);
            $this->cart[$lineKey]['available_sales_qty'] = $available;
        }

        if ($qty > $available) {
            $this->toast()->warning('Cannot exceed available stock ('.$available.' '.($line['sales_uom'] ?? '').') for '.$line['name'])->send();
            $qty = $available;
        }

        $this->cart[$lineKey]['qty'] = $qty;
        $this->recalculateTotals();
    }

    public function remove(string $lineKey): void
    {
        unset($this->cart[$lineKey]);
        $this->recalculateTotals();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->recalculateTotals();
    }

    public function resetCart(): void
    {
        $this->cart = [];
        $this->subtotal = 0.0;
        $this->discount = 0.0;
        $this->tax = 0.0;
        $this->total = 0.0;
        $this->cashReceived = 0.0;
        $this->changeDue = 0.0;
        $this->paymentTotal = 0.0;
        $this->paymentRemaining = 0.0;
        $this->payments = [['method' => 'cash', 'amount' => 0.0]];
        $this->selectedTableId = null;
        $this->currentSaleId = null;
        $this->search = '';
    }

    public function updatedDiscount(): void
    {
        $this->discount = (float) ($this->discount ?: 0);
        $this->recalculateTotals();
    }

    public function updatedCashReceived(): void
    {
        $this->recalculateTotals();
    }

    protected function recalculateTotals(): void
    {
        $gross = 0.0;
        foreach ($this->cart as $line) {
            $gross += $line['price'] * $line['qty'];
        }
        $grossAfterDiscount = max(0, $gross - (float) ($this->discount ?: 0));
        $rate = $this->vatRate();
        // VAT is INCLUSIVE: the cart prices already contain VAT, so extract the
        // VAT portion from the (post-discount) gross and leave the customer total unchanged.
        $this->tax = $rate > 0 ? round($grossAfterDiscount * $rate / (100 + $rate), 2) : 0.0;
        $this->total = $grossAfterDiscount;                          // amount the customer pays (VAT included)
        $this->subtotal = round($grossAfterDiscount - $this->tax, 2); // net of VAT (recorded as revenue)
        $this->changeDue = max(0, $this->paymentTotal - $this->total);
    }

    /**
     * VAT rate (%) for this branch. Inclusive. Configurable via POS settings,
     * defaults to the Nigerian standard rate of 7.5%.
     */
    protected function vatRate(): float
    {
        return (float) (BranchPosConfiguration::query()
            ->where('branch_id', $this->branchId)
            ->value('vat_rate') ?? 7.5);
    }

    public function addPaymentRow(): void
    {
        $this->payments[] = ['method' => 'cash', 'amount' => 0.0, 'bank_account_id' => null, 'payer_bank' => null];
        $this->recalcPayments();
    }

    public function removePaymentRow(int $index): void
    {
        if (isset($this->payments[$index])) {
            array_splice($this->payments, $index, 1);
        }
        if (empty($this->payments)) {
            $this->payments[] = ['method' => 'cash', 'amount' => 0.0, 'bank_account_id' => null, 'payer_bank' => null];
        }
        $this->recalcPayments();
    }

    public function updatedPayments(): void
    {
        $this->recalcPayments();
    }

    protected function recalcPayments(): void
    {
        $total = 0.0;
        $defaultBankId = $this->getDefaultDepartmentBankAccountId() ?? $this->bankAccounts->first()?->id ?? null;
        foreach ($this->payments as $index => $p) {
            if (($p['method'] ?? '') === 'transfer' && empty($p['bank_account_id']) && $defaultBankId) {
                $this->payments[$index]['bank_account_id'] = $defaultBankId;
            }
            $total += (float) ($p['amount'] ?? 0);
        }
        $this->payments = array_values($this->payments);
        $this->paymentTotal = $total;
        $this->paymentRemaining = max(0, $this->total - $this->paymentTotal);
        $this->changeDue = max(0, $this->paymentTotal - $this->total);

        // No surplus means no disposition choice to make.
        if ($this->changeDue <= 0.0001) {
            $this->overageDisposition = 'change';
        }
    }

    protected function getDefaultDepartmentBankAccountId(): ?int
    {
        if (! $this->departmentId) {
            return null;
        }

        $department = Department::find($this->departmentId);

        return $department?->bank_account_id;
    }

    protected function getDefaultDepartmentBankAccountLabel(): string
    {
        $bankAccountId = $this->getDefaultDepartmentBankAccountId();
        if (! $bankAccountId) {
            return 'No bank linked to department';
        }

        $bankAccount = BankAccount::find($bankAccountId);
        if (! $bankAccount) {
            return 'No bank linked to department';
        }

        $bankName = trim((string) ($bankAccount->bank_name ?? ''));
        $accountNumber = trim((string) ($bankAccount->account_number ?? ''));
        if ($bankName === '' && $accountNumber === '') {
            return 'Bank linked to department';
        }

        if ($accountNumber !== '') {
            return $bankName !== ''
                ? ($bankName.' · '.$accountNumber)
                : $accountNumber;
        }

        return $bankName;
    }

    public function completeSale(): void
    {
        try {
            // Check for active shift first
            if (! $this->hasActiveShift()) {
                $this->toast()->error('No active shift. Please start a shift before making sales.')->send();

                return;
            }

            if (empty($this->cart)) {
                $this->toast()->warning('Cart is empty.')->send();

                return;
            }

            foreach ($this->payments as $paymentData) {
                if (($paymentData['method'] ?? '') === 'transfer' && empty($paymentData['bank_account_id'])) {
                    $this->toast()->error('Transfer requires a department-linked bank account.')->send();

                    return;
                }
            }

            $this->recalcPayments();
            if ($this->paymentTotal + 0.0001 < $this->total) {
                $this->toast()->warning('Payments do not cover the total.')->send();

                return;
            }

            // Validate payments
            $this->validate([
                'payments.*.method' => 'required|in:cash,transfer,pos',
                'payments.*.amount' => 'required|numeric|min:0',
            ]);

            // Block the sale if any line has no selling price. A zero-price line
            // collects nothing and is almost always a mistake, so the whole sale
            // is refused until a price is set. (Zero cost is allowed.)
            $zeroPriceNames = collect($this->cart)
                ->filter(fn ($line) => (float) ($line['price'] ?? 0) <= 0)
                ->map(fn ($line) => $line['name'] ?? 'item')
                ->values();

            if ($zeroPriceNames->isNotEmpty()) {
                $this->toast()->error('Sale blocked — no selling price for: '.$zeroPriceNames->implode(', ').'. Set the price before selling.')->send();

                return;
            }

            DB::transaction(function () {
                $productIds = array_values(array_unique(array_map(
                    static fn ($line): string => (string) ($line['product_id'] ?? ''),
                    $this->cart
                )));

                $productsById = Product::query()
                    ->with(['unitOfMeasure', 'salesUom'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                $stocksByProduct = [];
                $stockQuery = ProductStock::query()
                    ->whereDate('stock_date', Carbon::today())
                    ->whereIn('product_id', $productIds);

                if (Schema::hasColumn('product_stocks', 'department_id') && $this->departmentId) {
                    $departmentIds = $this->resolveEquivalentSalesDepartmentIds();
                    if (empty($departmentIds)) {
                        $departmentIds = [(int) $this->departmentId];
                    }
                    $stockQuery->whereIn('department_id', $departmentIds)
                        ->orderByRaw('department_id = ? DESC', [(int) $this->departmentId]);
                }

                $stockQuery->orderByDesc('id')->lockForUpdate();

                foreach ($stockQuery->get() as $stock) {
                    $productId = (string) $stock->product_id;
                    if (! isset($stocksByProduct[$productId])) {
                        $stocksByProduct[$productId] = $stock;
                    }
                }

                // Resolve how any overpayment is handled. The change is always given
                // from cash, so a "give change" disposition trims the recorded cash
                // down to the bill total; "overage" keeps the full tender in the drawer
                // and books the surplus as income (see SaleObserver / GlPostingService).
                $amountTendered = round($this->paymentTotal, 2);
                $overage = round(max(0, $amountTendered - $this->total), 2);
                $changeGiven = 0.0;
                $overShortAmount = 0.0;
                $overShortDisposition = null;
                $overShortGlStatus = null;
                $paymentRows = $this->payments;

                if ($overage > 0.0001) {
                    if ($this->overageDisposition === 'overage') {
                        $overShortAmount = $overage;
                        $overShortDisposition = 'overage';
                        $overShortGlStatus = 'pending';
                    } else {
                        $changeGiven = $overage;
                        $overShortDisposition = 'change';
                        // Reduce recorded cash so the persisted payments net to the bill.
                        $remainingToTrim = $overage;
                        foreach ($paymentRows as $i => $row) {
                            if ($remainingToTrim <= 0.0001) {
                                break;
                            }
                            if (($row['method'] ?? '') !== 'cash') {
                                continue;
                            }
                            $amt = (float) ($row['amount'] ?? 0);
                            $trim = min($amt, $remainingToTrim);
                            $paymentRows[$i]['amount'] = round($amt - $trim, 2);
                            $remainingToTrim = round($remainingToTrim - $trim, 2);
                        }
                    }
                }

                // Customer receipt reflects the product price only — never the surplus a
                // customer overpaid (whether returned as change or kept as overage). Trim
                // any overpayment from cash so the receipt's payment lines net to the bill.
                $receiptPayments = $this->payments;
                $remainingReceiptTrim = $overage;
                foreach ($receiptPayments as $i => $row) {
                    if ($remainingReceiptTrim <= 0.0001) {
                        break;
                    }
                    if (($row['method'] ?? '') !== 'cash') {
                        continue;
                    }
                    $amt = (float) ($row['amount'] ?? 0);
                    $trim = min($amt, $remainingReceiptTrim);
                    $receiptPayments[$i]['amount'] = round($amt - $trim, 2);
                    $remainingReceiptTrim = round($remainingReceiptTrim - $trim, 2);
                }

                $sale = Sale::create([
                    'sales_shift_id' => null, // Nullable - using general shifts table instead
                    'shift_id' => $this->activeShiftId,
                    'branch_id' => $this->branchId,
                    'department_id' => $this->departmentId,
                    'table_id' => $this->selectedTableId,
                    'sold_by_id' => auth()->id(),
                    'sold_by_type' => User::class,
                    'sale_number' => 'POS-'.Carbon::now()->format('Ymd-His'),
                    'sale_time' => Carbon::now(),
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => (float) ($this->discount ?: 0),
                    'total' => $this->total,
                    'status' => 'completed',
                    'order_type' => $this->orderType,
                    'notes' => null,
                    'amount_tendered' => $amountTendered,
                    'change_given' => $changeGiven,
                    'over_short_amount' => $overShortAmount,
                    'over_short_disposition' => $overShortDisposition,
                    'over_short_gl_status' => $overShortGlStatus,
                ]);

                $lowStockWarnings = [];

                foreach ($this->cart as $line) {
                    // ── Inventory item sale ──────────────────────────────────
                    if (isset($line['item_id'])) {
                        $itemQty = (float) $line['qty'];
                        if ($itemQty > 0) {
                            $invStock = Stock::where('item_id', $line['item_id'])
                                ->where('branch_id', $this->branchId)
                                ->value('average_cost');

                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'department_id' => $this->departmentId,
                                'product_id' => null,
                                'item_id' => $line['item_id'],
                                'quantity' => $itemQty,
                                'sales_quantity' => $itemQty,
                                'unit_price' => $line['price'],
                                'unit_cost' => (float) ($invStock ?? 0),
                                'subtotal' => $itemQty * $line['price'],
                                'discount' => 0,
                                'total' => $itemQty * $line['price'],
                            ]);
                            // Stock decrement is handled by SaleItem::saved() observer
                        }

                        continue;
                    }

                    // ── Production product sale (existing logic) ─────────────
                    $productId = (string) $line['product_id'];
                    $qty = (float) $line['qty']; // in SALES units (e.g. scoops)

                    $stock = $stocksByProduct[$productId] ?? null;
                    $available = $this->availableQuantity($stock); // in BASE units (e.g. grams)
                    $product = $productsById->get($productId);

                    // Hard cap the sale to what is actually available — comparing in the SAME
                    // unit as the cart quantity. availableQuantity() is base units while $qty is
                    // sales units; converting availability to sales units first is essential.
                    // The old code compared base grams to sales scoops, so min($qty,$available)
                    // was a no-op for any sales-UOM product and let oversell through checkout.
                    $availableSales = ($product && $product->hasSalesUomConversion())
                        ? floor($product->convertBaseToSalesQuantity($available))
                        : $available;
                    $actualQty = max(0.0, min($qty, $availableSales));

                    if ($actualQty < $qty) {
                        $lowStockWarnings[] = $availableSales <= 0
                            ? $line['name'].' (out of stock — not sold)'
                            : $line['name'].' (sold '.$actualQty.' of '.$qty.' requested)';
                    }

                    if ($actualQty > 0) {
                        // Calculate base quantity for stock deduction
                        $baseQty = $actualQty;
                        $salesQty = $actualQty;
                        $salesUomId = null;
                        $conversionFactor = null;

                        if ($product && $product->hasSalesUomConversion()) {
                            $baseQty = $product->convertSalesToBaseQuantity($actualQty);
                            $salesUomId = $product->sales_uom_id;
                            $conversionFactor = $product->sales_unit_weight ?? $product->convertSalesToBaseQuantity(1);
                        }

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'department_id' => $this->departmentId,
                            'product_id' => $productId,
                            'quantity' => $baseQty, // Base quantity in base UOM (e.g., grams)
                            'sales_quantity' => $salesQty, // Sales quantity (e.g., scoops)
                            'sales_uom_id' => $salesUomId,
                            'conversion_factor' => $conversionFactor,
                            'unit_price' => $line['price'],
                            'subtotal' => $salesQty * $line['price'],
                            'discount' => 0,
                            'total' => $salesQty * $line['price'],
                            'notes' => $actualQty < $qty ? 'Partial fulfillment: '.$actualQty.'/'.$qty : null,
                        ]);
                        // Product stock (quantity_sold / amount) and reservation release are
                        // handled once by the SaleItem::saved() observer for completed sales.
                        // The locked $stock above is only read to cap quantities to availability.
                    }
                }

                // Create payment records for each payment method. Amounts reflect cash
                // actually retained (change already trimmed when handed back).
                foreach ($paymentRows as $paymentData) {
                    $amount = (float) ($paymentData['amount'] ?? 0);
                    if ($amount > 0) {
                        Payment::create([
                            'sale_id' => $sale->id,
                            'branch_id' => $this->branchId,
                            'payment_method' => $paymentData['method'] ?? 'cash',
                            'bank_account_id' => $paymentData['bank_account_id'] ?? null,
                            'payer_bank' => $paymentData['payer_bank'] ?? null,
                            'amount' => $amount,
                            'payment_time' => Carbon::now(),
                            'status' => 'completed',
                            'reference_number' => null,
                            'notes' => null,
                        ]);
                    }
                }

                // Create receipt stored per sale
                $receiptHtml = $this->buildReceiptHtml($sale);
                $receipt = Receipt::create([
                    'sale_id' => $sale->id,
                    'content' => $receiptHtml,
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'payments' => $receiptPayments,
                    'change_due' => 0,
                    'meta' => [
                        'order_type' => $this->orderType,
                        'warnings' => $lowStockWarnings,
                        'amount_tendered' => $amountTendered,
                        'overage_kept' => $overShortAmount,
                    ],
                ]);

                $this->currentSaleId = $sale->id;
                $this->dispatch('pos-receipt-ready', receipt_id: $receipt->id);

                if (! empty($lowStockWarnings)) {
                    $this->toast()->warning('Sale completed with stock adjustments: '.implode(', ', $lowStockWarnings))->send();
                } else {
                    $this->toast()->success('Sale completed successfully!')->send();
                }
            });

            // If this sale was converted from a quotation, close the quotation out
            // and link it to the new sale.
            if ($this->convertingQuotationId && $this->currentSaleId) {
                $quotation = Quotation::find($this->convertingQuotationId);
                $sale = Sale::find($this->currentSaleId);
                if ($quotation && $sale && $quotation->status !== 'converted') {
                    $quotation->markConverted($sale);
                }
                $this->convertingQuotationId = null;
            }

            $this->clearCart();
            $this->discount = 0;
            $this->orderType = 'dine-in';
            $this->overageDisposition = 'change';
            $this->payments = [['method' => 'cash', 'amount' => 0.0, 'bank_account_id' => null, 'payer_bank' => null]];
            $this->recalcPayments();
        } catch (\Exception $e) {
            \Log::error('POS Sale Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'cart' => $this->cart,
            ]);
            $this->toast()->error('Payment failed: '.$e->getMessage())->send();
        }
    }

    #[Computed]
    public function bankAccounts()
    {
        return BankAccount::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();
    }

    /**
     * Open the "Save as Quotation" modal. A quotation is a non-binding priced
     * offer — it never touches stock or the GL, so (unlike completing a sale)
     * it does not require a verified shift, only a non-empty cart.
     */
    public function openQuotationModal(): void
    {
        if (empty($this->cart)) {
            $this->toast()->error('Add items to the cart before saving a quotation.')->send();

            return;
        }

        $this->quotationCustomerName = '';
        $this->quotationCustomerPhone = '';
        $this->quotationValidUntil = Carbon::now()->addDays(7)->toDateString();
        $this->quotationNotes = '';
        $this->showQuotationModal = true;
    }

    public function closeQuotationModal(): void
    {
        $this->showQuotationModal = false;
    }

    /** Persist the current cart as a quotation. No stock or GL side effects. */
    public function saveAsQuotation(): void
    {
        if (empty($this->cart)) {
            $this->toast()->error('Cart is empty.')->send();

            return;
        }

        $this->validate([
            'quotationCustomerName' => 'nullable|string|max:255',
            'quotationCustomerPhone' => 'nullable|string|max:50',
            'quotationValidUntil' => 'nullable|date',
            'quotationNotes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () {
            $quotation = Quotation::create([
                'quotation_number' => $this->uniqueQuotationNumber(),
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'created_by_id' => auth()->id(),
                'created_by_type' => User::class,
                'customer_name' => $this->quotationCustomerName ?: null,
                'customer_phone' => $this->quotationCustomerPhone ?: null,
                'status' => 'draft',
                'subtotal' => $this->subtotal,
                'discount' => (float) ($this->discount ?: 0),
                'total' => $this->total,
                'valid_until' => $this->quotationValidUntil ?: null,
                'notes' => $this->quotationNotes ?: null,
            ]);

            $sort = 0;
            foreach ($this->cart as $line) {
                $qty = (float) ($line['qty'] ?? 0);
                $price = (float) ($line['price'] ?? 0);
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => isset($line['item_id']) ? null : ($line['product_id'] ?? null),
                    'item_id' => $line['item_id'] ?? null,
                    'name' => $line['name'] ?? 'Item',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                    'sort_order' => $sort++,
                ]);
            }
        });

        $this->showQuotationModal = false;
        $this->convertingQuotationId = null;
        $this->toast()->success('Quotation saved.')->send();
        $this->clearCart();
    }

    /** Generate a quotation number, retrying on the rare random collision. */
    protected function uniqueQuotationNumber(): string
    {
        do {
            $number = Quotation::generateNumber();
        } while (Quotation::where('quotation_number', $number)->exists());

        return $number;
    }

    /**
     * Load a quotation's lines into the POS cart so the cashier can take payment
     * through the normal completeSale() flow. Remembers the quotation id so the
     * sale, once completed, marks the quotation as converted.
     */
    public function loadQuotationToCart(int $quotationId): void
    {
        $quotation = Quotation::with(['items.product', 'items.item'])->find($quotationId);
        if (! $quotation) {
            $this->toast()->warning('Quotation not found.')->send();

            return;
        }
        if ($quotation->status === 'converted') {
            $this->toast()->warning('This quotation has already been converted to a sale.')->send();

            return;
        }

        $this->cart = [];
        foreach ($quotation->items as $line) {
            if ($line->item_id) {
                $key = 'item_'.$line->item_id;
                $available = (float) (Stock::where('item_id', $line->item_id)
                    ->where('branch_id', $this->branchId)
                    ->value('quantity_available') ?? 0);
                $this->cart[$key] = [
                    'item_id' => $line->item_id,
                    'name' => $line->item->name ?? $line->name,
                    'price' => (float) $line->unit_price,
                    'qty' => (float) $line->quantity,
                    'uom' => $line->item->unitOfMeasure?->symbol ?? '',
                    'available' => $available,
                    'type' => 'inventory_item',
                ];
            } elseif ($line->product_id) {
                $this->cart[(string) $line->product_id] = [
                    'product_id' => $line->product_id,
                    'name' => $line->product->name ?? $line->name,
                    'price' => (float) $line->unit_price,
                    'qty' => (float) $line->quantity,
                    'low_stock' => false,
                    'available' => $this->availableQuantity($this->getTodayStockForProduct($line->product_id)),
                ];
            }
        }

        $this->discount = (float) $quotation->discount;
        $this->convertingQuotationId = $quotation->id;
        $this->recalculateTotals();
        $this->toast()->info("Quotation {$quotation->quotation_number} loaded. Take payment to convert it to a sale.")->send();
    }

    public function getProductsProperty(): CursorPaginator
    {
        return $this->getProductsForView();
    }

    /**
     * @return array<int>
     */
    protected function scopedSalesDepartmentIds(): array
    {
        if (! $this->departmentId) {
            return [];
        }

        $departmentIds = $this->resolveEquivalentSalesDepartmentIds();
        if (empty($departmentIds)) {
            $departmentIds = [(int) $this->departmentId];
        }

        return array_values(array_unique(array_map(static fn ($id) => (int) $id, $departmentIds)));
    }

    protected function getTodayStockForProduct(string $productId, bool $forUpdate = false): ?ProductStock
    {
        $hasDepartmentColumn = Schema::hasColumn('product_stocks', 'department_id');
        if ($hasDepartmentColumn && ! $this->departmentId) {
            return null;
        }

        $departmentIds = [];
        if ($hasDepartmentColumn) {
            $departmentIds = $this->resolveEquivalentSalesDepartmentIds();
            if (empty($departmentIds)) {
                $departmentIds = [(int) $this->departmentId];
            }
        }

        $q = ProductStock::query()
            ->whereDate('stock_date', Carbon::today())
            ->where('shift_type', $this->getProductStockShiftType())
            ->where('product_id', $productId);

        if ($hasDepartmentColumn) {
            $q->whereIn('department_id', $departmentIds)
                ->orderByRaw('department_id = ? DESC', [(int) $this->departmentId])
                ->orderByDesc('id');
        }

        if ($forUpdate) {
            $q->lockForUpdate();
        }

        return $q->first();
    }

    protected function toBaseQty(Product $product, float $salesQty): float
    {
        return $product->hasSalesUomConversion()
            ? $product->convertSalesToBaseQuantity($salesQty)
            : $salesQty;
    }

    protected function availableQuantity(?ProductStock $stock): float
    {
        if (! $stock) {
            return 0.0;
        }
        // available is closing quantity; if not up to date, compute
        $stock->updateCalculatedFields();

        return max(0, (float) $stock->closing_quantity);
    }

    /**
     * Returns inventory items dispatched to this sales dept that have a sell_price.
     * Available qty = total dispatched - total sold today by this dept.
     */
    public function getDispatchedItemsForSale(): array
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

        // Group by item_id and sum dispatched quantities
        $byItem = $dispatched->groupBy('item_id');

        // Sum quantities already sold from inventory today for this dept
        $itemIds = $byItem->keys()->all();
        $soldToday = SaleItem::whereNotNull('item_id')
            ->whereIn('item_id', $itemIds)
            ->whereHas('sale', fn ($q) => $q
                ->where('department_id', $this->departmentId)
                ->whereDate('sale_time', Carbon::today())
                ->where('status', 'completed')
            )
            ->selectRaw('item_id, SUM(quantity) as total_sold')
            ->groupBy('item_id')
            ->pluck('total_sold', 'item_id');

        $result = [];
        foreach ($byItem as $itemId => $rows) {
            $item = $rows->first()->item;
            if (! $item || ! $item->isSellable()) {
                continue;
            }

            $totalDispatched = (float) $rows->sum('quantity');
            $totalSold = (float) ($soldToday[$itemId] ?? 0);
            $available = max(0, $totalDispatched - $totalSold);

            $cartKey = 'item_'.$itemId;
            $inCart = (float) ($this->cart[$cartKey]['qty'] ?? 0);

            $result[] = [
                'item_id' => $itemId,
                'name' => $item->name,
                'sku' => $item->sku,
                'price' => (float) $item->sell_price,
                'uom' => $item->unitOfMeasure?->symbol ?? 'unit',
                'available' => $available,
                'in_cart' => $inCart,
            ];
        }

        return $result;
    }

    public function addItemToCart(int $itemId): void
    {
        $item = Item::whereNotNull('sell_price')->where('status', 'active')->find($itemId);

        if (! $item) {
            $this->toast()->error('Item not available for sale.')->send();

            return;
        }

        $cartKey = 'item_'.$itemId;
        $currentQty = (float) ($this->cart[$cartKey]['qty'] ?? 0);
        $newQty = $currentQty + 1;

        // Compute available from dispatches
        $dispatchedItems = $this->getDispatchedItemsForSale();
        $itemData = collect($dispatchedItems)->firstWhere('item_id', $itemId);

        if (! $itemData || $itemData['available'] <= 0) {
            $this->toast()->error('No dispatched stock available for '.$item->name.'.')->send();

            return;
        }

        if ($newQty > $itemData['available']) {
            $this->toast()->warning('Cannot exceed dispatched stock ('.$itemData['available'].' '.$item->unitOfMeasure?->symbol.') for '.$item->name)->send();

            return;
        }

        $this->cart[$cartKey] = [
            'item_id' => $itemId,
            'name' => $item->name,
            'price' => (float) $item->sell_price,
            'qty' => $newQty,
            'uom' => $item->unitOfMeasure?->symbol ?? 'unit',
            'available' => $itemData['available'],
            'type' => 'inventory_item',
        ];

        $this->recalculateTotals();
    }

    public function getAvailableForProduct(string $productId): float
    {
        if (is_array($this->productAvailabilityForView) && array_key_exists($productId, $this->productAvailabilityForView)) {
            return (float) ($this->productAvailabilityForView[$productId] ?? 0);
        }

        return $this->availableQuantity($this->getTodayStockForProduct($productId));
    }

    protected function getProductsForView(): CursorPaginator
    {
        if ($this->productsForView !== null) {
            return $this->productsForView;
        }

        if (! $this->departmentId) {
            return $this->productsForView = new CursorPaginator(
                collect(),
                $this->productsPerPage,
                null,
                ['path' => request()->url(), 'pageName' => 'products_cursor']
            );
        }

        $departmentIds = $this->scopedSalesDepartmentIds();
        if (empty($departmentIds)) {
            return $this->productsForView = new CursorPaginator(
                collect(),
                $this->productsPerPage,
                null,
                ['path' => request()->url(), 'pageName' => 'products_cursor']
            );
        }

        $wipTypeIds = \DB::table('product_types')->where('code', 'WIP')->pluck('id');

        $q = Product::query()
            ->active()
            ->available()
            ->whereNotIn('product_type_id', $wipTypeIds)
            ->where(function ($q) use ($departmentIds) {
                $q->whereIn('sales_department_id', $departmentIds)
                    ->orWhereHas('departments', function ($dq) use ($departmentIds) {
                        $dq->whereIn('department_id', $departmentIds);
                    });
            })
            ->select(['products.id', 'products.name', 'products.price', 'products.sku', 'products.uom_id', 'products.sales_uom_id'])
            ->with(['unitOfMeasure:id,symbol', 'salesUom:id,symbol'])
            ->orderBy('name');

        $hasDepartmentColumn = Schema::hasColumn('product_stocks', 'department_id');
        $departmentIdsForStock = [];
        if ($hasDepartmentColumn) {
            $departmentIdsForStock = $this->resolveEquivalentSalesDepartmentIds();
            if (empty($departmentIdsForStock)) {
                $departmentIdsForStock = [(int) $this->departmentId];
            }
        }

        $hasStatusColumn = Schema::hasColumn('product_stocks', 'status');
        $hasBranchColumn = Schema::hasColumn('product_stocks', 'branch_id');
        $hasReservedColumn = Schema::hasColumn('product_stocks', 'quantity_reserved');

        $latestStockIds = ProductStock::query()
            ->selectRaw('MAX(id) as id, product_id')
            ->whereDate('stock_date', Carbon::today())
            ->where('shift_type', $this->getProductStockShiftType())
            ->when($hasDepartmentColumn && ! empty($departmentIdsForStock), function ($query) use ($departmentIdsForStock) {
                $query->whereIn('department_id', $departmentIdsForStock);
            })
            ->when($hasBranchColumn && $this->branchId, function ($query) {
                $query->where('branch_id', $this->branchId);
            })
            ->when($hasStatusColumn, function ($query) {
                $query->where('status', '!=', 'opening_pending');
            })
            ->groupBy('product_id');

        $q->joinSub($latestStockIds, 'latest_stock', function ($join) {
            $join->on('products.id', '=', 'latest_stock.product_id');
        })
            ->join('product_stocks as ps', 'ps.id', '=', 'latest_stock.id')
            ->when($hasBranchColumn && $this->branchId, function ($query) {
                $query->where('ps.branch_id', $this->branchId);
            })
            ->where(function ($query) use ($hasReservedColumn) {
                $closingExpr = $hasReservedColumn
                    ? 'COALESCE(ps.closing_quantity, (ps.opening_quantity + ps.addition_quantity - ps.callback_quantity - ps.redress_quantity - ps.transfer_quantity - ps.glovo_quantity - ps.quantity_sold - ps.quantity_reserved))'
                    : 'COALESCE(ps.closing_quantity, (ps.opening_quantity + ps.addition_quantity - ps.callback_quantity - ps.redress_quantity - ps.transfer_quantity - ps.glovo_quantity - ps.quantity_sold))';

                if ($this->stockFilter === 'in_stock') {
                    $query->whereRaw("$closingExpr > 0");
                } elseif ($this->stockFilter === 'out_of_stock') {
                    $query->whereRaw("$closingExpr <= 0");
                } else {
                    // 'all': show everything with a stock record today (including zero stock)
                    $query->whereRaw("$closingExpr > 0");
                    if (Schema::hasColumn('product_stocks', 'stock_date')) {
                        $query->orWhereDate('ps.stock_date', Carbon::today());
                    }
                }
            });

        if (strlen($this->search)) {
            $q->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            });
        }

        return $this->productsForView = $q->cursorPaginate($this->productsPerPage, ['*'], 'products_cursor');
    }

    /**
     * @return array{sales_qty:float,sales_symbol:string,base_qty:float,base_symbol:string,converted:bool}
     */
    public function getPosStockDisplay(Product $product): array
    {
        $baseQty = (float) $this->getAvailableForProduct((string) $product->id);
        $baseSymbol = $product->unitOfMeasure?->symbol ?? ($product->base_uom ?? 'unit');
        $salesSymbol = $product->salesUom?->symbol ?? $baseSymbol;

        $converted = null;
        if ($product->sales_uom_id && $product->uom_id) {
            /** @var UomConversionService $converter */
            $converter = app(UomConversionService::class);
            $converted = $converter->tryConvert(
                $baseQty,
                (int) $product->uom_id,
                (int) $product->sales_uom_id,
                [
                    'branch_id' => $this->branchId,
                    'product_id' => (string) $product->id,
                ]
            );
        }

        $salesQty = $converted ?? $baseQty;

        return [
            'sales_qty' => $salesQty,
            'sales_symbol' => (string) $salesSymbol,
            'base_qty' => $baseQty,
            'base_symbol' => (string) $baseSymbol,
            'converted' => $converted !== null,
        ];
    }

    /**
     * @return array<string, float>
     */
    protected function getProductAvailabilityForView(CursorPaginator $products): array
    {
        if ($this->productAvailabilityForView !== null) {
            return $this->productAvailabilityForView;
        }

        $productIds = $products->getCollection()->pluck('id')
            ->filter()
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();

        if (empty($productIds)) {
            return $this->productAvailabilityForView = [];
        }

        $hasDepartmentColumn = Schema::hasColumn('product_stocks', 'department_id');
        $departmentIds = [];
        if ($hasDepartmentColumn) {
            $departmentIds = $this->resolveEquivalentSalesDepartmentIds();
            if (empty($departmentIds)) {
                $departmentIds = [(int) $this->departmentId];
            }
        }

        $stockQuery = ProductStock::query()
            ->whereDate('stock_date', Carbon::today())
            ->whereIn('product_id', $productIds)
            ->select([
                'id',
                'product_id',
                'department_id',
                'opening_quantity',
                'addition_quantity',
                'callback_quantity',
                'redress_quantity',
                'transfer_quantity',
                'glovo_quantity',
                'quantity_sold',
                'closing_quantity',
            ]);

        if ($hasDepartmentColumn) {
            $stockQuery->whereIn('department_id', $departmentIds)
                ->orderByRaw('department_id = ? DESC', [(int) $this->departmentId])
                ->orderByDesc('id');
        } else {
            $stockQuery->orderByDesc('id');
        }

        $availability = [];
        foreach ($stockQuery->get() as $stock) {
            $productId = (string) $stock->product_id;
            if (isset($availability[$productId])) {
                continue;
            }

            $available = $stock->closing_quantity !== null
                ? (float) $stock->closing_quantity
                : (float) (
                    $stock->opening_quantity
                    + $stock->addition_quantity
                    - $stock->callback_quantity
                    - $stock->redress_quantity
                    - $stock->transfer_quantity
                    - $stock->glovo_quantity
                    - $stock->quantity_sold
                );

            $availability[$productId] = max(0.0, $available);
        }

        return $this->productAvailabilityForView = $availability;
    }

    protected function getProductsPayloadForView(): array
    {
        if ($this->productsPayloadForView !== null) {
            return $this->productsPayloadForView;
        }

        $products = $this->getProductsForView();
        $availability = $this->getProductAvailabilityForView($products);

        $productIds = $products->getCollection()->pluck('id')
            ->filter()
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();

        $conversionMap = [];
        if (! empty($productIds)) {
            $conversionMap = Product::query()
                ->whereIn('id', $productIds)
                ->select([
                    'id',
                    'uom_id',
                    'sales_uom_id',
                    'sales_unit_weight',
                ])
                ->with(['unitOfMeasure:id,symbol', 'salesUom:id,symbol'])
                ->get()
                ->mapWithKeys(static function (Product $product): array {
                    $hasConversion = $product->hasSalesUomConversion();
                    $baseSymbol = $product->unitOfMeasure?->symbol ?? 'unit';
                    $salesSymbol = $product->salesUom?->symbol ?? $baseSymbol;
                    $conversionFactor = null;
                    if ($hasConversion) {
                        $conversionFactor = $product->sales_unit_weight ?: $product->convertSalesToBaseQuantity(1);
                    }

                    return [
                        (string) $product->id => [
                            'has_conversion' => $hasConversion,
                            'base_uom' => $baseSymbol,
                            'sales_uom' => $salesSymbol,
                            'conversion_factor' => $conversionFactor,
                        ],
                    ];
                })
                ->all();
        }

        $payload = [];
        foreach ($products as $product) {
            $productId = (string) $product->id;
            $conversion = $conversionMap[$productId] ?? [
                'has_conversion' => false,
                'base_uom' => 'unit',
                'sales_uom' => 'unit',
                'conversion_factor' => null,
            ];

            $payload[] = [
                'id' => $productId,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) ($product->price ?? 0),
                'available' => (float) ($availability[$productId] ?? 0),
                'has_conversion' => $conversion['has_conversion'],
                'base_uom' => $conversion['base_uom'],
                'sales_uom' => $conversion['sales_uom'],
                'conversion_factor' => $conversion['conversion_factor'],
            ];
        }

        return $this->productsPayloadForView = $payload;
    }

    protected function buildReceiptHtml(Sale $sale): string
    {
        $documentService = new PosDocumentService;

        return $documentService->generateBrandedReceiptHtml($sale);
    }

    // Table Management Methods
    public function selectTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if (! $table) {
            $this->toast()->error('Table not found.')->send();

            return;
        }

        // Save current cart if there's anything in it and a table is selected
        if (! empty($this->cart) && $this->selectedTableId && $this->selectedTableId !== $tableId) {
            $this->saveTableTab();
        }

        $this->selectedTableId = $tableId;

        // Load the table's active sale if exists
        $activeSale = $table->getActiveSale();
        if ($activeSale) {
            $this->loadTableTab($activeSale);
        } else {
            $this->clearCart();
        }
    }

    protected function loadTableTab(Sale $sale): void
    {
        $sale->loadMissing(['saleItems.product', 'saleItems.item']);
        $this->cart = [];
        foreach ($sale->saleItems as $saleItem) {
            if ($saleItem->item_id) {
                $key = 'item_'.$saleItem->item_id;
                $available = (float) (Stock::where('item_id', $saleItem->item_id)
                    ->where('branch_id', $this->branchId)
                    ->value('quantity_available') ?? 0);
                $this->cart[$key] = [
                    'item_id' => $saleItem->item_id,
                    'name' => $saleItem->item->name ?? 'Item',
                    'price' => (float) $saleItem->unit_price,
                    'qty' => (float) $saleItem->quantity,
                    'uom' => $saleItem->item->unitOfMeasure?->symbol ?? '',
                    'available' => $available,
                    'type' => 'inventory_item',
                ];
            } else {
                $this->cart[(string) $saleItem->product_id] = [
                    'product_id' => $saleItem->product_id,
                    'name' => $saleItem->product->name ?? 'Product',
                    'price' => (float) $saleItem->unit_price,
                    'qty' => (float) $saleItem->quantity,
                    'low_stock' => false,
                    'available' => $this->availableQuantity($this->getTodayStockForProduct($saleItem->product_id)),
                ];
            }
        }
        $this->discount = (float) $sale->discount;
        $this->orderType = $sale->order_type ?? 'dine-in';
        $this->currentSaleId = $sale->id;
        $this->recalculateTotals();
    }

    public function saveTableTab(): void
    {
        if (! $this->selectedTableId) {
            $this->toast()->warning('No table selected.')->send();

            return;
        }

        if (empty($this->cart)) {
            $this->toast()->warning('Cart is empty.')->send();

            return;
        }

        if (! $this->hasActiveShift()) {
            $this->toast()->error('No active shift.')->send();

            return;
        }

        $table = Table::find($this->selectedTableId);
        if (! $table) {
            $this->toast()->error('Table not found.')->send();

            return;
        }

        DB::transaction(function () use ($table) {
            // Check if there's an existing hold sale for this table
            $existingSale = $table->getActiveSale();
            $existingItems = collect();

            if ($existingSale) {
                // Update existing sale
                $existingSale->update([
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => (float) ($this->discount ?: 0),
                    'total' => $this->total,
                    'order_type' => $this->orderType,
                ]);

                $existingItems = $existingSale->saleItems()->with(['product', 'item'])->get();

                // Delete existing items
                $existingSale->saleItems()->delete();

                $sale = $existingSale;
            } else {
                // Create new sale
                $sale = Sale::create([
                    'sales_shift_id' => null, // Nullable - using general shifts table instead
                    'shift_id' => $this->activeShiftId,
                    'branch_id' => $this->branchId,
                    'department_id' => $this->departmentId,
                    'table_id' => $table->id,
                    'sold_by_id' => auth()->id(),
                    'sold_by_type' => User::class,
                    'sale_number' => 'TAB-'.$table->table_number.'-'.Carbon::now()->format('Ymd-His'),
                    'sale_time' => Carbon::now(),
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => (float) ($this->discount ?: 0),
                    'total' => $this->total,
                    'status' => 'hold',
                    'order_type' => $this->orderType,
                    'notes' => 'Table: '.$table->table_number,
                ]);
            }

            // Reserve stock delta (base UOM) — only for production products, not inventory items
            $productIds = array_values(array_unique(array_filter(array_map(
                static fn ($line) => isset($line['item_id']) ? null : ($line['product_id'] ?? null),
                $this->cart
            ))));
            $productsById = Product::query()
                ->with(['unitOfMeasure', 'salesUom'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $existingReserved = [];
            foreach ($existingItems as $item) {
                $product = $item->product;
                if ($product && $item->product_id) {
                    $existingReserved[(string) $item->product_id] = ($existingReserved[(string) $item->product_id] ?? 0)
                        + $this->toBaseQty($product, (float) $item->quantity);
                }
            }

            $newReserved = [];
            foreach ($this->cart as $line) {
                if (isset($line['item_id'])) {
                    continue;
                } // inventory items don't use product_stocks
                $productId = (string) $line['product_id'];
                $product = $productsById->get($productId);
                if (! $product) {
                    continue;
                }
                $newReserved[$productId] = ($newReserved[$productId] ?? 0)
                    + $this->toBaseQty($product, (float) $line['qty']);
            }

            $allProductIds = array_values(array_unique(array_merge(array_keys($existingReserved), array_keys($newReserved))));
            foreach ($allProductIds as $productId) {
                $delta = ($newReserved[$productId] ?? 0) - ($existingReserved[$productId] ?? 0);
                if (abs($delta) < 0.0001) {
                    continue;
                }

                $stock = $this->getTodayStockForProduct($productId, true);
                if (! $stock) {
                    throw new \RuntimeException('Stock record not found for product '.$productId);
                }
                $stock->updateCalculatedFields();
                $available = $this->availableQuantity($stock);
                if ($delta > 0 && $available < $delta) {
                    throw new \RuntimeException('Insufficient stock to reserve for table.');
                }

                if (Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                    $stock->quantity_reserved = max(0, (float) ($stock->quantity_reserved ?? 0) + $delta);
                }
                $stock->updateCalculatedFields();
                $stock->save();
            }

            // Add items to sale
            foreach ($this->cart as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'department_id' => $this->departmentId,
                    'product_id' => isset($line['item_id']) ? null : ($line['product_id'] ?? null),
                    'item_id' => $line['item_id'] ?? null,
                    'quantity' => (float) $line['qty'],
                    'unit_price' => $line['price'],
                    'subtotal' => $line['qty'] * $line['price'],
                    'discount' => 0,
                    'total' => $line['qty'] * $line['price'],
                ]);
            }

            $table->markAsOccupied();
            $this->currentSaleId = $sale->id;
        });

        $this->toast()->success('Tab saved for Table '.$table->table_number)->send();
    }

    public function completeTableSale(): void
    {
        if (! $this->selectedTableId) {
            $this->toast()->warning('No table selected.')->send();

            return;
        }

        $table = Table::find($this->selectedTableId);
        if (! $table) {
            $this->toast()->error('Table not found.')->send();

            return;
        }

        $activeSale = $table->getActiveSale();
        if ($activeSale) {
            DB::transaction(function () use ($activeSale) {
                $items = $activeSale->saleItems()->with('product')->get();
                foreach ($items as $item) {
                    $product = $item->product;
                    if (! $product) {
                        continue;
                    }
                    $baseQty = $this->toBaseQty($product, (float) $item->quantity);
                    $stock = $this->getTodayStockForProduct((string) $item->product_id, true);
                    if ($stock && Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                        $stock->quantity_reserved = max(0, (float) ($stock->quantity_reserved ?? 0) - $baseQty);
                        $stock->updateCalculatedFields();
                        $stock->save();
                    }
                }
            });
        }

        // Use the existing completeSale method but update table_id
        $this->completeSale();

        // If the sale was completed successfully, delete the old hold sale
        if ($activeSale && $this->currentSaleId && $this->currentSaleId !== $activeSale->id) {
            DB::transaction(function () use ($activeSale, $table) {
                $activeSale->saleItems()->delete();
                $activeSale->delete();
                
                // Mark table as available after payment
                $table->markAsAvailable();
            });
        } else {
            // Fallback: just mark table as available
            $table->markAsAvailable();
        }

        $this->selectedTableId = null;
    }

    public function clearTable(): void
    {
        if (! $this->selectedTableId) {
            return;
        }

        $table = Table::find($this->selectedTableId);
        if ($table) {
            // Delete any hold sales for this table
            $activeSale = $table->getActiveSale();
            if ($activeSale) {
                $items = $activeSale->saleItems()->with('product')->get();
                foreach ($items as $item) {
                    $product = $item->product;
                    if (! $product) {
                        continue;
                    }
                    $baseQty = $this->toBaseQty($product, (float) $item->quantity);
                    $stock = $this->getTodayStockForProduct((string) $item->product_id, true);
                    if ($stock && Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                        $stock->quantity_reserved = max(0, (float) ($stock->quantity_reserved ?? 0) - $baseQty);
                        $stock->updateCalculatedFields();
                        $stock->save();
                    }
                }
                $activeSale->saleItems()->delete();
                $activeSale->delete();
            }
            $table->markAsAvailable();
        }

        $this->selectedTableId = null;
        $this->clearCart();
        $this->toast()->success('Table cleared.')->send();
    }

    #[Computed]
    public function tables()
    {
        if (! $this->departmentId || ! $this->showTableManagement) {
            return collect([]);
        }

        return Table::where('branch_id', $this->branchId)
            ->where('department_id', $this->departmentId)
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get();
    }

    public function createTable(): void
    {
        $this->validate([
            'newTableNumber' => 'required|string|max:10',
            'newTableName' => 'nullable|string|max:50',
            'newTableCapacity' => 'required|integer|min:1|max:20',
        ]);

        if (! $this->branchId || ! $this->departmentId) {
            $this->toast()->error('Branch or department not found.')->send();

            return;
        }

        // Check if table number already exists in this department
        $exists = Table::where('branch_id', $this->branchId)
            ->where('department_id', $this->departmentId)
            ->where('table_number', $this->newTableNumber)
            ->exists();

        if ($exists) {
            $this->toast()->error('Table number already exists in this department.')->send();

            return;
        }

        Table::create([
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'table_number' => $this->newTableNumber,
            'table_name' => $this->newTableName ?: 'Table '.$this->newTableNumber,
            'capacity' => $this->newTableCapacity,
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->newTableNumber = '';
        $this->newTableName = '';
        $this->newTableCapacity = 4;
        $this->showTableModal = false;

        unset($this->tables);
        $this->toast()->success('Table created successfully.')->send();
    }

    public function deleteTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if (! $table) {
            $this->toast()->error('Table not found.')->send();

            return;
        }

        if ($table->hasActiveSale()) {
            $this->toast()->error('Cannot delete table with active orders.')->send();

            return;
        }

        $table->delete();
        unset($this->tables);
        $this->toast()->success('Table deleted successfully.')->send();
    }

    public function toggleTableStatus(int $tableId): void
    {
        $table = Table::find($tableId);
        if (! $table) {
            $this->toast()->error('Table not found.')->send();

            return;
        }

        $table->update(['is_active' => ! $table->is_active]);
        unset($this->tables);
        $this->toast()->success('Table status updated.')->send();
    }

    /**
     * Send receipt notification via email to customer
     */
    public function sendReceiptEmail(?string $customerEmail = null): void
    {
        if (! $this->currentSaleId) {
            $this->toast()->warning('No sale found.')->send();

            return;
        }

        if (! $customerEmail) {
            $this->toast()->warning('Customer email is required.')->send();

            return;
        }

        try {
            $sale = Sale::find($this->currentSaleId);
            $receipt = $sale->receipts()->latest()->first();

            if (! $receipt) {
                $this->toast()->error('No receipt found for this sale.')->send();

                return;
            }

            // Send notification
            $notification = new SalesReceiptNotification($receipt);
            Notification::route('mail', $customerEmail)
                ->notify($notification);

            $this->toast()->success('Receipt sent to '.$customerEmail)->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send receipt email: '.$e->getMessage());
            $this->toast()->error('Failed to send receipt: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.pos.index', [
            'products' => $this->getProductsPayloadForView(),
            'currency' => Settings::currencyLocalization('primary_currency', 'NGN'),
            'currencyLocale' => Settings::currencyLocalization('default_language', 'en_US'),
            'defaultBankAccount' => $this->getDefaultDepartmentBankAccountId(),
            'defaultBankAccountLabel' => $this->getDefaultDepartmentBankAccountLabel(),
        ]);
    }

    public function getPendingDispatchesProperty(): int
    {
        if (! $this->branchId || ! $this->departmentId) {
            return 0;
        }

        $departmentIds = $this->resolveEquivalentSalesDepartmentIds();
        if (empty($departmentIds)) {
            $departmentIds = [(int) $this->departmentId];
        }

        return ProductDispatch::query()
            ->where('branch_id', $this->branchId)
            ->whereIn('sales_department_id', $departmentIds)
            ->whereIn('status', ['pending_verification', 'accepted'])
            ->count();
    }

    /**
     * Resolve equivalent sales department IDs across branch/global scopes.
     *
     * @return array<int>
     */
    protected function resolveEquivalentSalesDepartmentIds(): array
    {
        if (! $this->departmentId) {
            return [];
        }

        $department = Department::find($this->departmentId);
        if (! $department) {
            return [(int) $this->departmentId];
        }

        $branchId = $this->branchId;
        $targetNameKey = $this->normalizeDepartmentKey($department->name);
        $targetSlugKey = $this->normalizeDepartmentKey((string) $department->slug);

        $ids = Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get(['id', 'name', 'slug'])
            ->filter(function (Department $candidate) use ($department, $targetNameKey, $targetSlugKey): bool {
                if ((int) $candidate->id === (int) $department->id) {
                    return true;
                }

                $candidateNameKey = $this->normalizeDepartmentKey($candidate->name);
                $candidateSlugKey = $this->normalizeDepartmentKey((string) $candidate->slug);

                return $this->areEquivalentDepartmentIdentities(
                    $targetNameKey,
                    $targetSlugKey,
                    $candidateNameKey,
                    $candidateSlugKey,
                );
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $department->id, $ids, true)) {
            $ids[] = (int) $department->id;
        }

        // Include departments combined into the same sales point (config/sales.php),
        // so this POS lists and reads stock for every member department's products.
        $groupIds = \App\Support\CombinedSalesPoints::departmentIds(
            $branchId,
            (string) $department->slug,
        );
        if (! empty($groupIds)) {
            $ids = array_values(array_unique(array_merge($ids, $groupIds)));
        }

        return $ids;
    }

    protected function normalizeDepartmentKey(string $value): string
    {
        $normalized = strtolower($value);
        $normalized = preg_replace('/[-_]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    /**
     * @return array<int, string>
     */
    protected function departmentIdentityTokens(string $value): array
    {
        $normalized = $this->normalizeDepartmentKey($value);
        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $normalized)));
        $stopWords = ['sales', 'sale', 'department', 'dept'];

        return array_values(array_filter($tokens, function (string $token) use ($stopWords): bool {
            return $token !== '' && ! in_array($token, $stopWords, true);
        }));
    }

    protected function areEquivalentDepartmentIdentities(
        string $targetNameKey,
        string $targetSlugKey,
        string $candidateNameKey,
        string $candidateSlugKey,
    ): bool {
        if (
            ($targetNameKey !== '' && $candidateNameKey === $targetNameKey)
            || ($targetSlugKey !== '' && $candidateSlugKey === $targetSlugKey)
            || ($targetNameKey !== '' && $candidateSlugKey === $targetNameKey)
            || ($targetSlugKey !== '' && $candidateNameKey === $targetSlugKey)
        ) {
            return true;
        }

        $targetTokens = $this->departmentIdentityTokens($targetNameKey.' '.$targetSlugKey);
        $candidateTokens = $this->departmentIdentityTokens($candidateNameKey.' '.$candidateSlugKey);
        if (empty($targetTokens) || empty($candidateTokens)) {
            return false;
        }

        $sharedTokens = array_intersect($targetTokens, $candidateTokens);
        $shorterTokenCount = min(count($targetTokens), count($candidateTokens));

        return count($sharedTokens) === $shorterTokenCount;
    }

    private function getProductStockShiftType(): string
    {
        return ProductStock::normalizeShiftType($this->shiftType);
    }
}
