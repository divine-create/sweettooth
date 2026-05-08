<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'department_id',
        'product_id',
        'quantity',
        'sales_quantity',
        'sales_uom_id',
        'conversion_factor',
        'unit_price',
        'subtotal',
        'discount',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'sales_quantity' => 'decimal:4',
        'conversion_factor' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'sales_uom_id');
    }

    /**
     * Get the effective UOM symbol (sales UOM or fall back to product's UOM)
     */
    public function getSalesUomSymbolAttribute(): string
    {
        return $this->salesUom?->symbol ?? $this->product?->uomSymbol ?? 'units';
    }

    /**
     * Get the display quantity (sales quantity if available, otherwise base quantity)
     */
    public function getDisplayQuantityAttribute(): float
    {
        return $this->sales_quantity ?? $this->quantity;
    }

    // Helper Methods
    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function calculateTotal(): float
    {
        return $this->subtotal - $this->discount;
    }

    public function updateCalculatedFields(): void
    {
        $this->subtotal = $this->calculateSubtotal();
        $this->total = $this->calculateTotal();
    }

    // Boot method to auto-calculate fields
    protected static function booted(): void
    {
        static::saving(function (SaleItem $saleItem) {
            $saleItem->updateCalculatedFields();
        });

        // Update parent sale totals when sale item changes
        static::saved(function (SaleItem $saleItem) {
            if ($saleItem->sale) {
                $saleItem->sale->calculateTotals();
                $saleItem->sale->saveQuietly();
            }

            // Update product stock - deduct quantity sold
            $sale = $saleItem->sale;
            if ($saleItem->wasRecentlyCreated && $sale) {
                // Try to find the correct ProductStock record
                // 1. Using explicit sales_shift_id (backward compatibility)
                // 2. Using department and date (new system)
                $productStock = null;
                
                if ($sale->sales_shift_id) {
                    $productStock = ProductStock::where('sales_shift_id', $sale->sales_shift_id)
                        ->where('product_id', $saleItem->product_id)
                        ->first();
                }

                // Prefer per-shift lookup (new system)
                if (!$productStock && $sale->shift_id) {
                    $productStock = ProductStock::where('shift_id', $sale->shift_id)
                        ->where('product_id', $saleItem->product_id)
                        ->first();
                }

                // Legacy fallback: department + date (only when shift_id is not set)
                if (!$productStock && $sale->department_id) {
                    $saleDate = $sale->sale_time ? $sale->sale_time->format('Y-m-d') : now()->format('Y-m-d');
                    $productStock = ProductStock::where('department_id', $sale->department_id)
                        ->where('product_id', $saleItem->product_id)
                        ->where('stock_date', $saleDate)
                        ->where('workflow_step', '!=', 'closing_completed')
                        ->orderByDesc('id')
                        ->first();
                }

                if ($productStock) {
                    // Important: We only update if this hasn't been updated manually (e.g. by POS)
                    // Or we just update and let POS not do it manually. 
                    // To be safe, we check if the amount has already changed recently.
                    // But in a transaction, this is tricky.
                    $productStock->quantity_sold = (float)$productStock->quantity_sold + $saleItem->quantity;
                    $productStock->amount = (float)$productStock->amount + $saleItem->total;
                    $productStock->updateCalculatedFields();
                    $productStock->save();
                }
            }
        });

        static::deleted(function (SaleItem $saleItem) {
            if ($saleItem->sale) {
                $saleItem->sale->calculateTotals();
                $saleItem->sale->saveQuietly();
            }

            // Restore product stock when sale item is deleted
            if ($saleItem->sale && $saleItem->sale->salesShift) {
                $productStock = ProductStock::where('sales_shift_id', $saleItem->sale->sales_shift_id)
                    ->where('product_id', $saleItem->product_id)
                    ->where('stock_date', $saleItem->sale->sale_time->format('Y-m-d'))
                    ->first();

                if ($productStock) {
                    $productStock->quantity_sold -= $saleItem->quantity;
                    $productStock->amount -= $saleItem->total;
                    $productStock->updateCalculatedFields();
                    $productStock->save();
                }
            }
        });
    }
}
