<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductDispatch extends Model
{
    protected $fillable = [
        'production_request_id',
        'sales_production_request_item_id',
        'branch_id',
        'daily_produce_id',
        'production_record_id',
        'production_shift_id',
        'sales_shift_id',
        'sales_department_id',
        'product_id',
        'dispatched_by_id',
        'dispatched_by_type',
        'quantity',
        'received_quantity',
        'uom',
        'dispatch_time',
        'shift_type',
        'dispatch_date',
        'received_by_id',
        'received_by_type',
        'received_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'dispatch_time' => 'datetime',
        'dispatch_date' => 'date',
        'received_at' => 'datetime',
    ];

    // Relationships
    public function productionRequest(): BelongsTo
    {
        return $this->belongsTo(ProductionRequest::class);
    }

    public function salesProductionRequestItem(): BelongsTo
    {
        return $this->belongsTo(SalesProductionRequestItem::class, 'sales_production_request_item_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dailyProduce(): BelongsTo
    {
        return $this->belongsTo(DailyProduce::class);
    }

    public function productionRecord(): BelongsTo
    {
        return $this->belongsTo(ProductionRecord::class);
    }

    public function productionShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'production_shift_id');
    }

    public function salesShift(): BelongsTo
    {
        return $this->belongsTo(SalesShift::class);
    }

    public function shift(): BelongsTo
    {
        return $this->salesShift();
    }

    public function salesDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sales_department_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function dispatchedBy(): MorphTo
    {
        return $this->morphTo('dispatched_by', 'dispatched_by_type', 'dispatched_by_id');
    }

    public function receivedBy(): MorphTo
    {
        return $this->morphTo('received_by', 'received_by_type', 'received_by_id');
    }

    public function productDispatchCallbacks(): HasMany
    {
        return $this->hasMany(ProductDispatchCallback::class);
    }

    // Helper Methods
    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending_verification' => 'Pending Verification',
            'accepted' => 'Accepted',
            'received' => 'Received',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'pending_verification' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'accepted' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'received' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    // Scopes
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('dispatch_date', $date);
    }

    public function scopeForShiftType($query, $shiftType)
    {
        return $query->where('shift_type', $shiftType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_verification');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeForSalesDepartment($query, $salesDepartmentId)
    {
        return $query->where('sales_department_id', $salesDepartmentId);
    }
}
