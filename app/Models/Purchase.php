<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GlEntry;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'recorded_by_id',
        'recorded_by_type',
        'purchase_number',
        'purchase_date',
        'supplier_name',
        'supplier_contact',
        'total_fob_fc',
        'total_fob_ngn',
        'other_costs',
        'landing_cost',
        'total_cost',
        'payment_status',
        'notes',
        'status',
        'gl_entry_id',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_fob_fc' => 'decimal:2',
        'total_fob_ngn' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'landing_cost' => 'decimal:2',
    ];

    /**
     * Scope to filter purchases by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter draft purchases
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter pending approval purchases
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope to filter approved purchases
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get the branch that owns the purchase
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the supplier for this purchase
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the employee who recorded the purchase
     */
    public function recorder(): BelongsTo
    {
        return $this->morphTo(
            'recorder',
            'recorded_by_type',
            'recorded_by_id'
        );
    }

    /**
     * Get the purchase items for this purchase
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * Get the approval request for this purchase
     */
    public function approvalRequest()
    {
        return $this->hasOne(PurchaseApprovalRequest::class);
    }

    /**
     * Get the stock movements created from this purchase
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', 'App\Models\Purchase');
    }

    /**
     * Get the GL entry for this purchase
     */
    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class, 'gl_entry_id');
    }

    /**
     * Get the bank account for this purchase (payment method)
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Calculate total purchase cost
     */
    public function calculateTotalCost(): float
    {
        return $this->total_fob_ngn + $this->other_costs;
    }

    /**
     * Sync purchase payment status from accounting payment records.
     */
    public function syncPaymentStatusFromPayments(bool $persist = true): string
    {
        $totalCost = (float) ($this->landing_cost ?? 0);
        if ($totalCost <= 0) {
            $totalCost = (float) ($this->total_cost ?? 0);
        }
        if ($totalCost <= 0) {
            $totalCost = (float) $this->calculateTotalCost();
        }

        $totalPaid = (float) $this->purchasePayments()
            ->where('status', 'paid')
            ->sum('amount');

        $status = 'pending';
        if ($totalCost > 0 && $totalPaid >= $totalCost) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        if ($persist && $this->payment_status !== $status) {
            $this->forceFill(['payment_status' => $status])->saveQuietly();
        }

        return $status;
    }

    /**
     * Generate unique purchase number
     */
    public static function generatePurchaseNumber($branchCode): string
    {
        $date = now()->format('Ymd');
        $lastPurchase = static::where('purchase_number', 'like', "PUR-{$branchCode}-{$date}-%")
            ->orderBy('purchase_number', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastNumber = (int) substr($lastPurchase->purchase_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PUR-{$branchCode}-{$date}-{$newNumber}";
    }
}
