<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_take_id',
        'item_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'variance_type',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    /**
     * Get the stock take
     */
    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Calculate variance
     */
    public function calculateVariance(): void
    {
        $this->variance = $this->physical_quantity - $this->system_quantity;

        if ($this->variance > 0) {
            $this->variance_type = 'surplus';
        } elseif ($this->variance < 0) {
            $this->variance_type = 'shortage';
        } else {
            $this->variance_type = 'match';
        }

        $this->save();
    }

    /**
     * Get variance percentage
     */
    public function getVariancePercentage(): float
    {
        if ($this->system_quantity == 0) {
            return 0;
        }

        return ($this->variance / $this->system_quantity) * 100;
    }

    /**
     * Check if variance is significant (more than 5%)
     */
    public function hasSignificantVariance(): bool
    {
        return abs($this->getVariancePercentage()) > 5;
    }
}
