<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesPointTransfer;
use App\Models\SalesShift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Moves a produced product's on-hand between two sales points (Departments).
 *
 * Stock effect (see SALES_POINT_TRANSFER_SPEC.md §4.2):
 *   - FROM point: ProductStock.transfer_quantity += qty  (available drops)
 *   - TO   point: ProductStock.addition_quantity += qty  (available rises; row created if absent)
 * Both feed the shared availability formula, so POS / stock-opening / shift-closing pick it up.
 *
 * All work happens in one transaction with row locks so two concurrent transfers can't
 * oversell the source.
 */
class SalesPointTransferService
{
    /**
     * Transfer $quantity (base UOM) of $productId from one sales point to another and
     * record it. Completes immediately (stock moves now).
     *
     * @param  Model|null  $actor      Who initiated it (for audit + created_by).
     * @param  array  $opts            transfer_type, sale_id, notes.
     */
    public function transfer(
        int $fromDepartmentId,
        int $toDepartmentId,
        string $productId,
        float $quantity,
        ?Model $actor = null,
        array $opts = []
    ): SalesPointTransfer {
        if ($quantity <= 0) {
            throw new RuntimeException('Transfer quantity must be greater than zero.');
        }
        if ($fromDepartmentId === $toDepartmentId) {
            throw new RuntimeException('Cannot transfer a product to the same sales point.');
        }

        $product = Product::find($productId);
        if (! $product) {
            throw new RuntimeException('Product not found.');
        }

        $fromShift = $this->activeShift($fromDepartmentId);
        if (! $fromShift) {
            throw new RuntimeException('The source sales point has no active shift. Open a shift there first.');
        }

        $toShift = $this->activeShift($toDepartmentId);
        if (! $toShift) {
            throw new RuntimeException('The destination sales point has no active shift. Open a shift there first.');
        }

        return DB::transaction(function () use (
            $product, $productId, $quantity, $fromDepartmentId, $toDepartmentId, $fromShift, $toShift, $actor, $opts
        ) {
            // ── Source: lock its stock row and check availability ────────────
            $fromStock = ProductStock::where('department_id', $fromDepartmentId)
                ->where('product_id', $productId)
                ->where('stock_date', $fromShift->shift_date)
                ->where('shift_type', $fromShift->shift_type)
                ->lockForUpdate()
                ->first();

            if (! $fromStock) {
                throw new RuntimeException('The source sales point has no stock record for this product in the current shift.');
            }

            $available = $this->availableOf($fromStock);
            if ($available < $quantity) {
                throw new RuntimeException(
                    "Only {$available} available at the source sales point; cannot transfer {$quantity}."
                );
            }

            $fromStock->transfer_quantity = (float) $fromStock->transfer_quantity + $quantity;
            $fromStock->save();

            // ── Destination: find or create its stock row, add the quantity ──
            $toStock = ProductStock::where('department_id', $toDepartmentId)
                ->where('product_id', $productId)
                ->where('stock_date', $toShift->shift_date)
                ->where('shift_type', $toShift->shift_type)
                ->lockForUpdate()
                ->first();

            if ($toStock) {
                $toStock->addition_quantity = (float) $toStock->addition_quantity + $quantity;
                $toStock->save();
            } else {
                $toStock = ProductStock::create([
                    'sales_shift_id' => $toShift->id,
                    'shift_id' => null,
                    'department_id' => $toDepartmentId,
                    'product_id' => $productId,
                    'stock_date' => $toShift->shift_date,
                    'shift_type' => $toShift->shift_type,
                    'opening_quantity' => 0,
                    'addition_quantity' => $quantity,
                    'workflow_step' => 'opening_verified',
                    'is_workflow_verified' => true,
                    'verified_at' => now(),
                    'verified_by' => $actor?->getKey(),
                    'notes' => 'Received via sales-point transfer',
                ]);
            }

            $transfer = SalesPointTransfer::create([
                'branch_id' => $fromShift->branch_id,
                'from_department_id' => $fromDepartmentId,
                'to_department_id' => $toDepartmentId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_cost' => (float) ($product->cost ?? 0),
                'sale_id' => $opts['sale_id'] ?? null,
                'transfer_type' => $opts['transfer_type'] ?? 'rebalance',
                'status' => 'completed',
                'from_shift_id' => $fromShift->id,
                'to_shift_id' => $toShift->id,
                'from_product_stock_id' => $fromStock->id,
                'to_product_stock_id' => $toStock->id,
                'notes' => $opts['notes'] ?? null,
                'created_by_id' => $actor?->getKey(),
                'created_by_type' => $actor ? $actor::class : null,
                'completed_at' => now(),
            ]);

            AuditService::log(
                $actor,
                'sales_point_transfer',
                $transfer,
                "Transferred {$quantity} of {$product->name} from department {$fromDepartmentId} to {$toDepartmentId}."
            );

            return $transfer;
        });
    }

    /**
     * Reverse a completed transfer: return the quantity to the source and remove it from
     * the destination. Fails if the destination no longer has enough un-sold stock.
     */
    public function reverse(SalesPointTransfer $transfer, ?Model $actor = null): SalesPointTransfer
    {
        if ($transfer->status !== 'completed') {
            throw new RuntimeException('Only completed transfers can be reversed.');
        }

        return DB::transaction(function () use ($transfer, $actor) {
            $toStock = ProductStock::whereKey($transfer->to_product_stock_id)->lockForUpdate()->first();
            $fromStock = ProductStock::whereKey($transfer->from_product_stock_id)->lockForUpdate()->first();

            if (! $toStock || ! $fromStock) {
                throw new RuntimeException('Original stock records for this transfer no longer exist.');
            }

            $qty = (float) $transfer->quantity;

            if ($this->availableOf($toStock) < $qty) {
                throw new RuntimeException('The destination no longer has enough un-sold stock to reverse this transfer.');
            }

            $toStock->addition_quantity = max(0.0, (float) $toStock->addition_quantity - $qty);
            $toStock->save();

            $fromStock->transfer_quantity = max(0.0, (float) $fromStock->transfer_quantity - $qty);
            $fromStock->save();

            $transfer->status = 'reversed';
            $transfer->save();

            AuditService::log(
                $actor,
                'sales_point_transfer_reversed',
                $transfer,
                "Reversed transfer #{$transfer->id} ({$qty} units)."
            );

            return $transfer;
        });
    }

    /**
     * The current available quantity of a ProductStock row, in base UOM.
     * Mirrors ProductStock::calculateClosing() computed from live fields.
     */
    protected function availableOf(ProductStock $stock): float
    {
        return (float) $stock->opening_quantity
            + (float) $stock->addition_quantity
            - (float) $stock->callback_quantity
            - (float) $stock->redress_quantity
            - (float) $stock->transfer_quantity
            - (float) $stock->glovo_quantity
            - (float) $stock->quantity_sold
            - (float) ($stock->quantity_reserved ?? 0);
    }

    protected function activeShift(int $departmentId): ?SalesShift
    {
        return SalesShift::where('department_id', $departmentId)
            ->where('status', 'active')
            ->orderByDesc('clock_in')
            ->first();
    }
}
