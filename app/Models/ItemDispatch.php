<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'request_id',
        'item_id',
        'dispatched_by_id',
        'dispatched_by_type',
        'received_by_id',
        'received_by_type',
        'quantity',
        'uom',
        'uom_id',
        'dispatch_time',
        'received_time',
        'shift',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'dispatch_time' => 'datetime',
        'received_time' => 'datetime',
    ];

    /**
     * Scope to filter by shift
     */
    public function scopeForShift($query, string $shift)
    {
        return $query->where('shift', $shift);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('dispatch_time', [$startDate, $endDate]);
    }

    /**
     * Get the item request
     */
    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class, 'request_id');
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the UOM (new canonical UOM reference).
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Get the employee who dispatched
     */
    public function dispatcher(): MorphTo
    {
        return $this->morphTo('dispatched_by', 'dispatched_by_type', 'dispatched_by_id');
    }

    /**
     * Get the employee who received
     */
    public function receiver(): MorphTo
    {
        return $this->morphTo('received_by', 'received_by_type', 'received_by_id');
    }

    /**
     * Check if dispatch has been received
     */
    public function isReceived(): bool
    {
        return ! is_null($this->received_time);
    }

    /**
     * Mark as received
     */
    public function markAsReceived(): void
    {
        $this->recei = now();
        $this->save();
    }
}
