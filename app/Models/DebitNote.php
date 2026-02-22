<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'debit_note_number',
        'supplier_id',
        'purchase_id',
        'branch_id',
        'debit_note_date',
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
        'debit_note_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_applied' => 'decimal:2',
        'gl_posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($debitNote) {
            if (empty($debitNote->debit_note_number)) {
                $debitNote->debit_note_number = static::generateDebitNoteNumber();
            }
        });
    }

    public static function generateDebitNoteNumber(): string
    {
        $prefix = 'DBN-' . date('Ym');
        $lastNote = static::withTrashed()
            ->where('debit_note_number', 'like', $prefix . '%')
            ->orderBy('debit_note_number', 'desc')
            ->first();

        if ($lastNote) {
            $lastNumber = (int) substr($lastNote->debit_note_number, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
        return $this->hasMany(DebitNoteItem::class);
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
}
