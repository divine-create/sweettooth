<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Schema;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'department_id',
        'product_id',
        'item_id',
        'quantity',
        'sales_quantity',
        'sales_uom_id',
        'conversion_factor',
        'unit_price',
        'unit_cost',
        'line_cost',
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
        'unit_cost' => 'decimal:2',
        'line_cost' => 'decimal:2',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
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

    /**
     * Capture the cost of goods sold for this line if it has not been provided.
     *
     * Product (recipe-made) lines: cost is taken from the product's saved cost,
     * falling back to its recipe-derived estimated cost. Both are expressed per
     * sales unit (same basis as unit_price), so the line cost is cost x sales qty.
     *
     * Direct inventory item lines: cost is the stock's weighted-average cost per
     * base unit, so the line cost is cost x base quantity.
     *
     * An explicitly supplied unit_cost/line_cost is never overwritten.
     */
    public function ensureCostCaptured(): void
    {
        if ((float) ($this->unit_cost ?? 0) > 0 || (float) ($this->line_cost ?? 0) > 0) {
            return;
        }

        if ($this->product_id) {
            $product = $this->product ?: Product::find($this->product_id);
            if (! $product) {
                return;
            }

            $costPerSalesUnit = (float) ($product->cost ?? 0);
            if ($costPerSalesUnit <= 0) {
                $costPerSalesUnit = (float) ($product->estimated_cost ?? 0);
            }

            if ($costPerSalesUnit > 0) {
                $salesQty = (float) ($this->sales_quantity ?? $this->quantity ?? 0);
                $this->unit_cost = round($costPerSalesUnit, 2);
                $this->line_cost = round($costPerSalesUnit * $salesQty, 2);
            }

            return;
        }

        if ($this->item_id) {
            $avg = Stock::where('item_id', $this->item_id)
                ->when($this->sale?->branch_id, fn ($q) => $q->where('branch_id', $this->sale->branch_id))
                ->value('average_cost');

            if ($avg !== null && (float) $avg > 0) {
                $this->unit_cost = round((float) $avg, 2);
                $this->line_cost = round((float) $avg * (float) ($this->quantity ?? 0), 2);
            }
        }
    }

    // Boot method to auto-calculate fields
    protected static function booted(): void
    {
        static::saving(function (SaleItem $saleItem) {
            $saleItem->updateCalculatedFields();
            $saleItem->ensureCostCaptured();
        });

        // Update parent sale totals when sale item changes
        static::saved(function (SaleItem $saleItem) {
            if ($saleItem->sale) {
                $saleItem->sale->calculateTotals();
                $saleItem->sale->saveQuietly();
            }

            // For direct inventory item sales: decrement stocks.quantity_available
            if ($saleItem->wasRecentlyCreated && $saleItem->item_id) {
                $sale = $saleItem->sale;
                $inventoryStock = Stock::where('item_id', $saleItem->item_id)
                    ->when($sale?->branch_id, fn ($q) => $q->where('branch_id', $sale->branch_id))
                    ->first();

                if ($inventoryStock) {
                    $before = (float) $inventoryStock->quantity_available;
                    $inventoryStock->quantity_available = max(0, $before - (float) $saleItem->quantity);
                    $inventoryStock->save();

                    StockMovement::create([
                        'stock_id'        => $inventoryStock->id,
                        'branch_id'       => $inventoryStock->branch_id,
                        'type'            => 'out',
                        'quantity'        => -(float) $saleItem->quantity,
                        'quantity_before' => $before,
                        'quantity_after'  => $inventoryStock->quantity_available,
                        'unit_cost'       => (float) ($inventoryStock->average_cost ?? 0),
                        'cost_impact'     => -((float) $saleItem->quantity * (float) ($inventoryStock->average_cost ?? 0)),
                        'reference_type'  => Sale::class,
                        'reference_id'    => (string) ($sale?->id ?? ''),
                        'moved_by_type'   => \App\Models\User::class,
                        'moved_by_id'     => auth()->id(),
                        'movement_date'   => now(),
                        'notes'           => 'POS direct sale from inventory',
                    ]);
                }
                return; // skip product_stocks update — no product_stock record for items
            }

            // Update product stock - deduct quantity sold.
            // Only a completed sale consumes stock. Hold/tab sales merely reserve it
            // (the POS component manages quantity_reserved itself), so deducting here
            // would double-count against availability.
            $sale = $saleItem->sale;
            if ($saleItem->wasRecentlyCreated && $sale && $sale->status === 'completed') {
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
                    // Release any quantity previously reserved for this line (e.g. a table
                    // tab being settled) before booking it as sold, so the same units are
                    // not counted against availability twice.
                    if (Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                        $reserved = (float) ($productStock->quantity_reserved ?? 0);
                        $release = min($reserved, (float) $saleItem->quantity);
                        $productStock->quantity_reserved = max(0, $reserved - $release);
                    }
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

            // Restore inventory stock when a direct inventory item sale is deleted
            if ($saleItem->item_id) {
                $sale = $saleItem->sale;
                $inventoryStock = Stock::where('item_id', $saleItem->item_id)
                    ->when($sale?->branch_id, fn ($q) => $q->where('branch_id', $sale->branch_id))
                    ->first();

                if ($inventoryStock) {
                    $before = (float) $inventoryStock->quantity_available;
                    $inventoryStock->quantity_available = $before + (float) $saleItem->quantity;
                    $inventoryStock->save();

                    StockMovement::create([
                        'stock_id'        => $inventoryStock->id,
                        'branch_id'       => $inventoryStock->branch_id,
                        'type'            => 'in',
                        'quantity'        => (float) $saleItem->quantity,
                        'quantity_before' => $before,
                        'quantity_after'  => $inventoryStock->quantity_available,
                        'unit_cost'       => (float) ($inventoryStock->average_cost ?? 0),
                        'cost_impact'     => (float) $saleItem->quantity * (float) ($inventoryStock->average_cost ?? 0),
                        'reference_type'  => Sale::class,
                        'reference_id'    => (string) ($sale?->id ?? ''),
                        'moved_by_type'   => \App\Models\User::class,
                        'moved_by_id'     => auth()->id(),
                        'movement_date'   => now(),
                        'notes'           => 'Reversal: POS sale item deleted',
                    ]);
                }
                return;
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
