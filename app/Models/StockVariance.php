<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockVariance extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'product_id',
        'product_stock_id',
        'quantity',
        'expected_quantity',
        'reason',
        'variance_date',
        'shift_type',
        'stage',
        'notes',
        'status',
        'resolution_type',
        'corrected_quantity',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'quantity'           => 'decimal:2',
        'expected_quantity'  => 'decimal:2',
        'corrected_quantity' => 'decimal:2',
        'variance_date'      => 'date',
        'resolved_at'        => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
