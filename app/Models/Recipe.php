<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Recipe extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'product_id',
        'product_name',
        'sku',
        'product_type_id',
        'cost_per_unit',
        'uom_id',
        'yield_quantity',
        'preparation_time',
        'instructions',
        'status',
        'is_wip',
        'is_active',
        'created_by_id',
        'created_by_type',
    ];

    protected $casts = [
        'product_type_id' => 'integer',
        'status' => 'string',
        'cost_per_unit' => 'decimal:4',
        'yield_quantity' => 'decimal:2',
        'is_wip' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id', 'id');
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

    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class)->withDefault();
    // }

    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function dailyProduces(): HasMany
    {
        return $this->hasMany(DailyProduce::class);
    }

    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class);
    }

    public function productionRequests(): HasMany
    {
        return $this->hasMany(ProductionRequest::class);
    }

    public function rawMaterialUtilizations(): HasMany
    {
        return $this->hasMany(RawMaterialUtilization::class);
    }

    /**
     * Calculate total ingredient cost for this recipe
     */
    public function calculateTotalIngredientCost(): float
    {
        $this->loadMissing('ingredients.item');

        return $this->ingredients->sum(function ($ingredient) {
            $unitCost = $this->resolveIngredientUnitCost($ingredient);

            return $ingredient->getActualQuantityNeeded() * $unitCost;
        });
    }

    /**
     * Calculate cost per unit of final product
     */
    public function calculateCostPerUnit(): float
    {
        $totalCost = $this->calculateTotalIngredientCost();
        $yieldQty = (float) $this->yield_quantity;

        if ($yieldQty <= 0) {
            return 0;
        }

        return $totalCost / $yieldQty;
    }

    /**
     * Calculate ingredients needed for a specific batch size
     */
    public function calculateIngredientsForBatch(int $batchSize): array
    {
        $this->loadMissing('ingredients.item');

        $ingredients = [];

        foreach ($this->ingredients as $ingredient) {
            $costPerUnit = $this->resolveIngredientUnitCost($ingredient);
            $quantityForBatch = $ingredient->getQuantityForBatchSize($batchSize);

            $ingredients[] = [
                'item_id' => $ingredient->item_id,
                'item_name' => $ingredient->item?->name ?? 'N/A',
                'quantity' => $quantityForBatch,
                'base_quantity' => (float) $ingredient->quantity,
                'uom_id' => $ingredient->uom_id,
                'uom_symbol' => $ingredient->unitOfMeasure?->symbol ?? 'N/A',
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $quantityForBatch * $costPerUnit,
                'waste_percentage' => (float) $ingredient->waste_percentage,
                'notes' => $ingredient->notes,
                'preparation_notes' => $ingredient->preparation_notes,
            ];
        }

        return $ingredients;
    }

    /**
     * Calculate ingredients needed for a specific production quantity (allows fractional batches)
     */
    public function calculateIngredientsForQuantity(float $plannedQuantity): array
    {
        $this->loadMissing('ingredients.item');

        $ingredients = [];
        $yieldQty = (float) $this->yield_quantity;

        if ($yieldQty <= 0) {
            return $ingredients;
        }

        $batchFactor = $plannedQuantity / $yieldQty;

        foreach ($this->ingredients as $ingredient) {
            $costPerUnit = $this->resolveIngredientUnitCost($ingredient);
            $quantityForBatchFactor = $ingredient->getQuantityForBatchFactor($batchFactor);

            $ingredients[] = [
                'item_id' => $ingredient->item_id,
                'item_name' => $ingredient->item?->name ?? 'N/A',
                'quantity' => $quantityForBatchFactor,
                'base_quantity' => (float) $ingredient->quantity,
                'uom_id' => $ingredient->uom_id,
                'uom_symbol' => $ingredient->unitOfMeasure?->symbol ?? 'N/A',
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $quantityForBatchFactor * $costPerUnit,
                'waste_percentage' => (float) $ingredient->waste_percentage,
                'notes' => $ingredient->notes,
                'preparation_notes' => $ingredient->preparation_notes,
            ];
        }

        return $ingredients;
    }

    /**
     * Calculate total cost for a specific batch size
     */
    public function calculateTotalCostForBatch(int $batchSize): float
    {
        $this->loadMissing('ingredients.item');

        return $this->ingredients->sum(function ($ingredient) use ($batchSize) {
            $unitCost = $this->resolveIngredientUnitCost($ingredient);

            return $ingredient->getQuantityForBatchSize($batchSize) * $unitCost;
        });
    }

    /**
     * Get recipe instructions as array
     */
    public function getInstructionsArray(): array
    {
        if (empty($this->instructions)) {
            return [];
        }

        $decoded = json_decode($this->instructions, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Calculate final yield for a batch size
     */
    public function calculateYieldForBatch(int $batchSize): float
    {
        return (float) $this->yield_quantity * $batchSize;
    }

    /**
     * Update ingredient costs from item purchase history
     */
    public function updateIngredientCostsFromItems(): void
    {
        $this->loadMissing('ingredients.item');

        foreach ($this->ingredients as $ingredient) {
            $itemUnitPrice = (float) ($ingredient->item?->unit_price ?? 0);
            if ($itemUnitPrice > 0) {
                $ingredient->update([
                    'cost_per_unit' => $itemUnitPrice
                ]);
                continue;
            }

            // Fallback to purchase history if item default price is not set.
            $averageCost = $ingredient->item?->purchaseItems()?->avg('cost_per_unit');

            if ($averageCost !== null && $averageCost > 0) {
                $ingredient->update([
                    'cost_per_unit' => $averageCost
                ]);
            } else {
                // If no purchase history, try to get from the most recent purchase
                $latestPurchaseItem = $ingredient->item?->purchaseItems()
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if ($latestPurchaseItem && $latestPurchaseItem->cost_per_unit > 0) {
                    $ingredient->update([
                        'cost_per_unit' => $latestPurchaseItem->cost_per_unit
                    ]);
                }
            }
        }
    }

    private function resolveIngredientUnitCost(RecipeIngredient $ingredient): float
    {
        $itemUnitPrice = (float) ($ingredient->item?->unit_price ?? 0);
        if ($itemUnitPrice > 0) {
            return $itemUnitPrice;
        }

        return (float) $ingredient->cost_per_unit;
    }
}
