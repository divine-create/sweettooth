<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionStoreTransfer extends Model
{
    protected $table = 'production_store_transfers';

    protected $fillable = [
        'branch_id',
        'from_store_id',
        'to_store_id',
        'from_department_id',
        'to_department_id',
        'item_id',
        'quantity',
        'received_quantity',
        'uom',
        'unit_cost',
        'status',
        'notes',
        'sent_by_id',
        'sent_by_type',
        'received_by_id',
        'received_by_type',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(ProductionStore::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(ProductionStore::class, 'to_store_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    /** Raw materials only — item_id is always a numeric Item id. */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function sentBy(): MorphTo
    {
        return $this->morphTo('sent_by', 'sent_by_type', 'sent_by_id');
    }

    public function receivedBy(): MorphTo
    {
        return $this->morphTo('received_by', 'received_by_type', 'received_by_id');
    }

    public function getItemNameAttribute(): string
    {
        return $this->item?->name ?? 'Unknown item';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_receipt' => 'Pending Receipt',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    /** received − sent, once received (negative = transit shortfall). Null while pending. */
    public function getVarianceAttribute(): ?float
    {
        if ($this->received_quantity === null) {
            return null;
        }

        return (float) $this->received_quantity - (float) $this->quantity;
    }
}
