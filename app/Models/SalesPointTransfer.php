<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A movement of a produced product's on-hand from one sales point (Department) to
 * another. See SALES_POINT_TRANSFER_SPEC.md and SalesPointTransferService.
 */
class SalesPointTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'from_department_id',
        'to_department_id',
        'product_id',
        'quantity',
        'unit_cost',
        'sale_id',
        'transfer_type',
        'status',
        'from_shift_id',
        'to_shift_id',
        'from_product_stock_id',
        'to_product_stock_id',
        'notes',
        'created_by_id',
        'created_by_type',
        'approved_by_id',
        'approved_by_type',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }
}
