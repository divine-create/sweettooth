<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchEmployeeManagement extends Model
{
    protected $fillable = ['branch_id', 'branch_staff', 'permissions_local', 'pin_assign', 'is_overridden'];

    protected $casts = [
        'branch_staff' => 'array',
        'permissions_local' => 'array',
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
        $globalSettings = GlobalEmployeeManagement::first();

        return [
            'branch_staff' => $branchSettings?->branch_staff ?? $globalSettings?->staff_profiles ?? ['add', 'edit'],
            'permissions_local' => $branchSettings?->permissions_local ?? $globalSettings?->permissions ?? ['pos', 'local_reports'],
            'pin_assign' => $branchSettings?->pin_assign ?? $globalSettings?->pin_login ?? 'enabled',
            'roles' => $globalSettings?->roles ?? ['create', 'edit'],
            'departments' => $globalSettings?->departments ?? ['sales', 'warehouse'],
            'shift_scheduling' => $globalSettings?->shift_scheduling ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
