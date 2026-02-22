<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'branch_id',
        'causer_type',
        'causer_id',
        'auditable_type',
        'auditable_id',
        'setting_type',
        'action',
        'user_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'status',
        'approval_request_id',
        'logged_at',
        'metadata',
        'details',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'details' => 'array',
        'logged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Who performed the action (User or Employee)
     */
    public function causer(): MorphTo
    {
        return $this->morphTo('causer', 'causer_type', 'causer_id');
    }

    /**
     * What was affected (any model)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /**
     * Linked approval request (if any)
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByWebGuard($query)
    {
        return $query->where('causer_type', \App\Models\User::class);
    }

    public function scopeByEmployeeGuard($query)
    {
        return $query->where('causer_type', \App\Models\Employee::class);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Attributes
     */
    public function getCauserNameAttribute(): string
    {
        return $this->causer?->name
            ?? $this->causer?->email
            ?? 'System';
    }

    public function getDisplayChangeAttribute(): string
    {
        if (!$this->old_values || !$this->new_values) {
            return '';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }
}
