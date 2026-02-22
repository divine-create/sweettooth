<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillableTimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'customer_id',
        'branch_id',
        'project_name',
        'entry_date',
        'hours',
        'hourly_rate',
        'total_amount',
        'description',
        'billable',
        'billed',
        'sale_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billable' => 'boolean',
        'billed' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($entry) {
            $entry->total_amount = $entry->hours * $entry->hourly_rate;
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function scopeUnbilled($query)
    {
        return $query->where('billable', true)->where('billed', false);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function markAsBilled(int $saleId): void
    {
        $this->update([
            'billed' => true,
            'sale_id' => $saleId,
        ]);
    }
}
