<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyBankPosition extends Model
{
    use SoftDeletes;

    protected $table = 'daily_bank_positions';

    protected $fillable = [
        'bank_account_id',
        'position_date',
        'opening_balance',
        'inflows_total',
        'outflows_total',
        'unavailable_balance',
        'unreflected_transfers_bf',
        'unreflected_pos_bf',
        'unreflected_transfers_cd',
        'unreflected_pos_cd',
        'available_balance',
        'variance_amount',
        'variance_notes',
        'reconciled',
        'reconciled_at',
    ];

    protected $casts = [
        'position_date' => 'date',
        'opening_balance' => 'decimal:2',
        'inflows_total' => 'decimal:2',
        'outflows_total' => 'decimal:2',
        'unavailable_balance' => 'decimal:2',
        'unreflected_transfers_bf' => 'decimal:2',
        'unreflected_pos_bf' => 'decimal:2',
        'unreflected_transfers_cd' => 'decimal:2',
        'unreflected_pos_cd' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    // Relationships
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DailyBankTransaction::class);
    }

    // Scopes
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

    public function scopeReconciled($query)
    {
        return $query->where('reconciled', true);
    }

    public function scopePending($query)
    {
        return $query->where('reconciled', false);
    }

    public function scopeWithVariance($query)
    {
        return $query->where('variance_amount', '!=', 0);
    }

    // Methods
    public function calculateBalance(): void
    {
        // Unavailable = Opening + Inflows - Outflows
        $this->unavailable_balance = floatval($this->opening_balance) +
                                    floatval($this->inflows_total) -
                                    floatval($this->outflows_total);

        // Available = Unavailable + Unreflected Carry Down
        $this->available_balance = floatval($this->unavailable_balance) +
                                  floatval($this->unreflected_transfers_cd) +
                                  floatval($this->unreflected_pos_cd);

        $this->save();
    }

    public function markReconciled(): bool
    {
        $this->reconciled = true;
        $this->reconciled_at = now();
        return $this->save();
    }

    public function getNetFlowForDay(): float
    {
        return floatval($this->inflows_total) - floatval($this->outflows_total);
    }

    // Check if there's a variance with actual GL balance
    public function checkVariance($actualBalance)
    {
        $variance = floatval($this->available_balance) - $actualBalance;

        if ($variance != 0) {
            $this->variance_amount = $variance;
            $this->variance_notes = "GL Balance: {$actualBalance}, Expected: {$this->available_balance}";
            $this->save();

            return false;
        }

        return true;
    }
}
