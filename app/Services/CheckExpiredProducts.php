<?php

namespace App\Services;

use App\Models\ExpiryConfirmation;
use App\Models\Product;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CheckExpiredProducts
{
    /**
     * Get expired or expiring products for a specific shift
     */
    public function getExpiredProductsForShift(int $salesShiftId, int|string $branchId, int $departmentId): Collection
    {
        $today = Carbon::today();

        // Get product stocks that are expired or expiring today
        $expiredStocks = ProductStock::with(['product', 'salesShift'])
            ->where('sales_shift_id', $salesShiftId)
            ->where('stock_date', $today)
            ->whereHas('product.productType', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->whereNotNull('expiry_date')
            ->where(function ($query) use ($today) {
                // Expired or expiring today
                $query->whereDate('expiry_date', '<=', $today);
            })
            ->get();

        // Filter out products that already have confirmations
        $expiredStocks = $expiredStocks->filter(function ($stock) use ($salesShiftId) {
            return ! $this->hasConfirmation($stock->id, $salesShiftId);
        });

        // Map to a more usable format
        return $expiredStocks->map(function ($stock) {
            $daysRemaining = $stock->getDaysRemaining();
            $shelfLifeStatus = $stock->getShelfLifeStatus();

            return [
                'product_stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'product_sku' => $stock->product->sku,
                'quantity' => $stock->opening_quantity,
                'uom' => $stock->product->unitOfMeasure?->symbol,
                'production_date' => $stock->production_date?->format('Y-m-d'),
                'expiry_date' => $stock->expiry_date?->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'shelf_life_status' => $shelfLifeStatus,
                'badge_color' => $stock->getShelfLifeBadgeColor(),
                'is_expired' => $stock->isExpired(),
            ];
        });
    }

    /**
     * Get products that are approaching expiry (warning/critical)
     */
    public function getExpiringProductsForShift(int $salesShiftId, int $branchId, int $departmentId): Collection
    {
        $today = Carbon::today();
        $warningDate = $today->copy()->addDays(2); // Products expiring in next 2 days

        $expiringStocks = ProductStock::with(['product', 'salesShift'])
            ->where('sales_shift_id', $salesShiftId)
            ->where('stock_date', $today)
            ->whereHas('product.productType', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today->copy()->addDay(), $warningDate])
            ->get();

        return $expiringStocks->map(function ($stock) {
            return [
                'product_stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'quantity' => $stock->opening_quantity,
                'uom' => $stock->product->unitOfMeasure?->symbol,
                'expiry_date' => $stock->expiry_date?->format('Y-m-d'),
                'days_remaining' => $stock->getDaysRemaining(),
                'shelf_life_status' => $stock->getShelfLifeStatus(),
            ];
        });
    }

    /**
     * Check if a product stock already has a confirmation for the shift
     */
    public function hasConfirmation(int $productStockId, int $salesShiftId): bool
    {
        return ExpiryConfirmation::where('product_stock_id', $productStockId)
            ->where('sales_shift_id', $salesShiftId)
            ->exists();
    }

    /**
     * Auto-mark expired products as callbacks if not confirmed within time limit
     *
     * @param  int  $hoursLimit  Number of hours before auto-marking
     * @return int Number of products auto-marked
     */
    public function autoMarkExpiredProducts(int $hoursLimit = 2): int
    {
        $cutoffTime = Carbon::now()->subHours($hoursLimit);
        $today = Carbon::today();

        // Get expired stocks from active shifts that haven't been confirmed
        $expiredStocks = ProductStock::with(['salesShift', 'product'])
            ->whereDate('stock_date', $today)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->whereHas('salesShift', function ($q) {
                $q->where('status', 'active');
            })
            ->get();

        $autoMarkedCount = 0;

        foreach ($expiredStocks as $stock) {
            // Check if shift started more than X hours ago and no confirmation exists
            if ($stock->salesShift->clock_in <= $cutoffTime &&
                ! $this->hasConfirmation($stock->id, $stock->sales_shift_id)) {

                // Auto-mark as callback
                $this->markAsCallback(
                    $stock->id,
                    $stock->sales_shift_id,
                    $stock->salesShift->employee_id,
                    'Auto-marked as callback: Product expired and not confirmed within '.$hoursLimit.' hours',
                    $stock->opening_quantity
                );

                $autoMarkedCount++;
            }
        }

        return $autoMarkedCount;
    }

    /**
     * Confirm a product is still good despite expiry date
     */
    public function confirmStillGood(int $productStockId, int $salesShiftId, int $employeeId, ?string $notes = null): ExpiryConfirmation
    {
        return ExpiryConfirmation::create([
            'product_stock_id' => $productStockId,
            'sales_shift_id' => $salesShiftId,
            'confirmed_by' => $employeeId,
            'action' => 'confirmed_good',
            'notes' => $notes,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark a product as callback and update the stock
     */
    public function markAsCallback(int $productStockId, int $salesShiftId, int $employeeId, ?string $notes = null, float $quantity = 0): ExpiryConfirmation
    {
        // Create confirmation record
        $confirmation = ExpiryConfirmation::create([
            'product_stock_id' => $productStockId,
            'sales_shift_id' => $salesShiftId,
            'confirmed_by' => $employeeId,
            'action' => 'marked_callback',
            'notes' => $notes,
            'confirmed_at' => now(),
        ]);

        // Update product stock callback quantity
        $productStock = ProductStock::find($productStockId);
        if ($productStock && $quantity > 0) {
            $productStock->callback_quantity = $quantity;
            $productStock->save();
        }

        return $confirmation;
    }
}
