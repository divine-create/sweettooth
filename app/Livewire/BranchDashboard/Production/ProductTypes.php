<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app.branch-dashboard')]
class ProductTypes extends BaseComponent
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

    public ?string $filterStatus = null;

    public ?int $filterDepartment = null;

    // Modal states
    public bool $showModal = false;

    public ?int $productTypeId = null;

    public bool $isEditing = false;

    // Form fields
    public ?int $department_id = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $status = 'active';

    public User|Employee|null $employees_department = null;

    public ?Department $department = null;

    private int $cacheTtlMinutes = 5;

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
    ];

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public function mount($deptSlug = null)
    {
        $requestedDeptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        if ($requestedDeptSlug) {
            $this->department = Department::select('id', 'name', 'slug')
                ->where('slug', $requestedDeptSlug)
                ->first();

            if ($this->department) {
                $this->dept_slug = $this->department->slug;
                $this->filterDepartment = $this->department->id;
            }
        }

        $this->employees_department = Employee::where('id', auth()->id())->first();
    }

    protected function getModelClass(): string
    {
        return ProductType::class;
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
        $query = ProductType::query()
            ->select(['id', 'department_id', 'name', 'code', 'description', 'status', 'created_at'])
            ->with(['department:id,name,slug,category_id', 'department.category:id,name']) // Select only needed columns
            ->withCount('products') // Use withCount instead of with and counting separately
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            });

        if ($this->department) {
            $query->where('department_id', $this->department->id);
        }

        return $query->orderBy('id', 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterStatus = null;
        $this->filterDepartment = null;
        $this->resetPage();
    }

    public function render()
    {
        if (!$this->department) {
            return view('livewire.branch-dashboard.production.product-types', [
                'headers' => [
                    ['index' => 'sequential_number', 'label' => '#'],
                    ['index' => 'code', 'label' => 'Code'],
                    ['index' => 'name', 'label' => 'Name'],
                    ['index' => 'department', 'label' => 'Department'],
                    ['index' => 'description', 'label' => 'Description'],
                    ['index' => 'products_count', 'label' => 'Products'],
                    ['index' => 'status', 'label' => 'Status'],
                    ['index' => 'action', 'label' => 'Actions', 'display' => true],
                ],
                'rows' => null,
                'department' => null,
                'productionDepartments' => Department::whereHas('category', function ($q) {
                    $q->where('name', 'Production');
                })
                ->orderBy('name')
                ->select('id', 'name', 'slug')
                ->get(),
                'no_department_selected' => true,
            ]);
        }

        $rows = Cache::remember($this->getCacheKey(), now()->addMinutes($this->cacheTtlMinutes), function () {
            return $this->getFilteredQuery()->paginate((int) ($this->quantity ?? 10));
        });

        // Add sequential numbers to the rows
        $currentPage = $rows->currentPage();
        $perPage = $rows->perPage();
        $startIndex = ($currentPage - 1) * $perPage + 1;

        $rows->getCollection()->each(function ($item, $index) use ($startIndex) {
            $item->setAttribute('sequential_number', $startIndex + $index);
        });

        // Get all production departments for the modal
        $productionDepartments = [];
        if (is_super_admin()) {
            $productionDepartments = Department::whereHas('category', function ($q) {
                $q->where('name', 'Production');
            })
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
        }

        return view('livewire.branch-dashboard.production.product-types', [
            'headers' => [
                ['index' => 'sequential_number', 'label' => '#'],
                ['index' => 'code', 'label' => 'Code'],
                ['index' => 'name', 'label' => 'Name'],
                ['index' => 'department', 'label' => 'Department'],
                ['index' => 'description', 'label' => 'Description'],
                ['index' => 'products_count', 'label' => 'Products'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'department' => $this->department,
            'productionDepartments' => $productionDepartments,
            'no_department_selected' => false,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetFields();
        // Auto-set department_id based on dept_slug
        if ($this->department) {
            $this->department_id = $this->department->id;
        }
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $productType = ProductType::findOrFail($id);

        $this->productTypeId = $productType->id;
        $this->department_id = $productType->department_id;
        $this->name = $productType->name;
        $this->code = $productType->code;
        $this->description = $productType->description ?? '';
        $this->status = $productType->status;
        $this->sort_order = $productType->sort_order;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ];

        if ($this->isEditing) {
            $rules['code'] = 'required|string|max:50|unique:product_types,code,'.$this->productTypeId;
        } else {
            $rules['code'] = 'required|string|max:50|unique:product_types,code';
        }

        $this->validate($rules);

        $data = [
            'department_id' => $this->department_id,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'status' => $this->status,
        ];

        if ($this->isEditing && $this->productTypeId) {
            $productType = ProductType::findOrFail($this->productTypeId);
            $productType->update($data);
            $message = 'Product type updated successfully!';
        } else {
            ProductType::create($data);
            $message = 'Product type created successfully!';
        }

        $this->toast()->success($message)->send();
        $this->clearCache();
        $this->closeModal();
    }

    public function delete($id): void
    {
        $this->productTypeId = $id;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete this product type?')
            ->confirm('Confirm', 'confirmedDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedDelete(string $message): void
    {
        if ($this->productTypeId) {
            $productType = ProductType::withTrashed()->find($this->productTypeId);

            if ($productType) {
                // Check if it has products
                if ($productType->products()->count() > 0) {
                    $this->dialog()->error('Error', 'Cannot delete product type with associated products!')->send();

                    return;
                }

                $productType->delete();
                // Log the delete for super admin
                if (is_super_admin()) {
                    \App\Services\AuditService::log(
                        auth()->user(),
                        'delete',
                        $productType,
                        'Super admin direct delete of product type'
                    );
                }
            }
            $this->dialog()->success('Success', 'Product type deleted successfully!')->send();
            $this->productTypeId = null;

            // Clear selection after successful deletion
            $this->resetBulkSelection();
            $this->clearCache();
        }
    }

    public function cancelledDelete(string $message): void
    {
        $this->productTypeId = null;
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function bulkDeleteItems(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete '.count($this->selectedIds).' product type(s)?')
            ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedBulkDelete(string $message): void
    {
        $productTypes = ProductType::withTrashed()->whereIn('id', $this->selectedIds)
            ->whereDoesntHave('products')
            ->get();
        ProductType::whereIn('id', $this->selectedIds)
            ->whereDoesntHave('products')
            ->delete();

        // Log for super admin
        if (is_super_admin()) {
            foreach ($productTypes as $productType) {
                \App\Services\AuditService::log(
                    auth()->user(),
                    'delete',
                    $productType,
                    'Super admin bulk delete of product type'
                );
            }
        }

        $this->dialog()->success('Success', 'Product types deleted successfully!')->send();

        // Clear selection after successful deletion
        $this->resetBulkSelection();
        $this->clearCache();
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
        $this->resetValidation();
    }

    public function resetFields()
    {
        $this->productTypeId = null;
        $this->department_id = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->status = 'active';
        $this->isEditing = false;
    }

    private function cacheVersionKey(): string
    {
        return 'product_types_cache_version_' . auth()->id();
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
            'product_types',
            auth()->id(),
            $this->getCacheVersion(),
            $this->dept_slug,
            $this->quantity,
            $this->search,
            $this->filterStatus,
            $this->filterDepartment,
            $this->currentPage(),
        ]);

        return md5($key);
    }

    private function currentPage(): int
    {
        return (int) $this->getPage();
    }

    private function clearCache(): void
    {
        $this->incrementCacheVersion();
    }
}
