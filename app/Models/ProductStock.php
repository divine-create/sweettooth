<?php

namespace App\Models;

use App\Notifications\StockLowNotification;
use App\Services\UomConversionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Shift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ProductStock extends Model
{
    public static function normalizeShiftType(?string $shiftType): string
    {
        $shiftType = strtolower(trim((string) ($shiftType ?? 'morning')));

        if (in_array($shiftType, ['morning', 'afternoon', 'night'], true)) {
            return $shiftType;
        }

        return 'morning';
    }

    protected $fillable = [
        'sales_shift_id',
        'shift_id',
        'department_id',
        'product_id',
        'stock_date',
        'shift_type',
        'opening_quantity',
        'addition_quantity',
        'production_date',
        'expiry_date',
        'callback_quantity',
        'redress_quantity',
        'total_available',
        'transfer_quantity',
        'glovo_quantity',
        'quantity_sold',
        'quantity_reserved',
        'closing_quantity',
        'amount',
        'notes',
    ];

    protected $casts = [
        'stock_date' => 'date',
        'production_date' => 'date',
        'expiry_date' => 'date',
        'opening_quantity' => 'decimal:2',
        'addition_quantity' => 'decimal:2',
        'callback_quantity' => 'decimal:2',
        'redress_quantity' => 'decimal:2',
        'total_available' => 'decimal:2',
        'transfer_quantity' => 'decimal:2',
        'glovo_quantity' => 'decimal:2',
        'quantity_sold' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'closing_quantity' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function salesShift(): BelongsTo
    {
        // sales_shift_id is nullable - we use the general shifts table instead
        return $this->belongsTo(SalesShift::class, 'sales_shift_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Helper Methods
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return Carbon::today()->greaterThan($this->expiry_date);
    }

    public function calculateExpiry(): ?Carbon
    {
        if (! $this->production_date || ! $this->product) {
            return null;
        }

        $shelfLifeDays = $this->product->shelf_life_days ?? 0;

        if ($shelfLifeDays <= 0) {
            return null;
        }

        return $this->production_date->copy()->addDays($shelfLifeDays);
    }

    public function getShelfLifeStatus(): string
    {
        if (! $this->expiry_date) {
            return 'unknown';
        }

        $today = Carbon::today();
        $daysRemaining = $today->diffInDays($this->expiry_date, false);

        if ($daysRemaining < 0) {
            return 'expired'; // 🔴
        }

        if (! $this->production_date) {
            return 'unknown';
        }

        $totalShelfLife = $this->production_date->diffInDays($this->expiry_date);

        if ($totalShelfLife <= 0) {
            return 'expired';
        }

        $percentageRemaining = ($daysRemaining / $totalShelfLife) * 100;

        if ($percentageRemaining > 50) {
            return 'fresh'; // 🟢
        } elseif ($percentageRemaining > 25) {
            return 'warning'; // 🟡
        } elseif ($percentageRemaining > 0) {
            return 'critical'; // 🟠
        }

        return 'expired'; // 🔴
    }

    public function getShelfLifeBadgeColor(): string
    {
        $status = $this->getShelfLifeStatus();

        return match ($status) {
            'fresh' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'critical' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            'expired' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    public function getDaysRemaining(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->expiry_date, false);
    }

    public function shouldCallback(): bool
    {
        // Check if product should be called back (expired or critically close to expiry)
        return $this->isExpired() || $this->getShelfLifeStatus() === 'expired';
    }

    public function calculateTotalAvailable(): float
    {
        // Formula: Opening + Addition - Callback - Redress
        return $this->opening_quantity
            + $this->addition_quantity
            - $this->callback_quantity
            - $this->redress_quantity;
    }

    public function calculateClosing(): float
    {
        // Formula: Total Available - Transfer - Glovo - Sold - Reserved
        return $this->total_available
            - $this->transfer_quantity
            - $this->glovo_quantity
            - $this->quantity_sold
            - ($this->quantity_reserved ?? 0);
    }

    public function updateCalculatedFields(): void
    {
        $this->total_available = $this->calculateTotalAvailable();

        // For closing_completed records, only recalculate closing_quantity when sales-side fields
        // actually changed (POS deduction, reservation, transfer, glovo). Block recalculation for
        // non-sales saves (e.g. Entry page updating opening_quantity) so manually corrected
        // closing values are preserved. Check getOriginal() because the caller may have already
        // changed workflow_step on the in-memory model before the hook fires.
        $isClosingCompleted = ($this->getOriginal('workflow_step') ?? null) === 'closing_completed';
        $hasSalesChange = $this->isDirty([
            'quantity_sold', 'quantity_reserved', 'transfer_quantity', 'glovo_quantity',
            'addition_quantity', 'callback_quantity', 'redress_quantity',
        ]);

        if (! $isClosingCompleted || $hasSalesChange) {
            $this->closing_quantity = $this->calculateClosing();
        }

        if ($this->production_date && ! $this->expiry_date) {
            $this->expiry_date = $this->calculateExpiry();
        }
    }

    // Boot method to auto-calculate fields
    protected static function booted(): void
    {
        static::saving(function (ProductStock $productStock) {
            $productStock->updateCalculatedFields();
        });

        static::saved(function (ProductStock $productStock) {
            $productStock->checkAndNotifyLowStock();
        });
    }

    private function checkAndNotifyLowStock(): void
    {
        $product = $this->product;
        $reorderLevel = $product?->reorder_level ?? null;

        if (! $reorderLevel || (float) $this->closing_quantity >= (float) $reorderLevel) {
            return;
        }

        $cacheKey = "stock_low_{$this->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            $recipients = User::role('Inventory Manager')
                ->where('branch_id', $this->branch_id)
                ->get();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new StockLowNotification($this));
                Cache::put($cacheKey, true, 86400);
            }
        } catch (\Throwable $e) {
            \Log::warning('Could not dispatch stock low notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get closing stock quantity converted to another UOM.
     *
     * @param  int|string|UnitOfMeasure  $toUom
     */
    public function getClosingQuantityInUom(int|string|UnitOfMeasure $toUom): ?float
    {
        if (! $this->product || ! $this->product->uom_id) {
            return null;
        }

        return app(UomConversionService::class)->tryConvert(
            (float) $this->closing_quantity,
            (int) $this->product->uom_id,
            $toUom,
            [
                'branch_id' => $this->product->branch_id,
                'product_id' => (string) $this->product_id,
            ]
        );
    }
}
