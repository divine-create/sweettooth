<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProbationReview extends Model
{
    protected $fillable = [
        'employee_id',
        'review_date',
        'probation_start_date',
        'probation_end_date',
        'review_type',
        'reviewer_id',
        'performance_score',
        'strengths',
        'areas_for_improvement',
        'goals_achievements',
        'training_recommendations',
        'overall_comments',
        'recommendation',
        'extended_probation_end_date',
        'extension_days',
        'extension_reason',
        'status',
        'acknowledged_by_id',
        'acknowledged_by_type',
        'acknowledged_at',
    ];

    protected $casts = [
        'review_date' => 'date',
        'probation_start_date' => 'date',
        'probation_end_date' => 'date',
        'extended_probation_end_date' => 'date',
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function acknowledgedBy(): MorphTo
    {
        return $this->morphTo('acknowledged_by', 'acknowledged_by_type', 'acknowledged_by_id');
    }
}
