<?php

namespace App\Livewire\BranchDashboard\DepartmentModule;

use App\Models\Department;
use App\Livewire\BaseComponent;
use Livewire\Attributes\Computed;
use App\Models\DepartmentCategory;
use App\Models\ApprovalAuditRequest;
use App\Services\AuditService;
use App\Services\DepartmentApprovalService;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Exportable;

    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $advancedSearch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $filterCategory = null;
    public ?string $deleteReason = '';
    public ?string $selectedDepartmentId = null;
    public bool $showDeleteReasonModal = false;
    public ?string $b_id = null;

    public function mount(): void
    {
        $this->b_id = request()->query('b_id');
        $this->mountBase();
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    #[Computed(seconds: 4200)]
    public function getDepartmentCategories()
    {
        return DepartmentCategory::all();
    }

    protected function getFilteredQuery()
    {
        $branchId = $this->b_id ?? request()->query('b_id');

        return Department::query()
            ->with(['branch', 'category'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->advancedSearch, function ($q) {
                $term = $this->advancedSearch;
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'advancedSearch', 'dateFrom', 'dateTo', 'filterCategory']);
        $this->resetPage();
    }

    public function exportCSV()
    {
        // Get filtered departments based on current filters
        $departments = $this->getFilteredQuery()->get();

        // Build CSV header row
        $csv = "ID,Name,Branch,Type,Description,Created At\n";
        
        // Add each department as a CSV row
        foreach ($departments as $department) {
            // Get branch name or show N/A if department is global
            $branchName = $department->branch ? $department->branch->name : 'N/A';
            // Format row with proper CSV escaping for quoted values
            $csv .= "\"{$department->id}\",\"{$department->name}\",\"{$branchName}\",\"{$department->type}\",\"{$department->description}\",\"{$department->created_at}\"\n";
        }

        // Stream the CSV file to browser for download
        return response()->streamDownload(function() use ($csv) {
            echo $csv;
        }, 'departments-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Initiate department deletion
     * 
     * Different flows for super admin vs regular employees:
     * 
     * Super Admin Flow:
     * - Shows confirmation dialog
     * - Immediate deletion on confirmation
     * - Creates completed audit log entry
     * 
     * Employee Flow:
     * - Shows modal asking for deletion reason
     * - Reason must be at least 5 characters
     * - Creates ApprovalAuditRequest for super admin review
     * - Creates pending audit log entry
     * 
     * @param int $departmentId The ID of department to delete
     * @return void
     */
    public function deleteDepartment($departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;

        if (is_super_admin()) {
            // Super admin: show confirmation dialog for immediate deletion
            $this->dialog()
                ->question('Warning!', 'Are you sure you want to delete this department?')
                ->confirm('Confirm', 'confirmedDeleteDepartment', 'Confirmed Successfully')
                ->cancel('Cancel', 'cancelledDeleteDepartment', 'Cancelled Successfully')
                ->send();
        } else {
            // Employee: show reason modal for approval workflow
            $this->showDeleteReasonModal = true;
        }
    }

    /**
     * Process confirmed department deletion
     * 
     * Handles two distinct deletion flows:
     * 
     * 1. Super Admin Delete:
     *    - Immediately deletes the department from database
     *    - Creates completed audit log entry
     *    - Shows success confirmation dialog
     * 
     * 2. Employee Delete:
     *    - Validates deletion reason (minimum 5 characters)
     *    - Creates ApprovalAuditRequest for super admin approval
     *    - Creates pending audit log entry
     *    - Shows success message with "pending approval" status
     *    - Super admin must review and approve before deletion
     * 
     * Security Note:
     * - Verifies department's branch_id matches current b_id context
     * - Prevents cross-branch deletion attempts
     * 
     * @param string $message Dialog message (from confirmation)
     * @return void
     */
    public function confirmedDeleteDepartment(string $message): void
    {
        if ($this->selectedDepartmentId) {
            // Find the department or fail if not found
            $department = Department::findOrFail($this->selectedDepartmentId);
            
            // Security check: ensure department belongs to current branch context
            if($department->branch_id != $this->b_id){
                $this->dialog()->error('Danger', 'You are not in the place to delete this department!')->send();
                return;
            }

            // Get current authenticated user (employee or super admin)
            $user = auth()->user() ?? auth()->user();
            
            if (is_super_admin()) {
                // ===== SUPER ADMIN DELETION =====
                // Log the deletion action as completed
                AuditService::log($user, 'delete', $department, 'Department deleted by super admin', 'completed');
                // Immediately delete the department from database
                $department->delete();
                // Show success confirmation
                $this->dialog()->success('Success', 'Department deleted successfully!')->send();
            } else {
                // ===== EMPLOYEE DELETION (APPROVAL REQUIRED) =====
                DepartmentApprovalService::requestDelete($department, $this->deleteReason);
                $this->toast()->success('Delete request submitted for approval')->send();
            }
            
            // Reset delete operation state
            $this->selectedDepartmentId = null;
            $this->deleteReason = '';
            $this->showDeleteReasonModal = false;
        }
    }

    /**
     * Cancel department deletion
     * 
     * Called when user clicks "Cancel" on confirmation dialog or modal.
     * Resets all deletion-related state variables.
     * 
     * @param string $message Dialog message (from cancellation)
     * @return void
     */
    public function cancelledDeleteDepartment(string $message): void
    {
        // Reset deletion state
        $this->selectedDepartmentId = null;
        $this->deleteReason = '';
        $this->showDeleteReasonModal = false;
        $this->toast()->info('Cancelled')->send();
    }

    /**
     * Initiate bulk deletion of departments
     * 
     * Similar to single delete, but handles multiple selected departments.
     * 
     * Super Admin Flow:
     * - Shows confirmation dialog with count
     * - Immediate deletion on confirmation
     * 
     * Employee Flow:
     * - Shows reason modal for approval workflow
     * 
     * @return void
     */
    public function bulkDeleteDepartments(): void
    {
        if (is_super_admin()) {
            // Super admin: show confirmation with count
            $this->dialog()
                ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' department(s)?')
                ->confirm('Confirm', 'confirmedBulkDelete', 'Confirmed Successfully')
                ->cancel('Cancel', 'cancelledBulkDelete', 'Cancelled Successfully')
                ->send();
        } else {
            // Employee: show reason modal for approval workflow
            $this->showDeleteReasonModal = true;
        }
    }

    /**
     * Process confirmed bulk department deletion
     * 
     * Super Admin: 
     * - Logs each deletion with "bulk" annotation
     * - Performs single batch delete query for efficiency
     * - Shows success count
     * 
     * Employee:
     * - Validates reason (minimum 5 characters)
     * - Creates separate ApprovalAuditRequest for each department
     * - Only creates requests for departments in current branch
     * - Shows count of approval requests submitted
     * 
     * Branch Security:
     * - For employees, only processes departments matching current b_id
     * 
     * @param string $message Dialog message (from confirmation)
     * @return void
     */
    public function confirmedBulkDelete(string $message): void
    {
        // Get current authenticated user (employee or super admin)
        $user = auth()->user() ?? auth()->user();
        
        if (is_super_admin()) {
            // ===== SUPER ADMIN BULK DELETE =====
            $departments = Department::whereIn('id', $this->selectedIds)
                ->withCount([
                    'productTypes',
                    'pages',
                    'tables',
                    'employees',
                    'products',
                    'primarySalesProducts',
                    'salesProductionRequests',
                    'salesProductionRequestItems',
                ])
                ->get();

            $blocked = $departments->filter(function ($department) {
                return $department->product_types_count > 0
                    || $department->pages_count > 0
                    || $department->tables_count > 0
                    || $department->employees_count > 0
                    || $department->products_count > 0
                    || $department->primary_sales_products_count > 0
                    || $department->sales_production_requests_count > 0
                    || $department->sales_production_request_items_count > 0;
            });

            $deletable = $departments->diff($blocked);

            // Log each department deletion as completed
            foreach ($deletable as $department) {
                AuditService::log($user, 'delete', $department, 'Department deleted by super admin (bulk)', 'completed');
            }

            if ($deletable->isNotEmpty()) {
                Department::whereIn('id', $deletable->pluck('id'))->delete();
            }

            if ($blocked->isNotEmpty()) {
                $this->toast()->warning(
                    $blocked->count() . ' department(s) were not deleted because they have related records.'
                )->send();
            }

            $this->dialog()->success('Success', $deletable->count() . ' department(s) deleted successfully!')->send();
        } else {
            // ===== EMPLOYEE BULK DELETE (APPROVAL REQUIRED) =====
            // Validate deletion reason length
            if (strlen($this->deleteReason) < 5) {
                $this->toast()->error('Reason must be at least 5 characters long')->send();
                return;
            }
            
            // Process each selected department
            foreach ($this->selectedIds as $id) {
                $department = Department::find($id);
                
                // Only process departments in current branch (security check)
                if ($department && $department->branch_id == $this->b_id) {
                    // Create approval request for this department
                    ApprovalAuditRequest::create([
                        'branch_id' => $this->b_id,
                        'requester_id' => $user->id,
                        'requester_type' => get_class($user),
                        'action' => 'delete:'.\App\Models\Department::class,
                        'description' => $this->deleteReason,
                        'payload' => $department->toArray(),
                        'status' => 'pending',
                    ]);
                    
                    // Log as pending audit entry
                    AuditService::log($user, 'delete', $department, $this->deleteReason, 'pending');
                }
            }
            
            $this->toast()->success(count($this->selectedIds) . ' delete request(s) submitted for approval')->send();
        }
        
        // Reset bulk delete operation state
        $this->selectedIds = [];
        $this->deleteReason = '';
        $this->showDeleteReasonModal = false;
    }

    /**
     * Cancel bulk deletion
     * 
     * Called when user clicks "Cancel" on confirmation dialog or modal.
     * Resets all bulk deletion state variables.
     * 
     * @param string $message Dialog message (from cancellation)
     * @return void
     */
    public function cancelledBulkDelete(string $message): void
    {
        // Reset bulk delete state
        $this->selectedIds = [];
        $this->deleteReason = '';
        $this->showDeleteReasonModal = false;
        $this->toast()->info('Cancelled')->send();
    }

    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No departments selected for export.');
            return;
        }

        $departments = Department::whereIn('id', $this->selectedIds)
            ->with(['category', 'branch'])
            ->get();

        $this->export(
            'departments_' . date('Y-m-d'),
            $departments,
            'exports.departments'
        );

        session()->flash('success', count($this->selectedIds) . ' departments exported successfully.');
        $this->resetBulkSelection();
    }

    /**
     * Render the department management view
     * 
     * Fetches filtered and paginated department data, then passes it to
     * the view along with table headers and branch context.
     * 
     * Data Structure Passed to View:
     * - headers: Array of column definitions for the table
     *   - Each header contains: index (field name), label (display text)
     *   - Actions column has display=true to always show
     * - rows: Paginated collection of departments matching all filters
     * - b_id: Current branch ID for maintaining context in the view
     * 
     * The quantity property controls pagination size (default 10 per page).
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Get filtered departments with pagination
        $rows = $this->getFilteredQuery()->paginate((int) ($this->quantity ?? 5));

        // Return view with table structure and data
        return view('livewire.branch-dashboard.department-module.index', [
            // Table column headers definition
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'name', 'label' => 'Department Name'],
                ['index' => 'category', 'label' => 'Category'],
                ['index' => 'branch', 'label' => 'Branch'],
                ['index' => 'description', 'label' => 'Description'],
                ['index' => 'created_at', 'label' => 'Created At'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            // Paginated department data
            'rows' => $rows,
            // Current branch ID for maintaining context
            'b_id' => $this->b_id,
        ]);
    }
}
