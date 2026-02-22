<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlEntry extends Model
{
    use SoftDeletes;

    protected $table = 'gl_entries';

    protected $fillable = [
        'gl_account_id',
        'accounting_period_id',
        'entry_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'description',
        'debit',
        'credit',
        'entry_date',
        'status',
        'entered_by_id',
        'entered_by_type',
        'posted_by_id',
        'posted_by_type',
        'posted_at',
        'reversed_by_id',
        'reversed_by_type',
        'reversed_at',
        'remarks',
        'branch_id',
        'cost_center',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    // Relationships
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enteredBy()
    {
        return $this->morphTo();
    }

    public function postedBy()
    {
        return $this->morphTo();
    }

    public function reversedBy()
    {
        return $this->morphTo();
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getReferenceLabelAttribute(): string
    {
        if (!empty($this->reference_number)) {
            return $this->reference_number;
        }

        if (!$this->reference_type || !$this->reference_id) {
            return '-';
        }

        $type = class_basename($this->reference_type);

        $label = match ($type) {
            'AccountingEntry' => 'Accounting Entry',
            'Sale' => 'Sale',
            'Purchase' => 'Purchase',
            'Payment' => 'Payment',
            'StockMovement' => 'Stock Movement',
            'InventoryAdjustment' => 'Inventory Adjustment',
            'PurchasePayment' => 'Purchase Payment',
            'TaxPayment' => 'Tax Payment',
            'Payroll' => 'Payroll',
            'FixedAsset' => 'Fixed Asset',
            'AssetDepreciation' => 'Asset Depreciation',
            default => $type,
        };

        return $label . ' #' . $this->reference_id;
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('gl_account_id', $accountId);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('accounting_period_id', $periodId);
    }

    public function scopeByReference($query, $type, $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    public function scopeByEntryType($query, $type)
    {
        return $query->where('entry_type', $type);
    }

    // Methods
    public function post(string|int $userId): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->status = 'posted';
        $this->posted_by_id = $userId;
        $this->posted_at = now();
        $this->save();

        // Update account balances
        $this->glAccount->updateBalance(floatval($this->debit), floatval($this->credit));

        return true;
    }

    public function reverse(string|int $userId): bool
    {
        if ($this->status !== 'posted') {
            return false;
        }

        // Create reversing entry
        self::create([
            'gl_account_id' => $this->gl_account_id,
            'accounting_period_id' => $this->accounting_period_id,
            'entry_type' => 'reversal',
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'description' => "Reversing entry for: {$this->description}",
            'debit' => floatval($this->credit),
            'credit' => floatval($this->debit),
            'entry_date' => now(),
            'status' => 'posted',
            'reversed_by_id' => $userId,
            'reversed_at' => now(),
            'branch_id' => $this->branch_id,
            'cost_center' => $this->cost_center,
        ]);

        $this->status = 'reversed';
        $this->save();

        return true;
    }
}
