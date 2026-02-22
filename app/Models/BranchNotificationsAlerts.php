<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchNotificationsAlerts extends Model
{
    protected $fillable = ['branch_id', 'alerts', 'is_overridden'];

    protected $casts = [
        'alerts' => 'array',
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
        $globalSettings = GlobalNotificationsAlerts::first();

        return [
            'alerts' => $branchSettings?->alerts ?? $globalSettings?->alerts ?? ['email', 'sms'],
            'task_todo' => $globalSettings?->task_todo ?? 'enabled',
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
