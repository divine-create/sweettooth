<?php

namespace App\Models;

use App\Helpers\CleanError;
use App\Enums\CallbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductDispatchCallback Model
 * 
 * Represents product return callbacks from Sales to Production.
 * Tracks the workflow: pending → approved_by_production → received_by_production → completed
 * 
 * When customers return products, they're recorded as callbacks.
 * Production must approve the return, then mark it as received.
 * Upon completion, stock updates are triggered automatically.
 * 
 * @property int $id
 * @property int|null $product_dispatch_id Reference to original product dispatch
 * @property int|null $sales_shift_id Sales shift when return occurred
 * @property int $product_id Product being returned
 * @property int|null $recorded_by_id ID of actor who recorded callback
 * @property string|null $recorded_by_type Class name of actor (Employee or User)
 * @property decimal $quantity Quantity being returned
 * @property string $uom Unit of measure
 * @property string $reason Reason for return (damaged, defective, etc.)
 * @property string $status Current status (enum: CallbackStatus)
 * @property int|null $approved_by_id ID of actor who approved
 * @property string|null $approved_by_type Class name of approving actor
 * @property \DateTime|null $approved_at When approval occurred
 * @property int|null $received_by_id ID of actor who received
 * @property string|null $received_by_type Class name of receiving actor
 * @property \DateTime|null $received_at When received
 * @property string|null $notes Additional notes
 * @property \DateTime $callback_time When callback was recorded
 * 
 * @relationship ProductDispatch productDispatch() Optionally related product dispatch
 * @relationship SalesShift salesShift() Sales shift from which return came
 * @relationship Product product() Product being returned
 * @relationship Employee|User recordedBy() Polymorphic: who recorded the callback
 * @relationship Employee|User approvedBy() Polymorphic: who approved the return
 * @relationship Employee|User receivedBy() Polymorphic: who received the return
 */
class ProductDispatchCallback extends Model
{
    protected $fillable = [
        'product_dispatch_id',
        'sales_shift_id',
        'product_id',
        'recorded_by_id',
        'recorded_by_type',
        'quantity',
        'uom',
        'reason',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'received_by_id',
        'received_by_type',
        'received_at',
        'notes',
        'callback_time',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'callback_time' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'status' => CallbackStatus::class,
    ];

    /**
     * Boot the model with validation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($callback) {
            $callback->validateQuantity();
        });

        static::updating(function ($callback) {
            // Only validate quantity if quantity changed
            if ($callback->isDirty('quantity')) {
                $callback->validateQuantity();
            }
        });
    }

    /**
     * Relationship: Product Dispatch
     */
    public function productDispatch(): BelongsTo
    {
        return $this->belongsTo(ProductDispatch::class);
    }

    /**
     * Relationship: Sales Shift
     */
    public function salesShift(): BelongsTo
    {
        return $this->belongsTo(SalesShift::class);
    }

    /**
     * Relationship: Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: User or Employee who recorded the callback (polymorphic)
     */
    public function recordedBy(): MorphTo
    {
        return $this->morphTo('recorded_by', 'recorded_by_type', 'recorded_by_id');
    }

    /**
     * Relationship: User or Employee who approved (polymorphic)
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    /**
     * Relationship: User or Employee who received (polymorphic)
     */
    public function receivedBy(): MorphTo
    {
        return $this->morphTo('received_by', 'received_by_type', 'received_by_id');
    }

    /**
     * Scope: Pending callbacks
     */
    public function scopePending($query)
    {
        return $query->where('status', CallbackStatus::PENDING->value);
    }

    /**
     * Scope: Approved callbacks
     */
    public function scopeApproved($query)
    {
        return $query->where('status', CallbackStatus::APPROVED_BY_PRODUCTION->value);
    }

    /**
     * Scope: Received callbacks
     */
    public function scopeReceived($query)
    {
        return $query->where('status', CallbackStatus::RECEIVED_BY_PRODUCTION->value);
    }

    /**
     * Scope: Completed callbacks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', CallbackStatus::COMPLETED->value);
    }

    /**
     * Scope: By sales shift
     */
    public function scopeBySalesShift($query, $salesShiftId)
    {
        return $query->where('sales_shift_id', $salesShiftId);
    }

    /**
     * Scope: By product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Check if callback can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === CallbackStatus::PENDING;
    }

    /**
     * Check if callback can be received
     */
    public function canBeReceived(): bool
    {
        return $this->status === CallbackStatus::APPROVED_BY_PRODUCTION;
    }

