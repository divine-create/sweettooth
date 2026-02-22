<?php

namespace App\Models;

use App\Enums\CallbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

/**
 * ProductionCallback Model
 * 
 * Represents callbacks for damaged or defective items reported by Production to Inventory.
 * Handles two callback types:
 *   1. Raw materials from stock (damaged, expired, quality issues)
 *   2. Finished products (damaged, quality issues from production)
 * 
 * Workflow: pending → approved_by_inventory → completed
 * 
 * When production discovers damaged items, they create a callback.
 * Inventory reviews and approves (or rejects) the callback.
 * Upon approval, stock is automatically updated.
 * 
 * @property int $id
 * @property int $shift_id Production shift when issue discovered
 * @property string $source_type Type of callback ('raw_material_from_stock' or 'finished_product_reject')
 * @property int|null $item_id Raw material item (if raw material callback)
 * @property int|null $product_id Finished product (if finished product callback)
 * @property int|null $recorded_by_id ID of actor who recorded callback
 * @property string|null $recorded_by_type Class name of actor (Employee or User)
 * @property decimal $quantity Quantity affected
 * @property string $uom Unit of measure
 * @property string $reason Reason for callback (damaged, expired, quality_issue, etc.)
 * @property string $status Current status (enum: CallbackStatus)
 * @property int|null $approved_by_id ID of actor who approved
 * @property string|null $approved_by_type Class name of approving actor
 * @property \DateTime|null $approved_at When approval occurred
 * @property string|null $notes Additional notes
 * @property \DateTime $callback_time When callback was recorded
 * 
 * @relationship Shift shift() Production shift when issue occurred
 * @relationship Item item() Raw material item (if applicable)
 * @relationship Product product() Finished product (if applicable)
 * @relationship Recipe recipe() Recipe for product (if applicable)
 * @relationship Employee|User recordedBy() Polymorphic: who recorded the callback
 * @relationship Employee|User approvedBy() Polymorphic: who approved the callback
 */
