<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryKit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'kit_price',
        'active',
    ];

    protected $casts = [
        'kit_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'inventory_kit_components')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getComponentCostAttribute(): float
    {
        return $this->components->sum(function ($component) {
            return $component->pivot->quantity * ($component->cost_price ?? 0);
        });
    }

    public function canAssemble(int $quantity = 1): bool
    {
        foreach ($this->components as $component) {
            $requiredQty = $component->pivot->quantity * $quantity;
            if (($component->quantity ?? 0) < $requiredQty) {
                return false;
            }
        }
        return true;
    }

    public function getAvailableKitsAttribute(): int
    {
        $minAvailable = PHP_INT_MAX;

        foreach ($this->components as $component) {
            $componentQty = $component->quantity ?? 0;
            $requiredPerKit = $component->pivot->quantity;

            if ($requiredPerKit > 0) {
                $available = floor($componentQty / $requiredPerKit);
                $minAvailable = min($minAvailable, $available);
            }
        }

        return $minAvailable === PHP_INT_MAX ? 0 : $minAvailable;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
