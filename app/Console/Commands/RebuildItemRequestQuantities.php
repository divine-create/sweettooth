<?php

namespace App\Console\Commands;

use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildItemRequestQuantities extends Command
{
    protected $signature = 'inventory:rebuild-request-quantities {request_id}';
    protected $description = 'Rebuild item_request_details.quantity_requested from production recipes for a request';

    public function handle(): int
    {
        $requestId = (int) $this->argument('request_id');

        $request = ItemRequest::with(['productionRequests'])->find($requestId);
        if (! $request) {
            $this->error("Request {$requestId} not found.");
            return self::FAILURE;
        }

        $totals = [];

        foreach ($request->productionRequests as $prodReq) {
            if (! $prodReq->recipe_id) {
                continue;
            }

            $recipe = Recipe::with('ingredients')->find($prodReq->recipe_id);
            if (! $recipe) {
                continue;
            }

            $yieldQty = (float) $recipe->yield_quantity;
            if ($yieldQty <= 0) {
                continue;
            }

            $requestedUnits = (float) ($prodReq->requested_units ?? 0);
            $plannedUnits = (float) ($prodReq->planned_production_quantity ?? 0);
            $baseUnits = $requestedUnits > 0 ? $requestedUnits : $plannedUnits;

            if ($baseUnits <= 0) {
                continue;
            }

            $batchCount = (int) ceil($baseUnits / $yieldQty);
            if ($batchCount <= 0) {
                continue;
            }

            $ingredients = $recipe->calculateIngredientsForBatch($batchCount);
            foreach ($ingredients as $ingredient) {
                $itemId = $ingredient['item_id'] ?? null;
                if (! $itemId) {
                    continue;
                }

                $qty = (float) ($ingredient['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $uomId = $ingredient['uom_id'] ?? null;

                if (! isset($totals[$itemId])) {
                    $totals[$itemId] = ['quantity' => 0, 'uom_id' => $uomId];
                }

                $totals[$itemId]['quantity'] += $qty;
                if (! $totals[$itemId]['uom_id'] && $uomId) {
                    $totals[$itemId]['uom_id'] = $uomId;
                }
            }
        }

        if (empty($totals)) {
            $this->warn('No ingredient quantities were calculated.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($requestId, $totals) {
            foreach ($totals as $itemId => $data) {
                $detail = ItemRequestDetail::where('request_id', $requestId)
                    ->where('item_id', $itemId)
                    ->first();

                if (! $detail) {
                    ItemRequestDetail::create([
                        'request_id' => $requestId,
                        'item_id' => $itemId,
                        'quantity_requested' => $data['quantity'],
                        'quantity_approved' => 0,
                        'quantity_dispatched' => 0,
                        'uom_id' => $data['uom_id'],
                    ]);
                    continue;
                }

                $detail->quantity_requested = $data['quantity'];
                if (! $detail->uom_id && $data['uom_id']) {
                    $detail->uom_id = $data['uom_id'];
                }
                $detail->save();
            }
        });

        $this->info('Request quantities rebuilt.');
        return self::SUCCESS;
    }
}
