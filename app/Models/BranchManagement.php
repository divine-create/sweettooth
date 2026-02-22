<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchManagement extends Model
{
    protected $fillable = ['branch_id', 'branch_details', 'operating_hours', 'tax_override', 'is_overridden'];

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
        $globalSettings = GlobalBranchManagement::first();

        return [
            'branch_details' => $branchSettings?->branch_details ?? $globalSettings?->branch_edit ?? 'enabled',
            'operating_hours' => $branchSettings?->operating_hours ?? $globalSettings?->branch_hours ?? 'set',
            'tax_override' => $branchSettings?->tax_override ?? 'view_only',
            'warehouse_add' => $globalSettings?->warehouse_add ?? 'enabled',
            'branch_admin_assign' => $globalSettings?->branch_admin_assign ?? 'enabled',
            'inter_branch_transfer' => $globalSettings?->inter_branch_transfer ?? 'enabled',
            'central_warehouse' => $globalSettings?->central_warehouse ?? 'enabled',
            'saas_tenant' => $globalSettings?->saas_tenant ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
