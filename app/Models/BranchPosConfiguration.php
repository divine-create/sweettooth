<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPosConfiguration extends Model
{
    protected $fillable = ['branch_id', 'pos_use', 'payment_modes', 'receipt_custom', 'is_overridden'];

    protected $casts = [
        'is_overridden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function getEffectiveSettings(int $branchId): array
    {
        $branchSettings = self::where('branch_id', $branchId)->first();
        $globalSettings = GlobalPosConfiguration::first();

        return [
            'pos_use' => $branchSettings?->pos_use ?? $globalSettings?->pos_interface ?? 'enabled',
            'payment_modes' => $branchSettings?->payment_modes ?? $globalSettings?->payment_modes ?? ['apply'],
            'receipt_custom' => $branchSettings?->receipt_custom ?? $globalSettings?->receipt_template ?? 'limited',
            'sales_returns' => $globalSettings?->sales_returns ?? 'enabled',
            'offline_mode' => $globalSettings?->offline_mode ?? 'enabled',
            'online_shop_sync' => $globalSettings?->online_shop_sync ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
