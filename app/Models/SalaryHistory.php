<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalaryHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'previous_salary',
        'new_salary',
        'change_amount',
        'change_percentage',
        'effective_date',
        'change_type',
        'reason',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'effective_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }
}
