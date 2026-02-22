<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\GlEntry;

class Sale extends Model
{
    protected $fillable = [
        'sales_shift_id',
        'branch_id',
        'department_id',
        'table_id',
        'sold_by_id',
        'sold_by_type',
        'sale_number',
        'sale_time',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'order_type',
        'notes',
        'gl_entry_id',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'sale_time' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function salesShift(): BelongsTo
    {
        return $this->belongsTo(SalesShift::class, 'sales_shift_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function soldBy(): MorphTo
    {
        return $this->morphTo('sold_by', 'sold_by_type', 'sold_by_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class, 'gl_entry_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    // Helper Methods
    public function calculateTotals(): void
    {
        // Calculate subtotal from sale items
        $this->subtotal = $this->saleItems->sum('subtotal');

        // Apply discount
        $subtotalAfterDiscount = $this->subtotal - $this->discount;

        // Calculate tax (if applicable)
        // Assuming tax is a percentage stored elsewhere or 0 for now
        // You can modify this based on your tax logic
        $this->tax = 0;

        // Calculate total
        $this->total = $subtotalAfterDiscount + $this->tax;
    }

    public function hasMultiplePaymentMethods(): bool
    {
        $paymentMethods = $this->payments()
            ->where('status', 'completed')
            ->distinct('payment_method')
            ->count('payment_method');

        return $paymentMethods > 1;
    }

    public function isFullyPaid(): bool
    {
        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return abs($totalPaid - $this->total) < 0.01; // Account for floating point precision
    }

    public function getRemainingAmount(): float
    {
        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        $remaining = $this->total - $totalPaid;

        return max(0, $remaining); // Never return negative
    }

    public function getTotalPaid(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getPaymentMethodBreakdown(): array
    {
        return $this->payments()
            ->where('status', 'completed')
            ->get()
            ->groupBy('payment_method')
            ->map(function ($payments) {
                return $payments->sum('amount');
            })
            ->toArray();
    }

    public function canBeCompleted(): bool
    {
        return $this->isFullyPaid() && $this->status === 'pending';
    }

    public function markAsCompleted(): bool
    {
        if ($this->canBeCompleted()) {
            $this->status = 'completed';

            return $this->save();
        }

        return false;
    }

    // Boot method to auto-update totals
    protected static function booted(): void
    {
        static::saved(function (Sale $sale) {
            if ($sale->saleItems()->exists()) {
                $sale->calculateTotals();
                // Prevent infinite loop by checking if totals changed
                if ($sale->isDirty(['subtotal', 'total'])) {
                    $sale->saveQuietly();
                }
            }
        });
    }
}
