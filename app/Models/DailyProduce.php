<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyProduce extends Model
{
    protected $fillable = [
        'shift_id',
        'production_request_id',
        'recipe_id',
        'produce_date',
        'shift_type',
        'opening_quantity',
        'requested_quantity',
        'produced_quantity',
        'batches_produced_this_shift',
        'batches_remaining',
        'fulfillment_status',
        'sent_out_quantity',
        'order_quantity',
        'callback_quantity',
        'closing_quantity',
        'expected_closing',
        'variance',
        'status',
        'notes',
    ];

    protected $casts = [
        'produce_date' => 'date',
        'shift_type' => 'string',
        'opening_quantity' => 'decimal:2',
        'requested_quantity' => 'decimal:2',
        'produced_quantity' => 'decimal:2',
        'batches_produced_this_shift' => 'integer',
        'batches_remaining' => 'integer',
        'sent_out_quantity' => 'decimal:2',
        'order_quantity' => 'decimal:2',
        'callback_quantity' => 'decimal:2',
        'closing_quantity' => 'decimal:2',
        'expected_closing' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function productionRequest(): BelongsTo
    {
        return $this->belongsTo(ProductionRequest::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class);
    }

    /**
     * Get net available quantity (produced minus callbacks/damaged)
     * Formula: Produced - Callback
     */
    public function getNetAvailable(): float
    {
        return $this->produced_quantity - $this->callback_quantity;
    }

    /**
     * Calculate expected closing quantity
     * Formula: Opening + Net Available - Sent Out - Order
     */
    public function calculateExpectedClosing(): float
    {
        $netAvailable = $this->getNetAvailable();

        return $this->opening_quantity +
               $netAvailable -
               $this->sent_out_quantity -
               $this->order_quantity;
    }

    /**
     * Calculate variance
     * Formula: Closing - Expected Closing
     */
    public function calculateVariance(): float
    {
        $expectedClosing = $this->calculateExpectedClosing();

        return $this->closing_quantity - $expectedClosing;
    }

    /**
     * Auto-calculate and save expected closing and variance
     */
    public function updateCalculations(): void
    {
        $this->expected_closing = $this->calculateExpectedClosing();
        $this->variance = $this->calculateVariance();
        $this->save();
    }

    /**
     * Get opening quantity from previous shift's closing
     */
    public static function getOpeningQuantityFromPreviousShift($recipeId, $branchId, $currentShiftDate, $currentShiftType): float
    {
        // Determine previous shift
        $previousShift = null;

        if ($currentShiftType === 'afternoon') {
            // If current is afternoon, previous is morning of same day
            $previousShift = static::whereHas('shift', function ($q) use ($branchId, $currentShiftDate) {
                $q->where('branch_id', $branchId)
                    ->where('shift_date', $currentShiftDate)
                    ->where('shift_type', 'morning');
            })
                ->where('recipe_id', $recipeId)
                ->first();
        } else {
            // If current is morning, previous is afternoon of previous day
            $previousDate = \Carbon\Carbon::parse($currentShiftDate)->subDay();
            $previousShift = static::whereHas('shift', function ($q) use ($branchId, $previousDate) {
                $q->where('branch_id', $branchId)
                    ->where('shift_date', $previousDate)
                    ->where('shift_type', 'afternoon');
            })
                ->where('recipe_id', $recipeId)
                ->first();
        }

        return $previousShift ? (float) $previousShift->closing_quantity : 0;
    }

    /**
     * Auto-create DailyProduce records from ProductionRequests for a shift
     */
    public static function autoCreateFromProductionRequests($shift)
    {
        $productionRequests = \App\Models\ProductionRequest::where('shift_id', $shift->id)
            ->with('recipe')
            ->get();

        $created = [];

        foreach ($productionRequests as $request) {
            if (! $request->recipe_id || ! $request->recipe) {
                continue;
            }

            // Get opening quantity from previous shift
            $openingQty = static::getOpeningQuantityFromPreviousShift(
                $request->recipe_id,
                $shift->branch_id,
                $shift->shift_date,
                $shift->shift_type
            );

            $dailyProduce = static::firstOrCreate([
                'shift_id' => $shift->id,
                'recipe_id' => $request->recipe_id,
                'production_request_id' => $request->id,
            ], [
                'produce_date' => $shift->shift_date,
                'shift_type' => $shift->shift_type,
                'opening_quantity' => $openingQty,
                'requested_quantity' => $request->planned_production_quantity,
                'produced_quantity' => 0,
                'batches_produced_this_shift' => 0,
                'batches_remaining' => $request->batches_requested,
                'fulfillment_status' => 'pending',
                'sent_out_quantity' => 0,
                'order_quantity' => 0,
                'callback_quantity' => 0,
                'closing_quantity' => $openingQty, // Initially same as opening
                'expected_closing' => $openingQty,
                'variance' => 0,
            ]);

            $created[] = $dailyProduce;
        }

        return $created;
    }

    /**
     * Get total produced quantity (sum of all production records)
     */
    public function getTotalProducedFromRecords(): float
    {
        return $this->productionRecords()->sum('quantity_approved');
    }

    /**
     * Check if there's a variance issue (variance exceeds acceptable threshold)
     */
    public function hasVarianceIssue($threshold = 5): bool
    {
        return abs($this->variance) > $threshold;
    }

    /**
     * Get variance percentage
     * Fixed to handle negative expected_closing and edge cases
     */
    public function getVariancePercentage(): float
    {
        // If expected closing is 0, check if there's actual variance
        if ($this->expected_closing == 0) {
            // If closing is also 0, no variance
            if ($this->closing_quantity == 0) {
                return 0;
            }

            // If expected is 0 but actual closing exists, it's 100% variance
            return $this->variance > 0 ? 100 : -100;
        }

        // For negative expected closing (over-usage scenario)
        // Calculate based on absolute value to avoid confusing negative percentages
        $percentage = ($this->variance / abs($this->expected_closing)) * 100;

        // Cap at reasonable bounds (-100% to 100%)
        return max(-100, min(100, $percentage));
    }

    /**
     * Get computed production status based on ItemRequest and production data
     */
    public function getComputedProductionStatus(): string
    {
        // Get the production request for this produce
        $productionRequest = $this->production_request_id
            ? \App\Models\ProductionRequest::with('itemRequest.requestDetails')->find($this->production_request_id)
            : \App\Models\ProductionRequest::where('shift_id', $this->shift_id)
                ->where('recipe_id', $this->recipe_id)
                ->with('itemRequest.requestDetails')
                ->first();

        if (! $productionRequest || ! $productionRequest->itemRequest) {
            return 'no_request';
        }

        $itemRequest = $productionRequest->itemRequest;
        $details = $itemRequest->requestDetails;

        // Check ItemRequest status (ingredients status)
        $allDispatched = true;
        $anyDispatched = false;
        $allApproved = true;
        $anyApproved = false;

        foreach ($details as $detail) {
            $requested = (float) $detail->quantity_requested;
            $approved = (float) $detail->quantity_approved;
            $dispatched = (float) $detail->quantity_dispatched;

            // Check approval status
            if ($approved == 0) {
                $allApproved = false;
            } else {
                $anyApproved = true;
            }

            // Check dispatch status
            if ($approved > 0 && $dispatched < $approved) {
                $allDispatched = false;
            }
            if ($dispatched > 0) {
                $anyDispatched = true;
            }
        }

        // Now check production progress
        $hasProduced = $this->produced_quantity > 0;
        $hasCompleted = $this->status === 'completed';
        $hasClosing = $this->closing_quantity > 0 || $this->sent_out_quantity > 0;

        // Determine status based on workflow
        if ($hasCompleted) {
            return 'completed';
        } elseif ($hasProduced && $hasClosing) {
            return 'production_complete'; // Produced and distributed
        } elseif ($hasProduced) {
            return 'producing'; // Currently producing
        } elseif ($allDispatched && $anyDispatched) {
            return 'ready_to_produce'; // All ingredients dispatched, ready to start
        } elseif ($anyDispatched) {
            return 'partially_dispatched'; // Some ingredients dispatched
        } elseif ($allApproved && $anyApproved) {
            return 'approved'; // All approved, waiting dispatch
        } elseif ($anyApproved) {
            return 'partially_approved'; // Some approved
        }

        return 'pending'; // Nothing approved yet
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        $status = $this->getComputedProductionStatus();

        return match ($status) {
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'production_complete' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
            'producing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'ready_to_produce' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            'partially_dispatched' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
            'approved' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'partially_approved' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'no_request' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200',
        };
    }

    /**
     * Can production start? (All ingredients must be dispatched)
     */
    public function canStartProduction(): bool
    {
        $status = $this->getComputedProductionStatus();

        return in_array($status, ['ready_to_produce', 'producing', 'production_complete']);
    }

    /**
     * Calculate how many products can actually be produced based on dispatched ingredients
     * Returns: [
     *   'producable_quantity' => X,
     *   'requested_quantity' => Y,
     *   'shortage' => Y - X,
     *   'shortage_percentage' => %,
     *   'limiting_ingredient' => 'ingredient_name',
     *   'ingredient_analysis' => [...],
     *   'can_produce_full_batch' => true/false
     * ]
     */
    public function calculateProducableQuantity(): array
    {
        // Get the production request
        $productionRequest = $this->production_request_id
            ? \App\Models\ProductionRequest::with([
                'recipe.ingredients.item',
                'itemRequest.requestDetails.item',
            ])->find($this->production_request_id)
            : \App\Models\ProductionRequest::where('shift_id', $this->shift_id)
                ->where('recipe_id', $this->recipe_id)
                ->with([
                    'recipe.ingredients.item',
                    'itemRequest.requestDetails.item',
                ])
                ->first();

        if (! $productionRequest || ! $productionRequest->recipe) {
            return [
                'producable_quantity' => 0,
                'requested_quantity' => $this->requested_quantity,
                'shortage' => $this->requested_quantity,
                'shortage_percentage' => 100,
                'limiting_ingredient' => 'No recipe found',
                'ingredient_analysis' => [],
                'can_produce_full_batch' => false,
            ];
        }

        $recipe = $productionRequest->recipe;
        $requestedQty = (float) $this->requested_quantity;
        $recipeYield = (float) $recipe->yield_quantity;

        // Calculate how many batches are needed
        $requestedBatches = $requestedQty / $recipeYield;

        $itemRequest = $productionRequest->itemRequest;

        if (! $itemRequest) {
            return [
                'producable_quantity' => 0,
                'requested_quantity' => $requestedQty,
                'shortage' => $requestedQty,
                'shortage_percentage' => 100,
                'limiting_ingredient' => 'No item request',
                'ingredient_analysis' => [],
                'can_produce_full_batch' => false,
            ];
        }

        $ingredientAnalysis = [];
        $minProducableBatches = PHP_FLOAT_MAX;
        $limitingIngredient = null;
        $hasLimitingIngredient = false;

        // Analyze each recipe ingredient
        foreach ($recipe->ingredients as $recipeIngredient) {
            $itemId = $recipeIngredient->item_id;
            $qtyNeededPerBatch = (float) $recipeIngredient->quantity;

            // Find matching item request detail
            $requestDetail = $itemRequest->requestDetails->firstWhere('item_id', $itemId);

            $requested = $requestDetail ? (float) $requestDetail->quantity_requested : 0;
            $approved = $requestDetail ? (float) $requestDetail->quantity_approved : 0;
            $dispatched = $requestDetail ? (float) $requestDetail->quantity_dispatched : 0;

            // Calculate how many batches can be made with dispatched quantity
            $producableBatchesFromThisIngredient = 0;
            $neededForRequestedBatches = $qtyNeededPerBatch * $requestedBatches;
            $remainingAfterProduction = $dispatched - $neededForRequestedBatches;

            if ($qtyNeededPerBatch > 0) {
                if ($dispatched >= $neededForRequestedBatches) {
                    // Can produce all requested batches
                    $producableBatchesFromThisIngredient = $requestedBatches;
                } else {
                    // Allow fractional batches based on dispatched quantity
                    $producableBatchesFromThisIngredient = $dispatched / $qtyNeededPerBatch;
                }
            }

            // Check if this ingredient is limiting
            $isLimiting = $producableBatchesFromThisIngredient < $requestedBatches;

            $analysis = [
                'item_name' => $recipeIngredient->item->name ?? 'Unknown',
                'quantity_per_batch' => $qtyNeededPerBatch,
                'total_needed_for_request' => $neededForRequestedBatches,
                'quantity_requested' => $requested,
                'quantity_approved' => $approved,
                'quantity_dispatched' => $dispatched,
                'quantity_used' => min($dispatched, $neededForRequestedBatches),
                'quantity_remaining' => max(0, $dispatched - $neededForRequestedBatches),
                'producable_batches' => $producableBatchesFromThisIngredient,
                'is_limiting' => $isLimiting,
                'shortage' => max(0, $neededForRequestedBatches - $dispatched),
                'uom' => $recipeIngredient->item->unitOfMeasure?->symbol ?? '',
            ];

            $ingredientAnalysis[] = $analysis;

            // Track minimum producable batches (limiting ingredient)
            if ($producableBatchesFromThisIngredient < $minProducableBatches) {
                $minProducableBatches = $producableBatchesFromThisIngredient;
                $limitingIngredient = $recipeIngredient->item->name ?? 'Unknown';
                $hasLimitingIngredient = true;
            }
        }

        // If no ingredients, can't produce
        if ($minProducableBatches === PHP_FLOAT_MAX) {
            $minProducableBatches = 0;
        }

        // Convert to final producable units
        // Cap producable batches at requested batches
        $minProducableBatches = min($minProducableBatches, $requestedBatches);
        $producableUnits = $minProducableBatches * $recipeYield;

        // Only mark ingredients as limiting if they actually limit production
        if ($hasLimitingIngredient && $minProducableBatches < $requestedBatches) {
            foreach ($ingredientAnalysis as &$analysis) {
                // Keep the limiting flag as calculated above
            }
        } else {
            // If we can produce all requested batches, no limiting ingredients
            foreach ($ingredientAnalysis as &$analysis) {
                $analysis['is_limiting'] = false;
            }
        }

        $shortage = max(0, $requestedQty - $producableUnits);
        $shortagePercentage = $requestedQty > 0 ? ($shortage / $requestedQty) * 100 : 0;

        return [
            'producable_quantity' => $producableUnits,
            'requested_quantity' => $requestedQty,
            'shortage' => $shortage,
            'shortage_percentage' => round($shortagePercentage, 2),
            'limiting_ingredient' => $limitingIngredient,
            'ingredient_analysis' => $ingredientAnalysis,
            'can_produce_full_batch' => $producableUnits >= $requestedQty,
        ];
    }

    /**
     * Convert quantity-based producability into integer batch output.
     */
    public function calculateProducableBatches(): array
    {
        $result = $this->calculateProducableQuantity();
        $yieldPerBatch = (float) ($this->recipe?->yield_quantity ?? 0);

        if ($yieldPerBatch <= 0) {
            return [
                'producable_batches' => 0,
                'requested_batches' => 0,
                'pending_batches' => 0,
                'producable_quantity' => (float) ($result['producable_quantity'] ?? 0),
                'shortage' => (float) ($result['shortage'] ?? 0),
                'limiting_ingredient' => $result['limiting_ingredient'] ?? null,
                'ingredient_analysis' => $result['ingredient_analysis'] ?? [],
                'can_produce_full_request' => false,
            ];
        }

        $requestedQuantity = (float) ($result['requested_quantity'] ?? 0);
        $producableQuantity = (float) ($result['producable_quantity'] ?? 0);
        $requestedBatches = (int) ceil($requestedQuantity / $yieldPerBatch);
        $producableBatches = (int) floor($producableQuantity / $yieldPerBatch);

        return [
            'producable_batches' => max(0, $producableBatches),
            'requested_batches' => max(0, $requestedBatches),
            'pending_batches' => max(0, $requestedBatches - $producableBatches),
            'producable_quantity' => $producableQuantity,
            'shortage' => (float) ($result['shortage'] ?? 0),
            'limiting_ingredient' => $result['limiting_ingredient'] ?? null,
            'ingredient_analysis' => $result['ingredient_analysis'] ?? [],
            'can_produce_full_request' => $producableBatches >= $requestedBatches,
        ];
    }

    /**
     * Recompute per-shift and request-level batch fulfillment counters.
     */
    public function syncBatchFulfillmentFields(bool $persist = true): void
    {
        $yieldPerBatch = (float) ($this->recipe?->yield_quantity ?? 0);
        $producedUnits = (float) ($this->produced_quantity ?? 0);
        $requestedBatches = (int) ($this->productionRequest?->batches_requested ?? 0);

        $producedBatches = $yieldPerBatch > 0 ? (int) floor($producedUnits / $yieldPerBatch) : 0;
        $this->batches_produced_this_shift = $producedBatches;
        $this->batches_remaining = $requestedBatches > 0 ? max(0, $requestedBatches - $producedBatches) : null;
        $this->fulfillment_status = ProductionRequest::deriveFulfillmentStatus($requestedBatches, $producedBatches);

        if ($persist) {
            $this->saveQuietly();
        }

        if ($this->productionRequest) {
            $this->productionRequest->syncBatchFulfillmentFromDailyProduces();
        }
    }
}
