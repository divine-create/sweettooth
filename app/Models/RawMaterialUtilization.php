<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialUtilization extends Model
{
    protected $fillable = [
        'shift_id',
        'recipe_id',
        'item_id',
        'quantity_required',
        'quantity_used',
        'units_produced',
        'variance',
        'variance_type',
        'cost_impact',
        'notes',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'quantity_used' => 'decimal:4',
        'units_produced' => 'decimal:2',
        'variance' => 'decimal:4',
        'variance_type' => 'string',
        'cost_impact' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
