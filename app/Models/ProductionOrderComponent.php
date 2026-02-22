<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_id',
        'quantity_required',
        'quantity_used',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'quantity_used' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($component) {
            // Auto-calculate costs
            if (!$component->unit_cost && $component->product) {
                $component->unit_cost = $component->product->cost_price ?? 0;
            }
            $component->total_cost = $component->quantity_required * $component->unit_cost;
        });

        static::saved(function ($component) {
            $component->productionOrder->updateCosts();
        });

        static::deleted(function ($component) {
            $component->productionOrder->updateCosts();
        });
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getRemainingToUseAttribute(): float
    {
        return max(0, $this->quantity_required - $this->quantity_used);
    }

    public function isFullyUsed(): bool
    {
        return $this->quantity_used >= $this->quantity_required;
    }

    public function isAvailable(): bool
    {
        $available = $this->product->quantity ?? 0;
        return $available >= $this->quantity_required;
    }
}
