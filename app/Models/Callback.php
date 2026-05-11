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
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'callback_date' => 'date',
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


}
