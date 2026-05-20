<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'item_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_damaged',
        'average_cost',
        'last_stock_take_date',
        'health_status',
        'expiry_date',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'quantity_damaged' => 'decimal:2',
        'average_cost' => 'decimal:4',
        'last_stock_take_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Accessor for backward compatibility
    public function getAvailableQuantityAttribute($value)
    {
        return $this->attributes['quantity_available'] ?? 0;
    }

    public function getReservedQuantityAttribute($value)
    {
        return $this->attributes['quantity_reserved'] ?? 0;
    }

    public function getTotalQuantityAttribute(): float
    {
        return (float) round(
            ((float) $this->quantity_available) + 
            ((float) $this->quantity_reserved) + 
            ((float) $this->quantity_damaged),
            2
        );
    }

    /**
     * Scope to filter stocks by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get the branch that owns the stock
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get all batches for this stock record
     */
    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Active batches ordered FEFO (earliest expiry first)
     */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class)
            ->where('status', 'active')
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc');
    }

    /**
     * Get the stock movements for this stock
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the health checks for this stock
     */
    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    /**
     * Check if stock is below reorder level
     */
    public function isBelowReorderLevel(): bool
    {
        return $this->item &&
               $this->item->reorder_level &&
               $this->quantity_available < $this->item->reorder_level;
    }

    /**
     * Check if stock exceeds max level
     */
    public function exceedsMaxLevel(): bool
    {
        return $this->item &&
               $this->item->max_stock_level &&
               $this->total_quantity > $this->item->max_stock_level;
    }

    /**
     * Update average cost using weighted average method
     */
    public function updateAverageCost(float $newQuantity, float $newCost): void
    {
        if ($newQuantity <= 0) {
            return;
        }

        $currentValue = $this->total_quantity * $this->average_cost;
        $newValue = $newQuantity * $newCost;
        $totalQuantity = $this->total_quantity + $newQuantity;

        if ($totalQuantity > 0) {
            $this->average_cost = ($currentValue + $newValue) / $totalQuantity;
            $this->save();
        }
    }

    /**
     * Get available stock converted from item base UOM to target UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     */
    public function getAvailableQuantityInUom(int|string|UnitOfMeasure $toUom): ?float
    {
        if (! $this->item || ! $this->item->uom_id) {
            return null;
        }

        return app(UomConversionService::class)->tryConvert(
            (float) $this->quantity_available,
            (int) $this->item->uom_id,
            $toUom,
            [
                'branch_id' => $this->branch_id,
                'item_id' => (int) $this->item_id,
            ]
        );
    }
}
