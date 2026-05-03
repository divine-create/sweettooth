<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    protected $fillable = [
        'branch_id',
        'department_id',
        'request_number',
        'requested_by_id',
        'requested_by_type',
        'request_date',
        'shift',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'status' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaterialRequestDetail::class, 'request_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(MaterialRequestDispatch::class, 'request_id');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->details()->exists();
    }

    public function isFullyDispatched(): bool
    {
        return $this->details()
            ->get()
            ->every(fn ($detail) => $detail->quantity_dispatched >= $detail->quantity_approved);
    }

    public static function generateRequestNumber($branchCode, $departmentCode): string
    {
        $date = now()->format('ymd');
        $prefix = "MTR-{$branchCode}-{$departmentCode}-{$date}";

        $lastRequest = static::where('request_number', 'like', "{$prefix}-%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$newNumber}";
    }
}