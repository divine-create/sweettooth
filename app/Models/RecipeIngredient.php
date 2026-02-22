<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'item_id',
        'quantity',
        'uom_id',
        'cost_per_unit',
        'waste_percentage',
        'sort_order',
        'notes',
        'preparation_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'waste_percentage' => 'decimal:2',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Accessor for UOM symbol
     */
    protected function uomSymbol(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->unitOfMeasure?->symbol ?? 'N/A',
        );
    }

    /**
     * Accessor for UOM (alias for uomSymbol for compatibility, mapped to enum values)
     */
    protected function uom(): Attribute
    {
        return Attribute::make(
            get: function() {
                $symbol = $this->unitOfMeasure?->symbol ?? 'g';
                $mapping = [
                    'g' => 'grams',
                    'L' => 'liters',
                    'ml' => 'ml',
                    'kg' => 'kg',
                    'pcs' => 'pcs',
                    'unit' => 'units',
                ];
                return $mapping[$symbol] ?? 'grams'; // Default to grams
            },
        );
    }

    /**
     * Calculate the actual quantity needed including waste
     */
    public function getActualQuantityNeeded(): float
    {
        $baseQuantity = (float) $this->quantity;
        $wastePercent = (float) $this->waste_percentage;

        if ($wastePercent > 0) {
            return $baseQuantity * (1 + ($wastePercent / 100));
        }

        return $baseQuantity;
    }

    /**
     * Calculate quantity needed in the linked item's base UOM.
     */
    public function getActualQuantityNeededInItemUom(): float
    {
        $quantity = $this->getActualQuantityNeeded();
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
                'branch_id' => $this->recipe?->branch_id ?: $this->item->branch_id,
                'item_id' => (int) $this->item_id,
            ]
        );

        return $converted ?? $quantity;
    }

    /**
     * Calculate the total cost for this ingredient
     */
    public function getTotalCost(): float
    {
        return $this->getActualQuantityNeeded() * (float) $this->cost_per_unit;
    }

    /**
     * Calculate ingredient cost for a specific batch size
     */
    public function getCostForBatchSize(int $batchSize): float
    {
        return $this->getTotalCost() * $batchSize;
    }

    /**
     * Calculate quantity needed for a specific batch size
     */
    public function getQuantityForBatchSize(int $batchSize): float
    {
        return $this->getActualQuantityNeeded() * $batchSize;
    }

    /**
     * Calculate quantity needed for a batch in item base UOM.
     */
    public function getQuantityForBatchSizeInItemUom(int $batchSize): float
    {
        return $this->getActualQuantityNeededInItemUom() * $batchSize;
    }

    /**
     * Calculate ingredient cost for a fractional batch factor
     */
    public function getCostForBatchFactor(float $batchFactor): float
    {
        return $this->getTotalCost() * $batchFactor;
    }

    /**
     * Calculate quantity needed for a fractional batch factor
     */
    public function getQuantityForBatchFactor(float $batchFactor): float
    {
        return $this->getActualQuantityNeeded() * $batchFactor;
    }

    /**
     * Calculate quantity needed for a fractional batch in item base UOM.
     */
    public function getQuantityForBatchFactorInItemUom(float $batchFactor): float
    {
        return $this->getActualQuantityNeededInItemUom() * $batchFactor;
    }
}
