<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A priced offer saved from the POS. Unlike a Sale, a Quotation never deducts
 * stock and never posts to the GL — those only happen when it is converted into
 * a real Sale through the normal POS completion flow.
 */
class Quotation extends Model
{
    protected $fillable = [
        'quotation_number',
        'branch_id',
        'department_id',
        'created_by_id',
        'created_by_type',
        'customer_name',
        'customer_phone',
        'status',
        'subtotal',
        'discount',
        'total',
        'valid_until',
        'notes',
        'converted_sale_id',
        'converted_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    /** A quote past its validity date that hasn't been converted/cancelled. */
    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->isPast()
            && ! in_array($this->status, ['converted', 'cancelled'], true);
    }

    /** Still open to be converted (not already converted/cancelled/expired). */
    public function isOpen(): bool
    {
        return ! in_array($this->status, ['converted', 'cancelled', 'expired'], true)
            && ! $this->isExpired();
    }

    public function markConverted(Sale $sale): void
    {
        $this->update([
            'status' => 'converted',
            'converted_sale_id' => $sale->id,
            'converted_at' => now(),
        ]);
    }

    /** Generate a unique, human-readable quotation number. */
    public static function generateNumber(): string
    {
        return 'QT-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
