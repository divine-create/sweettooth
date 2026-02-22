<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliationDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_reconciliation_id',
        'gl_entry_id',
        'daily_bank_transaction_id',
        'matched_amount',
        'matched_at',
        'matched_by_id',
        'match_type',
        'notes',
    ];

    protected $casts = [
        'matched_amount' => 'decimal:2',
        'matched_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(DailyBankTransaction::class, 'daily_bank_transaction_id');
    }

    /**
     * Scopes
     */
    public function scopeAuto($query)
    {
        return $query->where('match_type', 'auto');
    }

    public function scopeManual($query)
    {
        return $query->where('match_type', 'manual');
    }

    /**
     * Methods
     */
    public function isAmountMatched(): bool
    {
        $glAmount = 0;
        $bankAmount = 0;

        if ($this->glEntry) {
            $glAmount = $this->glEntry->debit + $this->glEntry->credit;
        }

        if ($this->bankTransaction) {
            $bankAmount = $this->bankTransaction->amount;
        }

        return abs($glAmount - $bankAmount) < 0.01;
    }
}
