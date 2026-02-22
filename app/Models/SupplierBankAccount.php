<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_bank_accounts';

    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'bank_code',
        'branch_code',
        'account_type',
        'currency',
        'is_primary',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the supplier this bank account belongs to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to get only active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary account.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get accounts by currency.
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Get formatted account number (masked).
     */
    public function getMaskedAccountNumber(): string
    {
        $accountNumber = $this->account_number;
        $length = strlen($accountNumber);
        if ($length > 4) {
            return str_repeat('*', $length - 4) . substr($accountNumber, -4);
        }
        return $accountNumber;
    }

    /**
     * Get full account identifier.
     */
    public function getFullAccountIdentifier(): string
    {
        return "{$this->bank_name} - {$this->getMaskedAccountNumber()}";
    }
}
