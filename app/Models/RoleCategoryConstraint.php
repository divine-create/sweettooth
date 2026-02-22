<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RoleCategoryConstraint extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'role_category_constraints';

    protected $fillable = [
        'role_name',
        'category_id',
        'department_type',
        'allowed_department_slugs',
        'is_active'
    ];

    protected $casts = [
        'allowed_department_slugs' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the category this constraint applies to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DepartmentCategory::class, 'category_id');
    }

    /**
     * Check if this constraint allows a specific department
     */
    public function allowsDepartment(string $departmentSlug): bool
    {
        return match ($this->department_type) {
            'branch_wide' => true,
            'category_wide' => true,
            'specific' => in_array($departmentSlug, $this->allowed_department_slugs ?? []),
        };
    }

    /**
     * Scope for active constraints
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for constraints of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('department_type', $type);
    }
}