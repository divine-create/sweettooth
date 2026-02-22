<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatePaymentFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'fee_amount',
        'fee_rate',
        'fee_date',
        'days_overdue',
        'status',
        'notes',
        'gl_posting_status',
        'gl_entry_id',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'fee_rate' => 'decimal:2',
        'fee_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isWaived(): bool
    {
        return $this->status === 'waived';
    }

    public static function calculateFee(Sale $sale, float $rate = 2.0): ?self
    {
        if (!$sale->due_date || !$sale->due_date->isPast()) {
            return null;
        }

        $daysOverdue = $sale->due_date->diffInDays(now());
        $feeAmount = $sale->total * ($rate / 100);

        return new static([
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'fee_amount' => $feeAmount,
            'fee_rate' => $rate,
            'fee_date' => now(),
            'days_overdue' => $daysOverdue,
        ]);
    }
}
