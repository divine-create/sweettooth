<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Enums\ProductionRequestSourceType;
use App\Enums\SalesProductionRequestStatus;
use App\Models\Department;
use App\Models\ProductionRequest;
use App\Models\Recipe;
use App\Models\SalesProductionRequestItem;
use App\Services\SalesProductionMaterialsService;
use App\Services\SalesProductionRequestWorkflowService;
use App\Services\UomConversionService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class SalesRequests extends Component
{
    use Interactions;
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;
    /** @var array<int> */
    public array $departmentIds = [];
    public string $search = '';
    public string $statusFilter = 'all';
    public string $priorityFilter = 'all';
    public int $perPage = 15;

    public function mount(?string $deptSlug = null): void
    {
        $deptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        if (! is_super_admin() && ! $deptSlug) {
            $userDept = auth()->user()?->department;
            if ($userDept && strtolower($userDept->category?->name ?? '') === 'production') {
                $deptSlug = $userDept->slug;
            } else {
                abort(403, 'Production department access required.');
            }
        }

        if ($deptSlug) {
            $this->dept_slug = $deptSlug;
            $branchId = $this->getBranchId();
            $this->department = Department::query()
                ->where('slug', $deptSlug)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)
                            ->orWhereNull('branch_id');
                    });
                })
                ->first();

            if (! $this->department) {
                abort(404, 'Department not found.');
            }

            $this->departmentIds = $this->resolveEquivalentProductionDepartmentIds($this->department);
        }
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function approveItem(int $itemId): void
    {
        try {
            DB::transaction(function () use ($itemId) {
                $item = $this->resolveItem($itemId);
                /** @var SalesProductionRequestWorkflowService $workflow */
                $workflow = app(SalesProductionRequestWorkflowService::class);

                $workflow->transitionItem($item, SalesProductionRequestStatus::APPROVED_BY_PRODUCTION);
                $this->createExecutionRequestForApprovedItem($item->fresh('request'));
            });

            $this->toast()->success('Sales request item approved. Use "Request Materials" when ready.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Approval failed: ' . $e->getMessage())->send();
        }
    }

    public function rejectItem(int $itemId): void
    {
        try {
            $item = $this->resolveItem($itemId);
            /** @var SalesProductionRequestWorkflowService $workflow */
            $workflow = app(SalesProductionRequestWorkflowService::class);

            $workflow->rejectItem($item, 'Rejected by production review.');

            $this->toast()->success('Sales request item rejected.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Rejection failed: ' . $e->getMessage())->send();
        }
    }

    public function approveFilteredPending(): void
    {
        $ids = $this->baseQuery()
            ->where('status', SalesProductionRequestStatus::PENDING->value)
            ->limit(200)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->toast()->warning('No pending items found for current filters.')->send();

            return;
        }

        $approved = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($id) {
                    $item = $this->resolveItem((int) $id);
                    /** @var SalesProductionRequestWorkflowService $workflow */
                    $workflow = app(SalesProductionRequestWorkflowService::class);

                    $workflow->transitionItem($item, SalesProductionRequestStatus::APPROVED_BY_PRODUCTION);
                    $this->createExecutionRequestForApprovedItem($item->fresh('request'));
                });
                $approved++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        if ($approved > 0) {
            $this->toast()->success("Approved {$approved} item(s)." . ($failed > 0 ? " {$failed} failed." : ''))->send();
        } else {
            $this->toast()->error('Bulk approval failed.')->send();
        }
    }

    public function requestMaterials(int $itemId): void
    {
        try {
            $item = $this->resolveItem($itemId);
            /** @var SalesProductionMaterialsService $materialsService */
            $materialsService = app(SalesProductionMaterialsService::class);
            $link = $materialsService->requestMaterials($item);

            $this->toast()->success(
                'Materials request ' . ($link->itemRequest?->request_number ?? '#' . $link->item_request_id) . ' created.'
            )->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Material request failed: ' . $e->getMessage())->send();
        }
    }

    public function syncMaterialsStatus(int $itemId): void
    {
        try {
            $item = $this->resolveItem($itemId);
            /** @var SalesProductionMaterialsService $materialsService */
            $materialsService = app(SalesProductionMaterialsService::class);
            $updated = $materialsService->syncItemMaterialsApproval($item);
            $status = is_string($updated->status) ? $updated->status : $updated->status->value;

            if ($status === SalesProductionRequestStatus::MATERIALS_APPROVED->value) {
                $this->toast()->success('Materials approved and item moved to next stage.')->send();
            } else {
                $this->toast()->warning('Materials are not fully approved/dispatched yet.')->send();
            }
        } catch (\Throwable $e) {
            $this->toast()->error('Sync failed: ' . $e->getMessage())->send();
        }
    }

    private function resolveItem(int $itemId): SalesProductionRequestItem
    {
        $query = SalesProductionRequestItem::query()
            ->with([
                'request',
                'recipe.product',
                'recipe.unitOfMeasure',
                'product.unitOfMeasure',
                'product.salesUom',
                'productionDepartment',
                'latestMaterialRequest.itemRequest',
            ])
            ->whereKey($itemId);

        if (! empty($this->departmentIds)) {
            $query->whereIn('production_department_id', $this->departmentIds);
        } elseif (! is_super_admin() && $this->department) {
            $query->where('production_department_id', $this->department->id);
        }

        return $query->firstOrFail();
    }

    /**
     * @return array{production_qty:float,production_symbol:string,sales_qty:float,sales_symbol:string,converted:bool}
     */
    public function getQuantityDisplay(SalesProductionRequestItem $item): array
    {
        $product = $item->product;
        $salesQty = (float) $item->quantity_requested;
        $salesSymbol = $product?->salesUom?->symbol
            ?? $product?->unitOfMeasure?->symbol
            ?? $item->recipe?->unitOfMeasure?->symbol
            ?? '';

        $productionUomId = $product?->uom_id ?? $item->recipe?->uom_id;
        $productionSymbol = $product?->unitOfMeasure?->symbol
            ?? $item->recipe?->unitOfMeasure?->symbol
            ?? $salesSymbol;

        $convertedQty = null;
        if ($product?->sales_uom_id && $productionUomId) {
            /** @var UomConversionService $converter */
            $converter = app(UomConversionService::class);
            $convertedQty = $converter->tryConvert(
                $salesQty,
                (int) $product->sales_uom_id,
                (int) $productionUomId,
                [
                    'branch_id' => $this->getBranchId(),
                    'product_id' => (string) $product->id,
                ]
            );
        }

        $productionQty = $convertedQty ?? $salesQty;

        return [
            'production_qty' => $productionQty,
            'production_symbol' => (string) $productionSymbol,
            'sales_qty' => $salesQty,
            'sales_symbol' => (string) $salesSymbol,
            'converted' => $convertedQty !== null,
        ];
    }

    private function resolveProductionQuantity(SalesProductionRequestItem $item): float
    {
        $display = $this->getQuantityDisplay($item);

        return (float) $display['production_qty'];
    }

    private function createExecutionRequestForApprovedItem(SalesProductionRequestItem $item): void
    {
        if (! $item->request) {
            return;
        }

        $token = '[SPRI:' . $item->id . ']';
        $hasSourceType = Schema::hasColumn('production_requests', 'source_type');
        $hasSourceId = Schema::hasColumn('production_requests', 'source_id');

        $existing = ProductionRequest::query();
        if ($hasSourceType && $hasSourceId) {
            $existing->where('source_type', ProductionRequestSourceType::SALES_DEMAND->value)
                ->where('source_id', (string) $item->id);
        } else {
            $existing->where('notes', 'like', '%' . $token . '%');
        }

        if ($existing->exists()) {
            return;
        }

        $recipe = $item->recipe;

        if (! $recipe && $item->recipe_id) {
            $recipe = Recipe::query()->find($item->recipe_id);
        }

        // Backward compatibility for older migrated rows that may have only product_id.
        if (! $recipe && $item->product_id) {
            $recipe = Recipe::query()
                ->where('department_id', $item->production_department_id)
                ->where('product_id', $item->product_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
        }

        if (! $recipe) {
            throw new DomainException("No active recipe found for sales request item {$item->id}.");
        }

        $requestedUnits = $this->resolveProductionQuantity($item);
        $yieldQuantity = (float) ($recipe->yield_quantity ?? 0);
        $plannedQuantity = $requestedUnits;

        if ($yieldQuantity > 0) {
            $batchCount = (int) ceil($requestedUnits / $yieldQuantity);
            $plannedQuantity = $batchCount * $yieldQuantity;
        }

        $notes = trim(sprintf(
            'Generated from sales request %s item %d %s',
            $item->request->request_number,
            $item->id,
            $token
        ));

        $attributes = [
            'shift_id' => null,
            'item_request_id' => null,
            'recipe_id' => $recipe->id,
            'planned_production_quantity' => $plannedQuantity,
            'requested_units' => $requestedUnits,
            'notes' => $notes,
            'production_department_id' => $item->production_department_id,
            // Sales-side approval already happened, so execution request starts approved.
            'status' => 'approved',
            'priority' => $item->request->priority ?? 'normal',
            'created_by_id' => auth()->id(),
        ];

        if ($hasSourceType && $hasSourceId) {
            $attributes['source_type'] = ProductionRequestSourceType::SALES_DEMAND->value;
            $attributes['source_id'] = (string) $item->id;
        }

        ProductionRequest::create($attributes);
    }

    private function baseQuery()
    {
        $branchId = $this->getBranchId();

        $query = SalesProductionRequestItem::query()
            ->with([
                'request:id,request_number,sales_department_id,priority,status,branch_id,created_at',
                'request.salesDepartment:id,name',
                'productionDepartment:id,name',
                'recipe:id,product_name,sku,yield_quantity,uom_id',
                'recipe.unitOfMeasure:id,symbol',
                'product:id,name,sku,uom_id,sales_uom_id',
                'product.unitOfMeasure:id,symbol',
                'product.salesUom:id,symbol',
                'latestMaterialRequest',
                'latestMaterialRequest.itemRequest:id,request_number,status',
            ])
            ->whereHas('request', function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        if (! empty($this->departmentIds)) {
            $query->whereIn('production_department_id', $this->departmentIds);
        } elseif (! is_super_admin() && $this->department) {
            $query->where('production_department_id', $this->department->id);
        } elseif ($this->department) {
            $query->where('production_department_id', $this->department->id);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter !== 'all') {
            $query->whereHas('request', function ($q) {
                $q->where('priority', $this->priorityFilter);
            });
        }

        if (trim($this->search) !== '') {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('request', function ($rq) use ($search) {
                    $rq->where('request_number', 'like', $search);
                })
                    ->orWhereHas('recipe', function ($rq) use ($search) {
                        $rq->where('product_name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    })
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    })
                    ->orWhereHas('productionDepartment', function ($dq) use ($search) {
                        $dq->where('name', 'like', $search);
                    });
            });
        }

        return $query->latest('created_at');
    }

    public function render()
    {
        $this->syncPendingMaterialsApprovals();

        $items = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.branch-dashboard.production.request.sales-requests', [
            'items' => $items,
        ]);
    }

    private function syncPendingMaterialsApprovals(): void
    {
        /** @var SalesProductionMaterialsService $materialsService */
        $materialsService = app(SalesProductionMaterialsService::class);
        if (! empty($this->departmentIds)) {
            foreach ($this->departmentIds as $departmentId) {
                $materialsService->syncPendingMaterialsApprovals(
                    $this->getBranchId(),
                    (int) $departmentId,
                    100
                );
            }

            return;
        }

        $materialsService->syncPendingMaterialsApprovals($this->getBranchId(), $this->department?->id, 100);
    }

    /**
     * Resolve equivalent production department records across branch/global scopes.
     *
     * @return array<int>
     */
    private function resolveEquivalentProductionDepartmentIds(Department $department): array
    {
        $branchId = $this->getBranchId();
        $targetNameKey = $this->normalizeDepartmentKey($department->name);
        $targetSlugKey = $this->normalizeDepartmentKey((string) $department->slug);

        $ids = Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['production']);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get(['id', 'name', 'slug'])
            ->filter(function (Department $candidate) use ($department, $targetNameKey, $targetSlugKey): bool {
                if ((int) $candidate->id === (int) $department->id) {
                    return true;
                }

                $candidateNameKey = $this->normalizeDepartmentKey($candidate->name);
                $candidateSlugKey = $this->normalizeDepartmentKey((string) $candidate->slug);

                return ($targetNameKey !== '' && $candidateNameKey === $targetNameKey)
                    || ($targetSlugKey !== '' && $candidateSlugKey === $targetSlugKey)
                    || ($targetNameKey !== '' && $candidateSlugKey === $targetNameKey)
                    || ($targetSlugKey !== '' && $candidateNameKey === $targetSlugKey);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $department->id, $ids, true)) {
            $ids[] = (int) $department->id;
        }

        return $ids;
    }

    private function normalizeDepartmentKey(string $value): string
    {
        $normalized = strtolower($value);
        $normalized = preg_replace('/[-_]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bproduction\b/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }
}
