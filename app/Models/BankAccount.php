<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'branch_id',
        'bank_name',
        'bank_code',
        'account_number',
        'account_type',
        'gl_account_id',
        'opening_balance',
        'interest_rate',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'interest_rate' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function dailyPositions(): HasMany
    {
        return $this->hasMany(DailyBankPosition::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DailyBankTransaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    // Get current balance from latest position
    public function getCurrentBalance()
    {
        $latestPosition = $this->dailyPositions()
            ->orderBy('position_date', 'desc')
            ->first();

        return $latestPosition ? $latestPosition->available_balance : $this->opening_balance;
    }

    // Get balance as of specific date
    public function getBalanceAsOf($date)
    {
        $position = $this->dailyPositions()
            ->where('position_date', '<=', $date)
            ->orderBy('position_date', 'desc')
            ->first();

        return $position ? $position->available_balance : $this->opening_balance;
    }

    // Calculate total inflows for date range
    public function getInflowsForPeriod($startDate, $endDate)
    {
        return $this->transactions()
            ->where('position_date', '>=', $startDate)
            ->where('position_date', '<=', $endDate)
            ->where('transaction_type', 'inflow')
            ->sum('amount');
    }

    // Calculate total outflows for date range
    public function getOutflowsForPeriod($startDate, $endDate)
    {
        return $this->transactions()
            ->where('position_date', '>=', $startDate)
            ->where('position_date', '<=', $endDate)
            ->where('transaction_type', 'outflow')
            ->sum('amount');
    }
}
