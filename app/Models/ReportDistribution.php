<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDistribution extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'reportable_type',
        'reportable_id',
        'recipient_type',
        'recipient_id',
        'recipient_email',
        'recipient_name',
        'distribution_method',
        'sent_at',
        'viewed_at',
        'downloaded_at',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    /**
     * Get the owning reportable model.
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * Get the branch that owns the distribution.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the recipient (polymorphic - Employee or User).
     */
    public function recipient()
    {
        if ($this->recipient_type === 'employee') {
            return Employee::find($this->recipient_id);
        } elseif ($this->recipient_type === 'user') {
            return User::find($this->recipient_id);
        }

        return null;
    }

    /**
     * Scope a query to only include distributions for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for pending distributions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent distributions.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed distributions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark as sent.
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered()
    {
        $this->update(['status' => 'delivered']);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed($reason)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark as viewed.
     */
    public function markAsViewed()
    {
        $this->update(['viewed_at' => now()]);
    }

    /**
     * Mark as downloaded.
     */
    public function markAsDownloaded()
    {
        $this->update(['downloaded_at' => now()]);
    }
}
