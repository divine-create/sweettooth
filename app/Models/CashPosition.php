<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashPosition extends Model
{
    use SoftDeletes;

    protected $table = 'cash_positions';

    protected $fillable = [
        'cash_type',
        'branch_id',
        'position_date',
        'opening_balance',
        'sales_receipts',
        'withdrawals',
        'closing_balance',
        'physical_count',
        'counted_at',
        'variance_amount',
        'variance_notes',
        'counted_by_id',
        'counted_by_type',
    ];

    protected $casts = [
        'position_date' => 'date',
        'opening_balance' => 'decimal:2',
        'sales_receipts' => 'decimal:2',
        'withdrawals' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'physical_count' => 'decimal:2',
        'counted_at' => 'datetime',
        'variance_amount' => 'decimal:2',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function countedBy()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('cash_type', $type);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('position_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query
            ->where('position_date', '>=', $startDate)
            ->where('position_date', '<=', $endDate);
    }

    public function scopeWithVariance($query)
    {
        return $query->where('variance_amount', '!=', 0);
    }

    // Methods
    public function calculateBalance(): void
    {
        $this->closing_balance = floatval($this->opening_balance) +
            floatval($this->sales_receipts) -
            floatval($this->withdrawals);
        $this->save();
    }

    public function recordPhysicalCount($amount, $countedBy = null): bool
    {
        $this->physical_count = $amount;
        $this->counted_at = now();
        $this->counted_by_id = $countedBy ?? auth()->id();

        // Calculate variance
        $expectedBalance = floatval($this->closing_balance);
        $actualCount = floatval($amount);
        $this->variance_amount = $actualCount - $expectedBalance;

        if ($this->variance_amount != 0) {
            $this->variance_notes = "Expected: {$expectedBalance}, Counted: {$actualCount}";
        }

        return $this->save();
    }

    public function isBalanced(): bool
    {
        return floatval($this->closing_balance) === floatval($this->physical_count ?? 0);
    }

    public function getVariancePercentage(): float
    {
        if (floatval($this->closing_balance) == 0) {
            return 0;
        }

        return (floatval($this->variance_amount) / floatval($this->closing_balance)) * 100;
    }

    // Cash type constants
    public const SALES_CASH = 'sales_cash';
    public const PETTY_CASH = 'petty_cash';
    public const DRAWER_CASH = 'drawer_cash';
}
