<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'requested_by_id',
        'requested_by_type',
        'action',
        'auditable_id',
        'auditable_type',
        'status',
        'reason',
        'metadata',
        'approved_at',
        'approved_by_id',
        'approved_by_type',
        'executed_at',
        'executed_by_id',
        'executed_by_type',
        'rejection_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * Who requested the action (User or Employee)
     */
    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by', 'requested_by_type', 'requested_by_id');
    }

    /**
     * What model is being affected
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /**
     * Who approved the request (User or Employee)
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    /**
     * Who executed the approved action
     */
    public function executedBy(): MorphTo
    {
        return $this->morphTo('executed_by', 'executed_by_type', 'executed_by_id');
    }

    /**
     * Related audit logs
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Approve the request
     */
    public function approve(Model $approver, ?string $notes = null): self
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => $approver->id,
            'approved_by_type' => get_class($approver),
        ]);

        return $this;
    }

    /**
     * Reject the request
     */
    public function reject(Model $rejector, string $reason): self
    {
        $this->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by_id' => $rejector->id,
            'approved_by_type' => get_class($rejector),
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Create a new pending approval request
     */
    public static function createPending(Model $requestedBy, string $action, Model $auditable): self
    {
        return static::create([
            'requested_by_id' => $requestedBy->id,
            'requested_by_type' => get_class($requestedBy),
            'action' => $action,
            'auditable_id' => $auditable->id,
            'auditable_type' => get_class($auditable),
            'status' => 'pending',
            'metadata' => [
                'branch_id' => $auditable->branch_id ?? $auditable->branch ?? null,
            ],
        ]);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExecuted($query)
    {
        return $query->whereNotNull('executed_at');
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('metadata->branch_id', $branchId);
    }
}
