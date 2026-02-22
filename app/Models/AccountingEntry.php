<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    protected $fillable = [
        'branch_id',
        'accounting_period_id',
        'entry_type',
        'entry_date',
        'description',
        'reference',
        'amount',
        'debit_gl_account_id',
        'credit_gl_account_id',
        'bank_account_id',
        'source',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function debitGlAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'debit_gl_account_id');
    }

    public function creditGlAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'credit_gl_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
