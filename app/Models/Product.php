<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'product_type_id',
        'department_id',
        'sales_department_id',
        'category_id',
        'description',
        'price',
        'cost',
        'shelf_life_days',
        'uom_id',
        'sales_uom_id',
        'sales_unit_weight',
        'unit_weight',
        'recipe_yield',
        'recipe_yield_weight',
        'yield_percentage',
        'is_active',
        'is_available',
        'image_url',
        'allergens',
        'tags',
        'branch_id',
        'linked_item_id',
    ];

    protected $casts = [
        'sales_department_id' => 'integer',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'shelf_life_days' => 'integer',
        'uom_id' => 'integer',
        'sales_uom_id' => 'integer',
        'sales_unit_weight' => 'decimal:4',
        'unit_weight' => 'decimal:2',
        'recipe_yield' => 'decimal:2',
        'recipe_yield_weight' => 'decimal:2',
        'yield_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'allergens' => 'array',
        'tags' => 'array',
    ];

    /**
     * Scope to filter active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter available products
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to filter by product type
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('product_type_id', $typeId);
    }

    /**
     * Get the inventory item this product is sourced from (for direct inventory sales).
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'linked_item_id');
    }

    /**
     * Whether this product draws stock from a linked inventory item rather than production dispatch.
     */
    public function isSoldFromInventory(): bool
    {
        return $this->linked_item_id !== null;
    }

    /**
     * Scope to filter products that belong to a specific department
     * Only returns products that are available in that department
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $departmentId  Department ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->whereHas('departments', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId)
                ->where('is_available', true);
        });
    }

    /**
     * Get the product type that owns the product
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the primary sales department that owns this product.
     */
    public function salesDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sales_department_id');
    }

    /**
     * Get the production department that owns this product.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the unit of measure for this product (base UOM for production/inventory)
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Get the sales unit of measure for this product (e.g., scoop, cone, cup)
     */
    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'sales_uom_id');
    }

    /**
     * Get the effective sales UOM (falls back to base UOM if not set)
     */
    public function effectiveSalesUom(): BelongsTo
    {
        return $this->sales_uom_id ? $this->salesUom() : $this->unitOfMeasure();
    }

    /**
     * Check if product has different sales UOM from base UOM
     */
    public function hasSalesUomConversion(): bool
    {
        return $this->sales_uom_id !== null && $this->sales_uom_id !== $this->uom_id;
    }

    /**
     * Convert sales quantity to base quantity
     * 
     * @param float $salesQuantity Quantity in sales UOM (e.g., scoops)
     * @return float Quantity in base UOM (e.g., grams)
     */
    public function convertSalesToBaseQuantity(float $salesQuantity): float
    {
        if (!$this->hasSalesUomConversion()) {
            return $salesQuantity;
        }

        // Use sales_unit_weight if set (e.g., 100g per scoop)
        if ($this->sales_unit_weight) {
            return $salesQuantity * $this->sales_unit_weight;
        }

        // Otherwise use UOM conversion service
        $conversionService = app(UomConversionService::class);
        return $conversionService->tryConvert(
            $salesQuantity,
            $this->sales_uom_id,
            $this->uom_id,
            ['product_id' => $this->id]
        ) ?? $salesQuantity;
    }

    /**
     * Convert base quantity to sales quantity
     * 
     * @param float $baseQuantity Quantity in base UOM (e.g., grams)
     * @return float Quantity in sales UOM (e.g., scoops)
     */
    public function convertBaseToSalesQuantity(float $baseQuantity): float
    {
        if (!$this->hasSalesUomConversion()) {
            return $baseQuantity;
        }

        // Use sales_unit_weight if set (e.g., 100g per scoop)
        if ($this->sales_unit_weight) {
            return $baseQuantity / $this->sales_unit_weight;
        }

        // Otherwise use UOM conversion service
        $conversionService = app(UomConversionService::class);
        return $conversionService->tryConvert(
            $baseQuantity,
            $this->uom_id,
            $this->sales_uom_id,
            ['product_id' => $this->id]
        ) ?? $baseQuantity;
    }

    /**
     * Accessor for UOM symbol (e.g., 'g', 'kg', 'ml')
     */
    protected function uomSymbol(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->unitOfMeasure?->symbol ?? 'N/A',
        );
    }

    /**
     * Accessor for UOM display name (e.g., 'Grams', 'Kilograms')
     */
    protected function uomDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->unitOfMeasure?->name ?? 'N/A',
        );
    }

    /**
     * Accessor for full UOM code (e.g., 'g', 'kg', 'ml')
     */
    protected function uomCode(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->unitOfMeasure?->code ?? 'N/A',
        );
    }

    /**
     * Accessor for sales UOM symbol (e.g., 'scoop', 'cone', 'cup')
     */
    protected function salesUomSymbol(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->salesUom?->symbol ?? $this->uomSymbol,
        );
    }

    /**
     * Accessor for effective sales UOM symbol (falls back to base UOM)
     */
    protected function effectiveSalesUomSymbol(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->salesUom?->symbol ?? $this->uomSymbol,
        );
    }

    /**
     * Get display text for sales conversion
     */
    public function getSalesConversionDisplay(): string
    {
        if (!$this->hasSalesUomConversion()) {
            return '';
        }

        $baseSymbol = $this->uomSymbol;
        $salesSymbol = $this->salesUomSymbol;

        if ($this->sales_unit_weight) {
            return "1 {$salesSymbol} = {$this->sales_unit_weight} {$baseSymbol}";
        }

        return "1 {$salesSymbol} ≈ {$this->convertSalesToBaseQuantity(1)} {$baseSymbol}";
    }

    /**
     * Get the recipes for this product
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * Sales request line items that requested this product.
     */
    public function salesProductionRequestItems(): HasMany
    {
        return $this->hasMany(SalesProductionRequestItem::class);
    }

    /**
     * Get the departments that this product belongs to
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_product')
            ->withPivot(['is_available', 'department_price', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->cost || $this->cost == 0) {
            return null;
        }

        return (($this->price - $this->cost) / $this->cost) * 100;
    }

    /**
     * Check if product has allergens
     */
    public function hasAllergens(): bool
    {
        return ! empty($this->allergens);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price, 2);
    }

    /**
     * Calculate how many batches needed to produce desired quantity
     *
     * @param  float  $desiredQuantity  Number of units to produce
     * @return float Number of recipe batches needed
     */
    public function calculateBatchesNeeded(float $desiredQuantity): float
    {
        // Get yield from primary recipe
        $recipe = $this->recipes()->first();
        if (! $recipe || $recipe->yield_quantity <= 0) {
            return 0;
        }

        // Simple calculation: desired quantity / yield per batch
        return $desiredQuantity / $recipe->yield_quantity;
    }

    /**
     * Calculate raw material requirements for desired quantity
     *
     * @param  float  $desiredQuantity  Number of units to produce
     * @return array Array of ingredients with scaled quantities
     */
    public function calculateRawMaterialRequirements(float $desiredQuantity): array
    {
        $batchesNeeded = $this->calculateBatchesNeeded($desiredQuantity);

        if (! $this->recipes()->exists()) {
            return [];
        }

        // Get the primary recipe (or first recipe)
        $recipe = $this->recipes()->with('ingredients.item')->first();

        if (! $recipe) {
            return [];
        }

        $requirements = [];

        foreach ($recipe->ingredients as $ingredient) {
            $scaledQuantity = $ingredient->quantity * $batchesNeeded;

            $requirements[] = [
                'item_id' => $ingredient->item_id,
                'item_name' => $ingredient->item->name,
                'item_sku' => $ingredient->item->sku,
                'base_quantity' => $ingredient->quantity,
                'required_quantity' => $scaledQuantity,
                'uom' => $ingredient->item->unitOfMeasure?->symbol ?? 'N/A',
                'batches_needed' => $batchesNeeded,
            ];
        }

        return $requirements;
    }

    /**
     * Calculate total weight of desired quantity
     *
     * @param  float  $desiredQuantity  Number of units
     * @return float|null Total weight in grams/ml
     */
    public function calculateTotalWeight(float $desiredQuantity): ?float
    {
        if ($this->unit_weight) {
            return $desiredQuantity * $this->unit_weight;
        }

        return null;
    }

    /**
     * Sync recipe yield information from the primary recipe to product
     * 
     * @return bool Whether sync was successful
     */
    public function syncYieldFromRecipe(): bool
    {
        if (!$this->recipes()->exists()) {
            return false;
        }

        $recipe = $this->recipes()->first();
        
        if (!$recipe) {
            return false;
        }

        // Update recipe_yield from recipe's yield_quantity
        $updateData = [
            'recipe_yield' => $recipe->yield_quantity ?? 1,
        ];

        // Keep existing recipe_yield_weight if already set, otherwise calculate
        if (!$this->recipe_yield_weight && $this->unit_weight) {
            $updateData['recipe_yield_weight'] = $recipe->yield_quantity * $this->unit_weight;
        }

        $this->update($updateData);

        return true;
    }

    /**
     * Get estimated cost based on recipe ingredients
     *
     * @return float|null Estimated cost per unit
     */
    public function getEstimatedCostAttribute(): ?float
    {
        if (! $this->recipes()->exists()) {
            return $this->cost;
        }

        $recipe = $this->recipes()->with('ingredients.item.purchaseItems')->first();

        if (! $recipe) {
            return $this->cost;
        }

        $totalCost = 0;

        foreach ($recipe->ingredients as $ingredient) {
            // $ingredient->item is resolved by RecipeIngredient's accessor: an
            // Item for raw materials, but a Product for WIP sub-goods. Only real
            // inventory Items have a purchaseItems() relation — a Product does
            // not, so guard by type to avoid a fatal "undefined method" error.
            $model = $ingredient->item;
            $avgCost = null;

            if ($model instanceof Item) {
                // Average cost from recent purchase items.
                $avgCost = $model->purchaseItems()
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->avg('cost_per_unit');
            } elseif ($model instanceof self) {
                // WIP sub-product: use its saved cost.
                $avgCost = $model->cost;
            }

            // Fall back to the cost stored on the ingredient itself.
            if (! $avgCost) {
                $avgCost = (float) ($ingredient->cost_per_unit ?? 0);
            }

            if ($avgCost) {
                $totalCost += ($ingredient->quantity * $avgCost);
            }
        }

        // Cost per unit = total recipe cost / recipe yield
        if ($recipe->yield_quantity > 0) {
            return $totalCost / $recipe->yield_quantity;
        }

        return $totalCost;
    }

    /**
     * Effective cost per sales unit used for COGS: the saved cost, falling back
     * to the recipe-derived estimated cost. Returns 0.0 when neither is set —
     * callers treat a zero here as "no cost defined" and block the sale, since a
     * zero-cost line would silently post no cost of goods sold.
     */
    public function effectiveCost(): float
    {
        $cost = (float) ($this->cost ?? 0);

        if ($cost > 0) {
            return $cost;
        }

        return (float) ($this->estimated_cost ?? 0);
    }

    /**
     * Convert a quantity from this product base UOM to another UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     */
    public function convertFromBaseUom(float $quantity, int|string|UnitOfMeasure $toUom): ?float
    {
        if (! $this->uom_id) {
            return null;
        }

        return app(UomConversionService::class)->tryConvert(
            $quantity,
            (int) $this->uom_id,
            $toUom,
            [
                'branch_id' => $this->branch_id,
                'product_id' => (string) $this->id,
            ]
        );
    }

    /**
     * Convert a quantity from another UOM to this product base UOM.
     *
     * @param  int|string|UnitOfMeasure  $fromUom
     */
    public function convertToBaseUom(float $quantity, int|string|UnitOfMeasure $fromUom): ?float
    {
        if (! $this->uom_id) {
            return null;
        }

        return app(UomConversionService::class)->tryConvert(
            $quantity,
            $fromUom,
            (int) $this->uom_id,
            [
                'branch_id' => $this->branch_id,
                'product_id' => (string) $this->id,
            ]
        );
    }
}
