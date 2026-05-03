<?php

namespace App\Livewire\BranchDashboard\Roles;

use App\Livewire\BaseComponent;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use TallStackUi\Traits\Interactions;
use App\Services\RolePermissionService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Cache;

class Index extends BaseComponent
{
    use Interactions, WithPagination, Exportable;
    
    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $advancedSearch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Modal states
    public bool $showPermissionsModal = false;
    public bool $showRoleModal = false;
    public bool $showCreatePermissionModal = false;
    public bool $showStandalonePermissionModal = false;
    public ?int $selectedRoleId = null;
    public array $rolePermissions = [];

    // Role form
    public string $roleName = '';
    public string $roleGuard = 'web';
    public array $selectedPermissions = [];
    public bool $isEditing = false;

    // Permission form
    public string $permissionName = '';
    public string $permissionGuard = 'web';
    public ?string $permissionSearch = null;

    // Standalone permission form
    public string $standalonePermissionName = '';
    public string $standalonePermissionGuard = 'employee';

    private int $cacheTtlMinutes = 5;

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
    ];

    /**
     * Authorization check - Only super admins can access role management
     */
    public function mount()
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can manage roles');
        }
    }

    protected function getModelClass(): string
    {
        return Role::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return Role::query()
            ->withCount('users')
            ->select(['roles.id', 'roles.name', 'roles.guard_name', 'roles.created_at'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->advancedSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->advancedSearch . '%');
                });
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            });
    }

    public function applyFilters()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->advancedSearch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
        $this->clearCache();
    }

    // Export methods


    public function exportCSV()
    {
        $roles = $this->getFilteredQuery()->get();

        return $this->export(
            'roles_' . date('Y-m-d'),
            $roles,
            'exports.roles'
        );
    }

    // Modal methods
    public function viewPermissions($roleId)
    {
        $cacheKey = "role_permissions_{$roleId}_" . auth()->id();
        $role = Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($roleId) {
            return Role::with('permissions:id,name')->findOrFail($roleId);
        });

        $this->selectedRoleId = $roleId;
        $this->rolePermissions = $role->permissions->toArray();
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal()
    {
        $this->showPermissionsModal = false;
        $this->selectedRoleId = null;
        $this->rolePermissions = [];
    }

    public function openRoleModal()
    {
        $this->isEditing = false;
        $this->resetRoleForm();
        $this->showRoleModal = true;
    }

    public function editRole($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->isEditing = true;
        $this->selectedRoleId = $roleId;
        $this->roleName = $role->name;
        $this->roleGuard = 'web';
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->showRoleModal = true;
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->resetRoleForm();
    }

    public function resetRoleForm()
    {
        $this->roleName = '';
        $this->roleGuard = 'web';
        $this->selectedPermissions = [];
        $this->permissionSearch = null;
        $this->selectedRoleId = null;
        $this->isEditing = false;
    }

    public function saveRole()
    {
        $rules = [
            'roleName' => [
                'required',
                'string',
                'max:255',
            ],
        ];

        if (!$this->isEditing) {
            $rules['roleName'][] = Rule::unique('roles', 'name')->where('guard_name', 'web');
        }

        $this->validate($rules, [
            'roleName.unique' => 'The role name already exists.',
        ]);

        if ($this->isEditing && $this->selectedRoleId) {
            try {
                $role = Role::findOrFail($this->selectedRoleId);
                // Prevent renaming: always keep current name
                $this->roleName = $role->name;
                $message = 'Permissions updated successfully!';
            } catch (\Exception $e) {
                $this->toast()->error($e->getMessage())->send();
                return;
            }
        } else {
            $role = Role::create([
                'name' => $this->roleName,
                'guard_name' => 'web',
            ]);
            $message = 'Role created successfully!';
        }

        // Sync permissions - only permissions with matching guard
        $permissions = Permission::whereIn('id', $this->selectedPermissions)
            ->where('guard_name', 'web')
            ->get();
        
        if ($this->isEditing) {
            $role = Role::find($this->selectedRoleId);
        }
        $role->syncPermissions($permissions);

        $this->toast()->success($message)->send();
        $this->clearCache();
        $this->closeRoleModal();
    }

    // Delete methods
    public function deleteRole($roleId)
    {
        $this->selectedRoleId = $roleId;

        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete this role?')
            ->confirm('Confirm', 'confirmedDeleteRole', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledDeleteRole', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedDeleteRole(string $message): void
    {
        if ($this->selectedRoleId) {
            try {
                RolePermissionService::deleteRole($this->selectedRoleId);
                $this->dialog()->success('Success', 'Role deleted successfully!')->send();
                $this->clearCache();
            } catch (\Exception $e) {
                $this->dialog()->error('Error', $e->getMessage())->send();
            }
            $this->selectedRoleId = null;
        }
    }

    public function cancelledDeleteRole(string $message): void
    {
        $this->selectedRoleId = null;
        $this->dialog()->error('Cancelled', $message)->send();
    }

    // Bulk Delete
    public function bulkDeleteRoles(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' role(s)?')
            ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedBulkDelete(string $message): void
    {
        try {
            foreach ($this->selectedIds as $roleId) {
                RolePermissionService::deleteRole($roleId);
            }
            $this->dialog()->success('Success', count($this->selectedIds) . ' role(s) deleted successfully!')->send();
            $this->clearCache();
        } catch (\Exception $e) {
            $this->dialog()->error('Error', $e->getMessage())->send();
        }
        $this->selectedIds = [];
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function updatedRoleGuard()
    {
        // Clear selected permissions and search when guard changes
        $this->selectedPermissions = [];
        $this->permissionSearch = null;
    }

    public function toggleAllPermissions()
    {
        $allPermissionIds = Permission::where('guard_name', $this->roleGuard)->pluck('id')->toArray();

        if (count($this->selectedPermissions) === count($allPermissionIds)) {
            // Deselect all
            $this->selectedPermissions = [];
        } else {
            // Select all
            $this->selectedPermissions = $allPermissionIds;
        }
    }

    // Permission creation methods
    public function openCreatePermissionModal()
    {
        $this->permissionName = '';
        $this->permissionGuard = $this->roleGuard; // Match the role's guard
        $this->showCreatePermissionModal = true;
    }

    public function closeCreatePermissionModal()
    {
        $this->showCreatePermissionModal = false;
        $this->permissionName = '';
        $this->permissionGuard = 'web';
    }

    public function createPermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255|unique:permissions,name',
            'permissionGuard' => 'required|string',
        ]);

        $permission = Permission::create([
            'name' => $this->permissionName,
            'guard_name' => $this->permissionGuard,
        ]);

        // Automatically add to selected permissions
        $this->selectedPermissions[] = $permission->id;

        $this->toast()->success('Permission created and added to role!')->send();
        $this->closeCreatePermissionModal();
        $this->clearCache();
    }

    // Standalone permission methods
    public function openStandalonePermissionModal()
    {
        $this->standalonePermissionName = '';
        $this->standalonePermissionGuard = 'employee';
        $this->showStandalonePermissionModal = true;
    }

    public function closeStandalonePermissionModal()
    {
        $this->showStandalonePermissionModal = false;
        $this->standalonePermissionName = '';
        $this->standalonePermissionGuard = 'employee';
    }

    public function createStandalonePermission()
    {
        $this->validate([
            'standalonePermissionName' => 'required|string|max:255|unique:permissions,name',
            'standalonePermissionGuard' => 'required|string',
        ]);

        Permission::create([
            'name' => $this->standalonePermissionName,
            'guard_name' => $this->standalonePermissionGuard,
        ]);

        $this->toast()->success('Permission created successfully!')->send();
        $this->closeStandalonePermissionModal();
        $this->clearCache();
    }

    public function render()
    {
        $cacheKey = $this->getCacheKey();
        $rows = Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () {
            return $this->getFilteredQuery()->paginate((int) ($this->quantity ?? 10));
        });

        // Add sequential numbers to the rows
        $currentPage = $rows->currentPage();
        $perPage = $rows->perPage();
        $startIndex = ($currentPage - 1) * $perPage + 1;

        $rows->getCollection()->each(function ($item, $index) use ($startIndex) {
            $item->setAttribute('sequential_number', $startIndex + $index);
        });

        // Only load permissions when role modal is open to avoid loading on every render
        $allPermissions = collect();
        if ($this->showRoleModal) {
            $permissionsCacheKey = 'all_permissions_web_' . auth()->id() . '_' . md5($this->permissionSearch ?? '');
            $allPermissions = Cache::remember($permissionsCacheKey, now()->addMinutes(30), function () {
                $query = Permission::where('guard_name', 'web')
                    ->select('id', 'name', 'guard_name');

                if ($this->permissionSearch) {
                    $query->where('name', 'like', '%' . $this->permissionSearch . '%');
                }

                return $query->get();
            });
        }

        return view('livewire.branch-dashboard.roles.index', [
            'headers' => [
                ['index' => 'sequential_number', 'label' => '#'],
                ['index' => 'name', 'label' => 'Role Name'],
                ['index' => 'created_at', 'label' => 'Created At'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'allPermissions' => $allPermissions,
        ])->layout('components.layouts.app.branch-dashboard');
    }

    private function getCacheVersion(): int
    {
        return Cache::get('roles_cache_version_' . auth()->id(), 1);
    }

    private function incrementCacheVersion(): void
    {
        $versionKey = 'roles_cache_version_' . auth()->id();
        Cache::put($versionKey, $this->getCacheVersion() + 1, now()->addDay());
    }

    private function getCacheKey(): string
    {
        $currentPage = $this->getPage();

        $key = implode('_', [
            'roles_list',
            auth()->id(),
            $this->getCacheVersion(),
            $this->quantity,
            $this->search,
            $this->advancedSearch,
            $this->dateFrom,
            $this->dateTo,
            $currentPage,
        ]);

        return md5($key);
    }

    private function clearCache(): void
    {
        $this->incrementCacheVersion();
        Cache::flush();
    }
}
