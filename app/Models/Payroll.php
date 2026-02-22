<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'branch_id',
        'employee_id',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'base_salary',
        'overtime_hours',
        'overtime_rate',
        'allowances',
        'other_deductions',
        'tax_deductions',
        'gross_salary',
        'net_salary',
        'status',
        'bank_account_id',
        'gl_posting_status',
        'gl_posting_error',
        'gl_posted_at',
        'gl_payment_posting_status',
        'gl_payment_posting_error',
        'gl_payment_posted_at',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'created_by_id',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'base_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'allowances' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'tax_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'gl_posted_at' => 'datetime',
        'gl_payment_posted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function approvedBy()
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function calculateTotals(): void
    {
        $overtimePay = (float) $this->overtime_hours * (float) $this->overtime_rate;
        $gross = (float) $this->base_salary + (float) $this->allowances + $overtimePay;
        $net = $gross - (float) $this->tax_deductions - (float) $this->other_deductions;

        $this->gross_salary = $gross;
        $this->net_salary = $net;
    }

    protected static function booted(): void
    {
        static::saving(function (Payroll $payroll) {
            $payroll->calculateTotals();
        });
    }
}
