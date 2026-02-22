<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalFeedback extends Model
{
    protected $table = 'appraisal_feedbacks';

    protected $fillable = [
        'appraisal_id',
        'reviewer_id',
        'reviewer_type',
        'feedback_data',
        'submitted_at'
    ];

    protected $casts = [
        'feedback_data' => 'array',
        'submitted_at' => 'datetime',
        'reviewer_id' => 'uuid'
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class, 'appraisal_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopeForAppraisal($query, $appraisalId)
    {
        return $query->where('appraisal_id', $appraisalId);
    }

    public function scopeByReviewerType($query, $type)
    {
        return $query->where('reviewer_type', $type);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('submitted_at');
    }
}
