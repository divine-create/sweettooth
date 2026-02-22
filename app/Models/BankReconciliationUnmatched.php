<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankReconciliationUnmatched extends Model
{
    use HasFactory;

    protected $table = 'bank_reconciliation_unmatched';

    protected $fillable = [
        'bank_reconciliation_id',
        'item_type',
        'item_id',
        'amount',
        'transaction_date',
        'reference_number',
        'description',
        'notes',
        'resolution_status',
        'resolved_at',
        'resolved_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    /**
     * Get the polymorphic item
     */
    public function getItem()
    {
        if ($this->item_type === 'gl_entry') {
            return GlEntry::find($this->item_id);
        } elseif ($this->item_type === 'bank_transaction') {
            return DailyBankTransaction::find($this->item_id);
        }
        return null;
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('resolution_status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('resolution_status', 'resolved');
    }

    public function scopeGlEntries($query)
    {
        return $query->where('item_type', 'gl_entry');
    }

    public function scopeBankTransactions($query)
    {
        return $query->where('item_type', 'bank_transaction');
    }

    /**
     * Methods
     */
    public function markResolved($userId = null): void
    {
        $this->resolution_status = 'resolved';
        $this->resolved_at = now();
        $this->resolved_by_id = $userId ?? auth()->id();
        $this->save();
    }

    public function markIgnored(): void
    {
        $this->resolution_status = 'ignored';
        $this->save();
    }
}
