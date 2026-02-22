<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'bank_account',
        'bank_name',
        'status',
        'credit_rating',
        'is_verified',
        'payment_terms_days',
        'outstanding_balance',
        'credit_limit',
        'avg_delivery_days',
        'quality_rating',
        'on_time_delivery_percentage',
        'notes',
        'parent_supplier_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'quality_rating' => 'decimal:1',
        'on_time_delivery_percentage' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the parent supplier for supplier hierarchies.
     */
    public function parentSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'parent_supplier_id');
    }

    /**
     * Get child suppliers (for enterprise suppliers).
     */
    public function childSuppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'parent_supplier_id');
    }

    /**
     * Get the branch this supplier is assigned to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created this supplier.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all contacts for this supplier.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    /**
     * Get primary contact for this supplier.
     */
    public function primaryContact(): HasMany
    {
        return $this->contacts()->where('is_primary', true);
    }

    /**
     * Get all payment terms for this supplier.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(SupplierTerms::class);
    }

    /**
     * Get active terms for this supplier.
     */
    public function activeTerms(): HasMany
    {
        return $this->terms()->where('is_active', true);
    }

    /**
     * Get all bank accounts for this supplier.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    /**
     * Get primary bank account for this supplier.
     */
    public function primaryBankAccount(): HasMany
    {
        return $this->bankAccounts()->where('is_primary', true);
    }

    /**
     * Get all documents for this supplier.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    /**
     * Get verified documents for this supplier.
     */
    public function verifiedDocuments(): HasMany
    {
        return $this->documents()->where('is_verified', true);
    }

    /**
     * Get performance history for this supplier.
     */
    public function performanceHistory(): HasMany
    {
        return $this->hasMany(SupplierPerformanceHistory::class);
    }

    /**
     * Get all purchases from this supplier.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope to get only active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only verified suppliers.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get suppliers by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId)->orWhereNull('branch_id');
    }

    /**
     * Scope to get suppliers with outstanding balance.
     */
    public function scopeWithOutstanding($query)
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    /**
     * Scope to search suppliers by code, name, or email.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('code', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%");
    }

    // ========== CUSTOM METHODS ==========

    /**
     * Check if supplier can accept new purchase orders based on credit limit.
     */
    public function canAcceptPurchase($purchaseAmount): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return ($this->outstanding_balance + $purchaseAmount) <= $this->credit_limit;
    }

    /**
     * Get the credit available for this supplier.
     */
    public function getAvailableCredit(): float
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    /**
     * Get the latest performance record.
     */
    public function getLatestPerformance(): ?SupplierPerformanceHistory
    {
        return $this->performanceHistory()->latest('record_date')->first();
    }

    /**
     * Calculate overall supplier score based on recent performance.
     */
    public function getOverallScore(): ?float
    {
        $latest = $this->getLatestPerformance();
        if (!$latest || !$latest->overall_score) {
            return null;
        }
        return $latest->overall_score;
    }

    /**
     * Get active primary contact.
     */
    public function getActivePrimaryContact(): ?SupplierContact
    {
        return $this->contacts()
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active primary bank account.
     */
    public function getActivePrimaryBankAccount(): ?SupplierBankAccount
    {
        return $this->bankAccounts()
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if any documents are expiring soon (within 30 days).
     */
    public function hasExpiringDocuments(): bool
    {
        $thirtyDaysFromNow = now()->addDays(30);
        return $this->documents()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->exists();
    }

    /**
     * Get expiring documents (within 30 days).
     */
    public function getExpiringDocuments()
    {
        $thirtyDaysFromNow = now()->addDays(30);
        return $this->documents()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('expiry_date', '>=', now())
            ->get();
    }

    /**
     * Get expired documents.
     */
    public function getExpiredDocuments()
    {
        return $this->documents()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();
    }

    /**
     * Update outstanding balance by adding/subtracting amount.
     */
    public function updateOutstandingBalance($amount): void
    {
        $this->increment('outstanding_balance', $amount);
    }

    /**
     * Get formatted outstanding balance.
     */
    public function getFormattedOutstandingBalance(): string
    {
        return number_format($this->outstanding_balance, 2);
    }

    /**
     * Get formatted credit limit.
     */
    public function getFormattedCreditLimit(): string
    {
        return number_format($this->credit_limit, 2);
    }

    /**
     * Get credit utilization percentage.
     */
    public function getCreditUtilizationPercentage(): float
    {
        if ($this->credit_limit == 0) {
            return 0;
        }
        return ($this->outstanding_balance / $this->credit_limit) * 100;
    }
}
