<?php

namespace App\Models;

use App\Enums\SalesProductionRequestStatus;
use App\Services\SalesProductionRequestWorkflowService;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesProductionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'sales_department_id',
        'request_number',
        'status',
        'priority',
        'requested_by_id',
        'requested_by_type',
        'approved_by_production_id',
        'approved_by_production_at',
        'completed_at',
        'dispatched_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'status' => SalesProductionRequestStatus::class,
        'approved_by_production_at' => 'datetime',
        'completed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesProductionRequest $request) {
            if (! $request->status) {
                $request->status = SalesProductionRequestStatus::PENDING;
            }
        });

        static::updating(function (SalesProductionRequest $request) {
            if (! $request->isDirty('status')) {
                return;
            }

            $from = self::normalizeStatusValue($request->getOriginal('status'));
            $to = self::normalizeStatusValue($request->status);

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("Invalid request status transition: {$from->value} -> {$to->value}");
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sales_department_id');
    }

    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by', 'requested_by_type', 'requested_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesProductionRequestItem::class, 'sales_production_request_id');
    }

    public static function generateRequestNumber(string $branchCode, string $salesDeptCode): string
    {
        $date = now()->format('Ymd');
        $prefix = sprintf('SPR-%s-%s-%s', $branchCode, $salesDeptCode, $date);

        $last = static::query()
            ->where('request_number', 'like', $prefix . '-%')
            ->orderByDesc('request_number')
            ->value('request_number');

        $next = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    public function canTransitionTo(SalesProductionRequestStatus|string $targetStatus): bool
    {
        $from = $this->status instanceof SalesProductionRequestStatus
            ? $this->status
            : SalesProductionRequestStatus::from((string) $this->status);
        $to = $targetStatus instanceof SalesProductionRequestStatus
            ? $targetStatus
            : SalesProductionRequestStatus::from($targetStatus);

        return $from->canTransitionTo($to);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function transitionTo(SalesProductionRequestStatus|string $targetStatus, array $attributes = []): self
    {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->transitionRequest($this, $targetStatus, $attributes);
    }

    public function reject(?string $reason = null): self
    {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->rejectRequest($this, $reason);
    }

    public function cancel(?string $reason = null): self
    {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->cancelRequest($this, $reason);
    }

    /**
     * @param  mixed  $status
     */
    private static function normalizeStatusValue(mixed $status): SalesProductionRequestStatus
    {
        if ($status instanceof SalesProductionRequestStatus) {
            return $status;
        }

        return SalesProductionRequestStatus::from((string) $status);
    }
}
