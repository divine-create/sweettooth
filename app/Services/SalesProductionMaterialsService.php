<?php

namespace App\Services;

use App\Helpers\CleanError;
use App\Enums\ProductionRequestSourceType;
use App\Enums\SalesProductionRequestStatus;
use App\Models\Department;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\ProductionRequest;
use App\Models\Recipe;
use App\Models\SalesProductionItemMaterialRequest;
use App\Models\SalesProductionRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\UomConversionService;

class SalesProductionMaterialsService
{
    public function requestMaterials(SalesProductionRequestItem $item): ?SalesProductionItemMaterialRequest
    {
        return DB::transaction(function () use ($item) {
            $item = SalesProductionRequestItem::query()
                ->with([
                    'request',
                    'product',
                    'recipe.ingredients',
                    'productionDepartment',
                    'latestMaterialRequest.itemRequest',
                ])
                ->lockForUpdate()
                ->find($item->id);
            if (! $item) {
                CleanError::show('Sales request item not found. Please refresh and try again.');
                return null;
            }

            if (! $item->request) {
                CleanError::show('Sales request header is missing for this item.');
                return null;
            }

            $currentStatus = $this->normalizeStatus($item->status);
            if (
                $currentStatus !== SalesProductionRequestStatus::APPROVED_BY_PRODUCTION
                && $currentStatus !== SalesProductionRequestStatus::MATERIALS_REQUESTED
            ) {
                CleanError::show(
                    "Materials can only be requested from approved items. Current status: {$currentStatus->value}"
                );
                return null;
            }

            $existingLink = $item->latestMaterialRequest;
            if ($existingLink && $existingLink->itemRequest && $existingLink->itemRequest->status !== 'cancelled') {
                if ($currentStatus === SalesProductionRequestStatus::APPROVED_BY_PRODUCTION) {
                    $this->transitionToMaterialsRequested($item, $existingLink->itemRequest->request_number);
                }

                $this->attachItemRequestToExecutionRequests($item, $existingLink->item_request_id);

                return $existingLink->fresh(['itemRequest', 'salesItem']);
            }

            $recipe = $this->resolveRecipe($item);
            if (! $recipe) {
                CleanError::show("No active recipe found for sales request item {$item->id}.");
                return null;
            }

            $requestedBaseQuantity = $this->resolveRequestedBaseQuantity($item);
            $plannedQuantity = $this->calculatePlannedQuantity(
                $requestedBaseQuantity,
                (float) ($recipe->yield_quantity ?? 0)
            );
            $ingredients = $recipe->calculateIngredientsForQuantity($plannedQuantity);

            if (empty($ingredients)) {
                CleanError::show(
                    "Recipe '{$recipe->product_name}' has no ingredients defined. Add ingredients before requesting materials."
                );
                return null;
            }

            $actor = current_actor();
            if (! $actor) {
                CleanError::show('Unable to determine current actor for material request creation.');
                return null;
            }

            $branchId = (string) $item->request->branch_id;
            $department = $item->productionDepartment ?? Department::query()->find($item->production_department_id);
            if (! $department) {
                CleanError::show('Production department is missing for this sales request item.');
                return null;
            }

            $deptCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $department->name) ?: 'PROD', 0, 4));
            $branchCode = strtoupper(substr(str_replace('-', '', $branchId), 0, 8));
            $requestNumber = ItemRequest::generateRequestNumber($branchCode, $deptCode);

            $itemRequest = ItemRequest::create([
                'branch_id' => $branchId,
                'department_id' => $department->id,
                'request_number' => $requestNumber,
                'requested_by_id' => $actor->id,
                'requested_by_type' => get_class($actor),
                'request_date' => today(),
                'shift' => null,
                'status' => 'pending',
                'approved_by_id' => null,
                'approved_by_type' => null,
                'cancelled_by_id' => null,
                'cancelled_by_type' => null,
                'dispatched_by_id' => null,
                'dispatched_by_type' => null,
                'notes' => sprintf(
                    'Raw materials for sales request %s item %d [SPRI:%d]',
                    $item->request->request_number,
                    $item->id,
                    $item->id
                ),
            ]);

            $detailsCreated = 0;
            foreach ($ingredients as $ingredient) {
                $ingredientItemId = (int) ($ingredient['item_id'] ?? 0);
                $quantity = (float) ($ingredient['quantity'] ?? 0);
                $uomId = isset($ingredient['uom_id']) ? (int) $ingredient['uom_id'] : 0;

                if ($ingredientItemId <= 0 || $quantity <= 0 || $uomId <= 0) {
                    CleanError::show(
                        "Invalid ingredient mapping detected for recipe '{$recipe->product_name}'. Check item and UOM setup."
                    );
                    return null;
                }

                ItemRequestDetail::create([
                    'request_id' => $itemRequest->id,
                    'item_id' => $ingredientItemId,
                    'quantity_requested' => $quantity,
                    'quantity_approved' => 0,
                    'quantity_dispatched' => 0,
                    'uom_id' => $uomId,
                    'notes' => $ingredient['notes'] ?? null,
                ]);
                $detailsCreated++;
            }

            if ($detailsCreated === 0) {
                CleanError::show('No item request details were created for the material request.');
                return null;
            }

            $link = SalesProductionItemMaterialRequest::create([
                'sales_production_request_item_id' => $item->id,
                'item_request_id' => $itemRequest->id,
                'created_by_id' => $actor->id,
                'notes' => sprintf(
                    'Auto-created from sales request %s item %d.',
                    $item->request->request_number,
                    $item->id
                ),
            ]);

