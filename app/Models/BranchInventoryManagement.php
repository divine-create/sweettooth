<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInventoryManagement extends Model
{
    protected $table = 'branch_inventory_managements';

    protected $fillable = ['branch_id', 'local_stock', 'stock_adjustment', 'purchase_returns', 'supplier_link', 'low_stock_alert', 'csv_import', 'is_overridden'];

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
        $globalSettings = GlobalInventoryManagement::first();

        return [
            'local_stock' => $branchSettings?->local_stock ?? $globalSettings?->categories ?? ['add', 'edit', 'view'],
            'stock_adjustment' => $branchSettings?->stock_adjustment ?? $globalSettings?->stock_adjustment ?? 'local',
            'purchase_returns' => $branchSettings?->purchase_returns ?? $globalSettings?->purchase_returns ?? 'submit',
            'supplier_link' => $branchSettings?->supplier_link ?? $globalSettings?->supplier_management ?? ['view'],
            'low_stock_alert' => $branchSettings?->low_stock_alert ?? $globalSettings?->low_stock_alert ?? 'customize',
            'csv_import' => $branchSettings?->csv_import ?? $globalSettings?->import_csv ?? 'local',
            'categories' => $globalSettings?->categories ?? ['add', 'edit', 'delete'],
            'brands' => $globalSettings?->brands ?? ['add', 'edit', 'delete'],
            'products' => $globalSettings?->products ?? ['add', 'edit', 'sku_auto'],
            'multi_variant' => $globalSettings?->multi_variant ?? 'enabled',
            'expiry_tracking' => $globalSettings?->expiry_tracking ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
