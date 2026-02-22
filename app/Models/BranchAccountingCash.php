<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAccountingCash extends Model
{
    protected $fillable = ['branch_id', 'local_expenses', 'cash_transactions', 'is_overridden'];

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
        $globalSettings = GlobalAccountingCash::first();

        return [
            'local_expenses' => $branchSettings?->local_expenses ?? $globalSettings?->expenses_categories ?? ['add'],
            'cash_transactions' => $branchSettings?->cash_transactions ?? $globalSettings?->cash_bank ?? 'track',
            'profit_loss_reports' => $globalSettings?->profit_loss_reports ?? 'by_date',
            'accounting_entries' => $globalSettings?->accounting_entries ?? 'auto',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
