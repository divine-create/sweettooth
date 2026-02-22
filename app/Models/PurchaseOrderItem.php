<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'quantity_invoiced',
        'unit_price',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'quantity_invoiced' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->line_total = $item->quantity_ordered * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->purchaseOrder->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->purchaseOrder->calculateTotals();
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function getRemainingToReceiveAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function getRemainingToInvoiceAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_invoiced);
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function isFullyInvoiced(): bool
    {
        return $this->quantity_invoiced >= $this->quantity_ordered;
    }
}
