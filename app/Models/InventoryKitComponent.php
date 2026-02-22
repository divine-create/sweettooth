<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryKitComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_kit_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function inventoryKit(): BelongsTo
    {
        return $this->belongsTo(InventoryKit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->quantity * ($this->product->cost_price ?? 0);
    }
}
