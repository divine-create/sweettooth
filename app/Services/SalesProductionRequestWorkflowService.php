<?php

namespace App\Services;

use App\Enums\SalesProductionRequestStatus;
use App\Models\SalesProductionRequest;
use App\Models\SalesProductionRequestItem;
use App\Models\User;
use App\Notifications\ProductionRequestStatusNotification;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SalesProductionRequestWorkflowService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function transitionRequest(
        SalesProductionRequest $request,
        SalesProductionRequestStatus|string $targetStatus,
        array $attributes = []
    ): SalesProductionRequest {
        return DB::transaction(function () use ($request, $targetStatus, $attributes) {
            $locked = SalesProductionRequest::query()->lockForUpdate()->findOrFail($request->id);
            $from = $this->normalizeStatus($locked->status);
            $to = $this->normalizeStatus($targetStatus);

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("Invalid request status transition: {$from->value} -> {$to->value}");
            }

            $updates = array_merge($attributes, ['status' => $to->value]);
            $updates = $this->applyLifecycleTimestamps($locked, $to, $updates);
            $locked->fill($updates)->save();
            $refreshed = $locked->refresh();

            $this->dispatchStatusNotification($refreshed, $from, $to);

            return $refreshed;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function transitionItem(
        SalesProductionRequestItem $item,
        SalesProductionRequestStatus|string $targetStatus,
        array $attributes = [],
        bool $syncParent = true
    ): SalesProductionRequestItem {
        return DB::transaction(function () use ($item, $targetStatus, $attributes, $syncParent) {
            $locked = SalesProductionRequestItem::query()->lockForUpdate()->findOrFail($item->id);
            $from = $this->normalizeStatus($locked->status);
            $to = $this->normalizeStatus($targetStatus);

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("Invalid item status transition: {$from->value} -> {$to->value}");
            }

            $locked->fill(array_merge($attributes, ['status' => $to->value]))->save();

            if ($syncParent && $locked->request) {
                $this->syncRequestStatusFromItems($locked->request);
            }

            return $locked->refresh();
        });
    }

    public function rejectRequest(SalesProductionRequest $request, ?string $reason = null): SalesProductionRequest
    {
        $current = $this->normalizeStatus($request->status);
        if (! $current->canReject()) {
            throw new DomainException("Cannot reject request in status {$current->value}");
        }

        $attributes = [];
        if ($reason) {
            $attributes['notes'] = $this->appendReason($request->notes, 'Rejected', $reason);
        }

        return $this->transitionRequest($request, SalesProductionRequestStatus::REJECTED, $attributes);
    }

    public function cancelRequest(SalesProductionRequest $request, ?string $reason = null): SalesProductionRequest
    {
        $current = $this->normalizeStatus($request->status);
        if (! $current->canCancel()) {
            throw new DomainException("Cannot cancel request in status {$current->value}");
        }

        $attributes = [];
        if ($reason) {
            $attributes['notes'] = $this->appendReason($request->notes, 'Cancelled', $reason);
        }

        return $this->transitionRequest($request, SalesProductionRequestStatus::CANCELLED, $attributes);
    }

    public function rejectItem(SalesProductionRequestItem $item, ?string $reason = null, bool $syncParent = true): SalesProductionRequestItem
    {
        $current = $this->normalizeStatus($item->status);
        if (! $current->canReject()) {
            throw new DomainException("Cannot reject item in status {$current->value}");
        }

        $attributes = [];
        if ($reason) {
            $attributes['notes'] = $this->appendReason($item->notes, 'Rejected', $reason);
        }

        return $this->transitionItem($item, SalesProductionRequestStatus::REJECTED, $attributes, $syncParent);
    }

    public function cancelItem(SalesProductionRequestItem $item, ?string $reason = null, bool $syncParent = true): SalesProductionRequestItem
    {
        $current = $this->normalizeStatus($item->status);
        if (! $current->canCancel()) {
            throw new DomainException("Cannot cancel item in status {$current->value}");
        }

        $attributes = [];
        if ($reason) {
            $attributes['notes'] = $this->appendReason($item->notes, 'Cancelled', $reason);
        }

        return $this->transitionItem($item, SalesProductionRequestStatus::CANCELLED, $attributes, $syncParent);
    }

    public function syncRequestStatusFromItems(SalesProductionRequest $request): SalesProductionRequest
    {
        $statuses = $request->items()
            ->pluck('status')
            ->filter()
            ->map(fn ($status) => $this->normalizeStatus($status)->value)
            ->unique()
            ->values();

        if ($statuses->isEmpty()) {
            return $request;
        }

        if ($statuses->count() === 1) {
            $status = SalesProductionRequestStatus::from($statuses->first());

            if ($this->normalizeStatus($request->status) !== $status) {
                return $this->transitionRequest($request, $status);
            }
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    private function applyLifecycleTimestamps(
        SalesProductionRequest $request,
        SalesProductionRequestStatus $targetStatus,
        array $updates
    ): array {
        if ($targetStatus === SalesProductionRequestStatus::APPROVED_BY_PRODUCTION && ! array_key_exists('approved_by_production_at', $updates)) {
            $updates['approved_by_production_at'] = now();
        }

        if ($targetStatus === SalesProductionRequestStatus::COMPLETED && ! array_key_exists('completed_at', $updates)) {
            $updates['completed_at'] = now();
        }

        if ($targetStatus === SalesProductionRequestStatus::DISPATCHED && ! array_key_exists('dispatched_at', $updates)) {
            $updates['dispatched_at'] = now();
        }

        if ($targetStatus === SalesProductionRequestStatus::RECEIVED_BY_SALES && ! array_key_exists('received_at', $updates)) {
            $updates['received_at'] = now();
        }

        if ($targetStatus === SalesProductionRequestStatus::CANCELLED) {
            $updates['completed_at'] = $updates['completed_at'] ?? $request->completed_at;
            $updates['dispatched_at'] = $updates['dispatched_at'] ?? null;
            $updates['received_at'] = $updates['received_at'] ?? null;
        }

        return $updates;
    }

    private function normalizeStatus(SalesProductionRequestStatus|string $status): SalesProductionRequestStatus
    {
        if ($status instanceof SalesProductionRequestStatus) {
            return $status;
        }

        return SalesProductionRequestStatus::from($status);
    }

    private function dispatchStatusNotification(
        SalesProductionRequest $request,
        SalesProductionRequestStatus $from,
        SalesProductionRequestStatus $to
    ): void {
        try {
            $recipients = $this->resolveNotificationRecipients($to, $request);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ProductionRequestStatusNotification($request, $from, $to));
            }
        } catch (\Throwable $e) {
            \Log::warning('Could not dispatch production request notification', ['error' => $e->getMessage()]);
        }
    }

    private function resolveNotificationRecipients(
        SalesProductionRequestStatus $status,
        SalesProductionRequest $request
    ): \Illuminate\Support\Collection {
        $branchId = $request->branch_id;

        $role = match ($status) {
            SalesProductionRequestStatus::PENDING               => 'Production Manager',
            SalesProductionRequestStatus::MATERIALS_REQUESTED   => 'Inventory Manager',
            SalesProductionRequestStatus::MATERIALS_APPROVED    => 'Production Manager',
            SalesProductionRequestStatus::PROCESSING            => 'Production Manager',
            SalesProductionRequestStatus::COMPLETED             => 'Sales Manager',
            SalesProductionRequestStatus::DISPATCHED            => 'Sales Manager',
            default                                             => null,
        };

        if ($role) {
            return User::role($role)->where('branch_id', $branchId)->get();
        }

        if (in_array($status, [
            SalesProductionRequestStatus::APPROVED_BY_PRODUCTION,
            SalesProductionRequestStatus::REJECTED,
            SalesProductionRequestStatus::CANCELLED,
            SalesProductionRequestStatus::RECEIVED_BY_SALES,
        ])) {
            $requester = $request->requestedBy;
            return $requester ? collect([$requester]) : collect();
        }

        return collect();
    }

    private function appendReason(?string $currentNotes, string $prefix, string $reason): string
    {
        $base = trim((string) $currentNotes);
        $suffix = sprintf('%s: %s', $prefix, trim($reason));

        return $base === '' ? $suffix : $base . PHP_EOL . PHP_EOL . $suffix;
    }
}
