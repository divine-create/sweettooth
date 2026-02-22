<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_note_number',
        'sales_order_id',
        'sale_id',
        'customer_id',
        'branch_id',
        'delivery_date',
        'delivery_address',
        'status',
        'carrier',
        'tracking_number',
        'notes',
        'delivered_by',
        'delivered_at',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (empty($note->delivery_note_number)) {
                $note->delivery_note_number = static::generateDeliveryNoteNumber();
            }
        });
    }

    public static function generateDeliveryNoteNumber(): string
    {
        $prefix = 'DN-' . date('Ym');
        $lastNote = static::withTrashed()
            ->where('delivery_note_number', 'like', $prefix . '%')
            ->orderBy('delivery_note_number', 'desc')
            ->first();

        if ($lastNote) {
            $lastNumber = (int) substr($lastNote->delivery_note_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDispatched(): bool
    {
        return $this->status === 'dispatched';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function markAsDispatched(): void
    {
        $this->update(['status' => 'dispatched']);
    }

    public function markAsDelivered(?int $deliveredBy = null): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_by' => $deliveredBy ?? auth()->id(),
            'delivered_at' => now(),
        ]);

        // Update sales order item quantities
        foreach ($this->items as $item) {
            if ($item->salesOrderItem) {
                $item->salesOrderItem->increment('quantity_delivered', $item->quantity);
            }
        }
    }
}
