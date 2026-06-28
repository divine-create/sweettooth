<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollRun extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'status',
        'notes',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'created_by_id',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'payroll_run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    /** Number of payslips in the run (uses the loaded relation when available). */
    public function getPayrollsCountAttribute(): int
    {
        return $this->relationLoaded('payrolls')
            ? $this->payrolls->count()
            : $this->payrolls()->count();
    }

    /** Total gross across the run's payslips (cancelled excluded). */
    public function getTotalGrossAttribute(): float
    {
        return $this->relationLoaded('payrolls')
            ? (float) $this->payrolls->where('status', '!=', 'cancelled')->sum('gross_salary')
            : (float) $this->payrolls()->where('status', '!=', 'cancelled')->sum('gross_salary');
    }

    /** Total net across the run's payslips (cancelled excluded). */
    public function getTotalNetAttribute(): float
    {
        return $this->relationLoaded('payrolls')
            ? (float) $this->payrolls->where('status', '!=', 'cancelled')->sum('net_salary')
            : (float) $this->payrolls()->where('status', '!=', 'cancelled')->sum('net_salary');
    }
}
