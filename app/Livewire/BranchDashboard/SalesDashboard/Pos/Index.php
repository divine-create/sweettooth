<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Pos;

use App\Helpers\Settings;
use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Product;
use App\Models\ProductDispatch;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\SalesShift;
use App\Models\Shift;
use App\Models\Table;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Department;
use App\Services\CurrencyFormattingService;
use App\Services\PosDocumentService;
use App\Services\SalesStockVerificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\{Layout, Url, Computed, On};

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use SalesDepartmentContext;

    // Note: salesDeptSlug, branchId, departmentId, departmentName, branchName
    // are now provided by SalesDepartmentContext trait

    public string $search = '';
    public array $cart = [];
    public float $subtotal = 0.0;
    public float $discount = 0.0;
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
    protected ?Collection $productsForView = null;
    protected ?array $productAvailabilityForView = null;
    protected ?array $productsPayloadForView = null;
    
    public function __set(string $name, mixed $value): void
    {
        // Ensure float properties stay as floats
        if (in_array($name, ['subtotal', 'discount', 'tax', 'total', 'cashReceived', 'changeDue', 'paymentTotal', 'paymentRemaining'])) {
            $value = (float)$value;
        }
        parent::__set($name, $value);
    }

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
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    public function mount(): void
    {
        $this->mountBase();
        $this->initializeDepartmentContext(); // Using trait method
        $this->departmentName = $this->departmentName ?: 'POS';
        $this->loadActiveShift(); // Load shift first before checking stock verification

        // Super admins bypass stock verification check
        if (!is_super_admin() && !can_access_all_branches()) {
            // Check if stock has been verified for today's shift for this department
            if (!$this->checkStockVerification()) {
                // Ensure we have a department slug before redirecting
                if (!$this->salesDeptSlug) {
                    $this->toast()->error('Department not found. Please contact administrator.')->send();
                    return;
                }

                // Redirect to stock opening with department context
                $this->redirectToStockOpening();
                return;
            }
        }

        $this->payments = [['method' => 'cash', 'amount' => 0.0]];
        $this->recalculateTotals();
        $this->recalcPayments();
        $this->checkTableManagement();
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

    protected function checkStockVerification(): bool
    {
        // Super admins bypass verification
        if (is_super_admin() || can_access_all_branches()) {
            return true;
        }

        // Check if stock opening has been saved for today's shift for this department
        // This is department-level, not employee-level: once any employee from the department
        // verifies stock for the shift, all other employees can access POS

        if (!$this->departmentId) {
            // No department specified, allow access
            return true;
        }

        // Get current shift type
        $shiftType = 'morning'; // default
        if ($this->activeShiftId) {
            $shift = Shift::find($this->activeShiftId);
            $shiftType = $shift?->shift_type ?? 'morning';
        }

        // Use the verification service
        $verificationService = app(SalesStockVerificationService::class);
        return $verificationService->checkStockVerificationForShift(
            $this->departmentId,
            Carbon::today()->toDateString(),
            $shiftType,
            $this->branchId
        );
    }

    public function toggleTableManagement(): void
    {
        if (!$this->departmentId) {
            $this->toast()->error('Department not found.')->send();
            return;
        }

        $department = Department::find($this->departmentId);
        if (!$department) {
            $this->toast()->error('Department not found.')->send();
            return;
        }

        $department->enable_table_management = !$department->enable_table_management;
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

                if (!$tableExists) {
                    Table::create([
                        'branch_id' => $this->branchId,
                        'department_id' => $this->departmentId,
                        'table_number' => (string) $i,
                        'table_name' => 'Table ' . $i,
                        'status' => 'available',
                        'capacity' => 4,
                        'is_active' => true,
                    ]);
                }
            }
            $this->toast()->success('Table management enabled! Default tables created for ' . $department->name . '.')->send();
        } else {
            $message = $department->enable_table_management
                ? 'Table management enabled for ' . $department->name . '.'
                : 'Table management disabled for ' . $department->name . '.';
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

        $this->activeShiftId = $shift?->id;
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
        // no-op: search is used in the view via query
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

        $stock = $this->getTodayStockForProduct($productId);
        $available = $this->availableQuantity($stock);

        $lineKey = (string)$productId;
        $currentQty = $this->cart[$lineKey]['qty'] ?? 0;
        $newQty = $currentQty + 1;

        // Convert sales quantity to base quantity for stock checking
        $baseQuantity = $product->hasSalesUomConversion()
            ? $product->convertSalesToBaseQuantity($newQty)
            : $newQty;

        // Strict stock enforcement: cannot add if out of stock
        if ($available <= 0) {
            $this->toast()->error('Out of stock for ' . $product->name . '. Cannot add to cart.')->send();
            return;
        }

        // Strict stock enforcement: cannot exceed available quantity (in base UOM)
        if ($baseQuantity > $available) {
            $this->toast()->warning('Cannot add more than available stock (' . $available . ' ' . $product->uomSymbol . ') for ' . $product->name)->send();
            return;
        }

        $this->cart[$lineKey] = [
            'product_id' => $productId,
            'name' => $product->name,
            'price' => (float)($product->price ?? 0),
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
        if (!isset($this->cart[$lineKey])) return;
        $productId = (string)$this->cart[$lineKey]['product_id'];
        $stock = $this->getTodayStockForProduct($productId);
        $available = $this->availableQuantity($stock);
        $newQty = $this->cart[$lineKey]['qty'] + 1;

        // Strict stock enforcement: cannot exceed available quantity
        if ($newQty > $available) {
            $this->toast()->warning('Cannot exceed available stock (' . $available . ') for ' . $this->cart[$lineKey]['name'])->send();
            return;
        }

        $this->cart[$lineKey]['qty'] = $newQty;
        $this->cart[$lineKey]['available'] = $available;
        $this->recalculateTotals();
    }

    public function decrement(string $lineKey): void
    {
        if (!isset($this->cart[$lineKey])) return;
        $newQty = max(1, $this->cart[$lineKey]['qty'] - 1);
        $this->cart[$lineKey]['qty'] = $newQty;
        $this->recalculateTotals();
    }

    public function updateQuantity(string $lineKey, $quantity): void
    {
        if (!isset($this->cart[$lineKey])) return;

        $qty = max(1, (float)$quantity);
        $productId = (string)$this->cart[$lineKey]['product_id'];
        $stock = $this->getTodayStockForProduct($productId);
        $available = $this->availableQuantity($stock);

        // Enforce stock limit
        if ($qty > $available) {
            $this->toast()->warning('Cannot exceed available stock (' . $available . ') for ' . $this->cart[$lineKey]['name'])->send();
            $qty = $available;
        }

        $this->cart[$lineKey]['qty'] = $qty;
        $this->cart[$lineKey]['available'] = $available;
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
        $this->recalculateTotals();
    }

    public function updatedCashReceived(): void
    {
        $this->recalculateTotals();
    }

    protected function recalculateTotals(): void
    {
        $this->subtotal = 0.0;
        foreach ($this->cart as $line) {
            $this->subtotal += $line['price'] * $line['qty'];
        }
        $subAfterDiscount = max(0, $this->subtotal - $this->discount);
        $this->tax = 0.0; // hook if needed
        $this->total = $subAfterDiscount + $this->tax;
        $this->changeDue = max(0, $this->paymentTotal - $this->total);
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
            $total += (float)($p['amount'] ?? 0);
        }
        $this->payments = array_values($this->payments);
        $this->paymentTotal = $total;
        $this->paymentRemaining = max(0, $this->total - $this->paymentTotal);
        $this->changeDue = max(0, $this->paymentTotal - $this->total);
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
                ? ($bankName . ' · ' . $accountNumber)
                : $accountNumber;
        }

        return $bankName;
    }

    public function completeSale(): void
    {
        try {
            // Check for active shift first
            if (!$this->hasActiveShift()) {
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

            $sale = Sale::create([
                'sales_shift_id' => null, // Nullable - using general shifts table instead
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'table_id' => $this->selectedTableId,
                'sold_by_id' => auth()->id(),
                'sold_by_type' => \App\Models\User::class,
                'sale_number' => 'POS-' . Carbon::now()->format('Ymd-His'),
                'sale_time' => Carbon::now(),
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'total' => $this->total,
                'status' => 'completed',
                'order_type' => $this->orderType,
                'notes' => null,
            ]);

            $lowStockWarnings = [];

            foreach ($this->cart as $line) {
                $productId = (string)$line['product_id'];
                $qty = (float)$line['qty'];

                $stock = $stocksByProduct[$productId] ?? null;
                $available = $this->availableQuantity($stock);

                // Process sale with available quantity or full quantity
                $actualQty = ($available > 0 && $qty > $available) ? $available : $qty;

                if ($actualQty < $qty) {
                    $lowStockWarnings[] = $line['name'] . ' (sold ' . $actualQty . ' of ' . $qty . ' requested)';
                }

                if ($actualQty > 0) {
                    // Get the product for UOM conversion
                    $product = $productsById->get($productId);
                    
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
                        'notes' => $actualQty < $qty ? 'Partial fulfillment: ' . $actualQty . '/' . $qty : null,
                    ]);

                    if ($stock) {
                        // Deduct base quantity from stock
                        $stock->quantity_sold = (float)$stock->quantity_sold + $baseQty;
                        $stock->amount = (float)$stock->amount + ($salesQty * $line['price']);
                        $stock->updateCalculatedFields();
                        $stock->save();
                    }
                }
            }

            // Create payment records for each payment method
            foreach ($this->payments as $paymentData) {
                $amount = (float)($paymentData['amount'] ?? 0);
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
                'payments' => $this->payments,
                'change_due' => max(0, $this->paymentTotal - $this->total),
                'meta' => ['order_type' => $this->orderType, 'warnings' => $lowStockWarnings],
            ]);

            $this->currentSaleId = $sale->id;
            $this->dispatch('pos-receipt-ready', receipt_id: $receipt->id);

            if (!empty($lowStockWarnings)) {
                $this->toast()->warning('Sale completed with stock adjustments: ' . implode(', ', $lowStockWarnings))->send();
            } else {
                $this->toast()->success('Sale completed successfully!')->send();
            }
        });

            $this->clearCart();
            $this->discount = 0;
            $this->orderType = 'dine-in';
            $this->payments = [['method' => 'cash', 'amount' => 0.0, 'bank_account_id' => null, 'payer_bank' => null]];
            $this->recalcPayments();
        } catch (\Exception $e) {
            \Log::error('POS Sale Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'cart' => $this->cart,
            ]);
            $this->toast()->error('Payment failed: ' . $e->getMessage())->send();
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

    public function holdSale(): void
    {
        // Check for active shift first
        if (!$this->hasActiveShift()) {
            $this->toast()->error('No active shift. Please start a shift before holding sales.')->send();
            return;
        }

        // Persist as draft without affecting stock
        $sale = Sale::create([
            'sales_shift_id' => null, // Nullable - using general shifts table instead
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'table_id' => $this->selectedTableId,
            'sold_by_id' => auth()->id(),
            'sold_by_type' => \App\Models\User::class,
            'sale_number' => 'HOLD-' . Carbon::now()->format('Ymd-His'),
            'sale_time' => Carbon::now(),
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => 'hold',
            'order_type' => $this->orderType,
        ]);

        foreach ($this->cart as $line) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'department_id' => $this->departmentId,
                'product_id' => (string)$line['product_id'],
                'quantity' => (float)$line['qty'],
                'unit_price' => $line['price'],
                'subtotal' => $line['qty'] * $line['price'],
                'discount' => 0,
                'total' => $line['qty'] * $line['price'],
            ]);
        }

        $this->currentSaleId = $sale->id;
        $this->toast()->info('Sale put on hold.')->send();
        $this->clearCart();
    }

    public function resumeSale(int $saleId): void
    {
        $sale = Sale::with('saleItems.product')->find($saleId);
        if (!$sale || $sale->status !== 'hold') {
            $this->toast()->warning('Hold ticket not found.')->send();
            return;
        }
        $this->cart = [];
        foreach ($sale->saleItems as $item) {
            $this->cart[(string)$item->product_id] = [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Product',
                'price' => (float)$item->unit_price,
                'qty' => (float)$item->quantity,
                'low_stock' => false,
                'available' => $this->availableQuantity($this->getTodayStockForProduct($item->product_id)),
            ];
        }
        $this->discount = (float)$sale->discount;
        $this->orderType = $sale->order_type ?? 'dine-in';
        $this->recalculateTotals();
    }

    public function getProductsProperty(): Collection
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
        if ($hasDepartmentColumn && !$this->departmentId) {
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

    protected function availableQuantity(?ProductStock $stock): float
    {
        if (!$stock) return 0.0;
        // available is closing quantity; if not up to date, compute
        $stock->updateCalculatedFields();
        return max(0, (float)$stock->closing_quantity);
    }

    public function getAvailableForProduct(string $productId): float
    {
        return $this->availableQuantity($this->getTodayStockForProduct($productId));
    }

    protected function getProductsForView(): Collection
    {
        if ($this->productsForView !== null) {
            return $this->productsForView;
        }

        if (! $this->departmentId) {
            return $this->productsForView = collect();
        }

        $departmentIds = $this->scopedSalesDepartmentIds();
        if (empty($departmentIds)) {
            return $this->productsForView = collect();
        }

        $q = Product::query()
            ->active()
            ->available()
            ->whereIn('sales_department_id', $departmentIds)
            ->select(['id', 'name', 'price', 'sku'])
            ->orderBy('name');

        if (strlen($this->search)) {
            $q->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $this->productsForView = $q->get();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, float>
     */
    protected function getProductAvailabilityForView(Collection $products): array
    {
        if ($this->productAvailabilityForView !== null) {
            return $this->productAvailabilityForView;
        }

        $productIds = $products->pluck('id')
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

        $productIds = $products->pluck('id')
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
        $documentService = new PosDocumentService();
        return $documentService->generateBrandedReceiptHtml($sale);
    }

    // Table Management Methods
    public function selectTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        // Save current cart if there's anything in it and a table is selected
        if (!empty($this->cart) && $this->selectedTableId && $this->selectedTableId !== $tableId) {
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
        $this->cart = [];
        foreach ($sale->saleItems as $item) {
            $this->cart[(string)$item->product_id] = [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Product',
                'price' => (float)$item->unit_price,
                'qty' => (float)$item->quantity,
                'low_stock' => false,
                'available' => $this->availableQuantity($this->getTodayStockForProduct($item->product_id)),
            ];
        }
        $this->discount = (float)$sale->discount;
        $this->orderType = $sale->order_type ?? 'dine-in';
        $this->currentSaleId = $sale->id;
        $this->recalculateTotals();
    }

    public function saveTableTab(): void
    {
        if (!$this->selectedTableId) {
            $this->toast()->warning('No table selected.')->send();
            return;
        }

        if (empty($this->cart)) {
            $this->toast()->warning('Cart is empty.')->send();
            return;
        }

        if (!$this->hasActiveShift()) {
            $this->toast()->error('No active shift.')->send();
            return;
        }

        $table = Table::find($this->selectedTableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        DB::transaction(function () use ($table) {
            // Check if there's an existing hold sale for this table
            $existingSale = $table->getActiveSale();

            if ($existingSale) {
                // Update existing sale
                $existingSale->update([
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'order_type' => $this->orderType,
                ]);

                // Delete existing items
                $existingSale->saleItems()->delete();

                $sale = $existingSale;
            } else {
                // Create new sale
                $sale = Sale::create([
                    'sales_shift_id' => null, // Nullable - using general shifts table instead
                    'branch_id' => $this->branchId,
                    'department_id' => $this->departmentId,
                    'table_id' => $table->id,
                    'sold_by_id' => auth()->id(),
                    'sold_by_type' => \App\Models\User::class,
                    'sale_number' => 'TAB-' . $table->table_number . '-' . Carbon::now()->format('Ymd-His'),
                    'sale_time' => Carbon::now(),
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'status' => 'hold',
                    'order_type' => $this->orderType,
                    'notes' => 'Table: ' . $table->table_number,
                ]);
            }

            // Add items to sale
            foreach ($this->cart as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'department_id' => $this->departmentId,
                    'product_id' => (string)$line['product_id'],
                    'quantity' => (float)$line['qty'],
                    'unit_price' => $line['price'],
                    'subtotal' => $line['qty'] * $line['price'],
                    'discount' => 0,
                    'total' => $line['qty'] * $line['price'],
                ]);
            }

            $table->markAsOccupied();
            $this->currentSaleId = $sale->id;
        });

        $this->toast()->success('Tab saved for Table ' . $table->table_number)->send();
    }

    public function completeTableSale(): void
    {
        if (!$this->selectedTableId) {
            $this->toast()->warning('No table selected.')->send();
            return;
        }

        $table = Table::find($this->selectedTableId);
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        // Use the existing completeSale method but update table_id
        $this->completeSale();

        // Mark table as available after payment
        $table->markAsAvailable();

        $this->selectedTableId = null;
    }

    public function clearTable(): void
    {
        if (!$this->selectedTableId) {
            return;
        }

        $table = Table::find($this->selectedTableId);
        if ($table) {
            // Delete any hold sales for this table
            $activeSale = $table->getActiveSale();
            if ($activeSale) {
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
        if (!$this->departmentId || !$this->showTableManagement) {
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

        if (!$this->branchId || !$this->departmentId) {
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
            'table_name' => $this->newTableName ?: 'Table ' . $this->newTableNumber,
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
        if (!$table) {
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
        if (!$table) {
            $this->toast()->error('Table not found.')->send();
            return;
        }

        $table->update(['is_active' => !$table->is_active]);
        unset($this->tables);
        $this->toast()->success('Table status updated.')->send();
    }

    /**
     * Send receipt notification via email to customer
     */
    public function sendReceiptEmail(?string $customerEmail = null): void
    {
        if (!$this->currentSaleId) {
            $this->toast()->warning('No sale found.')->send();
            return;
        }

        if (!$customerEmail) {
            $this->toast()->warning('Customer email is required.')->send();
            return;
        }

        try {
            $sale = Sale::find($this->currentSaleId);
            $receipt = $sale->receipts()->latest()->first();

            if (!$receipt) {
                $this->toast()->error('No receipt found for this sale.')->send();
                return;
            }

            // Send notification
            $notification = new \App\Notifications\SalesReceiptNotification($receipt);
            \Illuminate\Support\Facades\Notification::route('mail', $customerEmail)
                ->notify($notification);

            $this->toast()->success('Receipt sent to ' . $customerEmail)->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send receipt email: ' . $e->getMessage());
            $this->toast()->error('Failed to send receipt: ' . $e->getMessage())->send();
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

        $targetTokens = $this->departmentIdentityTokens($targetNameKey . ' ' . $targetSlugKey);
        $candidateTokens = $this->departmentIdentityTokens($candidateNameKey . ' ' . $candidateSlugKey);
        if (empty($targetTokens) || empty($candidateTokens)) {
            return false;
        }

        $sharedTokens = array_intersect($targetTokens, $candidateTokens);
        $shorterTokenCount = min(count($targetTokens), count($candidateTokens));

        return count($sharedTokens) === $shorterTokenCount;
    }

}
