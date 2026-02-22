<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'branch_id',
        'gl_account_id',
        'accounting_period_id',
        'planned_amount',
        'forecast_amount',
        'category',
        'is_approved',
        'description',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'created_by_id',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'forecast_amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function approvedBy()
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
