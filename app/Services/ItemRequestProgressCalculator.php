<?php

namespace App\Services;

use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;

class ItemRequestProgressCalculator
{
    public function calculateProgress(ItemRequest $itemRequest): array
    {
        $details = $itemRequest->requestDetails;
        $totalItems = $details->count();
        $completedItems = 0;
        $totalRequested = 0;
        $totalProduced = 0;

        foreach ($details as $detail) {
            $requested = (float) $detail->quantity_requested;
            $approved = (float) $detail->quantity_approved;
            $dispatched = (float) $detail->quantity_dispatched;

            $totalRequested += $requested;

            // Calculate produced quantity from production records
            $produced = $this->getProducedQuantity($detail);
            $totalProduced += $produced;

            // Item is complete if dispatched >= approved >= requested
            if ($dispatched >= $approved && $approved >= $requested && $requested > 0) {
                $completedItems++;
            }
        }

        $overallProgress = $totalRequested > 0 ? ($totalProduced / $totalRequested) * 100 : 0;
        $itemsProgress = ($completedItems / max($totalItems, 1)) * 100;

        return [
            'overall_progress' => min(100, $overallProgress),
            'items_progress' => min(100, $itemsProgress),
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'total_requested' => $totalRequested,
            'total_produced' => $totalProduced,
            'status' => $this->determineStatus($overallProgress, $itemsProgress, $itemRequest->status)
        ];
    }

    private function getProducedQuantity(ItemRequestDetail $detail): float
    {
        // Sum quantities from production records linked to this item
        return \App\Models\ProductionRecord::whereHas('dailyProduce.productionRequest.itemRequest', function($q) use ($detail) {
            $q->where('id', $detail->request_id);
        })
        ->where('item_id', $detail->item_id)
        ->sum('quantity_produced');
    }

    private function determineStatus(float $overallProgress, float $itemsProgress, string $requestStatus): string
    {
        if ($requestStatus === 'cancelled') return 'cancelled';
        if ($requestStatus === 'completed') return 'completed';
        if ($overallProgress >= 100 && $itemsProgress >= 100) return 'completed';
        if ($overallProgress > 0 || $itemsProgress > 0) return 'in_progress';
        return 'pending';
    }
}