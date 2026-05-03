<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionMaterialReturn extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'department_id',
        'shift_id',
        'item_dispatch_id',
        'item_id',
        'quantity',
        'uom',
        'reason',
        'status',
        'notes',
        'returned_by_id',
        'returned_by_type',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the branch this return belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department that initiated the return.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the shift during which the return occurred.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the specific inventory dispatch being returned.
     */
    public function itemDispatch(): BelongsTo
    {
        return $this->belongsTo(ItemDispatch::class, 'item_dispatch_id');
    }

    /**
     * Get the raw material item being returned.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the employee who initiated the return.
     */
    public function returnedBy(): MorphTo
    {
        return $this->morphTo('returned_by');
    }

    /**
     * Get the employee/user who approved the return.
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by');
    }
}
