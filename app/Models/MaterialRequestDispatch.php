<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaterialRequestDispatch extends Model
{
    protected $table = 'material_request_dispatches';

    protected $fillable = [
        'request_id',
        'item_id',
        'dispatched_by_id',
        'dispatched_by_type',
        'quantity',
        'uom_id',
        'dispatch_time',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'dispatch_time' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function dispatcher(): MorphTo
    {
        return $this->morphTo('dispatched_by');
    }
}