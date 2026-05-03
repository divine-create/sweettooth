<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestDetail extends Model
{
    protected $table = 'material_request_details';

    protected $fillable = [
        'request_id',
        'item_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_dispatched',
        'uom_id',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'quantity_dispatched' => 'decimal:2',
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

    public function getRemainingToApprove(): float
    {
        return (float) $this->quantity_requested - (float) $this->quantity_approved;
    }

    public function getRemainingToDispatch(): float
    {
        return (float) $this->quantity_approved - (float) $this->quantity_dispatched;
    }

    public function isFullyApproved(): bool
    {
        return $this->quantity_approved >= $this->quantity_requested;
    }

    public function isFullyDispatched(): bool
    {
        return $this->quantity_dispatched >= $this->quantity_approved;
    }
}