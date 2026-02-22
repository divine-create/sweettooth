<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchReportsAnalytics extends Model
{
    protected $fillable = ['branch_id', 'branch_reports', 'date_filter', 'export', 'is_overridden'];

    protected $casts = [
        'branch_reports' => 'array',
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
        $globalSettings = GlobalReportsAnalytics::first();

        return [
            'branch_reports' => $branchSettings?->branch_reports ?? $globalSettings?->reports ?? ['sales', 'stock'],
            'date_filter' => $branchSettings?->date_filter ?? $globalSettings?->custom_date_range ?? 'enabled',
            'export' => $branchSettings?->export ?? $globalSettings?->export ?? ['csv'],
            'multi_select_delete' => $globalSettings?->multi_select_delete ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
