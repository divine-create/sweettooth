<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SupplierDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_documents';

    protected $fillable = [
        'supplier_id',
        'document_type',
        'document_name',
        'file_path',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the supplier this document belongs to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who verified this document.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope to get only verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get documents by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to get expiring documents (within 30 days).
     */
    public function scopeExpiring($query)
    {
        $thirtyDaysFromNow = now()->addDays(30);
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('expiry_date', '>=', now());
    }

    /**
     * Scope to get expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Check if document has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date < now()->toDateString();
    }

    /**
     * Check if document is expiring soon (within 30 days).
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        $thirtyDaysFromNow = now()->addDays(30);
        return $this->expiry_date <= $thirtyDaysFromNow && $this->expiry_date >= now()->toDateString();
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date);
    }

    /**
     * Mark document as verified.
     */
    public function markAsVerified($userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark document as unverified.
     */
    public function markAsUnverified(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Get document status.
     */
    public function getStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }
        if (!$this->is_verified) {
            return 'unverified';
        }
        return 'valid';
    }
}
