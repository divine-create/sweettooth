<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'reconciliation_date',
        'bank_balance',
        'book_balance',
        'difference',
        'status',
        'completed_at',
        'completed_by_id',
        'notes',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'bank_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(BankReconciliationDetail::class);
    }

    public function unmatchedItems(): HasMany
    {
        return $this->hasMany(BankReconciliationUnmatched::class);
    }

    /**
     * Scopes
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Methods
     */
    public function isBalanced(): bool
    {
        return abs($this->difference) < 0.01;
    }

    public function markCompleted($userId = null): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->completed_by_id = $userId ?? auth()->id();
        $this->save();
    }

    public function getMatchedCount(): int
    {
        return $this->details()->count();
    }

    public function getUnmatchedCount(): int
    {
        return $this->unmatchedItems()
            ->where('resolution_status', 'pending')
            ->count();
    }

    public function getMatchedAmount(): float
    {
        return $this->details()->sum('matched_amount');
    }
}
