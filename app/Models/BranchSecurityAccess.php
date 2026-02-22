<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSecurityAccess extends Model
{
    protected $fillable = ['branch_id', 'local_logs', 'is_overridden'];

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
        $globalSettings = GlobalSecurityAccess::first();

        return [
            'local_logs' => $branchSettings?->local_logs ?? $globalSettings?->audit_logs ?? 'view',
            'authentication' => $globalSettings?->authentication ?? '2fa',
            'data_isolation' => $globalSettings?->data_isolation ?? 'saas_company',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
