<?php

namespace App\Models;

use App\Services\UomConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'item_id',
        'quantity',
        'uom',
        'unit_fob_fc',
        'unit_fob_ngn',
        'total_fob_fc',
        'total_fob_ngn',
        'allocated_other_costs',
        'total_cost',
        'cost_per_unit',
        'expiry_date',
        'batch_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_fob_fc' => 'decimal:2',
        'unit_fob_ngn' => 'decimal:2',
        'total_fob_fc' => 'decimal:2',
        'total_fob_ngn' => 'decimal:2',
        'allocated_other_costs' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * Get the purchase that owns the purchase item
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Calculate total FOB in FC
     */
    public function calculateTotalFobFc(): float
    {
        return $this->quantity * $this->unit_fob_fc;
    }

    /**
     * Calculate total FOB in NGN
     */
    public function calculateTotalFobNgn(): float
    {
        return $this->quantity * $this->unit_fob_ngn;
    }

    /**
     * Calculate total cost including allocated other costs
     */
    public function calculateTotalCost(): float
    {
        return $this->total_fob_ngn + $this->allocated_other_costs;
    }

    /**
     * Calculate cost per unit
     */
    public function calculateCostPerUnit(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return $this->calculateTotalCost() / $this->quantity;
    }

    /**
     * Convert purchased quantity to item base UOM (if conversion exists).
     */
    public function quantityInItemBaseUom(): float
    {
        if (! $this->item || ! $this->item->uom_id || ! $this->uom) {
            return (float) $this->quantity;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            (float) $this->quantity,
            (string) $this->uom,
            (int) $this->item->uom_id,
            [
                'branch_id' => $this->item->branch_id,
                'item_id' => (int) $this->item_id,
            ]
        );

        return $converted ?? (float) $this->quantity;
    }
}
