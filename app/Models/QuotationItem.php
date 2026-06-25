<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line on a Quotation. Deliberately has NO model events that touch
 * stock — quotations are non-binding offers until converted to a Sale.
 */
class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'product_id',
        'item_id',
        'name',
        'quantity',
        'unit_price',
        'subtotal',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
