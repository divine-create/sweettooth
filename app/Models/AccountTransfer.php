<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_bank_account_id',
        'to_bank_account_id',
        'transfer_date',
        'amount',
        'reference',
        'notes',
        'branch_id',
        'created_by',
        'gl_posting_status',
        'gl_entry_id',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'gl_posted_at' => 'datetime',
    ];

    public function fromBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
    }

    public function toBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function isPosted(): bool
    {
        return $this->gl_posting_status === 'posted';
    }

    public function isPending(): bool
    {
        return $this->gl_posting_status === 'pending';
    }
}
