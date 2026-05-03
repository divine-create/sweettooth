<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStoreStock extends Model
{
    protected $table = 'production_store_stocks';

    protected $fillable = [
        'store_id',
        'item_id',
        'quantity_available',
        'quantity_reserved',
        'average_cost',
        'last_stock_take_date',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'average_cost' => 'decimal:4',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ProductionStore::class, 'store_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the material (Item or Product/WIP) for this stock record.
     */
    public function getMaterialAttribute()
    {
        if (is_numeric($this->item_id)) {
            return $this->item;
        }
        
        return \App\Models\Product::find($this->item_id);
    }

    public function getMaterialNameAttribute(): string
    {
        return $this->material->name ?? 'Unknown Item';
    }

    public function getMaterialSkuAttribute(): string
    {
        return $this->material->sku ?? 'N/A';
    }

    public function getMaterialUomAttribute(): string
    {
        return $this->material->unitOfMeasure->symbol ?? 'units';
    }

    public function getQuantityAttribute(): float
    {
        return ($this->quantity_available ?? 0) - ($this->quantity_reserved ?? 0);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity_available ?? 0;
    }
}