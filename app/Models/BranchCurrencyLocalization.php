<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchCurrencyLocalization extends Model
{
    protected $fillable = ['branch_id', 'currency_display', 'language', 'units_local', 'is_overridden'];

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
        $globalSettings = GlobalCurrencyLocalization::first();

        return [
            'currency_display' => $branchSettings?->currency_display === 'inherit' ? ($globalSettings?->primary_currency ?? 'NGN') : ($branchSettings?->currency_display ?? $globalSettings?->primary_currency ?? 'NGN'),
            'language' => $branchSettings?->language === 'inherit' ? ($globalSettings?->default_language ?? 'en') : ($branchSettings?->language ?? $globalSettings?->default_language ?? 'en'),
            'units_local' => $branchSettings?->units_local === 'inherit' ? ($globalSettings?->units_of_measure ?? ['piece', 'kg', 'liter']) : ($branchSettings?->units_local ?? $globalSettings?->units_of_measure ?? ['piece', 'kg', 'liter']),
            'multi_currency' => $globalSettings?->multi_currency ?? 'enabled',
            'currency_list' => $globalSettings?->currency_list ?? ['USD', 'EUR', 'GBP', 'INR', 'NGN'],
            'multi_tax' => $globalSettings?->multi_tax ?? 'enabled',
            'language_options' => $globalSettings?->language_options ?? ['en', 'es', 'fr', 'ar'],
            'date_format' => $globalSettings?->date_format ?? 'MM/DD/YYYY',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
