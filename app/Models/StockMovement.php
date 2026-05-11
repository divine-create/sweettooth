<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\GlEntry;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'branch_id',
        'type',
        'adjustment_reason',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'cost_impact',
        'reference_type',
        'reference_id',
        'moved_by_type',
        'moved_by_id',
        'movement_date',
        'notes',
        'gl_entry_id',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'cost_impact' => 'decimal:2',
        'movement_date' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (StockMovement $movement) {
            if (! $movement->branch_id && $movement->stock_id) {
                $movement->branch_id = Stock::where('id', $movement->stock_id)->value('branch_id');
            }
            if (! $movement->movement_date) {
                $movement->movement_date = now();
            }
        });
    }

    // Automatically include department_name in all collections/JSON
    protected $appends = ['department_name'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function mover(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'moved_by_type', 'moved_by_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id')
            ->withDefault(null);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class, 'gl_entry_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function isInbound(): bool
    {
        return in_array($this->type, ['in', 'return']);
    }

    public function isOutbound(): bool
    {
        return in_array($this->type, ['out', 'damaged', 'transfer']);
    }

    /**
     * Smart accessor: returns correct department name for ALL reference types
     * Avoids lazy-loading by checking if reference is already loaded
     */
    public function getDepartmentNameAttribute(): ?string
    {
        // Handle invalid/missing reference types (manual_adjustment, production_callback, etc.)
        if (!$this->reference_type || strpos($this->reference_type, '\\') === false) {
            return ucfirst(str_replace('_', ' ', $this->reference_type ?? 'Manual'));
        }

        // Only access reference if it's already been loaded to avoid lazy-loading errors
        if (!$this->relationLoaded('reference')) {
            return null;
        }

        $ref = $this->getRelation('reference');
        if (! $ref) {
            return null;
        }

        // 1. Purchase → show "Purchases"
        if (is_a($ref, \App\Models\Purchase::class, true)) {
            return 'Purchases';
        }

        // 2. MaterialRequestDetail → get from request
        if (is_a($ref, \App\Models\MaterialRequestDetail::class, true)) {
            return $ref->request?->department?->name;
        }

        // 3. ItemRequest, Issue, Transfer, etc. → try common relations
        $relations = ['department', 'fromDepartment', 'toDepartment', 'from_department', 'to_department', 'request'];

        foreach ($relations as $relation) {
            if (method_exists($ref, $relation)) {
                $dept = $ref->{$relation};
                if ($dept) {
                    return $dept->name ?? $dept;
                }
            }
        }

        // 3. Fallback: show model name
        return class_basename($ref);
    }
}
