<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchCustomerSupplierManagement extends Model
{
    protected $fillable = ['branch_id', 'local_customers', 'local_suppliers', 'is_overridden'];

    protected $casts = [
        'local_customers' => 'array',
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
        $globalSettings = GlobalCustomerSupplierManagement::first();

        return [
            'local_customers' => $branchSettings?->local_customers ?? $globalSettings?->customers ?? ['add', 'view'],
            'local_suppliers' => $branchSettings?->local_suppliers ?? $globalSettings?->suppliers ?? ['view'],
            'party_import' => $globalSettings?->party_import ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
