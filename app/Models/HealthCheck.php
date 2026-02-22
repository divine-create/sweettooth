<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'checked_by_id',
        'checked_by_type',
        'check_date',
        'condition',
        'quantity_affected',
        'observations',
        'action_taken',
    ];

    protected $casts = [
        'check_date' => 'date',
        'quantity_affected' => 'decimal:2',
    ];

    /**
     * Scope to filter by condition
     */
    public function scopeWithCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_date', [$startDate, $endDate]);
    }

    /**
     * Get the stock record
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the employee who checked
     */
    public function checker(): MorphTo
    {
        return $this->morphTo(
            'checked_by',
            'checked_by_type',
            'checked_by_id', );
    }

    /**
     * Check if condition requires action
     */
    public function requiresAction(): bool
    {
        return in_array($this->condition, ['poor', 'damaged', 'expired']);
    }

    /**
     * Check if action has been taken
     */
    public function hasActionTaken(): bool
    {
        return ! is_null($this->action_taken) && trim($this->action_taken) !== '';
    }
}
