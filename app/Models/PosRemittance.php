<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosRemittance extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'bank_account_id',
        'amount',
        'remitted_at',
        'reference',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remitted_at' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
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
