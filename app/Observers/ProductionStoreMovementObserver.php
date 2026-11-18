<?php

namespace App\Observers;

use App\Models\ProductionStoreMovement;
use App\Services\ProductionStockService;

class ProductionStoreMovementObserver
{
    /**
     * Handle the ProductionStoreMovement "created" event.
     */
    public function created(ProductionStoreMovement $movement): void
    {
        try {
            app(ProductionStockService::class)->recordMovement($movement);
        } catch (\Throwable $e) {
            \Log::warning('Failed to record movement in ProductionStoreDailyStock: ' . $e->getMessage(), [
                'movement_id' => $movement->id,
            ]);
        }
    }
}
