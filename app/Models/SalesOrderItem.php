<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'quantity_ordered',
        'quantity_delivered',
        'quantity_invoiced',
        'unit_price',
        'discount_percent',
        'tax_rate',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_delivered' => 'decimal:4',
        'quantity_invoiced' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateLineTotal();
        });

        static::saved(function ($item) {
            $item->salesOrder->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->salesOrder->calculateTotals();
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function calculateLineTotal(): void
    {
        $subtotal = $this->quantity_ordered * $this->unit_price;
        $discount = $subtotal * ($this->discount_percent / 100);
        $this->line_total = $subtotal - $discount;
    }

    public function getRemainingToDeliverAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_delivered);
    }

    public function getRemainingToInvoiceAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_invoiced);
    }

    public function isFullyDelivered(): bool
    {
        return $this->quantity_delivered >= $this->quantity_ordered;
    }

    public function isFullyInvoiced(): bool
    {
        return $this->quantity_invoiced >= $this->quantity_ordered;
    }
}
