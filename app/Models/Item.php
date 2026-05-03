<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'sku',
        'category_id',
        'uom_id',
        'reorder_level',
        'max_stock_level',
        'unit_price',
        'last_unit_price',
        'status',
        'requires_request',
    ];

    protected $casts = [
        'reorder_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'last_unit_price' => 'decimal:4',
        'requires_request' => 'boolean',
    ];

    /**
     * Scope to filter items by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get the branch that owns the item
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the category for this item
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Get category name (for backward compatibility and display)
     */
    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category?->name ?? 'Uncategorized',
        );
    }

    /**
     * Get the unit of measure for this item
     */
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
            get: fn () => $this->unitOfMeasure?->symbol ?? 'N/A',
        );
    }

    /**
     * Alias accessor for UOM (for backward compatibility)
     */
    protected function uom(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->uomSymbol,
        );
    }

    /**
     * Get the stocks for this item
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the purchase items for this item
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the item request details for this item
     */
    public function itemRequestDetails(): HasMany
    {
        return $this->hasMany(ItemRequestDetail::class);
    }

    /**
     * Get the item dispatches for this item
     */
    public function itemDispatches(): HasMany
    {
        return $this->hasMany(ItemDispatch::class);
    }

    /**
     * Get the stock take details for this item
     */
    public function stockTakeDetails(): HasMany
    {
        return $this->hasMany(StockTakeDetail::class);
    }

    /**
     * Get current stock for this item at a specific branch
     */
    public function getCurrentStock($branchId = null)
    {
        $branchId = $branchId ?? $this->branch_id;

        return $this->stocks()
            ->where('branch_id', $branchId)
            ->first()?->quantity_available ?? 0;
    }

    /**
     * Check if item is below reorder level
     */
    public function isBelowReorderLevel($branchId = null): bool
    {
        if (! $this->reorder_level) {
            return false;
        }

        return $this->getCurrentStock($branchId) < $this->reorder_level;
    }

    /**
     * Convert a quantity expressed in this item's base UOM to another UOM.
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
                'item_id' => (int) $this->id,
            ]
        );
    }

    /**
     * Convert a quantity from another UOM into this item's base UOM.
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
                'item_id' => (int) $this->id,
            ]
        );
    }

    /**
     * Compatibility alias used in production costing screens.
     */
    protected function costPerUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) ($this->unit_price ?? 0),
            set: fn ($value) => [
                'unit_price' => (float) $value,
                'last_unit_price' => (float) $value,
            ],
        );
    }

    /**
     * Compatibility alias for analytics screens expecting item->price.
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) ($this->unit_price ?? 0),
            set: fn ($value) => [
                'unit_price' => (float) $value,
            ],
        );
    }
}
