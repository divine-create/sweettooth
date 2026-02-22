<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaimItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_claim_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'receipt_path',
        'gl_account_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function expenseClaim(): BelongsTo
    {
        return $this->belongsTo(ExpenseClaim::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public static function categories(): array
    {
        return [
            'travel' => 'Travel & Transportation',
            'meals' => 'Meals & Entertainment',
            'supplies' => 'Office Supplies',
            'communication' => 'Communication',
            'accommodation' => 'Accommodation',
            'professional' => 'Professional Services',
            'other' => 'Other',
        ];
    }
}
