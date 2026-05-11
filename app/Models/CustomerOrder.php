<?php

namespace App\Models;

use App\Enums\CustomerOrderStatus;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $fillable = [
        'branch_id',
        'order_number',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => CustomerOrderStatus::class,
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerOrder $order) {
            if (! $order->status) {
                $order->status = CustomerOrderStatus::PENDING;
            }

            if (! $order->order_number) {
                $order->order_number = static::generateOrderNumber();
            }
        });

        static::updating(function (CustomerOrder $order) {
            if (! $order->isDirty('status')) {
                return;
            }

            $from = self::normalizeStatus($order->getOriginal('status'));
            $to = self::normalizeStatus($order->status);

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("Invalid order status transition: {$from->value} -> {$to->value}");
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'CO-' . $date;

        $last = static::query()
            ->where('order_number', 'like', $prefix . '-%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(ProductDispatch::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', CustomerOrderStatus::PENDING->value);
    }

    public function totalDispatchedForProduct(string $productId): float
    {
        return (float) $this->dispatches()
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    private static function normalizeStatus(mixed $status): CustomerOrderStatus
    {
        if ($status instanceof CustomerOrderStatus) {
            return $status;
        }

        return CustomerOrderStatus::from((string) $status);
    }
}
