<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpiryConfirmation extends Model
{
    protected $fillable = [
        'product_stock_id',
        'sales_shift_id',
        'confirmed_by_id',
        'confirmed_by_type',
        'action',
        'notes',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    // Relationships
    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function salesShift(): BelongsTo
    {
        return $this->belongsTo(SalesShift::class);
    }

    public function confirmedBy(): MorphTo
    {
        return $this->morphTo('confirmed_by', 'confirmed_by_type', 'confirmed_by_id');
    }

    // Helper Methods
    public function isConfirmedGood(): bool
    {
        return $this->action === 'confirmed_good';
    }

    public function isMarkedCallback(): bool
    {
        return $this->action === 'marked_callback';
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            'confirmed_good' => 'Confirmed Still Good',
            'marked_callback' => 'Marked as Callback',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionBadgeColor(): string
    {
        return match ($this->action) {
            'confirmed_good' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'marked_callback' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    // Scopes
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('sales_shift_id', $shiftId);
    }

    public function scopeConfirmedGood($query)
    {
        return $query->where('action', 'confirmed_good');
    }

    public function scopeMarkedCallback($query)
    {
        return $query->where('action', 'marked_callback');
    }
}
