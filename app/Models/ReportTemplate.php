<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'template_name',
        'report_type',
        'report_category',
        'description',
        'template_structure',
        'chart_configurations',
        'formatting_options',
        'is_default',
        'is_active',
        'created_by_id',
        'created_by_type',
    ];

    protected $casts = [
        'template_structure' => 'array',
        'chart_configurations' => 'array',
        'formatting_options' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the branch that owns the template.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the employee who created the template.
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    /**
     * Scope a query to only include templates for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by report category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('report_category', $category);
    }

    /**
     * Scope a query to filter by report type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope to get default template.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Set as default template (unset others of same type).
     */
    public function setAsDefault()
    {
        // Unset other default templates of same type
        static::where('report_type', $this->report_type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Activate the template.
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the template.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
