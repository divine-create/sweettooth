<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPayment extends Model
{
    protected $fillable = [
        'branch_id',
        'tax_type',
        'amount',
        'payment_date',
        'bank_account_id',
        'reference_number',
        'status',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'gl_posted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function approvedBy()
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
