<?php

namespace App\Models;

use App\Enums\SalesProductionRequestStatus;
use App\Services\SalesProductionRequestWorkflowService;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesProductionRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_production_request_id',
        'production_department_id',
        'recipe_id',
        'product_id',
        'quantity_requested',
        'quantity_produced',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => SalesProductionRequestStatus::class,
        'quantity_requested' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesProductionRequestItem $item) {
            if (! $item->status) {
                $item->status = SalesProductionRequestStatus::PENDING;
            }
        });

        static::updating(function (SalesProductionRequestItem $item) {
            if (! $item->isDirty('status')) {
                return;
            }

            $from = self::normalizeStatusValue($item->getOriginal('status'));
            $to = self::normalizeStatusValue($item->status);

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("Invalid item status transition: {$from->value} -> {$to->value}");
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SalesProductionRequest::class, 'sales_production_request_id');
    }

    public function productionDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'production_department_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function executionRequests(): HasMany
    {
        return $this->hasMany(ProductionRequest::class, 'source_id', 'id')
            ->where('source_type', 'SALES_DEMAND');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(SalesProductionItemMaterialRequest::class, 'sales_production_request_item_id');
    }

    public function latestMaterialRequest(): HasOne
    {
        return $this->hasOne(SalesProductionItemMaterialRequest::class, 'sales_production_request_item_id')
            ->latestOfMany();
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
    public function transitionTo(
        SalesProductionRequestStatus|string $targetStatus,
        array $attributes = [],
        bool $syncParent = true
    ): self {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->transitionItem($this, $targetStatus, $attributes, $syncParent);
    }

    public function reject(?string $reason = null, bool $syncParent = true): self
    {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->rejectItem($this, $reason, $syncParent);
    }

    public function cancel(?string $reason = null, bool $syncParent = true): self
    {
        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->cancelItem($this, $reason, $syncParent);
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
