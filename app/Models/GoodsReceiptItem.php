<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'product_id',
        'purchase_order_item_id',
        'description',
        'quantity_expected',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
    ];

    protected $casts = [
        'quantity_expected' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'quantity_accepted' => 'decimal:4',
        'quantity_rejected' => 'decimal:4',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function getVarianceAttribute(): float
    {
        return $this->quantity_received - $this->quantity_expected;
    }

    public function hasVariance(): bool
    {
        return $this->variance !== 0;
    }

    public function hasRejections(): bool
    {
        return $this->quantity_rejected > 0;
    }
}
