<?php

namespace App\Services;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\Sale;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Post a sale transaction to GL
     * Creates revenue entry and AR/Cash entry
     */
    public function postSaleTransaction(Sale $sale): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            // Get department and validate GL accounts
            $department = $sale->department ?? $sale->branch->departments()->first();

            if ($department) {
                $glIssues = $this->validateDepartmentGlAccounts($department);
                if (!empty($glIssues)) {
                    Log::warning('GL account configuration issues for department', [
                        'department_id' => $department->id,
                        'department_name' => $department->name,
                        'issues' => $glIssues,
                        'sale_id' => $sale->id
                    ]);

                    // Try to setup defaults if no accounts configured
                    if (count($glIssues) >= 4) { // All accounts missing
                        Log::info('Setting up default GL accounts for department', [
                            'department_id' => $department->id
                        ]);
                        $this->setupDefaultDepartmentGlAccounts($department);
                    }
                }
            }

            // Get GL accounts - department-specific or fallback to defaults
            $revenueAccount = $this->getRevenueAccount($sale, $department);
            $arAccount = $this->getReceivableAccount($department);
            $cashAccount = $this->getCashAccount($department);

            $amount = (float) $sale->total;

            // Entry 1: Debit AR or Cash, Credit Revenue
            $entryType = $sale->isFullyPaid() ? 'sale' : 'sale';

            if ($sale->isFullyPaid()) {
                // Cash sale
                $this->createEntry([
                    'gl_account_id' => $cashAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'description' => "Sale #{$sale->sale_number}",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $sale->sale_time,
                    'branch_id' => $sale->branch_id,
                ]);

                $this->createEntry([
                    'gl_account_id' => $revenueAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'description' => "Sale Revenue #{$sale->sale_number}",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $sale->sale_time,
                    'branch_id' => $sale->branch_id,
                ]);
            } else {
                // Credit sale
                $this->createEntry([
                    'gl_account_id' => $arAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'description' => "Credit Sale #{$sale->sale_number}",
                    'debit' => $amount,
                    'credit' => 0,
                    'entry_date' => $sale->sale_time,
                    'branch_id' => $sale->branch_id,
                ]);

                $this->createEntry([
                    'gl_account_id' => $revenueAccount->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'description' => "Sale Revenue #{$sale->sale_number}",
                    'debit' => 0,
                    'credit' => $amount,
                    'entry_date' => $sale->sale_time,
                    'branch_id' => $sale->branch_id,
                ]);
            }

            // Update sale record with GL posting status
            $sale->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Sale GL posting failed: ' . $e->getMessage(), [
                'sale_id' => $sale->id,
                'error' => $e,
            ]);

            $sale->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Post a payment transaction to GL
     * Creates cash/bank entry and AR reduction or revenue entry
     */
    public function postPaymentTransaction(Payment $payment): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $amount = (float) $payment->amount;
            $paymentMethod = strtolower($payment->payment_method);

            // Determine which account to debit based on payment method
            if ($paymentMethod === 'cash') {
                $cashAccount = GlAccount::where('account_number', '1101')->firstOrFail();
            } elseif ($paymentMethod === 'pos' || $paymentMethod === 'transfer') {
                $cashAccount = GlAccount::where('account_number', '1110')->firstOrFail();
            } else {
                throw new Exception('Unknown payment method: ' . $payment->payment_method);
            }

            // AR reduction
            $arAccount = GlAccount::where('account_number', '1200')->firstOrFail();

            // Entry: Debit Cash, Credit AR
            $this->createEntry([
                'gl_account_id' => $cashAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payment',
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'description' => "Payment received - {$payment->payment_method}",
                'debit' => $amount,
                'credit' => 0,
                'entry_date' => $payment->payment_time,
                'branch_id' => $payment->branch_id,
            ]);

            $this->createEntry([
                'gl_account_id' => $arAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'payment',
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'description' => "AR reduction - {$payment->payment_method}",
                'debit' => 0,
                'credit' => $amount,
                'entry_date' => $payment->payment_time,
                'branch_id' => $payment->branch_id,
            ]);

            // Update payment record
            $payment->update([
                'gl_posting_status' => 'posted',
                'gl_posted_at' => now(),
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment GL posting failed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e,
            ]);

            $payment->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Post COGS for sold inventory
     */
    public function postCogsSale(Sale $sale, $cogsAmount): bool
    {
        try {
            DB::beginTransaction();

            $period = $this->getCurrentPeriod();
            if (!$period || $period->status !== 'open') {
                throw new Exception('No open accounting period found');
            }

            $cogsAccount = GlAccount::where('account_number', '5110')->firstOrFail();
            $fgAccount = GlAccount::where('account_number', '1330')->firstOrFail();

            $amount = (float) $cogsAmount;

            // Debit COGS, Credit FG Inventory
            $this->createEntry([
                'gl_account_id' => $cogsAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'sale_cogs',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'description' => "COGS for Sale #{$sale->sale_number}",
                'debit' => $amount,
                'credit' => 0,
                'entry_date' => $sale->sale_time,
                'branch_id' => $sale->branch_id,
            ]);

            $this->createEntry([
                'gl_account_id' => $fgAccount->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'sale_cogs',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'description' => "FG reduction for Sale #{$sale->sale_number}",
                'debit' => 0,
                'credit' => $amount,
                'entry_date' => $sale->sale_time,
                'branch_id' => $sale->branch_id,
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('COGS GL posting failed: ' . $e->getMessage(), [
                'sale_id' => $sale->id,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * Create a GL entry and post it
     */
    private function createEntry(array $data): GlEntry
    {
        $entry = GlEntry::create([
            ...$data,
            'status' => 'draft',
            'entered_by_id' => auth()->id() ?? null,
            'entered_by_type' => auth()->user() ? get_class(auth()->user()) : null,
        ]);

        // Auto-post the entry
        $entry->post(auth()->id() ?? 1);

        return $entry;
    }

    /**
     * Get current open accounting period
     */
    public function getCurrentPeriod(): ?AccountingPeriod
    {
        return AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();
    }

    /**
     * Get revenue GL account based on sale attributes
     */
    private function getRevenueAccount(Sale $sale, $department = null): GlAccount
    {
        // Try department-specific revenue account first
        if ($department && $department->revenueAccount) {
            return $department->revenueAccount;
        }

        // Fallback to default revenue account
        return GlAccount::where('account_number', '4110')->firstOrFail();
    }

    private function getReceivableAccount($department = null): GlAccount
    {
        // Try department-specific receivable account first
        if ($department && $department->receivableAccount) {
            return $department->receivableAccount;
        }

        // Fallback to default AR account
        return GlAccount::where('account_number', '1200')->firstOrFail();
    }

    private function getCashAccount($department = null): GlAccount
    {
        // Try department-specific cash account first
        if ($department && $department->cashAccount) {
            return $department->cashAccount;
        }

        // Fallback to default cash account
        return GlAccount::where('account_number', '1110')->firstOrFail();
    }

    private function getTaxAccount($department = null): GlAccount
    {
        // Try department-specific tax account first
        if ($department && $department->taxAccount) {
            return $department->taxAccount;
        }

        // Fallback to default tax account
        return GlAccount::where('account_number', '2100')->firstOrFail();
    }

    /**
     * Validate that required GL accounts are configured for a department
     */
    public function validateDepartmentGlAccounts($department): array
    {
        $issues = [];

        if (!$department) {
            $issues[] = 'No department specified';
            return $issues;
        }

        $requiredAccounts = [
            'revenue_account_id' => 'Revenue Account',
            'receivable_account_id' => 'Accounts Receivable',
            'cash_account_id' => 'Cash Account',
            'tax_account_id' => 'Tax Account',
        ];

        foreach ($requiredAccounts as $field => $name) {
            if (!$department->$field) {
                $issues[] = "{$name} not configured for department '{$department->name}'";
            } else {
                $account = GlAccount::find($department->$field);
                if (!$account) {
                    $issues[] = "{$name} (ID: {$department->$field}) not found in GL accounts";
                } elseif (!$account->is_active) {
                    $issues[] = "{$name} '{$account->account_name}' is inactive";
                }
            }
        }

        return $issues;
    }

    /**
     * Setup default GL accounts for a department
     */
    public function setupDefaultDepartmentGlAccounts($department): bool
    {
        try {
            // Get default accounts
            $defaults = [
                'revenue_account_id' => GlAccount::where('account_number', '4110')->value('id'),
                'receivable_account_id' => GlAccount::where('account_number', '1200')->value('id'),
                'cash_account_id' => GlAccount::where('account_number', '1110')->value('id'),
                'tax_account_id' => GlAccount::where('account_number', '2100')->value('id'),
            ];

            $department->update($defaults);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to setup default GL accounts for department', [
                'department_id' => $department->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all GL accounts for a period
     */
    public function getAccountsForPeriod(AccountingPeriod $period): array
    {
        return GlAccount::where('is_active', true)
            ->where('is_header', false)
            ->get()
            ->map(function (GlAccount $account) use ($period) {
                $entries = GlEntry::where('gl_account_id', $account->id)
                    ->where('accounting_period_id', $period->id)
                    ->where('status', 'posted')
                    ->get();

                return [
                    'account' => $account,
                    'debit_total' => $entries->sum('debit'),
                    'credit_total' => $entries->sum('credit'),
                    'balance' => $account->getBalance(),
                ];
            })
            ->toArray();
    }

    /**
     * Get trial balance for a period
     */
    public function getTrialBalance(AccountingPeriod $period): array
    {
        $accounts = $this->getAccountsForPeriod($period);

        return [
            'total_debits' => collect($accounts)->sum('debit_total'),
            'total_credits' => collect($accounts)->sum('credit_total'),
            'accounts' => $accounts,
        ];
    }
}
