<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'purchase_id',
        'supplier_id',
        'branch_id',
        'receipt_date',
        'status',
        'notes',
        'received_by',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (empty($receipt->receipt_number)) {
                $receipt->receipt_number = static::generateReceiptNumber();
            }
        });
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'GR-' . date('Ym');
        $lastReceipt = static::withTrashed()
            ->where('receipt_number', 'like', $prefix . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);

        // Update purchase order item quantities
        foreach ($this->items as $item) {
            if ($item->purchaseOrderItem) {
                $item->purchaseOrderItem->increment('quantity_received', $item->quantity_accepted);
            }
        }

        // Update purchase order status if fully received
        if ($this->purchaseOrder && $this->purchaseOrder->isFullyReceived()) {
            $this->purchaseOrder->update(['status' => 'received']);
        } elseif ($this->purchaseOrder) {
            $this->purchaseOrder->update(['status' => 'partially_received']);
        }
    }

    public function getTotalReceivedAttribute(): float
    {
        return $this->items->sum('quantity_received');
    }

    public function getTotalAcceptedAttribute(): float
    {
        return $this->items->sum('quantity_accepted');
    }

    public function getTotalRejectedAttribute(): float
    {
        return $this->items->sum('quantity_rejected');
    }
}
