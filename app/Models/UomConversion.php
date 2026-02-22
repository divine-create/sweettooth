<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UomConversion extends Model
{
    use HasFactory;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'branch_id',
        'item_id',
        'product_id',
        'from_uom_id',
        'to_uom_id',
        'factor',
        'is_active',
        'is_system',
        'notes',
        'created_by_id',
        'created_by_type',
    ];

    protected $casts = [
        'factor' => 'decimal:12',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_uom_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_uom_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
