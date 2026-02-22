<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingTaxReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'gross_amount',
        'tax_rate',
        'withheld_amount',
        'net_amount',
        'receipt_date',
        'certificate_number',
        'gl_posting_status',
        'gl_entry_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'withheld_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'receipt_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($receipt) {
            $receipt->withheld_amount = $receipt->gross_amount * ($receipt->tax_rate / 100);
            $receipt->net_amount = $receipt->gross_amount - $receipt->withheld_amount;
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function glEntry(): BelongsTo
    {
        return $this->belongsTo(GlEntry::class);
    }
}
