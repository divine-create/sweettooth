<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Enums\ProductionRequestSourceType;
use App\Enums\SalesProductionRequestStatus;
use App\Models\ProductDispatch;
use App\Models\ProductionRequest;
use App\Models\SalesProductionRequestItem;

class SalesProductionDispatchService
{
    /**
     * Resolve linked sales request item from a production request source linkage.
     */
    public function resolveLinkedSalesItemFromProductionRequest(?ProductionRequest $productionRequest): ?SalesProductionRequestItem
    {
        if (! $productionRequest) {
            return null;
        }

        if (
            $productionRequest->source_type !== ProductionRequestSourceType::SALES_DEMAND->value
            || empty($productionRequest->source_id)
        ) {
            return null;
        }

        return SalesProductionRequestItem::query()->find((int) $productionRequest->source_id);
    }

    /**
     * Resolve and persist dispatch -> sales item linkage if possible.
     */
    public function attachSalesItemLink(ProductDispatch $dispatch): ?SalesProductionRequestItem
    {
        if (! empty($dispatch->sales_production_request_item_id)) {
            return SalesProductionRequestItem::query()->find((int) $dispatch->sales_production_request_item_id);
        }

        $item = $this->resolveLinkedSalesItemFromProductionRequest($dispatch->productionRequest);
        if (! $item) {
            return null;
        }

        $dispatch->update([
            'sales_production_request_item_id' => $item->id,
        ]);

        return $item;
    }

    public function markItemAsDispatched(SalesProductionRequestItem $item): ?SalesProductionRequestItem
    {
        return $this->advanceItemTo($item, SalesProductionRequestStatus::DISPATCHED);
    }

    public function markItemAsReceivedBySales(SalesProductionRequestItem $item): ?SalesProductionRequestItem
    {
        return $this->advanceItemTo($item, SalesProductionRequestStatus::RECEIVED_BY_SALES);
    }

    private function advanceItemTo(
        SalesProductionRequestItem $item,
        SalesProductionRequestStatus $targetStatus
    ): ?SalesProductionRequestItem {
        $item = SalesProductionRequestItem::query()->find($item->id);
        if (! $item) {
            CleanError::show('Sales request item not found. Please refresh and try again.');
            return null;
        }
        $currentStatus = $this->normalizeStatus($item->status);

        if ($currentStatus === $targetStatus) {
            return $item;
        }

        if ($currentStatus === SalesProductionRequestStatus::RECEIVED_BY_SALES) {
            return $item;
        }

        $ordered = [
            SalesProductionRequestStatus::MATERIALS_APPROVED,
            SalesProductionRequestStatus::PROCESSING,
            SalesProductionRequestStatus::COMPLETED,
            SalesProductionRequestStatus::DISPATCHED,
            SalesProductionRequestStatus::RECEIVED_BY_SALES,
        ];

        $orderedValues = array_map(static fn (SalesProductionRequestStatus $status) => $status->value, $ordered);
        $currentIndex = array_search($currentStatus->value, $orderedValues, true);
        $targetIndex = array_search($targetStatus->value, $orderedValues, true);

        if ($currentIndex === false || $targetIndex === false) {
            CleanError::show(
                "Cannot transition sales request item {$item->id} from {$currentStatus->value} to {$targetStatus->value}."
            );
            return $item;
        }

        if ($currentIndex > $targetIndex) {
            return $item;
        }

        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        for ($i = $currentIndex + 1; $i <= $targetIndex; $i++) {
            $item = $workflow->transitionItem($item, $ordered[$i]);
        }

        return $item->fresh();
    }

    private function normalizeStatus(SalesProductionRequestStatus|string $status): SalesProductionRequestStatus
    {
        if ($status instanceof SalesProductionRequestStatus) {
            return $status;
        }

        return SalesProductionRequestStatus::from($status);
    }
}
