<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cash a collecting sales point holds on behalf of a home sales point for a cross-point
 * sale line. See SALES_POINT_TRANSFER_SPEC.md §3.4 and SalesPointSettlementService.
 */
class SalesPointSettlement extends Model
{
    protected $fillable = [
        'branch_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'owed_by_department_id',
        'owed_to_department_id',
        'amount',
        'status',
        'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function owedByDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owed_by_department_id');
    }

    public function owedToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owed_to_department_id');
    }
}
