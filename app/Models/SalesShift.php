<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesShift extends Model
{
    protected $table = 'sales_shifts';

    protected $fillable = [
        'branch_id',
        'department_id',
        'employee_id',
        'shift_number',
        'shift_date',
        'shift_type',
        'clock_in',
        'clock_out',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_variance',
        'status',
        'verified_by_id',
        'verified_by_type',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_variance' => 'decimal:2',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function verifiedBy(): MorphTo
    {
        return $this->morphTo('verified_by', 'verified_by_type', 'verified_by_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'sales_shift_id');
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'sales_shift_id');
    }

    // Helper Methods
    public function calculateCashVariance(): float
    {
        return $this->closing_cash - $this->expected_cash;
    }

    public function getActiveSales(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->sales()
            ->where('status', 'completed')
            ->with(['saleItems', 'payments'])
            ->get();
    }

    public function getTotalSales(): float
    {
        return $this->sales()
            ->where('status', 'completed')
            ->sum('total');
    }

    public function getTotalCashSales(): float
    {
        return $this->sales()
            ->where('status', 'completed')
            ->whereHas('payments', function ($query) {
                $query->where('payment_method', 'cash')
                    ->where('status', 'completed');
            })
            ->with('payments')
            ->get()
            ->sum(function ($sale) {
                return $sale->payments()
                    ->where('payment_method', 'cash')
                    ->where('status', 'completed')
                    ->sum('amount');
            });
    }

    public function updateExpectedCash(): void
    {
        $this->expected_cash = $this->opening_cash + $this->getTotalCashSales();
        $this->save();
    }

    public function updateCashVariance(): void
    {
        $this->cash_variance = $this->calculateCashVariance();
        $this->save();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'submitted', 'verified']);
    }

    public static function generateShiftNumber(string $departmentKey, string $userId, $createdAt, string $shiftType): string
    {
        $dept = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $departmentKey));
        if ($dept === '') {
            $dept = 'DEPT';
        }

        $user = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $userId));
        if ($user === '') {
            $user = 'USER';
        }
        if (strlen($user) > 8) {
            $user = substr($user, 0, 8);
        }

        $timestamp = Carbon::parse($createdAt)->format('YmdHis');
        $typeCode = strtoupper(substr((string) $shiftType, 0, 1));
        $base = "SS-{$dept}-{$user}-{$timestamp}-{$typeCode}";
        $seq = 1;

        do {
            $candidate = sprintf('%s-%03d', $base, $seq);
            $seq++;
        } while (self::where('shift_number', $candidate)->exists());

        return $candidate;
    }
}