    /**
     * Approve the callback (Sales→Production approval)
     *
     * Marks the return as approved by production, confirming they accept the returned product.
     * If no actor is provided, uses current_actor() for polymorphic tracking.
     * Uses pessimistic locking to prevent race conditions.
     *
     * @param \App\Models\Employee|\App\Models\User|null $actor The actor approving (defaults to current_actor())
     * @return bool True if successfully approved, false if cannot be approved
     * @throws RuntimeException If no actor is authenticated
     *
     * @example
     * $callback = ProductDispatchCallback::find(1);
     * $actor = current_actor();  // Employee or User
     * if ($callback->approve($actor)) {
     *     echo "Approved successfully";
     * }
     */
    public function approve($actor = null): bool
    {
        return DB::transaction(function () use ($actor) {
            // Lock the record to prevent concurrent updates
            $locked = self::where('id', $this->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== CallbackStatus::PENDING) {
                return false;
            }

            $actor = $actor ?? current_actor();
            if (! $actor) {
                CleanError::show('No authenticated actor found.');
                return false;
            }

            $locked->update([
                'status' => CallbackStatus::APPROVED_BY_PRODUCTION,
                'approved_by_id' => $actor->id,
                'approved_by_type' => get_class($actor),
                'approved_at' => now(),
            ]);

            // Refresh the current instance
            $this->refresh();

            return true;
        });
    }

    /**
     * Mark callback as received by production
     *
     * Records that production has physically received the returned product.
     * Can only be called after approval. Once received, callback can be completed
     * with automatic stock updates. Uses pessimistic locking to prevent race conditions.
     *
     * @param \App\Models\Employee|\App\Models\User|null $actor The actor receiving (defaults to current_actor())
     * @return bool True if successfully marked as received, false if cannot be received
     * @throws RuntimeException If no actor is authenticated
     *
     * @example
     * $callback->markAsReceived(current_actor());
     * // Now callback can be completed with stock updates
     */
    public function markAsReceived($actor = null): bool
    {
        return DB::transaction(function () use ($actor) {
            // Lock the record to prevent concurrent updates
            $locked = self::where('id', $this->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== CallbackStatus::APPROVED_BY_PRODUCTION) {
                return false;
            }

            $actor = $actor ?? current_actor();
            if (! $actor) {
                CleanError::show('No authenticated actor found.');
                return false;
            }

            $locked->update([
                'status' => CallbackStatus::RECEIVED_BY_PRODUCTION,
                'received_by_id' => $actor->id,
                'received_by_type' => get_class($actor),
                'received_at' => now(),
            ]);

            // Refresh the current instance
            $this->refresh();

            return true;
        });
    }

    /**
     * Complete the callback
     */
    public function complete(): bool
    {
        if ($this->status !== CallbackStatus::RECEIVED_BY_PRODUCTION) {
            return false;
        }

        $this->update([
            'status' => CallbackStatus::COMPLETED,
        ]);

        return true;
    }

    /**
     * Mark callback as approved and received (convenience method)
     */
    public function approveAndReceive($actor = null): bool
    {
        if (!$this->approve($actor)) {
            return false;
        }

        return $this->markAsReceived($actor);
    }

    /**
     * Mark callback as approved, received, and completed (full workflow)
     */
    public function approveReceiveAndComplete($actor = null): bool
    {
        if (!$this->approve($actor)) {
            return false;
        }

        if (!$this->markAsReceived($actor)) {
            return false;
        }

        return $this->completeWithStockUpdate();
    }

