<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\DailyBankPosition;
use App\Models\DailyBankTransaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BankPositionService
{
    /**
     * Create or update daily bank position for a bank account
     */
    public function createDailyPosition(BankAccount $bankAccount, Carbon $date): DailyBankPosition
    {
        return DB::transaction(function () use ($bankAccount, $date) {
            $position = DailyBankPosition::updateOrCreate(
                [
                    'bank_account_id' => $bankAccount->id,
                    'position_date' => $date,
                ],
                [
                    'opening_balance' => $this->getOpeningBalance($bankAccount, $date),
                    'inflows_total' => 0,
                    'outflows_total' => 0,
                    'unavailable_balance' => 0,
                    'available_balance' => 0,
                ]
            );

            $this->calculateDailyPosition($position);

            return $position;
        });
    }

    /**
     * Record an inflow transaction (POS, Transfer, Deposit, etc.)
     */
    public function recordInflow(
        BankAccount $bankAccount,
        string $subtype,
        float $amount,
        string $description,
        ?string $referenceNumber = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?Carbon $transactionDate = null
    ): DailyBankTransaction {
        $date = ($transactionDate ?? now())->toDateString();

        return DB::transaction(function () use (
            $bankAccount,
            $subtype,
            $amount,
            $description,
            $referenceNumber,
            $referenceType,
            $referenceId,
            $date
        ) {
            $position = $this->ensureDailyPosition($bankAccount, $date);

            $transaction = DailyBankTransaction::create([
                'daily_bank_position_id' => $position->id,
                'bank_account_id' => $bankAccount->id,
                'transaction_type' => DailyBankTransaction::INFLOW,
                'transaction_subtype' => $subtype,
                'amount' => $amount,
                'description' => $description,
                'reference_number' => $referenceNumber,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now(),
                'status' => DailyBankTransaction::PENDING,
            ]);

            // Update position totals
            $position->inflows_total = floatval($position->inflows_total) + $amount;
            $this->calculateDailyPosition($position);

            return $transaction;
        });
    }

    /**
     * Record an outflow transaction (Withdrawal, Transfer, Charges, etc.)
     */
    public function recordOutflow(
        BankAccount $bankAccount,
        string $subtype,
        float $amount,
        string $description,
        ?string $referenceNumber = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?Carbon $transactionDate = null
    ): DailyBankTransaction {
        $date = ($transactionDate ?? now())->toDateString();

        return DB::transaction(function () use (
            $bankAccount,
            $subtype,
            $amount,
            $description,
            $referenceNumber,
            $referenceType,
            $referenceId,
            $date
        ) {
            $position = $this->ensureDailyPosition($bankAccount, $date);

            $transaction = DailyBankTransaction::create([
                'daily_bank_position_id' => $position->id,
                'bank_account_id' => $bankAccount->id,
                'transaction_type' => DailyBankTransaction::OUTFLOW,
                'transaction_subtype' => $subtype,
                'amount' => $amount,
                'description' => $description,
                'reference_number' => $referenceNumber,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now(),
                'status' => DailyBankTransaction::PENDING,
            ]);

            // Update position totals
            $position->outflows_total = floatval($position->outflows_total) + $amount;
            $this->calculateDailyPosition($position);

            return $transaction;
        });
    }

    /**
     * Mark transaction as cleared (confirmed at bank)
     */
    public function clearTransaction(DailyBankTransaction $transaction, ?Carbon $clearedDate = null): bool
    {
        return DB::transaction(function () use ($transaction, $clearedDate) {
            $transaction->markCleared($clearedDate);

            // Update position if it's for today
            $position = $transaction->dailyBankPosition;
            $this->calculateDailyPosition($position);

            return true;
        });
    }

    /**
     * Record unreflected items (pending transfers, POS, etc.)
     */
    public function recordUnreflectedItems(
        DailyBankPosition $position,
        float $transfersBf = 0,
        float $posBf = 0,
        float $transfersCd = 0,
        float $posCd = 0
    ): DailyBankPosition {
        $position->unreflected_transfers_bf = $transfersBf;
        $position->unreflected_pos_bf = $posBf;
        $position->unreflected_transfers_cd = $transfersCd;
        $position->unreflected_pos_cd = $posCd;

        $this->calculateDailyPosition($position);

        return $position;
    }

    /**
     * Calculate daily position balances
     */
    public function calculateDailyPosition(DailyBankPosition $position): void
    {
        // Unavailable = Opening + Inflows - Outflows
        $position->unavailable_balance = floatval($position->opening_balance) +
                                        floatval($position->inflows_total) -
                                        floatval($position->outflows_total);

        // Available = Unavailable + Unreflected C/D items
        $position->available_balance = floatval($position->unavailable_balance) +
                                      floatval($position->unreflected_transfers_cd) +
                                      floatval($position->unreflected_pos_cd);

        $position->save();
    }

    /**
     * Reconcile daily position with GL balance
     */
    public function reconcilePosition(DailyBankPosition $position, float $actualGlBalance): bool
    {
        return DB::transaction(function () use ($position, $actualGlBalance) {
            // Check if balances match
            if (abs(floatval($position->available_balance) - $actualGlBalance) < 0.01) {
                // Balanced
                $position->variance_amount = 0;
                $position->variance_notes = 'Reconciled successfully';
                $position->markReconciled();

                return true;
            }

            // Variance found
            $variance = floatval($position->available_balance) - $actualGlBalance;
            $position->variance_amount = $variance;
            $position->variance_notes = "GL Balance: {$actualGlBalance}, Expected: {$position->available_balance}";
            $position->save();

            return false;
        });
    }

    /**
     * Get opening balance (from previous day's closing or bank account opening)
     */
    protected function getOpeningBalance(BankAccount $bankAccount, Carbon $date): float
    {
        $previousPosition = DailyBankPosition::where('bank_account_id', $bankAccount->id)
            ->where('position_date', '<', $date)
            ->orderBy('position_date', 'desc')
            ->first();

        if ($previousPosition) {
            return floatval($previousPosition->available_balance);
        }

        return floatval($bankAccount->opening_balance);
    }

    /**
     * Ensure daily position exists (create if not)
     */
    protected function ensureDailyPosition(BankAccount $bankAccount, string $date): DailyBankPosition
    {
        return DailyBankPosition::firstOrCreate(
            [
                'bank_account_id' => $bankAccount->id,
                'position_date' => $date,
            ],
            [
                'opening_balance' => $this->getOpeningBalance($bankAccount, Carbon::parse($date)),
                'inflows_total' => 0,
                'outflows_total' => 0,
            ]
        );
    }

    /**
     * Get bank position for a specific date range
     */
    public function getPositionsByDateRange(BankAccount $bankAccount, Carbon $start, Carbon $end)
    {
        return DailyBankPosition::where('bank_account_id', $bankAccount->id)
            ->whereBetween('position_date', [$start, $end])
            ->orderBy('position_date')
            ->get();
    }

    /**
     * Get summary of all bank positions for a date
     */
    public function getDailySummary(Carbon $date)
    {
        return DailyBankPosition::where('position_date', $date)
            ->with('bankAccount')
            ->get();
    }

    /**
     * Calculate total cash available across all banks
     */
    public function getTotalCashPosition(?Carbon $date = null): float
    {
        $date = $date ?? now();

        return DailyBankPosition::where('position_date', $date)
            ->sum('available_balance');
    }

    /**
     * Identify unreflected/pending items
     */
    public function getPendingItems(BankAccount $bankAccount, ?Carbon $date = null)
    {
        return DailyBankTransaction::where('bank_account_id', $bankAccount->id)
            ->where('status', DailyBankTransaction::PENDING);
    }

    /**
     * Get aging of unreflected items
     */
    public function getUnreflectedAging(BankAccount $bankAccount)
    {
        return DailyBankTransaction::where('bank_account_id', $bankAccount->id)
            ->where('status', DailyBankTransaction::PENDING)
            ->where('transaction_date', '<', now()->subDays(7))
            ->orderBy('transaction_date')
            ->get();
    }
}
