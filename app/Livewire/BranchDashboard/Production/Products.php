<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\ApprovalAuditRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use function is_super_admin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app.branch-dashboard')]
class Products extends BaseComponent
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

    public ?int $filterProductType = null;

    public ?int $filterDepartment = null;

    public ?string $filterStatus = null;

    // Modal states
    public bool $showModal = false;

    public ?string $productId = null;

    public bool $isEditing = false;

    // Form fields
    public string $name = '';

    public string $sku = '';

    public ?int $product_type_id = null;

    public ?int $sales_department_id = null;

    public ?int $category_id = null;

    public string $description = '';

    public $price = 0;

    public $cost = null;

    public int $shelf_life_days = 0;

    public ?int $uom_id = null;

    public ?int $sales_uom_id = null;

    public bool $is_active = true;

    public bool $is_available = true;

    public string $image_url = '';

    public array $allergens = [];

    public array $tags = [];

    public User|Employee|null $employee = null;

    private int $cacheTtlMinutes = 5;

    // Audit modal for approval requests
    public bool $showAuditModal = false;

    public ?string $auditAction = null;          // create|edit|delete

    public string $auditReason = '';              // User-provided reason

    public $pendingItemId = null;            // Product ID pending action

    public array $pendingItemData = [];           // Data to save on approval

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

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

        $this->employee = Employee::where('id', auth()->id())->first();
    }

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
    ];

    protected function getModelClass(): string
    {
        return Product::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->select('products.id')->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    protected function getFilteredQuery()
    {
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                'products.branch_id',
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('products.name', 'like', '%'.$this->search.'%')
                        ->orWhere('products.sku', 'like', '%'.$this->search.'%')
                        ->orWhere('products.description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterProductType, function ($query) {
                $query->where('product_type_id', $this->filterProductType);
            })
            ->when($this->filterDepartment, function ($query) {
                $query->whereHas('productType', function ($q) {
                    $q->where('department_id', $this->filterDepartment);
                });
            })
            ->when($this->department, function ($query) {
                $query->whereHas('productType', function ($q) {
                    $q->where('department_id', $this->department->id);
                });
            })
            ->when($this->filterStatus !== null, function ($query) {
                switch ($this->filterStatus) {
                    case 'active':
                        $query->where('is_active', true);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'available':
                        $query->where('is_available', true);
                        break;
                    case 'unavailable':
                        $query->where('is_available', false);
                        break;
                }
            })
            ->where(function ($query) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $this->getBranchId());
            });

        return $query->orderBy('created_at', 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterProductType()
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
        $this->filterProductType = null;
        $this->filterDepartment = null;
        $this->filterStatus = null;
        $this->resetPage();
    }

    // Auto-generate SKU
    public function updatedProductTypeId()
    {
        $this->generateSku();
    }

    public function updatedName()
    {
        $this->generateSku();
    }

    public function updatedSalesDepartmentId($value)
    {
        if ($value === '' || $value === null) {
            $this->sales_department_id = null;
        }
    }

    private function generateSku()
    {
        if (! $this->isEditing && ! empty($this->product_type_id) && ! empty($this->name)) {
            $productType = ProductType::find($this->product_type_id);
            if ($productType) {
                $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->name), 0, 3));
                $randomCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $this->sku = $productType->code.'-'.$nameCode.'-'.$randomCode;
            }
        }
    }

    public function render()
    {
        if (!$this->department) {
            return view('livewire.branch-dashboard.production.products', [
                'headers' => [
                    ['index' => 'id', 'label' => '#'],
                    ['index' => 'sku', 'label' => 'SKU'],
                    ['index' => 'name', 'label' => 'Product Name'],
                    ['index' => 'product_type', 'label' => 'Type'],
                    ['index' => 'department', 'label' => 'Department'],
                    ['index' => 'sales_department', 'label' => 'Sales Dept'],
                    ['index' => 'price', 'label' => 'Price'],
                    ['index' => 'shelf_life', 'label' => 'Shelf Life'],
                    ['index' => 'uom', 'label' => 'UOM'],
                    ['index' => 'recipe_status', 'label' => 'Recipe'],
                    ['index' => 'status', 'label' => 'Status'],
                    ['index' => 'action', 'label' => 'Actions', 'display' => true],
                ],
                'rows' => null,
                'productTypes' => [],
                'departments' => Department::whereHas('category', function ($q) {
                    $q->where('name', 'Production');
                })
                ->orderBy('name')
                ->select('id', 'name', 'slug')
                ->get(),
                'unitOfMeasures' => [],
                'salesDepartments' => [],
                'employees_department' => null,
                'no_department_selected' => true,
            ]);
        }

        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 10);

        $dropdownCacheKey = 'products_dropdowns_' . ($this->dept_slug ?? 'all');
        [
            'productTypes' => $productTypes,
            'departments' => $departments,
            'salesDepartments' => $salesDepartments,
        ] = Cache::remember($dropdownCacheKey, now()->addHour(), function () {
            $productTypes = ProductType::with('department:id,name')
                ->where('department_id', $this->department->id)
                ->active()
                ->ordered()
                ->select('id', 'name', 'code', 'department_id')
                ->get();

            $departments = Department::whereHas('category', function ($q) {
                $q->where('name', 'Production');
            })
                ->orderBy('name')
                ->select('id', 'name', 'slug')
                ->get();

            $salesDepartments = $this->salesDepartmentsQuery()
                ->orderBy('name')
                ->select('id', 'name', 'branch_id')
                ->get();

            return compact('productTypes', 'departments', 'salesDepartments');
        });
        $unitOfMeasures = Cache::remember('unit_of_measures_all', now()->addDay(), function () {
            return \App\Models\UnitOfMeasure::orderBy('name')
                ->select('id', 'name', 'symbol')
                ->get();
        });

        $employees_department = $this->department;

        return view('livewire.branch-dashboard.production.products', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'sku', 'label' => 'SKU'],
                ['index' => 'name', 'label' => 'Product Name'],
                ['index' => 'price', 'label' => 'Price'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'productTypes' => $productTypes,
            'departments' => $departments,
            'unitOfMeasures' => $unitOfMeasures,
            'salesDepartments' => $salesDepartments,
            'employees_department' => $employees_department,
            'no_department_selected' => false,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetFields();
        $salesDepartmentIds = $this->salesDepartmentsQuery()
            ->orderBy('name')
            ->pluck('id');

        if ($salesDepartmentIds->count() === 1) {
            $this->sales_department_id = (int) $salesDepartmentIds->first();
        }

        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $product = Product::findOrFail($id);

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->product_type_id = $product->product_type_id;
        $this->sales_department_id = $product->sales_department_id;
        $this->category_id = $product->category_id;
        $this->description = $product->description ?? '';
        $this->price = $product->price;
        $this->cost = $product->cost;
        $this->shelf_life_days = $product->shelf_life_days;
        $this->uom_id = $product->uom_id;
        $this->sales_uom_id = $product->sales_uom_id;
        $this->is_active = $product->is_active;
        $this->is_available = $product->is_available;
        $this->image_url = $product->image_url ?? '';
        $this->allergens = $product->allergens ?? [];
        $this->tags = $product->tags ?? [];

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'product_type_id' => 'required|exists:product_types,id',
            'sales_department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'shelf_life_days' => 'required|integer|min:0',
            'uom_id' => 'required|exists:units_of_measure,id',
            'sales_uom_id' => 'nullable|exists:units_of_measure,id',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'image_url' => 'nullable|string',
        ];

        if ($this->isEditing) {
            $rules['sku'] = 'required|string|max:255|unique:products,sku,'.$this->productId;
        } else {
            $rules['sku'] = 'required|string|max:255|unique:products,sku';
        }

        $this->validate($rules);

        if ($this->sales_department_id && ! $this->isValidSalesDepartment($this->sales_department_id)) {
            $this->addError('sales_department_id', 'Please select a valid sales department for this branch.');

            return;
        }

        $productData = [
            'id' => $this->isEditing ? $this->productId : null,
            'name' => $this->name,
            'sku' => strtoupper($this->sku),
            'product_type_id' => $this->product_type_id,
            'sales_department_id' => $this->sales_department_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'price' => $this->price,
            'cost' => $this->cost,
            'shelf_life_days' => $this->shelf_life_days,
            'uom_id' => $this->uom_id,
            'sales_uom_id' => $this->sales_uom_id,
            'is_active' => $this->is_active,
            'is_available' => $this->is_available,
            'image_url' => $this->image_url,
            'allergens' => $this->allergens,
            'tags' => $this->tags,
        ];

        // Super admin bypass - save directly without audit
        if (is_super_admin()) {
            $this->saveProduct($productData);

            return;
        }

        // Non-super-admin: show audit modal
        $this->auditAction = $this->isEditing ? 'edit' : 'create';
        $this->pendingItemId = $this->isEditing ? $this->productId : null;
        $this->pendingItemData = $productData;
        $this->showAuditModal = true;
    }

    private function saveProduct(array $data)
    {
        try {
            if ($data['id']) {
                $product = Product::findOrFail($data['id']);
                unset($data['id']);
                $product->update($data);
                $this->toast()->success('Product updated successfully')->send();
            } else {
                unset($data['id']);
                Product::create($data);
                $this->toast()->success('Product created successfully')->send();
            }

            $this->closeModal();
            $this->clearCache();
            // Refresh the component to show updated data
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            $this->toast()->error('Failed to save product: '.$e->getMessage())->send();
        }
    }

    public function delete($id): void
    {
        $this->productId = $id;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete this product?')
            ->confirm('Confirm', 'confirmedDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedDelete(string $message): void
    {
        if (! $this->productId) {
            return;
        }

        // Super admin bypass - delete directly with audit log
        if (is_super_admin()) {
            try {
                $product = Product::withTrashed()->find($this->productId); // Use find() instead of findOrFail to avoid exception
                if ($product) {
                    $product->delete();
                    // Log the delete action
                    \App\Services\AuditService::log(
                        auth()->user(),
                        'delete',
                        $product,
                        'Super admin direct delete of product'
                    );
                }
                $this->dialog()->success('Success', 'Product deleted successfully!')->send();
                $this->productId = null;

                // Clear selection after successful deletion
                $this->resetBulkSelection();
                $this->clearCache();
            } catch (\Exception $e) {
                $this->dialog()->error('Error', 'Failed to delete product: '.$e->getMessage())->send();
            }

            return;
        }

        // Non-super-admin: show audit modal
        $this->auditAction = 'delete';
        $this->pendingItemId = $this->productId;
        $this->pendingItemData = ['id' => $this->productId];
        $this->showAuditModal = true;
    }

    public function cancelledDelete(string $message): void
    {
        $this->productId = null;
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function bulkDeleteItems(): void
    {
        // Super admin - proceed directly
        if (is_super_admin()) {
            $this->dialog()
                ->question('Warning!', 'Are you sure you want to delete '.count($this->selectedIds).' product(s)?')
                ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
                ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
                ->send();

            return;
        }

        // Non-super-admin: require audit reason for bulk deletes
        $this->auditAction = 'bulk_delete';
        $this->showAuditModal = true;
    }

    public function confirmedBulkDelete(string $message): void
    {
        // This is for super admin bulk delete
        if (! is_super_admin()) {
            return;
        }

        $products = Product::withTrashed()->whereIn('id', $this->selectedIds)->get();

        // Perform soft delete for all selected products
        Product::whereIn('id', $this->selectedIds)->delete();

        // Log the bulk delete
        foreach ($products as $product) {
            \App\Services\AuditService::log(
                auth()->user(),
                'delete',
                $product,
                'Super admin bulk delete of product'
            );
        }
        $this->dialog()->success('Success', 'Products deleted successfully!')->send();

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
        $this->productId = null;
        $this->name = '';
        $this->sku = '';
        $this->product_type_id = null;
        $this->sales_department_id = null;
        $this->category_id = null;
        $this->description = '';
        $this->price = 0;
        $this->cost = null;
        $this->shelf_life_days = 0;
        $this->uom_id = null;
        $this->sales_uom_id = null;
        $this->is_active = true;
        $this->is_available = true;
        $this->image_url = '';
        $this->allergens = [];
        $this->tags = [];
        $this->isEditing = false;
    }

    public function submitAuditRequest()
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ]);

        $requester = current_actor();

        if ($this->auditAction === 'bulk_delete') {
            // For bulk delete, store all IDs in payload
            ApprovalAuditRequest::create([
                'requester_id' => $requester->id,
                'requester_type' => get_class($requester),
                'action' => 'product:bulk_delete',
                'description' => $this->auditReason,
                'payload' => ['ids' => $this->selectedIds],
                'status' => 'pending',
                'branch_id' => $this->getBranchId(),
            ]);
            $this->selectedIds = [];
        } else {
            // For create/edit/delete single product
            ApprovalAuditRequest::create([
                'requester_id' => $requester->id,
                'requester_type' => get_class($requester),
                'action' => 'product:'.$this->auditAction,
                'description' => $this->auditReason,
                'payload' => $this->pendingItemData,
                'status' => 'pending',
                'branch_id' => $this->getBranchId(),
            ]);
        }

        $this->closeAuditModal();
        $this->closeModal();
        $this->toast()->success('Approval request submitted for review')->send();
        // Refresh the component to show updated data
        $this->dispatch('refresh');
    }

    public function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;
        $this->pendingItemId = null;
        $this->pendingItemData = [];
    }

    private function cacheVersionKey(): string
    {
        return 'products_cache_version_' . auth()->id();
    }

    private function getCacheVersion(): int
    {
        return Cache::get($this->cacheVersionKey(), 1);
    }

    private function incrementCacheVersion(): void
    {
        Cache::put($this->cacheVersionKey(), $this->getCacheVersion() + 1, now()->addDay());
    }

    private function getListCacheKey(): string
    {
        $key = implode('_', [
            'products_list',
            auth()->id(),
            $this->getCacheVersion(),
            $this->getBranchId(),
            $this->dept_slug,
            $this->quantity,
            $this->search,
            $this->filterProductType,
            $this->filterDepartment,
            $this->filterStatus,
            $this->currentPage(),
        ]);

        return md5($key);
    }

    private function clearCache(): void
    {
        $this->incrementCacheVersion();
        Cache::forget('products_dropdowns_' . ($this->dept_slug ?? 'all'));
    }

    private function currentPage(): int
    {
        return (int) $this->getPage();
    }

    private function salesDepartmentsQuery()
    {
        $branchId = $this->getBranchId();

        return Department::query()
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            });
    }

    private function isValidSalesDepartment(int $departmentId): bool
    {
        return $this->salesDepartmentsQuery()
            ->where('id', $departmentId)
            ->exists();
    }
}
