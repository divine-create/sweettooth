<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'branch_id',
        'department_id',
        'employee_id',
        'shift_number',
        'shift_date',
        'shift_type',
        'clock_in',
        'clock_out',
        'status',
        'notes',
        'workflow_state',
        'metadata',
        'stock_verified_at',
        'shift_closed_at',
        'auto_clocked_out_at',
        'auto_clock_out_reason',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_type' => 'string',
        'status' => 'string',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'metadata' => 'array',
        'stock_verified_at' => 'datetime',
        'shift_closed_at' => 'datetime',
        'auto_clocked_out_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(ShiftConfiguration::class, 'metadata->config_id');
    }

    public function dailyProduces(): HasMany
    {
        return $this->hasMany(DailyProduce::class);
    }

    public function productionRequests(): HasMany
    {
        return $this->hasMany(ProductionRequest::class);
    }

    public function productionCallbacks(): HasMany
    {
        return $this->hasMany(ProductionCallback::class);
    }

    public function rawMaterialUtilizations(): HasMany
    {
        return $this->hasMany(RawMaterialUtilization::class);
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
        $base = "SH-{$dept}-{$user}-{$timestamp}-{$typeCode}";
        $seq = 1;

        do {
            $candidate = sprintf('%s-%03d', $base, $seq);
            $seq++;
        } while (self::where('shift_number', $candidate)->exists());

        return $candidate;
    }
}