            $this->transitionToMaterialsRequested($item, $itemRequest->request_number);
            $this->attachItemRequestToExecutionRequests($item, $itemRequest->id);

            return $link->fresh(['itemRequest', 'salesItem']);
        });
    }

    public function syncItemMaterialsApproval(SalesProductionRequestItem $item): ?SalesProductionRequestItem
    {
        $item = SalesProductionRequestItem::query()
            ->with(['latestMaterialRequest.itemRequest', 'request'])
            ->find($item->id);
        if (! $item) {
            CleanError::show('Sales request item not found. Please refresh and try again.');
            return null;
        }

        if ($this->normalizeStatus($item->status) !== SalesProductionRequestStatus::MATERIALS_REQUESTED) {
            return $item;
        }

        $itemRequest = $item->latestMaterialRequest?->itemRequest;
        if (! $itemRequest) {
            return $item;
        }

        if ($itemRequest->status !== 'completed') {
            return $item;
        }

        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);

        return $workflow->transitionItem(
            $item,
            SalesProductionRequestStatus::MATERIALS_APPROVED,
            [
                'notes' => $this->appendNote(
                    $item->notes,
                    "Materials approved via inventory request {$itemRequest->request_number}."
                ),
            ]
        );
    }

    public function syncPendingMaterialsApprovals(?string $branchId = null, ?int $productionDepartmentId = null, int $limit = 200): int
    {
        $itemIds = SalesProductionRequestItem::query()
            ->where('status', SalesProductionRequestStatus::MATERIALS_REQUESTED->value)
            ->when($productionDepartmentId, fn ($query) => $query->where('production_department_id', $productionDepartmentId))
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('request', function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId);
                });
            })
            ->whereHas('latestMaterialRequest.itemRequest', function ($query) {
                $query->where('status', 'completed');
            })
            ->limit($limit)
            ->pluck('id');

        $synced = 0;
        foreach ($itemIds as $itemId) {
            $item = SalesProductionRequestItem::query()->find($itemId);
            if (! $item) {
                continue;
            }

            $updated = $this->syncItemMaterialsApproval($item);
            if ($this->normalizeStatus($updated->status) === SalesProductionRequestStatus::MATERIALS_APPROVED) {
                $synced++;
            }
        }

        return $synced;
    }

    protected function resolveRequestedBaseQuantity(SalesProductionRequestItem $item): float
    {
        $requested = (float) ($item->quantity_requested ?? 0);
        $product = $item->product;
        if (! $product) {
            return $requested;
        }

        $salesUomId = $product->sales_uom_id;
        $baseUomId = $product->uom_id;
        if (! $salesUomId || ! $baseUomId || (int) $salesUomId === (int) $baseUomId) {
            return $requested;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            $requested,
            (int) $salesUomId,
            (int) $baseUomId,
            [
                'product_id' => (int) $product->id,
                'branch_id' => (string) ($item->request?->branch_id ?? ''),
            ]
        );

        return $converted ?? $requested;
    }

    private function resolveRecipe(SalesProductionRequestItem $item): ?Recipe
    {
        if ($item->recipe_id) {
            $recipe = Recipe::query()->with('ingredients')->find($item->recipe_id);
            if ($recipe) {
                return $recipe;
            }
        }

        if (! $item->product_id) {
            return null;
        }

        return Recipe::query()
            ->with('ingredients')
            ->where('department_id', $item->production_department_id)
            ->where('product_id', $item->product_id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
    }

    private function calculatePlannedQuantity(float $requestedUnits, float $yieldQuantity): float
    {
        if ($requestedUnits <= 0) {
            CleanError::show('Requested quantity must be greater than zero.');
            return 0.0;
        }

        if ($yieldQuantity <= 0) {
            return $requestedUnits;
        }

        $batchCount = (int) ceil($requestedUnits / $yieldQuantity);

        return $batchCount * $yieldQuantity;
    }

    private function transitionToMaterialsRequested(SalesProductionRequestItem $item, string $requestNumber): void
    {
        if ($this->normalizeStatus($item->status) === SalesProductionRequestStatus::MATERIALS_REQUESTED) {
            return;
        }

        /** @var SalesProductionRequestWorkflowService $workflow */
        $workflow = app(SalesProductionRequestWorkflowService::class);
        $workflow->transitionItem(
            $item,
            SalesProductionRequestStatus::MATERIALS_REQUESTED,
            [
                'notes' => $this->appendNote($item->notes, "Materials requested: {$requestNumber}."),
            ]
        );
    }

    private function attachItemRequestToExecutionRequests(SalesProductionRequestItem $item, int $itemRequestId): void
    {
        $query = ProductionRequest::query()
            ->whereNull('item_request_id');

        $hasSourceType = Schema::hasColumn('production_requests', 'source_type');
        $hasSourceId = Schema::hasColumn('production_requests', 'source_id');

        if ($hasSourceType && $hasSourceId) {
            $query->where('source_type', ProductionRequestSourceType::SALES_DEMAND->value)
                ->where('source_id', (string) $item->id);
        } else {
            $query->where('notes', 'like', '%[SPRI:' . $item->id . ']%');
        }

        $query->update(['item_request_id' => $itemRequestId]);
    }

    private function normalizeStatus(SalesProductionRequestStatus|string $status): SalesProductionRequestStatus
    {
        if ($status instanceof SalesProductionRequestStatus) {
            return $status;
        }

        return SalesProductionRequestStatus::from($status);
    }

    private function appendNote(?string $current, string $note): string
    {
        $current = trim((string) $current);

        if ($current === '') {
            return $note;
        }

        return $current . PHP_EOL . PHP_EOL . $note;
    }
}
