<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Callback extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'product_id',
        'quantity',
        'reason',
        'callback_date',
        'shift_type',
        'notes',
        'status',
        'product_stock_id',
        'expected_quantity',
        'resolution_type',
        'corrected_quantity',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'quantity'           => 'decimal:2',
        'callback_date'      => 'date',
        'expected_quantity'  => 'decimal:2',
        'corrected_quantity' => 'decimal:2',
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
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }
}
