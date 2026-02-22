<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesProductionItemMaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_production_request_item_id',
        'item_request_id',
        'created_by_id',
        'notes',
    ];

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesProductionRequestItem::class, 'sales_production_request_item_id');
    }

    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

