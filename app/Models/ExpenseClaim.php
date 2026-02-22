<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'claim_date',
        'total_amount',
        'status',
        'description',
        'approved_by',
        'approved_at',
        'paid_via_bank_account_id',
        'gl_posting_status',
        'gl_entry_id',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'gl_posted_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidViaBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'paid_via_bank_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseClaimItem::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function calculateTotal(): float
    {
        return $this->items()->sum('amount');
    }

    public function updateTotal(): void
    {
        $this->update(['total_amount' => $this->calculateTotal()]);
    }
}
