<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'legacy_dispatch_uom',
        'category',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all active units ordered by sort_order
     */
    public static function active()
    {
        return self::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get units by category
     */
    public static function byCategory($category)
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get categories
     */
    public static function categories()
    {
        return self::where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->sort()
            ->toArray();
    }

    /**
     * Find by code
     */
    public static function findByCode($code)
    {
        return self::where('code', $code)->first();
    }

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UomConversion::class, 'from_uom_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UomConversion::class, 'to_uom_id');
    }

    /**
     * Resolve conversion factor from this UOM to target UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function getConversionFactorTo(int|string|UnitOfMeasure $toUom, array $context = []): ?float
    {
        return app(UomConversionService::class)
            ->tryConvert(1.0, (int) $this->id, $toUom, $context);
    }

    /**
     * Convert quantity from this UOM into another UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function convertQuantity(float $quantity, int|string|UnitOfMeasure $toUom, array $context = []): ?float
    {
        return app(UomConversionService::class)->tryConvert($quantity, (int) $this->id, $toUom, $context);
    }

    /**
     * Check if a conversion path exists from this UOM to target UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     * @param  array{branch_id?:string|null,item_id?:int|null,product_id?:string|null,max_depth?:int|null}  $context
     */
    public function canConvertTo(int|string|UnitOfMeasure $toUom, array $context = []): bool
    {
        return $this->getConversionFactorTo($toUom, $context) !== null;
    }
}
