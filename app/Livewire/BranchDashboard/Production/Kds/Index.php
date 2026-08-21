<?php

namespace App\Livewire\BranchDashboard\Production\Kds;

use App\Enums\SalesProductionRequestStatus;
use App\Models\Department;
use App\Models\SalesProductionRequestItem;
use App\Services\SalesProductionRequestWorkflowService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    /** @var array<int> */
    public array $departmentIds = [];

    public int $lastSeenItemId = 0;

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

        $latest = $this->baseQuery()->first();
        if ($latest) {
            $this->lastSeenItemId = $latest->id;
        }
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public function approveItem(int $itemId): void
    {
        try {
            DB::transaction(function () use ($itemId) {
                $item = $this->resolveItem($itemId);
                /** @var SalesProductionRequestWorkflowService $workflow */
                $workflow = app(SalesProductionRequestWorkflowService::class);
                $workflow->transitionItem($item, SalesProductionRequestStatus::APPROVED_BY_PRODUCTION);
            });
            $this->toast()->success('Request item approved.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Approval failed: '.$e->getMessage())->send();
        }
    }

    private function resolveItem(int $itemId): SalesProductionRequestItem
    {
        $query = SalesProductionRequestItem::query()
            ->with(['request'])
            ->whereKey($itemId);

        if (! empty($this->departmentIds)) {
            $query->whereIn('production_department_id', $this->departmentIds);
        } elseif (! is_super_admin() && $this->department) {
            $query->where('production_department_id', $this->department->id);
        }

        return $query->firstOrFail();
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

        // Show items needing attention
        $query->whereIn('status', [
            SalesProductionRequestStatus::PENDING->value,
        ]);

        return $query->latest('created_at');
    }

    public function render()
    {
        $items = $this->baseQuery()->get();

        $currentMaxId = $items->max('id') ?? 0;

        if ($currentMaxId > $this->lastSeenItemId) {
            $this->dispatch('play-kds-notification');
            $this->lastSeenItemId = $currentMaxId;
        }

        return view('livewire.branch-dashboard.production.kds.index', [
            'items' => $items,
        ]);
    }

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
