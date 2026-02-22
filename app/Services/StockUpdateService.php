<?php

namespace App\Services;

use App\Enums\CallbackStatus;
use App\Models\DailyProduce;
use App\Models\ProductDispatchCallback;
use App\Models\ProductionCallback;
use App\Models\ProductStock;
use App\Models\Recipe;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Centralized Stock Update Service
 *
 * This service handles all stock updates related to callbacks and dispatches.
 * Centralizing stock operations ensures:
 * - Consistent logic across the application
 * - Proper transaction handling
 * - Audit logging
 * - Error handling
 */
class StockUpdateService
{
    /**
     * Handle stock updates for a product dispatch callback
     *
     * Updates ProductStock and DailyProduce when a callback is completed.
     *
     * @param ProductDispatchCallback $callback
     * @return void
     * @throws RuntimeException
     */
    public function handleProductDispatchCallback(ProductDispatchCallback $callback): void
    {
        DB::transaction(function () use ($callback) {
            $this->updateProductStock($callback);
            $this->updateDailyProduce($callback);

            Log::info('Stock updated for product dispatch callback', [
                'callback_id' => $callback->id,
                'product_id' => $callback->product_id,
                'quantity' => $callback->quantity,
            ]);
        });
    }

    /**
     * Handle stock updates for a production callback
     *
     * Updates raw material or finished product stock when a production callback is approved.
     *
     * @param ProductionCallback $callback
     * @return void
     * @throws RuntimeException
     */
    public function handleProductionCallback(ProductionCallback $callback): void
    {
        DB::transaction(function () use ($callback) {
            if ($callback->isRawMaterial()) {
                $this->updateRawMaterialStock($callback);
            } elseif ($callback->isFinishedProduct()) {
                $this->updateFinishedProductStock($callback);
            }

            Log::info('Stock updated for production callback', [
                'callback_id' => $callback->id,
                'type' => $callback->callback_type,
                'quantity' => $callback->quantity,
            ]);
        });
    }

    /**
     * Update ProductStock when product is returned to sales
     *
     * @param ProductDispatchCallback $callback
     * @throws RuntimeException
     */
    protected function updateProductStock(ProductDispatchCallback $callback): void
    {
        $productStock = ProductStock::where('sales_shift_id', $callback->sales_shift_id)
            ->where('product_id', $callback->product_id)
            ->lockForUpdate()
            ->first();

        if (! $productStock) {
            throw new RuntimeException(
                "Product stock record not found for sales shift: {$callback->sales_shift_id}, product: {$callback->product_id}"
            );
        }

        $productStock->increment('callback_quantity', $callback->quantity);

        Log::debug('ProductStock updated', [
            'product_stock_id' => $productStock->id,
            'callback_quantity_added' => $callback->quantity,
            'new_callback_quantity' => $productStock->fresh()->callback_quantity,
        ]);
    }

    /**
     * Update DailyProduce when product is returned to production
     *
     * @param ProductDispatchCallback $callback
     * @throws RuntimeException
     */
    protected function updateDailyProduce(ProductDispatchCallback $callback): void
    {
        $productDispatch = $callback->productDispatch;
        if (! $productDispatch || ! $productDispatch->shift_id) {
            Log::warning('Product dispatch or shift not found for callback', [
                'callback_id' => $callback->id,
            ]);
            return;
        }

        $recipe = Recipe::where('product_id', $callback->product_id)->first();
        if (! $recipe) {
            Log::warning('Recipe not found for product', [
                'callback_id' => $callback->id,
                'product_id' => $callback->product_id,
            ]);
            return;
        }

        $dailyProduce = DailyProduce::where('shift_id', $productDispatch->shift_id)
            ->where('recipe_id', $recipe->id)
            ->lockForUpdate()
            ->first();

        if ($dailyProduce) {
            $dailyProduce->increment('callback_quantity', $callback->quantity);
            $dailyProduce->increment('closing_quantity', $callback->quantity);

            if (method_exists($dailyProduce, 'updateCalculations')) {
                $dailyProduce->updateCalculations();
            }

            Log::debug('DailyProduce updated', [
                'daily_produce_id' => $dailyProduce->id,
                'callback_quantity_added' => $callback->quantity,
            ]);
        } else {
            Log::warning('DailyProduce not found for callback', [
                'callback_id' => $callback->id,
                'shift_id' => $productDispatch->shift_id,
                'recipe_id' => $recipe->id,
            ]);
        }
    }

