<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StockBatch extends Model
{
    protected $fillable = [
        'stock_id',
        'branch_id',
        'purchase_item_id',
        'batch_number',
        'expiry_date',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'status',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'expiry_date'        => 'date',
        'received_at'        => 'date',
        'quantity_received'  => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'unit_cost'          => 'decimal:4',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /** Active batches ordered FEFO: earliest expiry first, NULLs last, then oldest received. */
    public function scopeFefoOrdered(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isDepleted(): bool
    {
        return (float) $this->quantity_remaining <= 0;
    }

    public function daysUntilExpiry(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }
}
