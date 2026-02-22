<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTerms extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_terms';

    protected $fillable = [
        'supplier_id',
        'payment_terms_days',
        'credit_limit',
        'minimum_order_quantity',
        'discount_percentage',
        'early_payment_discount',
        'early_payment_days',
        'delivery_terms',
        'lead_time_days',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'minimum_order_quantity' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'early_payment_discount' => 'decimal:2',
        'early_payment_days' => 'integer',
        'lead_time_days' => 'integer',
    ];

    /**
     * Get the supplier these terms belong to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to get only active terms.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate total discount (base + early payment).
     */
    public function getTotalDiscount($paymentDays = null): float
    {
        $discount = $this->discount_percentage;

        if ($paymentDays !== null && $this->early_payment_days && $paymentDays <= $this->early_payment_days) {
            $discount += $this->early_payment_discount;
        }

        return $discount;
    }

    /**
     * Check if minimum order quantity is met.
     */
    public function meetsMinimumOrderQuantity($quantity): bool
    {
        if (!$this->minimum_order_quantity) {
            return true;
        }
        return $quantity >= $this->minimum_order_quantity;
    }

    /**
     * Get payment due date based on invoice date.
     */
    public function getPaymentDueDate($invoiceDate = null): \Carbon\Carbon
    {
        $date = $invoiceDate ? new \Carbon\Carbon($invoiceDate) : now();
        return $date->addDays($this->payment_terms_days);
    }
}
