<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchBusinessConfiguration extends Model
{
    protected $fillable = ['branch_id', 'company_name', 'logo_upload', 'contact_details', 'business_type', 'storage_settings', 'subscription_plan', 'is_overridden'];

    protected $casts = [
        'contact_details' => 'array',
        'business_type' => 'array',
        'storage_settings' => 'array',
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
        $globalSettings = GlobalBusinessConfiguration::first();

        return [
            'company_name' => $branchSettings?->company_name ?? $globalSettings?->company_name ?? 'Your Business Name',
            'logo_upload' => $branchSettings?->logo_upload ?? $globalSettings?->logo_upload ?? 'enabled',
            'contact_details' => $branchSettings?->contact_details ?? $globalSettings?->contact_details ?? ['phone', 'email', 'website', 'vat_number'],
            'business_type' => $branchSettings?->business_type ?? $globalSettings?->business_type ?? ['retail', 'wholesale', 'services'],
            'storage_settings' => $branchSettings?->storage_settings ?? $globalSettings?->storage_settings ?? ['local', 's3'],
            'subscription_plan' => $branchSettings?->subscription_plan ?? $globalSettings?->subscription_plan ?? 'basic',
            'appearance_settings' => $globalSettings?->appearance_settings ?? [],
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
