<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'output_product_id',
        'output_quantity',
        'branch_id',
        'planned_date',
        'completed_date',
        'status',
        'total_material_cost',
        'total_labor_cost',
        'total_overhead_cost',
        'total_cost',
        'unit_cost',
        'notes',
        'created_by',
        'gl_posting_status',
        'gl_entry_id',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'output_quantity' => 'decimal:4',
        'planned_date' => 'date',
        'completed_date' => 'date',
        'total_material_cost' => 'decimal:2',
        'total_labor_cost' => 'decimal:2',
        'total_overhead_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'gl_posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'PRD-' . date('Ym');
        $lastOrder = static::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductionOrderComponent::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function calculateMaterialCost(): float
    {
        return $this->components->sum('total_cost');
    }

    public function calculateTotalCost(): float
    {
        return $this->total_material_cost + $this->total_labor_cost + $this->total_overhead_cost;
    }

    public function calculateUnitCost(): float
    {
        return $this->output_quantity > 0 ? $this->total_cost / $this->output_quantity : 0;
    }

    public function updateCosts(): void
    {
        $this->total_material_cost = $this->calculateMaterialCost();
        $this->total_cost = $this->calculateTotalCost();
        $this->unit_cost = $this->calculateUnitCost();
        $this->save();
    }

    public function canStart(): bool
    {
        if ($this->status !== 'planned') {
            return false;
        }

        // Check if all components are available
        foreach ($this->components as $component) {
            $available = $component->product->quantity ?? 0;
            if ($available < $component->quantity_required) {
                return false;
            }
        }

        return true;
    }

    public function start(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update(['status' => 'in_progress']);
        return true;
    }

    public function complete(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        // Deduct component quantities
        foreach ($this->components as $component) {
            $component->update(['quantity_used' => $component->quantity_required]);
            // Deduct from product inventory would be handled by observer
        }

        // Add output product quantity would be handled by observer

        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
        ]);

        $this->updateCosts();

        return true;
    }

    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
