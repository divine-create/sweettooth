<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionStore extends Model
{
    protected $table = 'production_stores';

    protected $fillable = [
        'branch_id',
        'department_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductionStoreStock::class, 'store_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductionStoreMovement::class, 'store_id');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function getOrCreateForDepartment(int $departmentId): self
    {
        $branchId = current_branch_id();
        $department = Department::find($departmentId);

        $store = static::forBranch($branchId)
            ->forDepartment($departmentId)
            ->active()
            ->first();

        if (!$store) {
            $deptName = $department?->name ?? 'Production';
            $store = static::create([
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'name' => "{$deptName} Store",
                'status' => 'active',
            ]);
        }

        return $store;
    }

    public static function forCurrentDepartment(): ?self
    {
        $user = current_actor();
        if (!$user) {
            return null;
        }

        $departmentId = $user->department_id;
        if (!$departmentId) {
            return null;
        }

        return static::forBranch(current_branch_id())
            ->forDepartment($departmentId)
            ->first();
    }
}