<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionStoreDailyStock extends Model
{
    use HasFactory;

    protected $table = 'production_store_daily_stocks';

    protected $fillable = [
        'store_id',
        'item_id',
        'stock_date',
        'shift_id',
        'opening_quantity',
        'addition_quantity',
        'quantity_used',
        'closing_quantity',
        'variance',
        'workflow_step',
        'is_workflow_verified',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'stock_date' => 'date',
        'opening_quantity' => 'decimal:2',
        'addition_quantity' => 'decimal:2',
        'quantity_used' => 'decimal:2',
        'closing_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'is_workflow_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(ProductionStore::class, 'store_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isRawMaterial(): bool
    {
        return is_numeric($this->item_id);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->isRawMaterial()) {
            return $this->item?->name ?? 'Raw Material #'.$this->item_id;
        }
        return $this->product?->name ?? 'Product #'.$this->item_id;
    }

    public function getUomSymbolAttribute(): string
    {
        if ($this->isRawMaterial()) {
            return $this->item?->unitOfMeasure?->symbol ?? ($this->item?->uom ?? 'units');
        }
        return $this->product?->unitOfMeasure?->symbol ?? 'units';
    }

    /**
     * Calculate variance and other derived fields.
     */
    public function updateCalculatedFields(): void
    {
        $theoretical_closing = (float)$this->opening_quantity + (float)$this->addition_quantity - (float)$this->quantity_used;
        $this->variance = (float)$this->closing_quantity - $theoretical_closing;
    }
}