class ProductionCallback extends Model
{
    protected $fillable = [
        'shift_id',
        'source_type',
        'item_id',
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
        'notes',
        'callback_time',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'callback_time' => 'datetime',
        'approved_at' => 'datetime',
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
     * Relationship: Production Shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Relationship: Raw Material Item (if applicable)
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Relationship: Finished Product (if applicable)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Recipe (for product callbacks)
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'product_id');
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
        return $query->where('status', CallbackStatus::APPROVED_BY_INVENTORY->value);
    }

    /**
     * Scope: Completed callbacks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', CallbackStatus::COMPLETED->value);
    }

    /**
     * Scope: Rejected callbacks
     */
    public function scopeRejected($query)
    {
        return $query->where('status', CallbackStatus::REJECTED->value);
    }

    /**
     * Scope: By shift
     */
    public function scopeByShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope: Raw material callbacks
     */
    public function scopeRawMaterial($query)
    {
        return $query->where('source_type', 'raw_material_from_stock');
    }

    /**
     * Scope: Finished product callbacks
     */
    public function scopeFinishedProduct($query)
    {
        return $query->where('source_type', 'finished_product_reject');
    }

    /**
     * Check if callback is for raw material
     */
    public function isRawMaterial(): bool
    {
        return $this->source_type === 'raw_material_from_stock';
    }

    /**
     * Check if callback is for finished product
     */
    public function isFinishedProduct(): bool
    {
        return $this->source_type === 'finished_product_reject';
    }

    /**
     * Check if callback can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === CallbackStatus::PENDING;
    }

    /**
     * Approve the callback with automatic stock updates
     * 
     * Critical method: Approves a production callback and immediately updates stock.
     * Business logic is in the MODEL, not in UI components - this enables API/job compatibility.
     * 
     * Workflow:
     *   1. Validates callback can be approved (pending status)
     *   2. Determines callback type (raw material or finished product)
     *   3. Updates appropriate stock table (Stock or DailyProduce)
     *   4. Records approval with actor information
     *   All within a transaction for consistency
     * 
     * @param \App\Models\Employee|\App\Models\User|null $actor The actor approving (defaults to current_actor())
     * @return bool True if successfully approved and stock updated, false if cannot be approved
     * @throws RuntimeException If no actor is authenticated or stock not found
     * 
     * @example
     * $callback = ProductionCallback::find(456);
     * $actor = current_actor();  // Employee or User
     * if ($callback->approve($actor)) {
     *     echo "Approved! Stock has been updated.";
     * }
     */
    public function approve($actor = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $actor = $actor ?? current_actor();
        if (! $actor) {
            throw new RuntimeException('No authenticated actor found');
        }

        return DB::transaction(function () use ($actor) {
            // Update stock based on type
            if ($this->isRawMaterial()) {
                $this->updateRawMaterialStock();
            } elseif ($this->isFinishedProduct()) {
                $this->updateFinishedProductStock();
            }

            // Then approve
            $this->update([
                'status' => CallbackStatus::APPROVED_BY_INVENTORY,
                'approved_by_id' => $actor->id,
                'approved_by_type' => get_class($actor),
                'approved_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Reject the callback
     * 
     * Allows inventory to reject a production callback with optional reason.
     * Rejection means the item will NOT be removed from stock and the original
     * owner must investigate the issue further.
     * 
     * @param \App\Models\Employee|\App\Models\User|null $actor The actor rejecting (defaults to current_actor())
     * @param string|null $reason Optional reason for rejection
     * @return bool True if successfully rejected, false if cannot be rejected
     * @throws RuntimeException If no actor is authenticated
     * 
     * @example
     * $callback->reject(current_actor(), 'Item is still usable - return to production');
     * // Item remains in stock, callback marked as rejected
     */
    public function reject($actor = null, $reason = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $actor = $actor ?? current_actor();
        if (! $actor) {
            throw new RuntimeException('No authenticated actor found');
        }

        $notes = $this->notes;
        if ($reason) {
            $notes .= "\n\nRejected: ".$reason;
        }

        $this->update([
            'status' => CallbackStatus::REJECTED,
            'approved_by_id' => $actor->id,
            'approved_by_type' => get_class($actor),
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Complete the callback
     */
    public function complete(): bool
    {
        if ($this->status !== CallbackStatus::APPROVED_BY_INVENTORY) {
            return false;
        }

        $this->update([
            'status' => CallbackStatus::COMPLETED,
        ]);

        return true;
    }

    /**
     * Get formatted source type
     */
    public function getFormattedSourceTypeAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->source_type));
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
     * Get the item name (raw material or product)
     */
    public function getItemNameAttribute(): string
    {
        if ($this->isRawMaterial() && $this->item) {
            return $this->item->name;
        }

        if ($this->isFinishedProduct() && $this->product) {
            return $this->product->name;
        }

        return 'Unknown';
    }

    /**
     * Validate callback quantity
     *
     * @throws ValidationException
     * @return bool
     */
    public function validateQuantity(): bool
    {
        // Raw materials have no strict quantity limit (may have been partially used)
        if ($this->isRawMaterial()) {
            return true;
        }

        // For finished products, validate against produced quantity
        if ($this->isFinishedProduct()) {
            if (! $this->product_id) {
                throw new InvalidArgumentException('Finished product callback missing product_id');
            }

            $dailyProduce = DailyProduce::whereHas('recipe', function ($q) {
                $q->where('product_id', $this->product_id);
            })
                ->where('shift_id', $this->shift_id)
                ->first();

            if (! $dailyProduce) {
                throw new RuntimeException(
                    'DailyProduce not found for product and shift'
                );
            }

            if ($this->quantity > $dailyProduce->produced_quantity) {
                throw new ValidationException(
                    "Callback quantity ({$this->quantity}) exceeds produced ({$dailyProduce->produced_quantity})"
                );
            }
        }

        return true;
    }

    /**
     * Update stock for raw material callbacks
     *
     * @throws InvalidArgumentException|RuntimeException
     * @return void
     */
    private function updateRawMaterialStock(): void
    {
        if (! $this->item_id) {
            throw new InvalidArgumentException('Raw material callback missing item_id');
        }

        $stock = Stock::where('item_id', $this->item_id)
            ->where('branch_id', $this->shift->branch_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new RuntimeException(
                'Stock record not found for item: '.$this->item_id
            );
        }

        // Decrease available, increase damaged
        $stock->quantity_available = max(0, $stock->quantity_available - $this->quantity);
        $stock->quantity_damaged += $this->quantity;
        $stock->save();

        // Log the movement
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'callback',
            'quantity' => -$this->quantity,
            'reference_type' => 'production_callback',
            'reference_id' => $this->id,
            'notes' => 'Production callback: '.$this->reason,
            'movement_date' => now(),
        ]);
    }

    /**
     * Update stock for finished product callbacks
     *
     * @throws InvalidArgumentException|RuntimeException
     * @return void
     */
    private function updateFinishedProductStock(): void
    {
        if (! $this->product_id) {
            throw new InvalidArgumentException('Finished product callback missing product_id');
        }

        $recipe = Recipe::where('product_id', $this->product_id)->first();
        if (! $recipe) {
            throw new RuntimeException('Recipe not found for product: '.$this->product_id);
        }

        $dailyProduce = DailyProduce::where('shift_id', $this->shift_id)
            ->where('recipe_id', $recipe->id)
            ->lockForUpdate()
            ->first();

        if (! $dailyProduce) {
            throw new RuntimeException(
                'DailyProduce not found for shift: '.$this->shift_id.
                ', recipe: '.$recipe->id
            );
        }

        $dailyProduce->callback_quantity += $this->quantity;
        $dailyProduce->updateCalculations();
    }
}