    /**
     * Get formatted reason
     */
    public function getFormattedReasonAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->reason));
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        $value = $this->status instanceof CallbackStatus ? $this->status->value : $this->status;
        return ucwords(str_replace('_', ' ', $value));
    }

    /**
     * Check if callback is stuck in a workflow state
     *
     * @param int $pendingTimeoutHours Hours before pending is considered stuck (default: 24)
     * @param int $approvedTimeoutHours Hours before approved is considered stuck (default: 48)
     * @return bool
     */
    public function isStuck(int $pendingTimeoutHours = 24, int $approvedTimeoutHours = 48): bool
    {
        $timeInCurrentState = now()->diffInHours($this->updated_at);

        $status = $this->status instanceof CallbackStatus
            ? $this->status
            : CallbackStatus::tryFrom((string) $this->status);

        if (! $status) {
            return false;
        }

        return match ($status) {
            CallbackStatus::PENDING => $timeInCurrentState > $pendingTimeoutHours,
            CallbackStatus::APPROVED_BY_PRODUCTION => $timeInCurrentState > $approvedTimeoutHours,
            default => false,
        };
    }

    /**
     * Get hours stuck in current state
     *
     * @return int
     */
    public function getHoursStuck(): int
    {
        return (int) now()->diffInHours($this->updated_at);
    }

    /**
     * Scope: Stuck callbacks
     */
    public function scopeStuck($query, int $pendingTimeoutHours = 24, int $approvedTimeoutHours = 48)
    {
        return $query->where(function ($q) use ($pendingTimeoutHours, $approvedTimeoutHours) {
            $q->where(function ($sub) use ($pendingTimeoutHours) {
                $sub->where('status', CallbackStatus::PENDING->value)
                    ->where('updated_at', '<', now()->subHours($pendingTimeoutHours));
            })->orWhere(function ($sub) use ($approvedTimeoutHours) {
                $sub->where('status', CallbackStatus::APPROVED_BY_PRODUCTION->value)
                    ->where('updated_at', '<', now()->subHours($approvedTimeoutHours));
            });
        });
    }

    /**
     * Validate callback quantity against available quantity
     *
     * @throws ValidationException
     * @return bool
     */
    public function validateQuantity(): bool
    {
        if (! $this->product_dispatch_id) {
            CleanError::show('Product dispatch reference is required for callbacks.');
            return false;
        }

        if (! $this->productDispatch) {
            CleanError::show('No dispatch found for callback.');
            return false;
        }

        $totalCallbacks = self::where('product_dispatch_id', $this->product_dispatch_id)
            ->where('id', '!=', $this->id ?? 'fake-id')
            ->whereIn('status', [
                CallbackStatus::PENDING->value,
                CallbackStatus::APPROVED_BY_PRODUCTION->value,
                CallbackStatus::RECEIVED_BY_PRODUCTION->value,
                CallbackStatus::COMPLETED->value,
            ])
            ->sum('quantity');

        $available = $this->productDispatch->received_quantity - $totalCallbacks;

        if ($this->quantity > $available) {
            CleanError::show(
                "Callback quantity ({$this->quantity}) exceeds available ({$available})."
            );
            return false;
        }

        return true;
    }

    /**
     * Complete the callback with automatic stock updates
     * 
     * This is the critical method that finalizes a callback and updates inventory.
     * IMPORTANT: Business logic is in the MODEL, not in UI components.
     * This allows stock updates to work from API, jobs, and UI.
     * 
     * Workflow:
     *   1. Updates ProductStock (adds to callback_quantity)
     *   2. Updates DailyProduce (increments callback_quantity and closing_quantity)
     *   3. Marks callback as completed
     *   All within a single database transaction for consistency
     * 
     * @return bool Always returns true on success
     * @throws RuntimeException If callback is not in 'received_by_production' status
     * 
     * @example
     * $callback = ProductDispatchCallback::find(123);
     * // After receiving the product:
     * $callback->completeWithStockUpdate();
     * 
     * // Stock is now updated:
     * $stock = ProductStock::find($stockId);
     * echo $stock->callback_quantity;  // Increased
     */
    public function completeWithStockUpdate(): bool
    {
        if ($this->status !== CallbackStatus::RECEIVED_BY_PRODUCTION) {
            CleanError::show(
                'Callback must be received before completion. Current status: '.$this->formatted_status
            );
            return false;
        }

        return DB::transaction(function () {
            $this->syncProductStock();
            $this->updateDailyProduce();
            $this->update(['status' => CallbackStatus::COMPLETED]);
            return true;
        });
    }

    /**
     * Sync ProductStock callback quantity to avoid double counting
     *
     * @throws RuntimeException
     * @return void
     */
    public function syncProductStock(): void
    {
        $productStock = ProductStock::where('sales_shift_id', $this->sales_shift_id)
            ->where('product_id', $this->product_id)
            ->lockForUpdate()
            ->first();

        if (! $productStock) {
            CleanError::show(
                'Product stock record not found for sales shift: '.$this->sales_shift_id
            );
            return;
        }

        $totalCallbacks = self::where('sales_shift_id', $this->sales_shift_id)
            ->where('product_id', $this->product_id)
            ->whereIn('status', [
                CallbackStatus::PENDING->value,
                CallbackStatus::APPROVED_BY_PRODUCTION->value,
                CallbackStatus::RECEIVED_BY_PRODUCTION->value,
                CallbackStatus::COMPLETED->value,
            ])
            ->sum('quantity');

        $productStock->callback_quantity = $totalCallbacks;
        $productStock->save();
    }

    /**
     * Update DailyProduce when product is returned to production
     *
     * @throws RuntimeException
     * @return void
     */
    private function updateDailyProduce(): void
    {
        // Find the original production that created this product
        $productDispatch = $this->productDispatch;
        if (! $productDispatch || ! $productDispatch->shift_id) {
            CleanError::show('Product dispatch or shift not found for callback.');
            return;
        }

        $recipe = Recipe::where('product_id', $this->product_id)->first();
        if (! $recipe) {
            CleanError::show('Recipe not found for product: '.$this->product_id);
            return;
        }

        $dailyProduce = DailyProduce::where('shift_id', $productDispatch->shift_id)
            ->where('recipe_id', $recipe->id)
            ->lockForUpdate()
            ->first();

        if ($dailyProduce) {
            $dailyProduce->increment('callback_quantity', $this->quantity);
            $dailyProduce->increment('closing_quantity', $this->quantity);
            $dailyProduce->updateCalculations();
        } else {
            CleanError::show(
                'DailyProduce not found for callback. Cannot complete stock update. ' .
                'Shift ID: ' . $productDispatch->shift_id .
                ', Recipe ID: ' . $recipe->id
            );
            return;
        }
    }
}
