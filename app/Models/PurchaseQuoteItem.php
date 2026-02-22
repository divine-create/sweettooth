<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_quote_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->line_total = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->purchaseQuote->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->purchaseQuote->calculateTotals();
        });
    }

    public function purchaseQuote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
