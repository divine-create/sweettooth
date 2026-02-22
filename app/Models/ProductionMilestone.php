<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_record_id',
        'milestone_type',
        'achieved_at',
        'achieved_by',
        'notes'
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
    ];

    /**
     * Get the production record that owns the milestone.
     */
    public function productionRecord(): BelongsTo
    {
        return $this->belongsTo(ProductionRecord::class);
    }

    /**
     * Get the user who achieved the milestone.
     */
    public function achievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'achieved_by');
    }
}