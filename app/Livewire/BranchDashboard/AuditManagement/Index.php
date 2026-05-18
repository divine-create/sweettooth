<?php

namespace App\Livewire\BranchDashboard\AuditManagement;

use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Services\AuditService;
use App\Models\ApprovalRequest;
use App\Models\ApprovalAuditRequest;
use Livewire\Attributes\{Layout, On, Title, Url};
use App\Services\InventoryApprovalService;
use App\Services\PurchaseAuditApprovalService;
use App\Services\ProductionApprovalService;
use App\Services\EmployeeApprovalService;
use App\Services\DepartmentApprovalService;
use App\Services\DepartmentCategoryApprovalService;
use App\Services\CallbackApprovalService;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title("Audit Mnagement")]
class Index extends Component
{
    use WithPagination;

    public $branchId;
    public $tab = 'logs';
    public $search = '';
    public $filterDepartment = '';
    public $filterAction = '';
    public $filterStatus = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $sortBy = 'logged_at';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        if (is_super_admin()) {
            $this->branchId = request()->query('b_id') ?? current_branch_id();
        } else {
            $this->branchId = request()->query('b_id');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function approveRequest($requestId)
    {
        try {
            \Log::info('🔵 [AUDIT APPROVAL] Starting approval process', [
                'request_id' => $requestId,
                'approver_id' => auth()->user()?->id ?? auth()->user()?->id,
            ]);

            $request = ApprovalAuditRequest::find($requestId);

            if (!$request) {
                throw new \Exception("Approval request #{$requestId} not found");
            }

            if ($request->status !== 'pending') {
                throw new \Exception("Request is no longer pending (current status: {$request->status})");
            }

            \Log::info('✅ [AUDIT APPROVAL] Request found and valid', [
                'action' => $request->action,
                'status' => $request->status,
            ]);

            // Execute the approved action - delegates to action handler
            \Log::info('🔵 [AUDIT APPROVAL] Executing approved action', ['action' => $request->action]);
            $auditable = $this->executeApprovedAction($request);
           
            if (!$auditable) {
                throw new \Exception('Failed to execute approval action - auditable returned null');
            }

            \Log::info('✅ [AUDIT APPROVAL] Action executed successfully', [
                'auditable_type' => is_object($auditable) ? get_class($auditable) : gettype($auditable),
                'auditable_id' => $auditable->id ?? 'N/A',
            ]);

            $approver = auth()->user() ?? auth()->user();
            if (!$approver) {
                throw new \Exception('Could not determine current approver');
            }

            \Log::info('🔵 [AUDIT APPROVAL] Updating request status to approved', [
                'approver_id' => $approver->id,
                'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
            ]);

            $request->update([
                'status' => 'approved',
                'approver_id' => $approver->id,
                'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
                'approved_at' => now(),
            ]);

            \Log::info('✅ [AUDIT APPROVAL] Request status updated');

            // Log the approval action
            // Extract just the action type (e.g., "update:App\Models\Employee" -> "update")
            $baseAction = explode(':', $request->action)[0];
            
            \Log::info('🔵 [AUDIT APPROVAL] Logging approval action', ['base_action' => $baseAction]);
            AuditService::log(
                $approver,
                "approve_{$baseAction}",
                $auditable,
                'Approved by ' . $approver->name,
                'completed'
            );

            \Log::info('✅ [AUDIT APPROVAL] Approval completed successfully');
            $this->dispatch('toast', message: 'Request approved successfully', type: 'success');
            $this->resetPage();
        } catch (\Exception $e) {
            \Log::error('❌ [AUDIT APPROVAL] Approval failed', [
                'request_id' => $requestId ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log the error
            $approver = auth()->user() ?? auth()->user();
            if ($approver) {
                try {
                    AuditService::log(
                        $approver,
                        'approve_failed',
                        null,
                        'Approval failed: ' . $e->getMessage(),
                        'failed'
                    );
                } catch (\Exception $auditError) {
                    \Log::error('❌ [AUDIT APPROVAL] Failed to log approval error', [
                        'audit_error' => $auditError->getMessage(),
                    ]);
                }
            }

            $this->dispatch('toast', message: 'Error approving request: ' . $e->getMessage(), type: 'error');
            throw $e;
        }
    }

    /**
     * Execute an approved action based on the request type
     * Generic handler that works with any model/action combination
     * Supports: create, update, delete, and sync operations
     */
    private function executeApprovedAction(ApprovalAuditRequest $request)
    {
        try {
            \Log::info('🔵 [EXECUTE ACTION] Starting action execution', [
                'action' => $request->action,
                'request_id' => $request->id,
            ]);

            // Parse action format: "action:model" or "action:model:relationship:id" 
            // (e.g., "create:department", "sync:App\Models\Employee:roles:123", "update_item:123")
             $parts = explode(':', $request->action);

             $action = $parts[0];
             $model = null;
             $relationship = null;
             $modelId = $request->payload['id'] ?? null;
            
            \Log::info('🔵 [EXECUTE ACTION] Parsed action details', [
                'action' => $action,
                'parts_count' => count($parts),
                'model_id' => $modelId,
            ]);

            // For sync actions with full namespace: "sync:App\Models\Employee:roles:id"
            if ($action === 'sync' && count($parts) >= 3) {
                $model = $parts[1];
                $relationship = $parts[2];
            } else {
                // Fallback for other formats
                $model = $parts[1] ?? null;
                $relationship = $parts[2] ?? null;
            }

            // Extract ID from action string for inventory operations (e.g., "update_item:123" -> 123)
            if (count($parts) > 1 && !$modelId && in_array($action, ['delete_item', 'delete_purchase', 'update_item'])) {
                $modelId = $parts[1];
            }

            $auditable = null;

            \Log::info('🔵 [EXECUTE ACTION] Dispatching to action handler', [
                'action' => $action,
                'model' => $model,
                'relationship' => $relationship,
            ]);

            $auditable = match ($action) {
                'create' => $this->handleCreateAction($model, $request->payload),
                'update' => $this->handleUpdateAction($model, $modelId, $request->payload),
                'delete' => $this->handleDeleteAction($model, $modelId),
                'sync' => $this->handleSyncAction($model, $modelId, $relationship, $request->payload),
                'stock_adjustment' => InventoryApprovalService::executeStockAdjustment($request),
                'create_item' => InventoryApprovalService::executeItemCreation($request),
                'update_item' => InventoryApprovalService::executeItemUpdate($request),
                'delete_item' => InventoryApprovalService::executeItemDeletion($request, $this->getApprover()),
                'create_purchase' => InventoryApprovalService::executePurchaseCreation($request, $this->getApprover()),
                'delete_purchase' => InventoryApprovalService::executePurchaseDeletion($request, $this->getApprover()),
                'approve_purchase' => PurchaseAuditApprovalService::approvePurchase($request, $this->getApprover()),
                // Production module handlers
                'product' => $this->handleProductAction($request),
                'recipe' => $this->handleRecipeAction($request),
                // Employee module handlers
                'employee' => $this->handleEmployeeAction($request),
                // Department module handlers
                'department' => $this->handleDepartmentAction($request),
                'department_category' => $this->handleDepartmentCategoryAction($request),
                // Callback handlers
                'callback' => $this->handleCallbackAction($request),
                default => throw new \Exception("Unknown action type: {$action}"),
            };

            \Log::info('✅ [EXECUTE ACTION] Action executed successfully', [
                'action' => $action,
                'auditable_type' => $auditable ? (is_object($auditable) ? get_class($auditable) : gettype($auditable)) : 'null',
            ]);

            return $auditable;
        } catch (\Exception $e) {
            \Log::error('❌ [EXECUTE ACTION] Action execution failed', [
                'action' => $request->action ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to execute action '{$request->action}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle create actions for any model
     * Supports both basic field creation and relationship syncing (roles, permissions, etc.)
     */
    private function handleCreateAction(string $modelName, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass) {
            return null;
        }

        try {
            // Extract only fillable fields from the payload
            $modelClass = "\\$modelClass";
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $createData = array_intersect_key($payload, array_flip($fillable));
            
            // Add slug for models that use it (e.g., Department)
            if (isset($createData['name']) && in_array('slug', $fillable)) {
                $createData['slug'] = Str::slug($createData['name']);
            }

            // Create the model
            $auditable = $modelClass::create($createData);

            // Handle role syncing if roles are included in payload
            if (isset($payload['selectedRoles'])) {
                $this->syncRolesFromPayload($auditable, $payload['selectedRoles']);
            }

            // Handle permission syncing if permissions are included
            if (isset($payload['selectedPermissions'])) {
                $this->syncPermissionsFromPayload($auditable, $payload['selectedPermissions']);
            }

            // Handle other relationship syncing patterns (selectedX, syncX, X_ids)
            foreach ($payload as $key => $value) {
                if (str_starts_with($key, 'selected') && is_array($value)) {
                    // Convert selectedRoles -> roles, selectedDepartments -> departments, etc.
                    $relationshipName = lcfirst(str_replace('selected', '', $key));
                    if (method_exists($auditable, $relationshipName) && $relationshipName !== 'roles' && $relationshipName !== 'permissions') {
                        $this->syncRelationship($auditable, $relationshipName, $value);
                    }
                }
            }

            return $auditable;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create {$modelClass}: " . $e->getMessage());
        }
    }

    /**
     * Handle update actions for any model
     * Supports both basic field updates and relationship syncing (roles, permissions, etc.)
     */
    private function handleUpdateAction(string $modelName, ?string $modelId, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass || !$modelId) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if (!$auditable) {
            return null;
        }

        try {
            // Extract only fillable fields for the model update
            $fillable = $auditable->getFillable();
            $updateData = array_intersect_key($payload, array_flip($fillable));
            
            // Update basic model fields
            if (!empty($updateData)) {
                $auditable->update($updateData);
            }

            // Handle role syncing if roles are included in payload
            if (isset($payload['selectedRoles'])) {
                $this->syncRolesFromPayload($auditable, $payload['selectedRoles']);
            }

            // Handle permission syncing if permissions are included
            if (isset($payload['selectedPermissions'])) {
                $this->syncPermissionsFromPayload($auditable, $payload['selectedPermissions']);
            }

            // Handle other relationship syncing patterns (selectedX, syncX, X_ids)
            foreach ($payload as $key => $value) {
                if (str_starts_with($key, 'selected') && is_array($value)) {
                    // Convert selectedRoles -> roles, selectedDepartments -> departments, etc.
                    $relationshipName = lcfirst(str_replace('selected', '', $key));
                    if (method_exists($auditable, $relationshipName) && $relationshipName !== 'roles' && $relationshipName !== 'permissions') {
                        $this->syncRelationship($auditable, $relationshipName, $value);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to update {$modelClass}: " . $e->getMessage());
        }

        return $auditable;
    }

    /**
     * Get the current approver (user or employee)
     */
    private function getApprover()
    {
        return auth()->user() ?? auth()->user();
    }

    /**
     * Handle delete actions for any model
     */
    private function handleDeleteAction(string $modelName, ?string $modelId)
    {
        // $modelMap = [
        //     'department' => Department::class,

        // ];

        $modelClass = $modelName;
        if (!$modelClass || !$modelId) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if ($auditable) {
            $auditable->delete();
        }

        return $auditable;
    }

    /**
     * Sync roles for an auditable model
     * Handles both role IDs and role names
     */
    private function syncRolesFromPayload($auditable, $roleData)
    {
        if (empty($roleData)) {
            return;
        }

        $roleNames = [];
        $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'employees';

        foreach ($roleData as $role) {
            if (is_numeric($role)) {
                // It's an ID, get the role name from the correct guard
                $roleObj = \Spatie\Permission\Models\Role::where('id', $role)
                    ->where('guard_name', $guardName)
                    ->first();
                if ($roleObj) {
                    $roleNames[] = $roleObj->name;
                }
            } else {
                // It's already a name
                $roleNames[] = $role;
            }
        }

        if (!empty($roleNames)) {
            $auditable->syncRoles($roleNames);
        }
    }

    /**
     * Sync permissions for an auditable model
     * Handles both permission IDs and permission names
     */
    private function syncPermissionsFromPayload($auditable, $permissionData)
    {
        if (empty($permissionData)) {
            return;
        }

        $permissionNames = [];
        $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'web';

        foreach ($permissionData as $permission) {
            if (is_numeric($permission)) {
                // It's an ID, get the permission name from the correct guard
                $permissionObj = \Spatie\Permission\Models\Permission::where('id', $permission)
                    ->where('guard_name', $guardName)
                    ->first();
                if ($permissionObj) {
                    $permissionNames[] = $permissionObj->name;
                }
            } else {
                // It's already a name
                $permissionNames[] = $permission;
            }
        }

        if (!empty($permissionNames)) {
            $auditable->syncPermissions($permissionNames);
        }
    }

    /**
     * Sync a generic many-to-many relationship
     */
    private function syncRelationship($auditable, string $relationshipName, array $syncData)
    {
        if (empty($syncData)) {
            return;
        }

        try {
            if (method_exists($auditable, $relationshipName)) {
                $auditable->{$relationshipName}()->sync($syncData);
            }
        } catch (\Exception $e) {
            // Silently fail for relationships that don't exist
            // This prevents errors when generic sync patterns are used
        }
    }

    /**
     * Handle sync actions for many-to-many relationships
     * Supports syncing roles, permissions, and other relationships
     */
    private function handleSyncAction(string $modelName, int|string|null $modelId, ?string $relationship, array $payload)
    {
        $modelClass = $modelName;
        if (!$modelClass || !$modelId || !$relationship) {
            return null;
        }

        $auditable = $modelClass::find($modelId);
        if (!$auditable) {
            return null;
        }

        // Get the sync data from payload
        // Expected format: $payload['sync_data'] = [1, 2, 3] or [1 => ['pivot_col' => 'val'], ...]
        $syncData = $payload['sync_data'] ?? $payload[$relationship] ?? [];

        if (empty($syncData)) {
            return $auditable;
        }

        try {
            // Special handling for roles - convert IDs to names if needed
            if ($relationship === 'roles') {
                // If syncData contains numeric values (IDs), convert to role names
                $roleNames = [];
                $guardName = property_exists($auditable, 'guard_name') ? $auditable->guard_name : 'employees';
                
                foreach ($syncData as $role) {
                    if (is_numeric($role)) {
                        // It's an ID, get the role name from the correct guard
                        $roleObj = \Spatie\Permission\Models\Role::where('id', $role)
                            ->where('guard_name', $guardName)
                            ->first();
                        if ($roleObj) {
                            $roleNames[] = $roleObj->name;
                        }
                    } else {
                        // It's already a name
                        $roleNames[] = $role;
                    }
                }
                if (!empty($roleNames)) {
                    $auditable->syncRoles($roleNames);
                }
            } else if ($relationship === 'permissions') {
                // Similar handling for permissions
                $auditable->syncPermissions($syncData);
            } else {
                // Generic sync for other relationships
                if (method_exists($auditable, $relationship)) {
                    $auditable->{$relationship}()->sync($syncData);
                } else {
                    $auditable->$relationship()->sync($syncData);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to sync {$relationship}: " . $e->getMessage());
        }

        return $auditable;
    }

    public function rejectRequest($requestId)
    {
        $request = ApprovalAuditRequest::find($requestId);
        
        if (!$request || $request->status !== 'pending') {
            $this->dispatch('toast', message: 'Request not found or already processed', type: 'error');
            return;
        }

        try {
            $approver = auth()->user() ?? auth()->user();
            
            // Handle purchase rejection - reset status back to draft
            $baseAction = explode(':', $request->action)[0];
            if ($baseAction === 'approve_purchase') {
                PurchaseAuditApprovalService::rejectPurchase($request, $approver, request('rejection_comment', ''));
            } else {
                $request->update([
                    'status' => 'rejected',
                    'approver_id' => $approver->id,
                    'approver_type' => is_object($approver) ? get_class($approver) : gettype($approver),
                    'denied_at' => now(),
                ]);
            }

            // Log the rejection action
            AuditService::log(
                $approver,
                "reject_{$baseAction}",
                null,
                'Rejected by ' . $approver->name,
                'completed'
            );

            $this->dispatch('toast', message: 'Request rejected successfully', type: 'info');
            $this->resetPage();
        } catch (\Exception $e) {
            // Log the error
            $approver = auth()->user() ?? auth()->user();
            AuditService::log(
                $approver,
                'reject_failed',
                null,
                'Rejection failed: ' . $e->getMessage(),
                'failed'
            );

            $this->dispatch('toast', message: 'Error rejecting request: ' . $e->getMessage(), type: 'error');
        }
    }

    public function updatingTab()
    {
        $this->clearFilters();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterDepartment = '';
        $this->filterAction = '';
        $this->filterStatus = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $logsQuery = AuditLog::query();
        $approvalsQuery = ApprovalAuditRequest::query();
        $isSuperAdmin = is_super_admin();

        // Apply search filter
        if ($this->search) {
            $logsQuery->where('description', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%");
            $approvalsQuery->where('description', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%");
        }

        // Apply branch filter (from branchId) - show all if no branch specified for super admin
        if ($this->branchId) {
            $logsQuery->where('branch_id', $this->branchId);
            $approvalsQuery->where(function ($q) {
                $q->where('branch_id', $this->branchId)
                    ->orWhereNull('branch_id'); // Show records with null branch_id too
            });
        } else if (!$isSuperAdmin && current_branch_id()) {
            // Non-super admin: filter to their branch
            $logsQuery->where('branch_id', current_branch_id());
            $approvalsQuery->where(function ($q) {
                $q->where('branch_id', current_branch_id())
                    ->orWhereNull('branch_id');
            });
        }

        // Department filter removed (audit_logs table doesn't have department_id in prod)

        // Apply action filter (match base action or exact match)
        if ($this->filterAction) {
            $logsQuery->where(function ($q) {
                $q->where('action', $this->filterAction)
                    ->orWhere('action', 'like', $this->filterAction . ':%');
            });
            $approvalsQuery->where(function ($q) {
                $q->where('action', $this->filterAction)
                    ->orWhere('action', 'like', $this->filterAction . ':%');
            });
        }

        // Apply status filter
        if (!empty($this->filterStatus)) {
            $logsQuery->where('status', $this->filterStatus);
            $approvalsQuery->where('status', $this->filterStatus);
        }

        // Apply date filter
        if (!empty($this->filterDateFrom)) {
            $fromDate = \Carbon\Carbon::parse($this->filterDateFrom)->startOfDay();
            $logsQuery->where('logged_at', '>=', $fromDate);
            $approvalsQuery->where('created_at', '>=', $fromDate);
        }

        if (!empty($this->filterDateTo)) {
            $toDate = \Carbon\Carbon::parse($this->filterDateTo)->endOfDay();
            $logsQuery->where('logged_at', '<=', $toDate);
            $approvalsQuery->where('created_at', '<=', $toDate);
        }

        // Get departments for filter
        $departments = Department::orderBy('name')->get();

        // Get data based on tab
        if ($this->tab === 'logs') {
            $logs = $logsQuery
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(15);

            // Get unique base actions (without IDs) for the filter dropdown
            $rawActions = AuditLog::distinct('action')->pluck('action');
            $actions = $rawActions->map(function ($action) {
                // Extract base action: "delete_item:6" -> "delete_item", "update_item:123" -> "update_item"
                $parts = explode(':', $action);
                // If second part is numeric, it's an ID - return just the first part
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    return $parts[0];
                }
                return $action;
            })->unique()->sort()->values();

            $statuses = AuditLog::distinct('status')->pluck('status')->sort();

            return view('livewire.branch-dashboard.audit-management.index', [
                'logs' => $logs,
                'approvals' => null,
                'actions' => $actions,
                'statuses' => $statuses,
                'departments' => $departments,
                'isSuperAdmin' => $isSuperAdmin,
            ]);
        } else {
            $approvals = $approvalsQuery
                ->with('requester', 'approver')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Get unique base actions (without IDs) for the filter dropdown
            $rawActions = ApprovalAuditRequest::distinct('action')->pluck('action');
            $actions = $rawActions->map(function ($action) {
                // Extract base action: "delete_item:6" -> "delete_item", "update_item:123" -> "update_item"
                $parts = explode(':', $action);
                // If second part is numeric, it's an ID - return just the first part
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    return $parts[0];
                }
                return $action;
            })->unique()->sort()->values();

            $statuses = ApprovalAuditRequest::distinct('status')->pluck('status')->sort();

            return view('livewire.branch-dashboard.audit-management.index', [
                'logs' => null,
                'approvals' => $approvals,
                'actions' => $actions,
                'statuses' => $statuses,
                'departments' => $departments,
                'isSuperAdmin' => $isSuperAdmin,
            ]);
        }
    }

    /**
     * Handle product-related approval actions (create, edit, delete)
     */
    private function handleProductAction(ApprovalAuditRequest $request)
    {
        // Extract sub-action from action string: "product:create", "product:edit", "product:delete", "product:bulk_delete"
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';

        return match ($subAction) {
            'create' => ProductionApprovalService::executeProductCreation($request),
            'edit' => ProductionApprovalService::executeProductUpdate($request),
            'delete' => ProductionApprovalService::executeProductDeletion($request),
            'bulk_delete' => ProductionApprovalService::executeProductBulkDeletion($request),
            'assignments' => ProductionApprovalService::executeProductAssignments($request),
            default => throw new \Exception("Unknown product action: {$subAction}"),
        };
    }

    /**
     * Handle recipe-related approval actions (create, edit, delete)
     */
    private function handleRecipeAction(ApprovalAuditRequest $request)
    {
        try {
            \Log::info('🔵 [HANDLE RECIPE ACTION] Starting recipe action handler', [
                'action' => $request->action,
                'request_id' => $request->id,
            ]);

        // Extract sub-action from action string: "recipe:create_recipe", "recipe:edit_recipe", "recipe:delete_recipe", "recipe:bulk_delete_recipe"
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create_recipe';

        \Log::info('🔵 [HANDLE RECIPE ACTION] Sub-action extracted', [
            'sub_action' => $subAction,
            'parts_count' => count($parts),
        ]);

        $result = match ($subAction) {
            'create_recipe' => ProductionApprovalService::executeRecipeCreation($request),
            'edit_recipe' => ProductionApprovalService::executeRecipeUpdate($request),
            'delete_recipe' => ProductionApprovalService::executeRecipeDeletion($request),
            'bulk_delete_recipe' => ProductionApprovalService::executeRecipeBulkDeletion($request),
            default => throw new \Exception("Unknown recipe action: {$subAction}"),
        };

        \Log::info('✅ [HANDLE RECIPE ACTION] Recipe action completed', [
            'sub_action' => $subAction,
            'result_type' => is_object($result) ? get_class($result) : gettype($result),
        ]);

        return $result;
        } catch (\Exception $e) {
            \Log::error('❌ [HANDLE RECIPE ACTION] Recipe action failed', [
                'action' => $request->action ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to handle recipe action: " . $e->getMessage());
        }
    }

    /**
     * Handle employee-related approval actions (create, update, delete, sync_roles, sync_permissions)
     */
    private function handleEmployeeAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';
        $approver = $this->getApprover();

        \Log::info('🔵 [HANDLE EMPLOYEE ACTION] Processing employee action', [
            'action' => $request->action,
            'sub_action' => $subAction,
        ]);

        return match ($subAction) {
            'create' => EmployeeApprovalService::executeCreate($request, $approver),
            'update' => EmployeeApprovalService::executeUpdate($request, $approver),
            'delete' => EmployeeApprovalService::executeDelete($request, $approver),
            'sync_roles' => EmployeeApprovalService::executeRoleSync($request, $approver),
            'sync_permissions' => EmployeeApprovalService::executePermissionSync($request, $approver),
            default => throw new \Exception("Unknown employee action: {$subAction}"),
        };
    }

    /**
     * Handle department-related approval actions (create, update, delete)
     */
    private function handleDepartmentAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';
        $approver = $this->getApprover();

        \Log::info('🔵 [HANDLE DEPARTMENT ACTION] Processing department action', [
            'action' => $request->action,
            'sub_action' => $subAction,
        ]);

        return match ($subAction) {
            'create' => DepartmentApprovalService::executeCreate($request, $approver),
            'update' => DepartmentApprovalService::executeUpdate($request, $approver),
            'delete' => DepartmentApprovalService::executeDelete($request, $approver),
            default => throw new \Exception("Unknown department action: {$subAction}"),
        };
    }

    /**
     * Handle department category-related approval actions (create, update, delete)
     */
    private function handleDepartmentCategoryAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'create';
        $approver = $this->getApprover();

        \Log::info('🔵 [HANDLE DEPARTMENT CATEGORY ACTION] Processing action', [
            'action' => $request->action,
            'sub_action' => $subAction,
        ]);

        return match ($subAction) {
            'create' => DepartmentCategoryApprovalService::executeCreate($request, $approver),
            'update' => DepartmentCategoryApprovalService::executeUpdate($request, $approver),
            'delete' => DepartmentCategoryApprovalService::executeDelete($request, $approver),
            default => throw new \Exception("Unknown department category action: {$subAction}"),
        };
    }

    /**
     * Handle callback-related approval actions (inventory, production)
     */
    private function handleCallbackAction(ApprovalAuditRequest $request)
    {
        $parts = explode(':', $request->action);
        $subAction = $parts[1] ?? 'inventory';
        $approver = $this->getApprover();

        \Log::info('🔵 [HANDLE CALLBACK ACTION] Processing callback action', [
            'action' => $request->action,
            'sub_action' => $subAction,
        ]);

        return match ($subAction) {
            'inventory' => CallbackApprovalService::executeInventoryCallback($request, $approver),
            'production' => CallbackApprovalService::executeProductionCallback($request, $approver),
            default => throw new \Exception("Unknown callback action: {$subAction}"),
        };
    }
}
