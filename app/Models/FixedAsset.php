<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $fillable = [
        'branch_id',
        'asset_name',
        'asset_tag',
        'asset_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'acquisition_date',
        'funding_source',
        'bank_account_id',
        'accumulated_depreciation',
        'is_active',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
        'created_by_id',
    ];

    protected $casts = [
        'asset_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'acquisition_date' => 'date',
        'is_active' => 'boolean',
        'gl_posted_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function monthlyDepreciationAmount(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        $depreciableAmount = max(0, (float) $this->asset_cost - (float) $this->salvage_value);
        return $depreciableAmount / (float) $this->useful_life_months;
    }
}
