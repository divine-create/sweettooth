<?php

namespace App\Models;

use App\Helpers\Settings;
use App\Services\CurrencyFormattingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasUuids;

    protected $fillable = [
        'sale_id',
        'receipt_number',
        'content',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payments',
        'change_due',
        'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'change_due' => 'decimal:2',
        'payments' => 'array',
        'meta' => 'array',
    ];

    /**
     * Specify which columns should use UUIDs
     */
    public function uniqueIds(): array
    {
        return ['receipt_number'];
    }

    /**
     * Relationship: Receipt belongs to a Sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Scope: Filter receipts by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter receipts by today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: Filter receipts by this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope: Filter by payment method
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->whereJsonContains('payments', [['method' => $method]]);
    }

    /**
     * Scope: Filter by minimum total
     */
    public function scopeMinTotal($query, $amount)
    {
        return $query->where('total', '>=', $amount);
    }

    /**
     * Accessor: Get formatted subtotal with currency
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return $this->formatCurrency($this->subtotal);
    }

    /**
     * Accessor: Get formatted tax with currency
     */
    public function getFormattedTaxAttribute(): string
    {
        return $this->formatCurrency($this->tax);
    }

    /**
     * Accessor: Get formatted discount with currency
     */
    public function getFormattedDiscountAttribute(): string
    {
        return $this->formatCurrency($this->discount);
    }

    /**
     * Accessor: Get formatted total with currency
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->formatCurrency($this->total);
    }

    /**
     * Accessor: Get formatted change due with currency
     */
    public function getFormattedChangeDueAttribute(): string
    {
        return $this->formatCurrency($this->change_due);
    }

    /**
     * Get total amount paid from all payment methods
     */
    public function getTotalPaid(): float
    {
        if (empty($this->payments)) {
            return 0;
        }

        return collect($this->payments)->sum('amount');
    }

    /**
     * Get payment breakdown by method
     */
    public function getPaymentBreakdown(): array
    {
        if (empty($this->payments)) {
            return [];
        }

        return collect($this->payments)
            ->groupBy('method')
            ->map(fn ($group) => $group->sum('amount'))
            ->toArray();
    }

    /**
     * Check if receipt has multiple payment methods
     */
    public function hasMultiplePaymentMethods(): bool
    {
        return count($this->payments ?? []) > 1;
    }

    /**
     * Get tax percentage
     */
    public function getTaxPercentage(): float
    {
        if ($this->subtotal == 0) {
            return 0;
        }

        return ($this->tax / $this->subtotal) * 100;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage(): float
    {
        if ($this->subtotal == 0) {
            return 0;
        }

        return ($this->discount / $this->subtotal) * 100;
    }

    /**
     * Get receipt age in hours
     */
    public function getAgeInHours(): int
    {
        return $this->created_at->diffInHours(now());
    }

    /**
     * Check if receipt can be voided (within 24 hours)
     */
    public function canBeVoided(): bool
    {
        return $this->getAgeInHours() <= 24;
    }

    /**
     * Generate printable receipt content with dynamic currency
     */
    public function generatePrintContent(): string
    {
        $currencyService = new CurrencyFormattingService();
        $symbol = $currencyService->getSymbol();

        $content = "=== RECEIPT ===\n";
        $content .= "Receipt #: {$this->receipt_number}\n";
        $content .= "Date: {$this->created_at->format('Y-m-d H:i:s')}\n";
        $content .= "Sale ID: {$this->sale_id}\n";
        $content .= "-------------------\n";
        $content .= "Subtotal: {$symbol}{$this->formatted_subtotal}\n";

        if ($this->tax > 0) {
            $content .= "Tax: {$symbol}{$this->formatted_tax}\n";
        }

        if ($this->discount > 0) {
            $content .= "Discount: -{$symbol}{$this->formatted_discount}\n";
        }

        $content .= "-------------------\n";
        $content .= "TOTAL: {$symbol}{$this->formatted_total}\n";
        $content .= "-------------------\n";

        if (! empty($this->payments)) {
            $content .= "Payments:\n";
            foreach ($this->payments as $payment) {
                $method = ucfirst($payment['method'] ?? 'Unknown');
                $amount = $this->formatCurrency($payment['amount'] ?? 0);
                $content .= "  {$method}: {$amount}\n";
            }
        }

        if ($this->change_due > 0) {
            $content .= "Change Due: {$symbol}{$this->formatted_change_due}\n";
        }

        $content .= "===================\n";
        $content .= "Thank you for your business!\n";

        return $content;
    }

    /**
     * Add metadata to receipt
     */
    public function addMeta(string $key, $value): void
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->update(['meta' => $meta]);
    }

    /**
     * Get metadata value
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Format currency amount for receipt display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    /**
     * Boot method to set default content if empty
     */
    protected static function booted(): void
    {
        static::creating(function (Receipt $receipt) {
            if (empty($receipt->content)) {
                $receipt->content = $receipt->generatePrintContent();
            }
        });
    }
}
