<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'adjustment_number',
        'product_id',
        'branch_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'unit_cost',
        'cost_impact',
        'adjustment_date',
        'reason',
        'approved_by',
        'approved_at',
        'created_by',
        'gl_posting_status',
        'gl_entry_id',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:4',
        'quantity_change' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'cost_impact' => 'decimal:2',
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
        'gl_posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $adjustment->adjustment_number = static::generateAdjustmentNumber();
            }

            // Calculate quantity after
            $adjustment->quantity_after = $adjustment->quantity_before + $adjustment->quantity_change;

            // Calculate cost impact
            if ($adjustment->unit_cost) {
                $adjustment->cost_impact = $adjustment->quantity_change * $adjustment->unit_cost;
            }
        });
    }

    public static function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-' . date('Ym');
        $lastAdjustment = static::withTrashed()
            ->where('adjustment_number', 'like', $prefix . '%')
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = (int) substr($lastAdjustment->adjustment_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function isIncrease(): bool
    {
        return $this->quantity_change > 0;
    }

    public function isDecrease(): bool
    {
        return $this->quantity_change < 0;
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public static function types(): array
    {
        return [
            'transfer' => 'Transfer',
            'write_off' => 'Write Off',
            'adjustment' => 'Manual Adjustment',
            'count' => 'Stock Count',
            'production' => 'Production',
            'damage' => 'Damage',
            'shrinkage' => 'Shrinkage',
        ];
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePendingApproval($query)
    {
        return $query->whereNull('approved_by');
    }
}