    /**
     * Update raw material stock for production callback
     *
     * @param ProductionCallback $callback
     * @throws RuntimeException
     */
    protected function updateRawMaterialStock(ProductionCallback $callback): void
    {
        if (!$callback->item_id) {
            throw new RuntimeException('Item ID is required for raw material callback');
        }

        $stock = Stock::where('item_id', $callback->item_id)
            ->where('branch_id', $callback->shift?->branch_id)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw new RuntimeException('Stock not found for raw material callback');
        }

        // Decrease available, increase damaged
        $stock->quantity_available = max(0, $stock->quantity_available - $callback->quantity);
        $stock->quantity_damaged += $callback->quantity;
        $stock->save();

        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'callback',
            'quantity' => -$callback->quantity,
            'reference_type' => 'production_callback',
            'reference_id' => $callback->id,
            'notes' => 'Production callback: '.$callback->reason,
            'movement_date' => now(),
        ]);

        Log::debug('Raw material stock updated', [
            'stock_id' => $stock->id,
            'quantity_decreased' => $callback->quantity,
        ]);
    }

    /**
     * Update finished product stock for production callback
     *
     * @param ProductionCallback $callback
     * @throws RuntimeException
     */
    protected function updateFinishedProductStock(ProductionCallback $callback): void
    {
        if (!$callback->product_id) {
            throw new RuntimeException('Product ID is required for finished product callback');
        }

        // Find daily produce record for this shift and product
        $recipe = Recipe::where('product_id', $callback->product_id)->first();

        if ($recipe && $callback->shift_id) {
            $dailyProduce = DailyProduce::where('shift_id', $callback->shift_id)
                ->where('recipe_id', $recipe->id)
                ->lockForUpdate()
                ->first();

            if ($dailyProduce) {
                // Add to callback quantity
                $dailyProduce->increment('callback_quantity', $callback->quantity);

                if (method_exists($dailyProduce, 'updateCalculations')) {
                    $dailyProduce->updateCalculations();
                }

                Log::debug('Finished product stock updated via DailyProduce', [
                    'daily_produce_id' => $dailyProduce->id,
                    'callback_quantity_added' => $callback->quantity,
                ]);
            }
        }
    }

    /**
     * Reverse stock update for a cancelled callback
     *
     * @param ProductDispatchCallback $callback
     * @return void
     */
    public function reverseProductDispatchCallback(ProductDispatchCallback $callback): void
    {
        if ($callback->status !== CallbackStatus::COMPLETED) {
            return;
        }

        DB::transaction(function () use ($callback) {
            // Reverse ProductStock update
            $productStock = ProductStock::where('sales_shift_id', $callback->sales_shift_id)
                ->where('product_id', $callback->product_id)
                ->lockForUpdate()
                ->first();

            if ($productStock) {
                $productStock->decrement('callback_quantity', $callback->quantity);
            }

            // Reverse DailyProduce update
            $productDispatch = $callback->productDispatch;
            if ($productDispatch && $productDispatch->shift_id) {
                $recipe = Recipe::where('product_id', $callback->product_id)->first();
                if ($recipe) {
                    $dailyProduce = DailyProduce::where('shift_id', $productDispatch->shift_id)
                        ->where('recipe_id', $recipe->id)
                        ->lockForUpdate()
                        ->first();

                    if ($dailyProduce) {
                        $dailyProduce->decrement('callback_quantity', $callback->quantity);
                        $dailyProduce->decrement('closing_quantity', $callback->quantity);

                        if (method_exists($dailyProduce, 'updateCalculations')) {
                            $dailyProduce->updateCalculations();
                        }
                    }
                }
            }

            Log::info('Stock update reversed for callback', [
                'callback_id' => $callback->id,
            ]);
        });
    }

    /**
     * Validate that stock can accommodate a callback
     *
     * @param int $productId
     * @param int $salesShiftId
     * @param float $quantity
     * @return bool
     */
    public function canAccommodateCallback(int $productId, int $salesShiftId, float $quantity): bool
    {
        $productStock = ProductStock::where('sales_shift_id', $salesShiftId)
            ->where('product_id', $productId)
            ->first();

        if (!$productStock) {
            return false;
        }

        // Check if callback quantity doesn't exceed what's available
        $availableForCallback = $productStock->quantity_available ?? 0;

        return $quantity <= $availableForCallback;
    }
}
