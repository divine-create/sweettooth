<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseEntry extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'bank_account_id',
        'gl_account_id',
        'entry_date',
        'description',
        'source',
        'reference',
        'amount',
        'notes',
        'created_by_id',
        'gl_posting_status',
        'gl_posted_at',
        'gl_posting_error',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
