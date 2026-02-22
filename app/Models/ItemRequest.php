<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'department_id',
        'request_number',
        'requested_by_id',
        'requested_by_type',
        'request_date',
        'required_date',
        'shift',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'cancelled_by_id',
        'cancelled_by_type',
        'cancelled_at',
        'dispatched_by_id',
        'dispatched_by_type',
        'dispatched_at',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Scope to filter requests by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    /**
     * Alias for creator relationship (for backward compatibility)
     */
    public function requester(): MorphTo
    {
        return $this->creator();
    }

    /**
     * Alias for creator relationship (for backward compatibility)
     */
    public function requestedBy(): MorphTo
    {
        return $this->creator();
    }

    public function approver(): MorphTo
    {
        return $this->morphTo('approved_by');
    }

    public function canceller(): MorphTo
    {
        return $this->morphTo('cancelled_by');
    }

    public function dispatcher(): MorphTo
    {
        return $this->morphTo('dispatched_by');
    }

    /**
     * Get the request details
     */
    public function requestDetails(): HasMany
    {
        return $this->hasMany(ItemRequestDetail::class, 'request_id');
    }

    /**
     * Get the item dispatches for this request
     */
    public function itemDispatches(): HasMany
    {
        return $this->hasMany(ItemDispatch::class, 'request_id');
    }

    /**
     * Get the production requests for this item request
     */
    public function productionRequests(): HasMany
    {
        return $this->hasMany(ProductionRequest::class);
    }

    public function salesMaterialLink(): HasOne
    {
        return $this->hasOne(SalesProductionItemMaterialRequest::class);
    }

    /**
     * Generate unique request number
     */
    public static function generateRequestNumber($branchCode, $departmentCode): string
    {
        $date = now()->format('Ymd');
        $lastRequest = static::where('request_number', 'like', "REQ-{$branchCode}-{$departmentCode}-{$date}-%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "REQ-{$branchCode}-{$departmentCode}-{$date}-{$newNumber}";
    }

    /**
     * Check if request can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->requestDetails()->count() > 0;
    }

    /**
     * Check if request is fully dispatched
     */
    public function isFullyDispatched(): bool
    {
        return $this->requestDetails()
            ->whereColumn('quantity_dispatched', '<', 'quantity_approved')
            ->doesntExist();
    }
}
