<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\RolePermission;

use App\Livewire\BaseComponent;
use App\Models\ApprovalAuditRequest;
use App\Services\AuditService;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, WithPagination;

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

    // Standalone permission form
    public string $standalonePermissionName = '';

    public string $standalonePermissionGuard = 'employee';

    // Approval workflow for non-admins
    public bool $showReasonModal = false;

    public string $operationReason = '';

    public ?string $pendingOperation = null; // 'create_role', 'update_role', 'delete_role', 'create_permission', 'create_standalone_permission'

    public array $pendingOperationData = [];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
    ];

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
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->advancedSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->advancedSearch.'%')
                        ->orWhere('guard_name', 'like', '%'.$this->advancedSearch.'%');
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
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->advancedSearch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    // Export methods


    public function exportCSV()
    {
        $roles = $this->getFilteredQuery()->get();

        return $this->export(
            'role_permissions_' . date('Y-m-d'),
            $roles,
            'exports.role_permissions'
        );
    }

    // Modal methods
    public function viewPermissions($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
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
        $this->roleGuard = $role->guard_name;
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
        $this->selectedRoleId = null;
        $this->isEditing = false;
    }

    public function saveRole()
    {
        $this->validate([
            'roleName' => 'required|string|max:255',
            'roleGuard' => 'required|string',
        ]);

        $actor = current_actor();
        $isAdmin = should_bypass_approval();

        // If non-admin, show reason modal instead of saving directly
        if (! $isAdmin) {
            $this->pendingOperation = $this->isEditing ? 'update_role' : 'create_role';
            $this->pendingOperationData = [
                'isEditing' => $this->isEditing,
                'selectedRoleId' => $this->selectedRoleId,
                'roleName' => $this->roleName,
                'roleGuard' => $this->roleGuard,
                'selectedPermissions' => $this->selectedPermissions,
            ];
            $this->operationReason = '';
            $this->showReasonModal = true;

            return;
        }

        // ADMIN: Process immediately
        if ($this->isEditing && $this->selectedRoleId) {
            $role = Role::findOrFail($this->selectedRoleId);
            $oldName = $role->name;
            $oldGuard = $role->guard_name;

            $role->update([
                'name' => $this->roleName,
                'guard_name' => $this->roleGuard,
            ]);

            // Log the role update
            $changes = [];
            if ($oldName !== $this->roleName) {
                $changes[] = "Name: {$oldName} → {$this->roleName}";
            }
            if ($oldGuard !== $this->roleGuard) {
                $changes[] = "Guard: {$oldGuard} → {$this->roleGuard}";
            }

            AuditService::log(
                $actor,
                'update',
                $role,
                'Updated role. Changes: '.implode(', ', $changes),
                'completed'
            );

            $message = 'Role updated successfully!';
        } else {
            $role = Role::create([
                'name' => $this->roleName,
                'guard_name' => $this->roleGuard,
            ]);

            // Log the role creation
            $permissionNames = Permission::whereIn('id', $this->selectedPermissions)
                ->pluck('name')
                ->implode(', ');

            AuditService::log(
                $actor,
                'create',
                $role,
                "Created role '{$this->roleName}' with guard '{$this->roleGuard}'. Permissions: {$permissionNames}",
                'completed'
            );

            $message = 'Role created successfully!';
        }

        // Sync permissions - only permissions with matching guard
        $permissions = Permission::whereIn('id', $this->selectedPermissions)
            ->where('guard_name', $this->roleGuard)
            ->get();
        $role->syncPermissions($permissions);

        $this->toast()->success($message)->send();
        $this->closeRoleModal();
    }

    public function proceedWithRoleOperation()
    {
        if (strlen($this->operationReason) < 5) {
            $this->toast()->error('Reason must be at least 5 characters long')->send();

            return;
        }

        $actor = current_actor();
        $data = $this->pendingOperationData;

        // Create approval request using appropriate service
        match ($this->pendingOperation) {
            'create_role', 'update_role', 'delete_role' =>
                // Role operations are not employee-specific, use generic ApprovalAuditRequest
                ApprovalAuditRequest::create([
                    'requester_id' => $actor->id,
                    'requester_type' => get_class($actor),
                    'action' => match ($this->pendingOperation) {
                        'create_role' => 'role:create',
                        'update_role' => 'role:update:'.$data['selectedRoleId'],
                        'delete_role' => 'role:delete:'.$data['selectedRoleId'],
                    },
                    'description' => $this->operationReason,
                    'payload' => $data,
                    'status' => 'pending',
                ]),
            'create_permission', 'create_standalone_permission' =>
                // Permission operations are not employee-specific, use generic ApprovalAuditRequest
                ApprovalAuditRequest::create([
                    'requester_id' => $actor->id,
                    'requester_type' => get_class($actor),
                    'action' => match ($this->pendingOperation) {
                        'create_permission' => 'permission:create:'.$data['permissionName'],
                        'create_standalone_permission' => 'permission:create_standalone:'.$data['standalonePermissionName'],
                    },
                    'description' => $this->operationReason,
                    'payload' => $data,
                    'status' => 'pending',
                ]),
        };

        $this->toast()->success('Operation submitted for approval!')->send();
        $this->closeReasonModal();
        $this->closeRoleModal();
    }

    public function closeReasonModal()
    {
        $this->showReasonModal = false;
        $this->operationReason = '';
        $this->pendingOperation = null;
        $this->pendingOperationData = [];
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
            $role = Role::findOrFail($this->selectedRoleId);

            // Validation: Check if role is assigned to employees
            if ($role->users()->count() > 0) {
                $this->dialog()->error('Error', 'Cannot delete role assigned to '.$role->users()->count().' employee(s)')->send();

                return;
            }

            $actor = current_actor();
            $isAdmin = should_bypass_approval();

            // If non-admin, show reason modal instead of deleting directly
            if (! $isAdmin) {
                $this->pendingOperation = 'delete_role';
                $this->pendingOperationData = [
                    'selectedRoleId' => $this->selectedRoleId,
                    'roleName' => $role->name,
                    'roleGuard' => $role->guard_name,
                ];
                $this->operationReason = '';
                $this->showReasonModal = true;

                return;
            }

            // ADMIN: Delete immediately
            // Log the deletion
            AuditService::log(
                $actor,
                'delete',
                $role,
                "Deleted role '{$role->name}' with guard '{$role->guard_name}'",
                'completed'
            );

            $role->delete();
            $this->dialog()->success('Success', 'Role deleted successfully!')->send();
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
            ->question('Warning!', 'Are you sure you want to delete '.count($this->selectedIds).' role(s)?')
            ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
            ->send();
    }

    public function confirmedBulkDelete(string $message): void
    {
        Role::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', count($this->selectedIds).' role(s) deleted successfully!')->send();
        $this->selectedIds = [];
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->error('Cancelled', $message)->send();
    }

    public function updatedRoleGuard()
    {
        // Clear selected permissions when guard changes
        $this->selectedPermissions = [];
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

        $actor = current_actor();
        $isAdmin = should_bypass_approval();

        // If non-admin, show reason modal instead
        if (! $isAdmin) {
            $this->pendingOperation = 'create_permission';
            $this->pendingOperationData = [
                'permissionName' => $this->permissionName,
                'permissionGuard' => $this->permissionGuard,
            ];
            $this->operationReason = '';
            $this->showReasonModal = true;

            return;
        }

        // ADMIN: Create immediately
        $permission = Permission::create([
            'name' => $this->permissionName,
            'guard_name' => $this->permissionGuard,
        ]);

        // Log permission creation
        AuditService::log(
            $actor,
            'create',
            $permission,
            "Created permission '{$this->permissionName}' with guard '{$this->permissionGuard}'",
            'completed'
        );

        // Automatically add to selected permissions
        $this->selectedPermissions[] = $permission->id;

        $this->toast()->success('Permission created and added to role!')->send();
        $this->closeCreatePermissionModal();
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

        $actor = current_actor();
        $isAdmin = should_bypass_approval();

        // If non-admin, show reason modal instead
        if (! $isAdmin) {
            $this->pendingOperation = 'create_standalone_permission';
            $this->pendingOperationData = [
                'standalonePermissionName' => $this->standalonePermissionName,
                'standalonePermissionGuard' => $this->standalonePermissionGuard,
            ];
            $this->operationReason = '';
            $this->showReasonModal = true;

            return;
        }

        // ADMIN: Create immediately
        $permission = Permission::create([
            'name' => $this->standalonePermissionName,
            'guard_name' => $this->standalonePermissionGuard,
        ]);

        // Log permission creation
        AuditService::log(
            $actor,
            'create',
            $permission,
            "Created standalone permission '{$this->standalonePermissionName}' with guard '{$this->standalonePermissionGuard}'",
            'completed'
        );

        $this->toast()->success('Permission created successfully!')->send();
        $this->closeStandalonePermissionModal();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate((int) ($this->quantity ?? 10));

        // Filter permissions by the selected guard
        $allPermissions = Permission::where('guard_name', $this->roleGuard)->get();

        return view('livewire.branch-dashboard.employee-module.role-permission.index', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'name', 'label' => 'Role Name'],
                ['index' => 'guard_name', 'label' => 'Guard'],
                ['index' => 'created_at', 'label' => 'Created At'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'allPermissions' => $allPermissions,
        ]);
    }
}
