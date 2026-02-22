<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseApprovalRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_id',
        'requested_by_id',
        'requested_by_type',
        'approved_by_id',
        'approved_by_type',
        'status',
        'request_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase this request is for
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the employee who requested approval
     */
    public function requestedBy()
    {
        return $this->morphTo('requested_by');
    }

    /**
     * Get the employee who approved this request
     */
    public function approvedBy()
    {
        return $this->morphTo('approved_by');
    }

    /**
     * Scope to filter pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
