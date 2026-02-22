<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\ApprovalAuditRequest;
use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Services\ProductionAuditService;
use Illuminate\Support\Facades\Cache;
use function is_super_admin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app.branch-dashboard')]
class Recipes extends BaseComponent
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public ?int $quantity = 10;

    public ?string $search = null;

    public ?int $filterDepartment = null;

    public ?string $filterStatus = null;

    // Modal states
    // public bool $showModal = false;

    public ?int $recipeId = null;

    // public bool $isEditing = false;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    // Audit modal for delete requests
    public bool $showAuditModal = false;

    public ?string $auditAction = null;

    public string $auditReason = '';

    public ?int $pendingRecipeId = null;

    private int $cacheTtlMinutes = 5;

    public function mount($deptSlug = null)
    {
        $requestedDeptSlug = $deptSlug
            ?? $this->dept_slug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        // Super Admin can access all departments, so dept slug is optional.
        if (!is_super_admin() && !$requestedDeptSlug) {
            abort(403, 'Department access required');
        }

        if ($requestedDeptSlug) {
            $this->dept_slug = $requestedDeptSlug;
            $this->setDepartmentFromSlug($requestedDeptSlug);
            $this->filterDepartment = $this->department?->id;
        }
    }

    public function updatedDeptSlug($newDeptSlug)
    {
        if (! $newDeptSlug) {
            if (is_super_admin()) {
                $this->department = null;
                $this->filterDepartment = null;
                $this->resetPage();
            }

            return;
        }

        $this->setDepartmentFromSlug($newDeptSlug);
        $this->filterDepartment = $this->department?->id;
        $this->resetPage();
    }

    private function setDepartmentFromSlug($deptSlug)
    {
        $branchId = $this->getBranchId();

        $this->department = Department::query()
            ->where('slug', $deptSlug)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->first();

        if (!$this->department) {
            abort(404, 'Department not found');
        }
    }

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
    ];

    protected function getModelClass(): string
    {
        return Recipe::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->select('id')->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    protected function getFilteredQuery()
    {
        $branchId = $this->getBranchId();

        return Recipe::query()
            ->select([
                'id',
                'product_name',
                'sku',
                'cost_per_unit',
                'branch_id',
                'created_at',
            ])
            ->where('branch_id', $branchId)
            ->when($this->department, function ($query) {
                $query->where('department_id', $this->department->id);
            })
            ->when($this->search, function ($query) {
                $searchTerm = trim($this->search);
                if (!empty($searchTerm)) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('product_name', 'like', '%'.$searchTerm.'%')
                            ->orWhere('sku', 'like', '%'.$searchTerm.'%')
                            ->orWhereHas('productType', function ($subQuery) use ($searchTerm) {
                                $subQuery->where('name', 'like', '%'.$searchTerm.'%');
                            });
                    });
                }
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->orderBy('created_at', 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterDepartment = null;
        $this->filterStatus = null;
        $this->resetPage();
    }

    // Auto-generate SKU based on product name
    public function updatedProductName()
    {
        if (! $this->isEditing && ! empty($this->product_name)) {
            $this->generateSku();
        }
    }

    public function render()
    {
        $rows = Cache::remember($this->getCacheKey(), now()->addMinutes($this->cacheTtlMinutes), function () {
            return $this->getFilteredQuery()->paginate($this->quantity ?? 10);
        });

        $dropdownCacheKey = 'recipes_dropdowns_' . ($this->dept_slug ?? 'all');
        [
            'products' => $products,
            'items' => $items,
        ] = Cache::remember($dropdownCacheKey, now()->addHour(), function () {
            $products = collect();
            if ($this->department) {
                $products = Product::active()
                    ->whereHas('productType', function ($q) {
                        $q->where('department_id', $this->department->id);
                    })
                    ->orderBy('name')
                    ->select('id', 'name')
                    ->get();
            }

            $items = Item::orderBy('name')
                ->select('id', 'name')
                ->get();

            return compact('products', 'items');
        });

        return view('livewire.branch-dashboard.production.recipes', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'sku', 'label' => 'SKU'],
                ['index' => 'product_name', 'label' => 'Product Name'],
                ['index' => 'cost_per_unit', 'label' => 'Cost/Unit'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'products' => $products,
            'items' => $items,
            'department' => $this->department,
            'dept_slug' => $this->dept_slug,
        ]);
    }

    // public function openEditModal($id)
    // {
    //     $recipe = Recipe::with('ingredients')->findOrFail($id);

    //     $this->recipeId = $recipe->id;
    //     $this->product_id = $recipe->product_id ?? null;
    //     $this->product_name = $recipe->product_name;
    //     $this->sku = $recipe->sku;
    //     $this->department_id = $recipe->department_id;
    //     $this->category_id = $recipe->category_id;
    //     $this->product_type = $recipe->product_type ?? '';
    //     $this->cost_per_unit = $recipe->cost_per_unit;
    //     $this->uom = $recipe->uom;
    //     $this->yield_quantity = $recipe->yield_quantity;
    //     $this->preparation_time = $recipe->preparation_time;
    //     $this->instructions = json_decode($recipe->instructions, true) ?? [];
    //     $this->status = $recipe->status;

    //     // Load existing ingredients
    //     $this->ingredients = $recipe->ingredients->map(function ($ingredient) {
    //         return [
    //             'id' => $ingredient->id,
    //             'item_id' => $ingredient->item_id,
    //             'quantity' => $ingredient->quantity,
    //             'uom' => $ingredient->uom,
    //             'notes' => $ingredient->notes ?? '',
    //         ];
    //     })->toArray();

    //     $this->isEditing = true;
    //     $this->showModal = true;
    // }

    public function delete($id): void
    {
        $this->recipeId = $id;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete this recipe?')
            ->confirm('Confirm', 'confirmedDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedDelete(string $message): void
    {
        if (! $this->recipeId) {
            return;
        }

        // Super admin bypass - delete directly with audit
        if (is_super_admin()) {
            try {
                $recipe = Recipe::findOrFail($this->recipeId);
                $actor = current_actor();
                ProductionAuditService::logRecipeDeleted(
                    $actor,
                    $recipe,
                    'Super admin direct delete'
                );
                $recipe->delete();
                $this->dialog()->success('Success', 'Recipe deleted successfully!')->send();
                $this->recipeId = null;
                $this->clearCache();
            } catch (\Exception $e) {
                $this->dialog()->error('Error', 'Failed to delete recipe: '.$e->getMessage())->send();
            }

            return;
        }

        // Non-super-admin: show audit modal
        $this->auditAction = 'delete_recipe';
        $this->pendingRecipeId = $this->recipeId;
        $this->showAuditModal = true;
    }

    public function cancelledDelete(string $message): void
    {
        $this->recipeId = null;
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function bulkDeleteItems(): void
    {
        // Super admin - proceed directly
        if (is_super_admin()) {
            $this->dialog()
                ->question('Warning!', 'Are you sure you want to delete '.count($this->selectedIds).' recipe(s)?')
                ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
                ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
                ->send();

            return;
        }

        // Non-super-admin: require audit reason for bulk deletes
        // Store IDs for later use
        $this->auditAction = 'bulk_delete_recipe';
        $this->showAuditModal = true;
    }

    public function confirmedBulkDelete(string $message): void
    {
        // This is for super admin bulk delete
        if (! is_super_admin()) {
            return;
        }

        $actor = current_actor();

        foreach ($this->selectedIds as $id) {
            $recipe = Recipe::find($id);
            if ($recipe) {
                // Log recipe deletion to audit trail using actor pattern
                ProductionAuditService::logRecipeDeleted(
                    $actor,
                    $recipe,
                    'Bulk deletion - Super admin confirmed'
                );

                $recipe->ingredients()->delete();
                $recipe->delete();
            }
        }
        $this->dialog()->success('Success', 'Recipes deleted successfully!')->send();
        $this->selectedIds = [];
        $this->clearCache();
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function submitAuditRequest()
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ]);

        $requester = current_actor();

        if ($this->auditAction === 'delete_recipe' && $this->pendingRecipeId) {
            ApprovalAuditRequest::create([
                'requester_id' => $requester->id,
                'requester_type' => get_class($requester),
                'action' => 'recipe:delete_recipe',
                'description' => $this->auditReason,
                'payload' => ['id' => $this->pendingRecipeId],
                'status' => 'pending',
                'branch_id' => $this->getBranchId(),
            ]);
        } elseif ($this->auditAction === 'bulk_delete_recipe') {
            // For bulk delete, store all IDs in payload
            ApprovalAuditRequest::create([
                'requester_id' => $requester->id,
                'requester_type' => get_class($requester),
                'action' => 'recipe:bulk_delete_recipe',
                'description' => $this->auditReason,
                'payload' => ['ids' => $this->selectedIds],
                'status' => 'pending',
                'branch_id' => $this->getBranchId(),
            ]);
            $this->selectedIds = [];
        }

        $this->closeAuditModal();
        $this->toast()->success('Approval request submitted for review')->send();
        $this->dispatch('refresh');
    }

    private function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;
        $this->pendingRecipeId = null;
    }

    private function cacheVersionKey(): string
    {
        return 'recipes_cache_version_' . auth()->id();
    }

    private function getCacheVersion(): int
    {
        return Cache::get($this->cacheVersionKey(), 1);
    }

    private function incrementCacheVersion(): void
    {
        Cache::put($this->cacheVersionKey(), $this->getCacheVersion() + 1, now()->addDay());
    }

    private function getCacheKey(): string
    {
        $key = implode('_', [
            'recipes_list',
            auth()->id(),
            $this->getCacheVersion(),
            $this->getBranchId(),
            $this->dept_slug,
            $this->quantity,
            $this->search,
            $this->filterStatus,
            $this->currentPage(),
        ]);

        return md5($key);
    }

    private function clearCache(): void
    {
        $this->incrementCacheVersion();
        Cache::forget('recipes_dropdowns_' . ($this->dept_slug ?? 'all'));
    }

    private function currentPage(): int
    {
        return (int) $this->getPage();
    }
}
