<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyBankTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'daily_bank_transactions';

    protected $fillable = [
        'daily_bank_position_id',
        'bank_account_id',
        'transaction_type',
        'transaction_subtype',
        'amount',
        'description',
        'reference_number',
        'reference_type',
        'reference_id',
        'transaction_date',
        'cleared_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'cleared_date' => 'datetime',
    ];

    // Relationships
    public function dailyBankPosition(): BelongsTo
    {
        return $this->belongsTo(DailyBankPosition::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeInflows($query)
    {
        return $query->where('transaction_type', 'inflow');
    }

    public function scopeOutflows($query)
    {
        return $query->where('transaction_type', 'outflow');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_subtype', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCleared($query)
    {
        return $query->where('status', 'cleared');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate);
    }

    // Methods
    public function markCleared($clearedDate = null): bool
    {
        $this->status = 'cleared';
        $this->cleared_date = $clearedDate ?? now();
        return $this->save();
    }

    public function markReversed(): bool
    {
        $this->status = 'reversed';
        return $this->save();
    }

    public function isOverdue(): bool
    {
        if ($this->status === 'cleared') {
            return false;
        }

        // Consider pending for more than 7 days as overdue
        return $this->transaction_date->addDays(7)->isPast();
    }

    public function getDaysOutstanding(): int
    {
        $checkDate = $this->cleared_date ?? now();
        return $this->transaction_date->diffInDays($checkDate);
    }

    // Transaction type constants
    public const INFLOW = 'inflow';
    public const OUTFLOW = 'outflow';

    // Transaction subtypes (from Excel structure)
    public const POS_SALES = 'pos_sales';
    public const TRANSFER_SALES = 'transfer_sales';
    public const REVERSED_TRANSFER = 'reversed_transfer';
    public const OTHER_INCOME = 'other_income';
    public const CASH_DEPOSIT = 'cash_deposit';
    public const TRANSFER_EXPENSE = 'transfer_expense';
    public const CASH_WITHDRAWAL = 'cash_withdrawal';
    public const CHARGEBACK = 'chargeback';
    public const BANK_CHARGE = 'bank_charge';

    // Status constants
    public const PENDING = 'pending';
    public const CLEARED = 'cleared';
    public const REVERSED = 'reversed';
}
