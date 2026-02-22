<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlAccount extends Model
{
    use SoftDeletes;

    protected $table = 'gl_accounts';

    protected $fillable = [
        'account_number',
        'account_name',
        'account_type',
        'account_category',
        'description',
        'debit_balance',
        'credit_balance',
        'normal_balance',
        'is_header',
        'parent_account_id',
        'is_active',
        'allow_manual_entry',
    ];

    protected $casts = [
        'debit_balance' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_header' => 'boolean',
        'is_active' => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GlAccount::class, 'parent_account_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'gl_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('account_category', $category);
    }

    public function scopeHeaders($query)
    {
        return $query->where('is_header', true);
    }

    public function scopeDetailAccounts($query)
    {
        return $query->where('is_header', false);
    }

    // Accessors
    public function getBalance(): float
    {
        // Asset, Expense, COGS accounts: Debit is positive
        if (in_array($this->account_type, ['asset', 'cogs', 'expense'])) {
            return floatval($this->debit_balance) - floatval($this->credit_balance);
        }

        // Liability, Equity, Revenue, Tax accounts: Credit is positive
        if (in_array($this->account_type, ['liability', 'equity', 'revenue', 'tax'])) {
            return floatval($this->credit_balance) - floatval($this->debit_balance);
        }

        return 0;
    }

    public function updateBalance(float $debit, float $credit): void
    {
        $this->debit_balance = floatval($this->debit_balance) + $debit;
        $this->credit_balance = floatval($this->credit_balance) + $credit;
        $this->save();
    }
}
