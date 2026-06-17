<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionRecord extends Model
{
    protected $fillable = [
        'daily_produce_id',
        'recipe_id',
        'batch_number',
        'produced_by_id',
        'produced_by_type',
        'quantity_produced',
        'quantity_approved',
        'quantity_rejected',
        'quantity_sent_out',
        'quantity_for_order',
        'quantity_remaining',
        'unit_cost',
        'total_production_cost',
        'production_time',
        'quality_status',
        'dispatch_status',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'quantity_produced' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'quantity_sent_out' => 'decimal:2',
        'quantity_for_order' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'production_time' => 'datetime',
        'quality_status' => 'string',
        'dispatch_status' => 'string',
        'unit_cost' => 'decimal:2',
        'total_production_cost' => 'decimal:2',
    ];

    public function dailyProduce(): BelongsTo
    {
        return $this->belongsTo(DailyProduce::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function producedBy(): MorphTo
    {
        return $this->morphTo('produced_by', 'produced_by_type', 'produced_by_id');
    }

    /**
     * Calculate and update quantity remaining in this batch
     */
    public function updateQuantityRemaining(): void
    {
        $this->quantity_remaining = $this->quantity_approved - $this->quantity_sent_out - $this->quantity_for_order;

        // Update dispatch status
        if ($this->quantity_remaining <= 0) {
            $this->dispatch_status = 'fully_dispatched';
        } elseif ($this->quantity_sent_out > 0 || $this->quantity_for_order > 0) {
            $this->dispatch_status = 'partial';
        } else {
            $this->dispatch_status = 'available';
        }

        $this->save();
    }

    /**
     * Check if batch has available quantity
     */
    public function hasAvailable(): bool
    {
        return $this->quantity_remaining > 0;
    }
}
