<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'table_number',
        'table_name',
        'status',
        'capacity',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Get active sales for this table (hold or pending)
    public function activeSales(): HasMany
    {
        return $this->hasMany(Sale::class)->whereIn('status', ['hold', 'pending']);
    }

    // Helper methods
    public function hasActiveSale(): bool
    {
        return $this->activeSales()->exists();
    }

    public function getActiveSale(): ?Sale
    {
        return $this->activeSales()->latest()->first();
    }

    public function getTotalAmount(): float
    {
        return $this->activeSales()->sum('total');
    }

    public function markAsOccupied(): void
    {
        if ($this->status !== 'occupied') {
            $this->update(['status' => 'occupied']);
        }
    }

    public function markAsAvailable(): void
    {
        if (! $this->hasActiveSale() && $this->status !== 'available') {
            $this->update(['status' => 'available']);
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
