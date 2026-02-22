<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'item_id',
        'requires_request',
        'quantity_requested',
        'quantity_approved',
        'quantity_dispatched',
        'uom_id',
        'notes',
    ];

    protected $casts = [
        'requires_request' => 'boolean',
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'quantity_dispatched' => 'decimal:2',
    ];

    public function getQuantityRequestedAttribute($value): float
    {
        $requested = (float) $value;
        if ($requested > 0) {
            return $requested;
        }

        $raw = $this->getAttributes();
        if (array_key_exists('quantity', $raw) && (float) $raw['quantity'] > 0) {
            return (float) $raw['quantity'];
        }

        return $requested;
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
     * Get the unit of measure
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Get available stock for this item
     */
    public function getAvailableStock(): float
    {
        $stock = Stock::where('item_id', $this->item_id)
            ->where('branch_id', $this->itemRequest->branch_id)
            ->first();

        if (!$stock) {
            return 0;
        }

        $available = max(0, (float) $stock->quantity_available);
        if (! $this->item || ! $this->uom_id || ! $this->item->uom_id) {
            return $available;
        }

        if ((int) $this->uom_id === (int) $this->item->uom_id) {
            return $available;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            $available,
            (int) $this->item->uom_id,
            (int) $this->uom_id,
            [
                'branch_id' => $this->itemRequest->branch_id,
                'item_id' => (int) $this->item_id,
            ]
        );

        return $converted ?? $available;
    }

    /**
     * Get remaining quantity to approve
     */
    public function getRemainingToApprove(): float
    {
        return $this->quantity_requested - $this->quantity_approved;
    }

    /**
     * Get remaining quantity to dispatch
     */
    public function getRemainingToDispatch(): float
    {
        return $this->quantity_approved - $this->quantity_dispatched;
    }

    /**
     * Check if detail is fully approved
     */
    public function isFullyApproved(): bool
    {
        return $this->quantity_approved >= $this->quantity_requested;
    }

    /**
     * Check if detail is fully dispatched
     */
    public function isFullyDispatched(): bool
    {
        return $this->quantity_dispatched >= $this->quantity_approved;
    }

    /**
     * Convert requested quantity from request UOM into item base UOM.
     */
    public function requestedQuantityInItemBaseUom(): float
    {
        return $this->convertQuantityToItemBase((float) $this->quantity_requested);
    }

    /**
     * Convert approved quantity from request UOM into item base UOM.
     */
    public function approvedQuantityInItemBaseUom(): float
    {
        return $this->convertQuantityToItemBase((float) $this->quantity_approved);
    }

    /**
     * Convert dispatched quantity from request UOM into item base UOM.
     */
    public function dispatchedQuantityInItemBaseUom(): float
    {
        return $this->convertQuantityToItemBase((float) $this->quantity_dispatched);
    }

    protected function convertQuantityToItemBase(float $quantity): float
    {
        if (! $this->item || ! $this->uom_id || ! $this->item->uom_id) {
            return $quantity;
        }

        if ((int) $this->uom_id === (int) $this->item->uom_id) {
            return $quantity;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            $quantity,
            (int) $this->uom_id,
            (int) $this->item->uom_id,
            [
                'branch_id' => $this->itemRequest?->branch_id ?? $this->item?->branch_id,
                'item_id' => (int) $this->item_id,
            ]
        );

        return $converted ?? $quantity;
    }
}
