<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'credit_note_number',
        'customer_id',
        'sale_id',
        'branch_id',
        'credit_note_date',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'reason',
        'amount_applied',
        'created_by',
        'gl_posting_status',
        'gl_entry_id',
        'gl_posting_error',
        'gl_posted_at',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_applied' => 'decimal:2',
        'gl_posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = static::generateCreditNoteNumber();
            }
        });
    }

    public static function generateCreditNoteNumber(): string
    {
        $prefix = 'CN-' . date('Ym');
        $lastNote = static::withTrashed()
            ->where('credit_note_number', 'like', $prefix . '%')
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastNote) {
            $lastNumber = (int) substr($lastNote->credit_note_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('line_total');
        $taxAmount = $this->items->sum(function ($item) {
            return $item->line_total * ($item->tax_rate / 100);
        });

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }

    public function getUnappliedAmountAttribute(): float
    {
        return $this->total - $this->amount_applied;
    }

    public function isFullyApplied(): bool
    {
        return $this->amount_applied >= $this->total;
    }

    public function canApply(): bool
    {
        return $this->status === 'issued' && !$this->isFullyApplied();
    }
}
