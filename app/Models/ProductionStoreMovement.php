<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionStoreMovement extends Model
{
    protected $table = 'production_store_movements';

    protected $fillable = [
        'store_id',
        'item_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'moved_by_id',
        'moved_by_type',
        'movement_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'movement_date' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ProductionStore::class, 'store_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference_type', 'reference_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo('moved_by_type', 'moved_by_id');
    }

    public function getActorNameAttribute(): string
    {
        $actor = $this->actor;
        return $actor?->name ?? $actor?->full_name ?? 'Unknown';
    }

    public function getItemNameAttribute(): string
    {
        return $this->item?->name ?? 'Unknown';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'Stock In',
            'out' => 'Stock Out',
            'adjustment' => 'Adjustment',
            'transfer' => 'Transfer',
            'damaged' => 'Damaged',
            'return' => 'Return',
            default => $this->type,
        };
    }
}