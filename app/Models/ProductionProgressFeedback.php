<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionProgressFeedback extends Model
{
    use HasFactory;

    protected $table = 'production_progress_feedback';

    protected $fillable = [
        'production_request_id',
        'milestone',
        'progress_percentage',
        'notes',
        'updated_by_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the production request this feedback belongs to
     */
    public function productionRequest(): BelongsTo
    {
        return $this->belongsTo(ProductionRequest::class);
    }

    /**
     * Get the user who updated this feedback
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Scope to get latest feedback
     */
    public function scopeLatest($query)
    {
        return $query->latest('created_at');
    }

    /**
     * Get milestone label
     */
    public function getMilestoneLabelAttribute(): string
    {
        return match ($this->milestone) {
            'started' => 'Production Started',
            'in_production' => 'In Production',
            'quality_check' => 'Quality Check',
            'completed' => 'Completed',
            default => ucfirst(str_replace('_', ' ', $this->milestone)),
        };
    }

    /**
     * Get milestone color
     */
    public function getMilestoneColorAttribute(): string
    {
        return match ($this->milestone) {
            'started' => 'blue',
            'in_production' => 'yellow',
            'quality_check' => 'purple',
            'completed' => 'green',
            default => 'gray',
        };
    }
}
