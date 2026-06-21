<?php

namespace App\Services;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\AccountTransfer;
use App\Models\ExpenseClaim;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ProductionOrder;
use App\Models\InventoryAdjustment;
use App\Models\Payroll;
use App\Models\PurchasePayment;
use App\Models\TaxPayment;
use App\Models\FixedAsset;
use App\Models\AssetDepreciation;
use App\Models\User;
use App\Notifications\GlPostingFailedNotification;
use App\Services\Accounting\Concerns\ResolvesAccountDefaults;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Exception;

class GlPostingService
{
    use ResolvesAccountDefaults;

    protected ?AccountingPeriod $currentPeriod = null;
    protected array $accountCache = [];
    protected ?string $branchId = null;

    /**
     * Resolve a GL account for the active branch by its configurable accounting
     * key (e.g. 'cogs', 'inventory', 'bank_main') from branch_accounting_defaults.
     * Preferred over getGlAccount(): no hard-coded account numbers.
     */
    protected function account(string $key): GlAccount
    {
        return $this->resolveAccount($key, $this->branchId);
    }

    /**
     * Get current accounting period, scoped to the active branch.
     */
    public function getCurrentPeriod(): ?AccountingPeriod
    {
        if ($this->currentPeriod) {
            return $this->currentPeriod;
        }

        $query = AccountingPeriod::current();
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }
        return $query->first();
    }

    /**
     * Create a GL entry with branch_id automatically injected.
     */
    protected function createEntry(array $attrs): GlEntry
    {
        return GlEntry::create(array_merge(['branch_id' => $this->branchId], $attrs));
    }

    /**
     * Get GL account by account number (cached)
     */
    protected function getGlAccount(string $accountNumber): GlAccount
    {
        if (!isset($this->accountCache[$accountNumber])) {
            $this->accountCache[$accountNumber] = GlAccount::where('account_number', $accountNumber)
                ->firstOrFail();
        }
        return $this->accountCache[$accountNumber];
    }

    /**
     * Idempotency guard for posting methods.
     */
    protected function alreadyPosted(string $referenceType, int $referenceId, array $entryTypes): bool
    {
        return GlEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('entry_type', $entryTypes)
            ->where('status', 'posted')
            ->exists();
    }

    /**
     * Post a sale transaction (Revenue + COGS)
     * Entry A: Debit Cash/Bank, Credit Sales Revenue
     * Entry B: Debit COGS, Credit Inventory
     */
    public function postSaleTransaction(Sale $sale): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $sale->branch_id;

            if ($this->alreadyPosted(Sale::class, (int) $sale->id, ['sale', 'sale_cogs', 'sale_tax'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $department = $this->getDepartmentForSale($sale);

            // Entry A: Record Sale Revenue
            // Debit: Accounts Receivable, Credit: Sales Revenue
            $revenueAccount = $this->getRevenueAccountForDepartment($department);
            $receivableAccount = $this->getReceivableAccountForDepartment($department);

            $this->createEntry([
                'gl_account_id' => $receivableAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'sale',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'reference_number' => $sale->reference_number ?? "SAL-{$sale->id}",
                'description' => "Sale Transaction - {$sale->reference_number}",
                'debit' => $sale->total,
                'credit' => 0,
                'entry_date' => $sale->sale_time,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit to Revenue
            $this->createEntry([
                'gl_account_id' => $revenueAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'sale',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'reference_number' => $sale->reference_number ?? "SAL-{$sale->id}",
                'description' => "Sale Revenue - {$sale->reference_number}",
                'debit' => 0,
                'credit' => $sale->subtotal,
                'entry_date' => $sale->sale_time,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Entry B: Record COGS
            // Debit: COGS, Credit: Inventory
            $cogsAccount = $this->getGlAccount('5010');
            $inventoryAccount = $this->getGlAccount('1200');

            $totalCogs = $sale->saleItems->sum(function ($item) {
                if (! empty($item->line_cost)) {
                    return (float) $item->line_cost;
                }

                if (! empty($item->unit_cost)) {
                    return (float) $item->unit_cost * (float) ($item->quantity ?? 0);
                }

                return 0;
            });

            if ($totalCogs > 0) {
                $this->createEntry([
                    'gl_account_id' => $cogsAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale_cogs',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->reference_number ?? "SAL-{$sale->id}",
                    'description' => "COGS - {$sale->reference_number}",
                    'debit' => $totalCogs,
                    'credit' => 0,
                    'entry_date' => $sale->sale_time,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale_cogs',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->reference_number ?? "SAL-{$sale->id}",
                    'description' => "Inventory Reduction - {$sale->reference_number}",
                    'debit' => 0,
                    'credit' => $totalCogs,
                    'entry_date' => $sale->sale_time,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            // Entry C: Record Sales Tax (if applicable)
            if ($sale->tax > 0) {
                $taxAccount = $this->getTaxAccountForDepartment($department);

                // VAT is INCLUSIVE: Accounts Receivable was already debited the full
                // (VAT-inclusive) total in Entry A, and revenue was credited net of VAT,
                // so here we only credit the VAT liability. (total = net subtotal + tax)
                $this->createEntry([
                    'gl_account_id' => $taxAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale_tax',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->reference_number ?? "SAL-{$sale->id}",
                    'description' => "Sales Tax Liability - {$sale->reference_number}",
                    'debit' => 0,
                    'credit' => $sale->tax,
                    'entry_date' => $sale->sale_time,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Sale Transaction', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('sale', (string) $sale->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a purchase transaction
     * Debit: Inventory, Credit: Accounts Payable
     */
    public function postPurchaseTransaction(Purchase $purchase): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $purchase->branch_id;

            if ($this->alreadyPosted(Purchase::class, (int) $purchase->id, ['purchase'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $inventoryAccount = $this->getGlAccount('1200');
            $apAccount = $this->getGlAccount('2010');

            $landingCost = $purchase->total_fob_ngn + ($purchase->other_costs ?? 0);

            // Debit: Inventory
            $this->createEntry([
                'gl_account_id' => $inventoryAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'purchase',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'reference_number' => $purchase->reference_number ?? "PUR-{$purchase->id}",
                'description' => "Purchase - {$purchase->reference_number}",
                'debit' => $landingCost,
                'credit' => 0,
                'entry_date' => $purchase->created_at,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: Accounts Payable
            $this->createEntry([
                'gl_account_id' => $apAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'purchase',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'reference_number' => $purchase->reference_number ?? "PUR-{$purchase->id}",
                'description' => "Accounts Payable - {$purchase->reference_number}",
                'debit' => 0,
                'credit' => $landingCost,
                'entry_date' => $purchase->created_at,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Purchase Transaction', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('purchase', (string) $purchase->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a payment transaction
     * Debit: Cash/Bank, Credit: Accounts Receivable
     */
    public function postPaymentTransaction(Payment $payment): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $payment->branch_id;

            if ($this->alreadyPosted(Payment::class, (int) $payment->id, ['payment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $department = $payment->sale?->department ?? $payment->sale?->branch?->departments()?->first();
            $receivableAccount = $this->getReceivableAccountForDepartment($department);
            $cashAccount = null;
            if (strtolower($payment->payment_method) === 'pos') {
                $cashAccount = $this->getGlAccount($this->getCashAccountNumberForPaymentMethod('pos'));
            } else {
                $cashAccount = $payment->bankAccount?->glAccount
                    ?? ($department?->cashAccount)
                    ?? $this->getGlAccount($this->getCashAccountNumberForPaymentMethod($payment->payment_method));
            }

            // Debit: Cash/Bank
            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payment',
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "PAY-{$payment->id}",
                'description' => "Payment received - {$payment->reference_number}",
                'debit' => $payment->amount,
                'credit' => 0,
                'entry_date' => $payment->payment_time,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: Accounts Receivable
            $this->createEntry([
                'gl_account_id' => $receivableAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payment',
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "PAY-{$payment->id}",
                'description' => "AR reduction - {$payment->reference_number}",
                'debit' => 0,
                'credit' => $payment->amount,
                'entry_date' => $payment->payment_time,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Payment Transaction', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('payment', (string) $payment->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post inventory adjustment (damage, shrinkage, etc.)
     */
    public function postInventoryAdjustment(StockMovement $movement): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $movement->branch_id;

            if ($this->alreadyPosted(StockMovement::class, (int) $movement->id, ['adjustment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $reason = $movement->adjustment_reason;
            if ($movement->type === 'damaged' && ! $reason) {
                $reason = 'damage';
            }

            if ($movement->type !== 'adjustment' && $movement->type !== 'damaged') {
                return true; // Not an adjustment that needs GL entry
            }

            if (! $reason) {
                return true; // No explicit adjustment reason to post
            }

            $inventoryAccount = $this->getGlAccount('1200');
            $adjustmentAccount = $this->getAdjustmentAccountForType($reason);

            $amount = $movement->cost_impact ?? ($movement->quantity * ($movement->unit_cost ?? 0));
            $amount = abs((float) $amount);

            $isIncrease = (float) $movement->quantity_after > (float) $movement->quantity_before;

            if ($isIncrease && $movement->type === 'adjustment') {
                // Debit Inventory, Credit Adjustment (inventory gain)
                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'reference_number' => "ADJ-{$movement->id}",
                    'description' => "Inventory Increase - {$movement->quantity} units",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $movement->movement_date ?? $movement->created_at,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                $this->createEntry([
                    'gl_account_id' => $adjustmentAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'reference_number' => "ADJ-{$movement->id}",
                    'description' => ucfirst($reason) . " Adjustment - {$movement->quantity} units",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $movement->movement_date ?? $movement->created_at,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            } else {
                // Debit Loss/Adjustment, Credit Inventory
                $this->createEntry([
                    'gl_account_id' => $adjustmentAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'reference_number' => "ADJ-{$movement->id}",
                    'description' => ucfirst($reason) . " Loss - {$movement->quantity} units",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $movement->movement_date ?? $movement->created_at,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'reference_number' => "ADJ-{$movement->id}",
                    'description' => "Inventory Reduction - {$movement->quantity} units",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $movement->movement_date ?? $movement->created_at,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Inventory Adjustment', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('inventory_adjustment', (string) $movement->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and post a manual GL entry
     */
    public function postManualEntry(GlEntry $entry): bool
    {
        // Validate entry is balanced
        if (floatval($entry->debit) !== floatval($entry->credit)) {
            throw new Exception('Journal entry must be balanced (debit = credit)');
        }

        // Validate GL account exists and is active
        $account = GlAccount::find($entry->gl_account_id);
        if (!$account || !$account->is_active) {
            throw new Exception('GL account is not active or does not exist');
        }

        // Validate period is open
        $period = AccountingPeriod::find($entry->accounting_period_id);
        if (!$period || $period->status !== 'open') {
            throw new Exception('Accounting period is not open');
        }

        return $entry->post(auth()->id());
    }

    /**
     * Post an account transfer transaction
     * Debit: To Bank Account GL, Credit: From Bank Account GL
     */
    public function postAccountTransfer(AccountTransfer $transfer): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $transfer->branch_id;

            if ($this->alreadyPosted(AccountTransfer::class, (int) $transfer->id, ['transfer'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $fromAccount = $transfer->fromBankAccount;
            $toAccount = $transfer->toBankAccount;

            if (!$fromAccount || !$toAccount) {
                throw new Exception('Bank accounts not found for transfer');
            }

            $fromGlAccount = $fromAccount->glAccount ?? $this->getGlAccount('1050');
            $toGlAccount = $toAccount->glAccount ?? $this->getGlAccount('1050');

            // Debit: To Bank Account
            $this->createEntry([
                'gl_account_id' => $toGlAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'transfer',
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'reference_number' => "TRF-{$transfer->id}",
                'description' => "Transfer from {$fromAccount->name} to {$toAccount->name}",
                'debit' => $transfer->amount,
                'credit' => 0,
                'entry_date' => $transfer->transfer_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: From Bank Account
            $this->createEntry([
                'gl_account_id' => $fromGlAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'transfer',
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'reference_number' => "TRF-{$transfer->id}",
                'description' => "Transfer from {$fromAccount->name} to {$toAccount->name}",
                'debit' => 0,
                'credit' => $transfer->amount,
                'entry_date' => $transfer->transfer_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Account Transfer', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('account_transfer', (string) $transfer->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post an expense claim transaction
     * Debit: Expense Accounts, Credit: Cash/Bank
     */
    public function postExpenseClaim(ExpenseClaim $claim): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $claim->branch_id;

            if ($this->alreadyPosted(ExpenseClaim::class, (int) $claim->id, ['expense_claim'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            // Determine payment account
            $cashAccount = $claim->paidViaBankAccount?->glAccount
                ?? $this->getGlAccount('1010');

            // Post each expense item to its respective expense account
            foreach ($claim->items as $item) {
                $expenseAccount = $item->glAccount ?? $this->getExpenseAccountForCategory($item->category);

                // Debit: Expense Account
                $this->createEntry([
                    'gl_account_id' => $expenseAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'expense_claim',
                    'reference_type' => ExpenseClaim::class,
                    'reference_id' => $claim->id,
                    'reference_number' => "EXP-{$claim->id}",
                    'description' => "Expense Claim - {$item->description}",
                    'debit' => $item->amount,
                    'credit' => 0,
                    'entry_date' => $claim->claim_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            // Credit: Cash/Bank for total
            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'expense_claim',
                'reference_type' => ExpenseClaim::class,
                'reference_id' => $claim->id,
                'reference_number' => "EXP-{$claim->id}",
                'description' => "Expense Claim Payment - {$claim->employee?->name}",
                'debit' => 0,
                'credit' => $claim->total_amount,
                'entry_date' => $claim->claim_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Expense Claim', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('expense_claim', (string) $claim->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a credit note transaction (sales return/refund)
     * Debit: Sales Revenue, Credit: Customer/Cash
     * If inventory returned: Debit: Inventory, Credit: COGS
     */
    public function postCreditNote(CreditNote $creditNote): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $creditNote->branch_id;

            if ($this->alreadyPosted(CreditNote::class, (int) $creditNote->id, ['credit_note', 'credit_note_cogs'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $revenueAccount = $this->getGlAccount('4000');
            $receivableAccount = $this->getGlAccount('1100'); // Accounts Receivable
            $inventoryAccount = $this->getGlAccount('1200');
            $cogsAccount = $this->getGlAccount('5010');

            // Debit: Sales Revenue (reducing revenue)
            $this->createEntry([
                'gl_account_id' => $revenueAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'credit_note',
                'reference_type' => CreditNote::class,
                'reference_id' => $creditNote->id,
                'reference_number' => $creditNote->credit_note_number,
                'description' => "Credit Note - {$creditNote->credit_note_number}",
                'debit' => $creditNote->subtotal,
                'credit' => 0,
                'entry_date' => $creditNote->credit_note_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: Accounts Receivable (reducing what customer owes)
            $this->createEntry([
                'gl_account_id' => $receivableAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'credit_note',
                'reference_type' => CreditNote::class,
                'reference_id' => $creditNote->id,
                'reference_number' => $creditNote->credit_note_number,
                'description' => "Credit Note - {$creditNote->credit_note_number}",
                'debit' => 0,
                'credit' => $creditNote->total,
                'entry_date' => $creditNote->credit_note_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Reverse COGS if items are returned to inventory
            $totalCogs = $creditNote->items->sum(function ($item) {
                return $item->quantity * ($item->product?->cost_price ?? 0);
            });

            if ($totalCogs > 0) {
                // Debit: Inventory (adding back)
                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'credit_note_cogs',
                    'reference_type' => CreditNote::class,
                    'reference_id' => $creditNote->id,
                    'reference_number' => $creditNote->credit_note_number,
                    'description' => "Credit Note Inventory Return - {$creditNote->credit_note_number}",
                    'debit' => $totalCogs,
                    'credit' => 0,
                    'entry_date' => $creditNote->credit_note_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                // Credit: COGS (reducing cost)
                $this->createEntry([
                    'gl_account_id' => $cogsAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'credit_note_cogs',
                    'reference_type' => CreditNote::class,
                    'reference_id' => $creditNote->id,
                    'reference_number' => $creditNote->credit_note_number,
                    'description' => "Credit Note COGS Reversal - {$creditNote->credit_note_number}",
                    'debit' => 0,
                    'credit' => $totalCogs,
                    'entry_date' => $creditNote->credit_note_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Credit Note', [
                'credit_note_id' => $creditNote->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('credit_note', (string) $creditNote->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a debit note transaction (purchase return)
     * Debit: Accounts Payable, Credit: Inventory
     */
    public function postDebitNote(DebitNote $debitNote): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $debitNote->branch_id;

            if ($this->alreadyPosted(DebitNote::class, (int) $debitNote->id, ['debit_note'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $apAccount = $this->getGlAccount('2010');
            $inventoryAccount = $this->getGlAccount('1200');

            // Debit: Accounts Payable (reducing what we owe)
            $this->createEntry([
                'gl_account_id' => $apAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'debit_note',
                'reference_type' => DebitNote::class,
                'reference_id' => $debitNote->id,
                'reference_number' => $debitNote->debit_note_number,
                'description' => "Debit Note - {$debitNote->debit_note_number}",
                'debit' => $debitNote->total,
                'credit' => 0,
                'entry_date' => $debitNote->debit_note_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: Inventory (reducing inventory)
            $this->createEntry([
                'gl_account_id' => $inventoryAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'debit_note',
                'reference_type' => DebitNote::class,
                'reference_id' => $debitNote->id,
                'reference_number' => $debitNote->debit_note_number,
                'description' => "Debit Note Inventory Return - {$debitNote->debit_note_number}",
                'debit' => 0,
                'credit' => $debitNote->subtotal,
                'entry_date' => $debitNote->debit_note_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Debit Note', [
                'debit_note_id' => $debitNote->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('debit_note', (string) $debitNote->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a production order transaction
     * Debit: Finished Goods Inventory, Credit: Raw Materials + Labor + Overhead
     */
    public function postProductionOrder(ProductionOrder $order): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $order->branch_id;

            if ($this->alreadyPosted(ProductionOrder::class, (int) $order->id, ['production'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $finishedGoodsAccount = $this->getGlAccount('1200'); // Finished Goods
            $rawMaterialsAccount = $this->getGlAccount('1200'); // Raw Materials
            $wipAccount = $this->getGlAccount('1200'); // Work in Progress (if exists)
            $laborAccount = $this->getGlAccount('6100'); // Direct Labor (if exists)
            $overheadAccount = $this->getGlAccount('6200'); // Manufacturing Overhead (if exists)

            // Debit: Finished Goods Inventory
            $this->createEntry([
                'gl_account_id' => $finishedGoodsAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'production',
                'reference_type' => ProductionOrder::class,
                'reference_id' => $order->id,
                'reference_number' => $order->order_number,
                'description' => "Production Output - {$order->order_number}",
                'debit' => $order->total_cost,
                'credit' => 0,
                'entry_date' => $order->completed_date ?? now(),
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            // Credit: Raw Materials (material cost)
            if ($order->total_material_cost > 0) {
                $this->createEntry([
                    'gl_account_id' => $rawMaterialsAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'production',
                    'reference_type' => ProductionOrder::class,
                    'reference_id' => $order->id,
                    'reference_number' => $order->order_number,
                    'description' => "Production Materials Used - {$order->order_number}",
                    'debit' => 0,
                    'credit' => $order->total_material_cost,
                    'entry_date' => $order->completed_date ?? now(),
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            // Credit: Labor (if applicable)
            if ($order->total_labor_cost > 0) {
                try {
                    $this->createEntry([
                        'gl_account_id' => $laborAccount->id,
                        'accounting_period_id' => $period->id,
                        'entry_type' => 'production',
                        'reference_type' => ProductionOrder::class,
                        'reference_id' => $order->id,
                        'reference_number' => $order->order_number,
                        'description' => "Production Labor - {$order->order_number}",
                        'debit' => 0,
                        'credit' => $order->total_labor_cost,
                        'entry_date' => $order->completed_date ?? now(),
                        'status' => 'draft',
                        'entered_by_id' => auth()->id(),
                    ])->post(auth()->id());
                } catch (Exception $e) {
                    // If labor account doesn't exist, add to materials
                    \Log::warning('Labor account not found, adding to materials', ['order_id' => $order->id]);
                }
            }

            // Credit: Overhead (if applicable)
            if ($order->total_overhead_cost > 0) {
                try {
                    $this->createEntry([
                        'gl_account_id' => $overheadAccount->id,
                        'accounting_period_id' => $period->id,
                        'entry_type' => 'production',
                        'reference_type' => ProductionOrder::class,
                        'reference_id' => $order->id,
                        'reference_number' => $order->order_number,
                        'description' => "Production Overhead - {$order->order_number}",
                        'debit' => 0,
                        'credit' => $order->total_overhead_cost,
                        'entry_date' => $order->completed_date ?? now(),
                        'status' => 'draft',
                        'entered_by_id' => auth()->id(),
                    ])->post(auth()->id());
                } catch (Exception $e) {
                    \Log::warning('Overhead account not found', ['order_id' => $order->id]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Production Order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('production_order', (string) $order->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post an inventory adjustment transaction
     */
    public function postInventoryAdjustmentEntry(InventoryAdjustment $adjustment): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $adjustment->branch_id;

            if ($this->alreadyPosted(InventoryAdjustment::class, (int) $adjustment->id, ['adjustment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (!$period) {
                throw new Exception('No open accounting period found');
            }

            $inventoryAccount = $this->getGlAccount('1200');
            $adjustmentAccount = $this->getAdjustmentAccountForType($adjustment->type);

            $amount = abs($adjustment->cost_impact);

            if ($adjustment->isDecrease()) {
                // Inventory decreased: Debit Adjustment Account, Credit Inventory
                $this->createEntry([
                    'gl_account_id' => $adjustmentAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reference_number' => $adjustment->adjustment_number,
                    'description' => ucfirst($adjustment->type) . " Adjustment - {$adjustment->adjustment_number}",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $adjustment->adjustment_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reference_number' => $adjustment->adjustment_number,
                    'description' => "Inventory Reduction - {$adjustment->adjustment_number}",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $adjustment->adjustment_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            } else {
                // Inventory increased: Debit Inventory, Credit Adjustment Account
                $this->createEntry([
                    'gl_account_id' => $inventoryAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reference_number' => $adjustment->adjustment_number,
                    'description' => "Inventory Increase - {$adjustment->adjustment_number}",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $adjustment->adjustment_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());

                $this->createEntry([
                    'gl_account_id' => $adjustmentAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'adjustment',
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reference_number' => $adjustment->adjustment_number,
                    'description' => ucfirst($adjustment->type) . " Adjustment - {$adjustment->adjustment_number}",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $adjustment->adjustment_date,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Inventory Adjustment', [
                'adjustment_id' => $adjustment->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('inventory_adjustment', (string) $adjustment->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get expense account based on category
     */
    protected function getExpenseAccountForCategory(string $category): GlAccount
    {
        $mapping = [
            'travel' => '6300',         // Travel Expenses
            'meals' => '6310',          // Meals & Entertainment
            'supplies' => '6320',       // Office Supplies
            'communication' => '6330',  // Communication
            'accommodation' => '6340',  // Accommodation
            'professional' => '6350',   // Professional Services
            'other' => '6900',          // Other Expenses
        ];

        $accountNumber = $mapping[$category] ?? '6900';

        try {
            return $this->getGlAccount($accountNumber);
        } catch (Exception $e) {
            // Fallback to general expense account
            return $this->getGlAccount('6900');
        }
    }

    /**
     * Get adjustment account based on adjustment type
     */
    protected function getAdjustmentAccountForType(string $type): GlAccount
    {
        $mapping = [
            'damage' => '5020',      // Damage Loss
            'shrinkage' => '5030',   // Shrinkage Loss
            'write_off' => '5040',   // Write-off Loss
            'adjustment' => '5050',  // Inventory Adjustment
            'count' => '5050',       // Stock Count Adjustment
            'transfer' => '5050',    // Transfer Adjustment
            'production' => '5010',  // Production COGS
        ];

        $accountNumber = $mapping[$type] ?? '5050';

        try {
            return $this->getGlAccount($accountNumber);
        } catch (Exception $e) {
            // Fallback to general adjustment account
            return $this->getGlAccount('5020');
        }
    }

    /**
     * Get appropriate cash account number based on payment method
     */
    protected function getCashAccountNumberForPaymentMethod(string $paymentMethod): string
    {
        $mapping = [
            'cash' => '1010',              // Cash - Head Office
            'transfer' => '1050',          // Bank Account - Main
            'bank_transfer' => '1050',     // Bank Account - Main
            'card' => '1050',              // Bank Account - Main
            'pos' => '1060',               // POS Clearing
            'cheque' => '1050',            // Bank Account - Main
        ];

        return $mapping[$paymentMethod] ?? '1010';
    }

    /**
     * Post payroll accrual (approve payroll)
     * Debit: Salaries Expense, Credit: Payroll Payable
     */
    public function postPayrollAccrual(Payroll $payroll): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $payroll->branch_id;

            if ($this->alreadyPosted(Payroll::class, (int) $payroll->id, ['payroll_accrual'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $expenseAccount = $this->getGlAccount('6100'); // Salaries & Wages Expense
            $payableAccount = $this->getGlAccount('2110'); // Payroll Payable
            $taxPayableAccount = $this->getGlAccount('2120'); // Payroll Tax Payable
            $deductionPayableAccount = $this->getGlAccount('2130'); // Other Deductions Payable

            $gross = (float) $payroll->gross_salary;
            $net = (float) $payroll->net_salary;
            $tax = (float) $payroll->tax_deductions;
            $other = (float) $payroll->other_deductions;

            $this->createEntry([
                'gl_account_id' => $expenseAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payroll_accrual',
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'reference_number' => "PAYROLL-{$payroll->id}",
                'description' => "Payroll Accrual - {$payroll->employee?->name}",
                'debit' => $gross,
                'credit' => 0,
                'entry_date' => $payroll->pay_period_end,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $payableAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payroll_accrual',
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'reference_number' => "PAYROLL-{$payroll->id}",
                'description' => "Payroll Payable - {$payroll->employee?->name}",
                'debit' => 0,
                'credit' => $net,
                'entry_date' => $payroll->pay_period_end,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            if ($tax > 0) {
                $this->createEntry([
                    'gl_account_id' => $taxPayableAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'payroll_accrual',
                    'reference_type' => Payroll::class,
                    'reference_id' => $payroll->id,
                    'reference_number' => "PAYROLL-{$payroll->id}",
                    'description' => "Payroll Tax Payable - {$payroll->employee?->name}",
                    'debit' => 0,
                    'credit' => $tax,
                    'entry_date' => $payroll->pay_period_end,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            if ($other > 0) {
                $this->createEntry([
                    'gl_account_id' => $deductionPayableAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'payroll_accrual',
                    'reference_type' => Payroll::class,
                    'reference_id' => $payroll->id,
                    'reference_number' => "PAYROLL-{$payroll->id}",
                    'description' => "Other Deductions Payable - {$payroll->employee?->name}",
                    'debit' => 0,
                    'credit' => $other,
                    'entry_date' => $payroll->pay_period_end,
                    'status' => 'draft',
                    'entered_by_id' => auth()->id(),
                ])->post(auth()->id());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Payroll Accrual', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('payroll', (string) $payroll->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post payroll payment
     * Debit: Payroll Payable, Credit: Cash/Bank
     */
    public function postPayrollPayment(Payroll $payroll): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $payroll->branch_id;

            if ($this->alreadyPosted(Payroll::class, (int) $payroll->id, ['payroll_payment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $payableAccount = $this->getGlAccount('2110');
            $cashAccount = $payroll->bankAccount?->glAccount ?? $this->getGlAccount('1050');

            $amount = (float) $payroll->net_salary;

            $this->createEntry([
                'gl_account_id' => $payableAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payroll_payment',
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'reference_number' => "PAYROLL-{$payroll->id}",
                'description' => "Payroll Payment - {$payroll->employee?->name}",
                'debit' => $amount,
                'credit' => 0,
                'entry_date' => $payroll->payment_date ?? now(),
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payroll_payment',
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'reference_number' => "PAYROLL-{$payroll->id}",
                'description' => "Payroll Cash/Bank - {$payroll->employee?->name}",
                'debit' => 0,
                'credit' => $amount,
                'entry_date' => $payroll->payment_date ?? now(),
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Payroll Payment', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('payroll', (string) $payroll->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post purchase payment (settle AP)
     * Debit: Accounts Payable, Credit: Cash/Bank
     */
    public function postPurchasePayment(PurchasePayment $payment): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $payment->branch_id;

            if ($this->alreadyPosted(PurchasePayment::class, (int) $payment->id, ['purchase_payment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $apAccount = $this->getGlAccount('2010');
            $cashAccount = $payment->bankAccount?->glAccount ?? $this->getGlAccount('1050');

            $this->createEntry([
                'gl_account_id' => $apAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'purchase_payment',
                'reference_type' => PurchasePayment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "PPAY-{$payment->id}",
                'description' => "Purchase Payment - {$payment->purchase?->purchase_number}",
                'debit' => $payment->amount,
                'credit' => 0,
                'entry_date' => $payment->payment_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'purchase_payment',
                'reference_type' => PurchasePayment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "PPAY-{$payment->id}",
                'description' => "Purchase Payment Cash/Bank - {$payment->purchase?->purchase_number}",
                'debit' => 0,
                'credit' => $payment->amount,
                'entry_date' => $payment->payment_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Purchase Payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('purchase_payment', (string) $payment->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post tax payment
     * Debit: Tax Payable, Credit: Cash/Bank
     */
    public function postTaxPayment(TaxPayment $payment): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $payment->branch_id;

            if ($this->alreadyPosted(TaxPayment::class, (int) $payment->id, ['tax_payment'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $taxAccount = $this->getGlAccount('2100');
            $cashAccount = $payment->bankAccount?->glAccount ?? $this->getGlAccount('1050');

            $this->createEntry([
                'gl_account_id' => $taxAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'tax_payment',
                'reference_type' => TaxPayment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "TAX-{$payment->id}",
                'description' => "Tax Payment - {$payment->tax_type}",
                'debit' => $payment->amount,
                'credit' => 0,
                'entry_date' => $payment->payment_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'tax_payment',
                'reference_type' => TaxPayment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number ?? "TAX-{$payment->id}",
                'description' => "Tax Payment Cash/Bank - {$payment->tax_type}",
                'debit' => 0,
                'credit' => $payment->amount,
                'entry_date' => $payment->payment_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Tax Payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('tax_payment', (string) $payment->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post fixed asset acquisition
     * Debit: Fixed Assets, Credit: Cash/Bank or Accounts Payable
     */
    public function postFixedAssetAcquisition(FixedAsset $asset): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $asset->branch_id;

            if ($this->alreadyPosted(FixedAsset::class, (int) $asset->id, ['asset_acquisition'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $assetAccount = $this->getGlAccount('1500'); // Fixed Assets
            $creditAccount = $asset->funding_source === 'ap'
                ? $this->getGlAccount('2010')
                : ($asset->bankAccount?->glAccount ?? $this->getGlAccount('1050'));

            $this->createEntry([
                'gl_account_id' => $assetAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'asset_acquisition',
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'reference_number' => $asset->asset_tag ?? "ASSET-{$asset->id}",
                'description' => "Asset Acquisition - {$asset->asset_name}",
                'debit' => $asset->asset_cost,
                'credit' => 0,
                'entry_date' => $asset->acquisition_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $creditAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'asset_acquisition',
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'reference_number' => $asset->asset_tag ?? "ASSET-{$asset->id}",
                'description' => "Asset Funding - {$asset->asset_name}",
                'debit' => 0,
                'credit' => $asset->asset_cost,
                'entry_date' => $asset->acquisition_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Asset Acquisition', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('fixed_asset', (string) $asset->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post asset depreciation
     * Debit: Depreciation Expense, Credit: Accumulated Depreciation
     */
    public function postAssetDepreciation(AssetDepreciation $depreciation): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $depreciation->asset?->branch_id;

            if ($this->alreadyPosted(AssetDepreciation::class, (int) $depreciation->id, ['asset_depreciation'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $expenseAccount = $this->getGlAccount('6200'); // Depreciation Expense
            $accumAccount = $this->getGlAccount('1510');  // Accumulated Depreciation

            $this->createEntry([
                'gl_account_id' => $expenseAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'asset_depreciation',
                'reference_type' => AssetDepreciation::class,
                'reference_id' => $depreciation->id,
                'reference_number' => "DEP-{$depreciation->id}",
                'description' => "Depreciation - {$depreciation->asset?->asset_name}",
                'debit' => $depreciation->depreciation_amount,
                'credit' => 0,
                'entry_date' => $depreciation->depreciation_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $accumAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'asset_depreciation',
                'reference_type' => AssetDepreciation::class,
                'reference_id' => $depreciation->id,
                'reference_number' => "DEP-{$depreciation->id}",
                'description' => "Accumulated Depreciation - {$depreciation->asset?->asset_name}",
                'debit' => 0,
                'credit' => $depreciation->depreciation_amount,
                'entry_date' => $depreciation->depreciation_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Asset Depreciation', [
                'depreciation_id' => $depreciation->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('fixed_asset', (string) $depreciation->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post asset disposal.
     * Debit: Accumulated Depreciation (removes contra-asset)
     * Debit: Loss on Disposal (remaining book value, if any)
     * Credit: Fixed Asset account (removes asset at cost)
     */
    public function postAssetDisposal(FixedAsset $asset): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $asset->branch_id;

            if ($this->alreadyPosted(FixedAsset::class, (int) $asset->id, ['asset_disposal'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $assetCost         = (float) $asset->asset_cost;
            $accumulatedDepr   = (float) $asset->accumulated_depreciation;
            $bookValue         = max(0, $assetCost - $accumulatedDepr);
            $today             = now()->toDateString();
            $ref               = $asset->asset_tag ?? "ASSET-{$asset->id}";

            $assetAccount      = $this->getGlAccount('1500'); // Fixed Assets
            $accumAccount      = $this->getGlAccount('1510'); // Accumulated Depreciation
            $lossAccount       = $this->getGlAccount('7100'); // Loss on Disposal / Other Expenses

            // Remove the accumulated depreciation (debit the contra-asset to zero it out)
            if ($accumulatedDepr > 0) {
                $this->createEntry([
                    'gl_account_id'        => $accumAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type'           => 'asset_disposal',
                    'reference_type'       => FixedAsset::class,
                    'reference_id'         => $asset->id,
                    'reference_number'     => $ref,
                    'description'          => "Disposal - Remove Accumulated Depreciation: {$asset->asset_name}",
                    'debit'                => $accumulatedDepr,
                    'credit'               => 0,
                    'entry_date'           => $today,
                    'status'               => 'draft',
                    'entered_by_id'        => auth()->id(),
                ])->post(auth()->id());
            }

            // Recognise loss on disposal (remaining book value)
            if ($bookValue > 0) {
                $this->createEntry([
                    'gl_account_id'        => $lossAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type'           => 'asset_disposal',
                    'reference_type'       => FixedAsset::class,
                    'reference_id'         => $asset->id,
                    'reference_number'     => $ref,
                    'description'          => "Disposal - Loss on Disposal: {$asset->asset_name}",
                    'debit'                => $bookValue,
                    'credit'               => 0,
                    'entry_date'           => $today,
                    'status'               => 'draft',
                    'entered_by_id'        => auth()->id(),
                ])->post(auth()->id());
            }

            // Remove the asset at full cost (credit)
            $this->createEntry([
                'gl_account_id'        => $assetAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type'           => 'asset_disposal',
                'reference_type'       => FixedAsset::class,
                'reference_id'         => $asset->id,
                'reference_number'     => $ref,
                'description'          => "Disposal - Remove Asset at Cost: {$asset->asset_name}",
                'debit'                => 0,
                'credit'               => $assetCost,
                'entry_date'           => $today,
                'status'               => 'draft',
                'entered_by_id'        => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Asset Disposal', [
                'asset_id' => $asset->id,
                'error'    => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('fixed_asset', (string) $asset->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Post a direct expense entry.
     * Debit: specified GL expense account, Credit: Cash/Bank
     */
    public function postExpenseEntry(\App\Models\ExpenseEntry $entry): bool
    {
        try {
            DB::beginTransaction();
            $this->branchId = $entry->branch_id;

            if ($this->alreadyPosted(\App\Models\ExpenseEntry::class, (int) $entry->id, ['expense_entry'])) {
                DB::commit();
                return true;
            }

            $period = $this->getCurrentPeriod();
            if (! $period) {
                throw new Exception('No open accounting period found');
            }

            $expenseAccount = $entry->gl_account_id
                ? $this->getGlAccount($entry->glAccount->account_number)
                : $this->getGlAccount('6000');

            $cashAccount = $entry->bankAccount?->glAccount ?? $this->getGlAccount('1050');

            $ref = $entry->reference ?? "EXP-{$entry->id}";

            $this->createEntry([
                'gl_account_id' => $expenseAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'expense_entry',
                'reference_type' => \App\Models\ExpenseEntry::class,
                'reference_id' => $entry->id,
                'reference_number' => $ref,
                'description' => $entry->description ?? "Expense Entry {$ref}",
                'debit' => $entry->amount,
                'credit' => 0,
                'entry_date' => $entry->entry_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'expense_entry',
                'reference_type' => \App\Models\ExpenseEntry::class,
                'reference_id' => $entry->id,
                'reference_number' => $ref,
                'description' => "Cash/Bank - {$entry->description}",
                'debit' => 0,
                'credit' => $entry->amount,
                'entry_date' => $entry->entry_date,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
            ])->post(auth()->id());

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('GL Posting Error - Expense Entry', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatchGlFailureNotification('expense_entry', (string) $entry->id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Select a department for a sale, falling back to the branch default.
     */
    protected function getDepartmentForSale(Sale $sale)
    {
        if ($sale->department) {
            return $sale->department;
        }

        return $sale->branch?->departments()?->first();
    }

    /**
     * Department-specific revenue account with fallback to default.
     */
    protected function getRevenueAccountForDepartment($department): GlAccount
    {
        if ($department && $department->revenueAccount) {
            return $department->revenueAccount;
        }

        return $this->getGlAccount('4000');
    }

    /**
     * Department-specific tax account with fallback to default.
     */
    protected function getTaxAccountForDepartment($department): GlAccount
    {
        if ($department && $department->taxAccount) {
            return $department->taxAccount;
        }

        return $this->getGlAccount('2020');
    }

    /**
     * Department-specific receivable account with fallback to default.
     */
    protected function getReceivableAccountForDepartment($department): GlAccount
    {
        if ($department && $department->receivableAccount) {
            return $department->receivableAccount;
        }

        return $this->getGlAccount('1100');
    }

    /**
     * Cash/bank account for a sale using explicit bank account, department defaults,
     * or payment method mapping as last resort.
     */
    protected function getCashAccountForSale(Sale $sale, $department, string $paymentMethod): GlAccount
    {
        if ($sale->bankAccount && $sale->bankAccount->glAccount) {
            return $sale->bankAccount->glAccount;
        }

        if ($department && $department->cashAccount) {
            return $department->cashAccount;
        }

        $cashAccountNumber = $this->getCashAccountNumberForPaymentMethod($paymentMethod);
        return $this->getGlAccount((string) $cashAccountNumber);
    }

    private function dispatchGlFailureNotification(string $entityType, string $entityId, string $error): void
    {
        $cacheKey = "gl_fail_notif_{$entityType}_{$entityId}";
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            $recipients = User::role(['Accountant', 'Accounting Manager'])
                ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
                ->get();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new GlPostingFailedNotification($entityType, $entityId, $error));
                Cache::put($cacheKey, true, 3600);
            }
        } catch (\Throwable $e) {
            \Log::warning('Could not dispatch GL failure notification', ['error' => $e->getMessage()]);
        }
    }
}
