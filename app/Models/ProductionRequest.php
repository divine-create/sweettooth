<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ProductionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'item_request_id',
        'recipe_id',
        'planned_production_quantity',
        'requested_units',
        'batches_requested',
        'batches_produced',
        'batches_pending',
        'partial_fulfillment_allowed',
        'fulfillment_status',
        'notes',
        'production_department_id',
        'status',
        'source_type',
        'source_id',
        'priority',
        'created_by_id',
        'started_at',
        'completed_at',
        'eta_override_minutes',
    ];

    protected $casts = [
        'planned_production_quantity' => 'decimal:2',
        'requested_units' => 'decimal:2',
        'batches_requested' => 'integer',
        'batches_produced' => 'integer',
        'batches_pending' => 'integer',
        'partial_fulfillment_allowed' => 'boolean',
        'eta_override_minutes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $request) {
            self::normalizeBatchTracking($request);
        });
    }

    /**
     * Scope to filter by shift
     */
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to filter by branch (via item request)
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->whereHas('itemRequest', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    /**
     * Get the shift this production request is assigned to
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the item request that triggered this production
     */
    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    /**
     * Get the recipe to be used for production
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class)->withDefault();
    }

    /**
     * Get the production department assigned to this request
     */
    public function productionDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'production_department_id');
    }

    /**
     * Get the user who created this request
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get progress feedback for this request
     */
    public function progressFeedback()
    {
        return $this->hasMany(ProductionProgressFeedback::class);
    }

    /**
     * Get dispatches for this request
     */
    public function dispatches()
    {
        return $this->hasMany(ProductDispatch::class, 'production_request_id');
    }

    /**
     * Daily produce rows linked to this production request.
     */
    public function dailyProduces(): HasMany
    {
        return $this->hasMany(DailyProduce::class, 'production_request_id');
    }

    /**
     * Production records linked through daily produce rows.
     */
    public function productionRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductionRecord::class,
            DailyProduce::class,
            'production_request_id',
            'daily_produce_id',
            'id',
            'id'
        );
    }

    /**
     * Check if production request has been assigned a recipe
     */
    public function hasRecipe(): bool
    {
        return ! is_null($this->recipe_id);
    }

    /**
     * Check if production request is ready for production
     */
    public function isReadyForProduction(): bool
    {
        return $this->shift_id &&
               $this->recipe_id &&
               $this->planned_production_quantity > 0;
    }

    /**
     * Get the computed status based on item request details
     */
    public function getComputedStatus(): string
    {
        if (! $this->itemRequest) {
            return 'unknown';
        }

        // Check if cancelled
        if ($this->itemRequest->status === 'cancelled') {
            return 'cancelled';
        }

        // Check request details to determine status
        $details = $this->itemRequest->requestDetails;

        if ($details->isEmpty()) {
            return 'pending';
        }

        $allCompleted = true;
        $anyPartiallyDispatched = false;
        $anyPartiallyApproved = false;
        $anyApproved = false;
        $allPending = true;

        foreach ($details as $detail) {
            $requested = (float) $detail->quantity_requested;
            $approved = (float) $detail->quantity_approved;
            $dispatched = (float) $detail->quantity_dispatched;

            // Check if this item is pending (approved = 0, dispatched = 0)
            if ($approved > 0 || $dispatched > 0) {
                $allPending = false;
            }

            // Check if this item is completed (requested = approved = dispatched AND not 0)
            if (! ($requested == $approved && $approved == $dispatched && $requested > 0)) {
                $allCompleted = false;
            }

            // Check if partially dispatched (dispatched > 0 but < approved)
            if ($dispatched > 0 && $dispatched < $approved) {
                $anyPartiallyDispatched = true;
            }

            // Check if partially approved (approved > 0 but < requested, and dispatched = 0)
            if ($approved > 0 && $approved < $requested) {
                $anyPartiallyApproved = true;
            }

            // Check if fully approved (requested = approved > 0, dispatched = 0)
            if ($approved > 0 && $requested == $approved && $dispatched == 0) {
                $anyApproved = true;
            }
        }

        // Determine status based on priority
        if ($allCompleted) {
            return 'completed';
        } elseif ($anyPartiallyDispatched) {
            return 'partially_dispatched';
        } elseif ($anyPartiallyApproved) {
            return 'partially_approved';
        } elseif ($anyApproved) {
            return 'approved';
        } elseif ($allPending) {
            return 'pending';
        }

        return 'pending';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        $status = $this->getComputedStatus();

        return match ($status) {
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'partially_dispatched' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'partially_approved' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'approved' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200',
        };
    }

    /**
     * Check if request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        // For direct production requests (no itemRequest yet)
        if (!$this->itemRequest) {
            return in_array($this->status, ['pending', 'approved']);
        }

        // For production-to-store requests (has itemRequest)
        return $this->itemRequest->status !== 'cancelled' &&
               $this->getComputedStatus() !== 'completed';
    }

    /**
     * Check if this is a production-to-store request
     */
    public function isProductionToStoreRequest(): bool
    {
        return !is_null($this->item_request_id);
    }

    public static function calculateBatchesFromQuantity(float $quantity, float $yieldPerBatch): int
    {
        if ($quantity <= 0 || $yieldPerBatch <= 0) {
            return 0;
        }

        return (int) ceil($quantity / $yieldPerBatch);
    }

    public function getPendingBatches(): int
    {
        return max(0, (int) ($this->batches_pending ?? 0));
    }

    public function canProduceMoreBatches(int $count = 1): bool
    {
        return $this->partial_fulfillment_allowed &&
            $this->getPendingBatches() >= $count &&
            ($this->fulfillment_status ?? 'pending') !== 'completed';
    }

    /**
     * Recalculate batch counters based on linked daily produce production.
     */
    public function syncBatchFulfillmentFromDailyProduces(bool $persist = true): void
    {
        $yieldPerBatch = (float) ($this->recipe?->yield_quantity ?? 0);
        $totalProducedUnits = (float) $this->dailyProduces()->sum('produced_quantity');

        $producedBatches = $yieldPerBatch > 0
            ? (int) floor($totalProducedUnits / $yieldPerBatch)
            : 0;

        $requestedBatches = (int) ($this->batches_requested ?? 0);
        $this->batches_produced = $producedBatches;
        $this->batches_pending = max(0, $requestedBatches - $producedBatches);
        $this->fulfillment_status = self::deriveFulfillmentStatus($requestedBatches, $producedBatches);

        if ($persist) {
            $this->saveQuietly();
        }
    }

    public static function deriveFulfillmentStatus(int $requestedBatches, int $producedBatches): string
    {
        if ($requestedBatches <= 0) {
            return 'pending';
        }
        if ($producedBatches > $requestedBatches) {
            return 'exceeded';
        }
        if ($producedBatches === 0) {
            return 'pending';
        }
        if ($producedBatches >= $requestedBatches) {
            return 'completed';
        }

        return 'partial';
    }

    private static function normalizeBatchTracking(self $request): void
    {
        $plannedQty = (float) ($request->planned_production_quantity ?? 0);
        $yieldPerBatch = (float) ($request->recipe?->yield_quantity ?? 0);

        if ($yieldPerBatch <= 0 && $request->recipe_id) {
            $yieldPerBatch = (float) (Recipe::whereKey($request->recipe_id)->value('yield_quantity') ?? 0);
        }

        if ($yieldPerBatch > 0 && $plannedQty > 0) {
            $request->batches_requested = self::calculateBatchesFromQuantity($plannedQty, $yieldPerBatch);
        }

        $requestedBatches = max(0, (int) ($request->batches_requested ?? 0));
        $producedBatches = max(0, (int) ($request->batches_produced ?? 0));
        $request->batches_pending = max(0, $requestedBatches - $producedBatches);
        $request->fulfillment_status = self::deriveFulfillmentStatus($requestedBatches, $producedBatches);
    }
}
