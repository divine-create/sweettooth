<?php

namespace App\Livewire\BranchDashboard\DepartmentModule\Department;

use App\Models\Branch;
use App\Models\Department;
use App\Models\ApprovalRequest;
use App\Models\ApprovalAuditRequest;
use App\Models\GlAccount;
use App\Services\AuditService;
use Livewire\Component;
use App\Models\DepartmentCategory;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\{Url, Layout, Title};
use TallStackUi\Traits\Interactions;

/**
 * Department Create/Update Livewire Component
 * 
 * Handles both creation of new departments and editing existing ones.
 * 
 * Key Features:
 * - Dual-mode: create mode and edit mode
 * - Different workflows for super admin vs regular employees
 * - Super admins can immediately create/update departments
 * - Employees must submit for approval (creates ApprovalAuditRequest)
 * - Super admin can select any branch; employees use assigned branch
 * - Reason/description modal for employees (minimum 5 characters)
 * - Full audit logging via AuditService
 * 
 * Workflows:
 * 
 * Super Admin Create:
 * - Enters department details
 * - Selects branch from dropdown
 * - Clicks save → Immediate creation
 * - Department appears in list immediately
 * 
 * Super Admin Update:
 * - Loads existing department data
 * - Edits fields
 * - Clicks save → Immediate update
 * 
 * Employee Create:
 * - Enters department details
 * - Uses their assigned branch (branch_id from URL parameter)
 * - Clicks save → Shows reason modal
 * - Submits reason → Creates ApprovalAuditRequest
 * - Super admin reviews and approves/rejects
 * 
 * Employee Update:
 * - Loads existing department data
 * - Edits fields
 * - Clicks save → Shows reason modal
 * - Submits reason → Creates ApprovalAuditRequest
 * - Super admin reviews and approves/rejects
 * 
 * @see App\Services\AuditService
 * @see App\Models\ApprovalAuditRequest
 */
#[Layout('components.layouts.app.branch-dashboard')]
#[Title("Modify Departments")]
class CreateOrUpdate extends Component
{
    use Interactions;

    /**
     * Department name - required field
     * @var string|null
     */
    public ?string $name='';

    /**
     * Department category ID - required field
     * Must exist in department_categories table
     * @var string|null
     */
    public ?string $category_id='';

    /**
     * Department description - optional field
     * @var string|null
     */
    public ?string $description='';

    /**
     * Default bank account for POS transfers for this department
     * @var int|null
     */
    public ?int $bank_account_id = null;

    /** GL account IDs for department accounting mappings */
    public ?int $revenue_account_id = null;
    public ?int $tax_account_id = null;
    public ?int $receivable_account_id = null;
    public ?int $cash_account_id = null;

    /**
     * ID of department being edited (create mode = empty)
     * @var string
     */
    public ?string $selectedDepartmentId= '';

    /**
     * Whether component is in edit mode (true) or create mode (false)
     * @var bool
     */
    public bool $isEditing = false;

    /**
     * Controls visibility of reason modal for non-super-admins
     * Used to collect reason/description for approval workflow
     * @var bool
     */
    public bool $showReasonModal = false;

    /**
     * Reason/description for creation/update (approval workflow)
     * Required for non-super-admin users (minimum 5 characters)
     * @var string
     */
    public string $creationReason = '';

    /**
     * Selected branch ID for this department
     * - Super admins: can select any branch via dropdown
     * - Employees: automatically set to their assigned branch
     * @var string|null
     */
    public ?string $branch_id = null;

    /**
     * Current branch context from URL parameter
     * Required for branch validation and context
     * @var string|null
     */
    #[Url(keep: true)]
    public ?string $b_id = null;

    /**
     * Mount the component
     * 
     * If editing: loads existing department data
     * If creating: initializes empty form
     * 
     * @param int|null $id Department ID for edit mode (null = create mode)
     * @return void
     */
    public function mount(?int $id = null)
    {
        if (!is_null($id)) {
            // Edit mode: load existing department
            $this->isEditing = true;
            $this->editDepartment($id);
        }
    }

    /**
     * Get all department categories (cached)
     * 
     * Used for category dropdown in form.
     * Cached for 4200 seconds since categories rarely change.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    #[Computed(seconds: 4200)]
    public function getDepartmentCategories()
    {
        return DepartmentCategory::all();
    }

    /**
     * Get all branches for super admin selection
     * 
     * Only used if user is super admin.
     * Cached for 4200 seconds.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    #[Computed(seconds: 4200)]
    public function getBranches()
    {
        return Branch::all();
    }

    /**
     * Get active bank accounts for selection
     */
    #[Computed(seconds: 4200)]
    public function getBankAccounts()
    {
        return BankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();
    }

    #[Computed(seconds: 300)]
    public function getGlAccountsByType(): array
    {
        $accounts = GlAccount::where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_number')
            ->get();

        return [
            'revenue' => $accounts->where('account_type', 'revenue')->values(),
            'tax'     => $accounts->whereIn('account_type', ['liability', 'tax'])->values(),
            'asset'   => $accounts->where('account_type', 'asset')->values(),
        ];
    }

    private function validateGlAccountType(?int $accountId, array $allowedTypes, string $field): void
    {
        if (! $accountId) {
            return;
        }
        $account = GlAccount::find($accountId);
        if (! $account) {
            $this->addError($field, 'Selected account does not exist.');
            return;
        }
        if (! in_array($account->account_type, $allowedTypes)) {
            $allowed = implode(' or ', array_map('ucfirst', $allowedTypes));
            $this->addError($field, "Must be a {$allowed} account. Selected: {$account->account_type} ({$account->account_name}).");
        }
    }

    /**
     * Get all departments (currently unused)
     * 
     * Legacy method - may be used in future for department relationships.
     * Filters by current branch if b_id provided in request.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    #[Computed(seconds: 4200)]
    public function getDepartments()
    {
        $branchId = request()->get('b_id');
        if (!$branchId) {
            return Department::all();
        }
        return Department::where('branch_id', $branchId)->get();
    }

    /**
     * Load department data for editing
     * 
     * Populates form fields with existing department data.
     * Called during mount when $id parameter is provided.
     * 
     * @param int $departmentId The ID of department to edit
     * @return void
     */
    public function editDepartment($departmentId)
    {
        // Load department or throw 404
        $department = Department::findOrFail($departmentId);
        
        // Set component to edit mode
        $this->isEditing = true;
        $this->selectedDepartmentId = $departmentId;
        
        // Populate form fields from department data
        $this->name = $department->name;
        $this->branch_id = $department->branch_id;
        $this->category_id = $department->category_id;
        $this->description = $department->description ?? '';
        $this->bank_account_id = $department->bank_account_id;
        $this->revenue_account_id = $department->revenue_account_id;
        $this->tax_account_id = $department->tax_account_id;
        $this->receivable_account_id = $department->receivable_account_id;
        $this->cash_account_id = $department->cash_account_id;
    }

    /**
     * Reset form to empty state
     * 
     * Clears all form fields and resets component state.
     * Called after successful save or when user cancels.
     * 
     * @return void
     */
    public function resetDepartmentForm()
    {
        $this->name = '';
        $this->category_id = null;
        $this->description = '';
        $this->selectedDepartmentId = null;
        $this->isEditing = false;
        $this->creationReason = '';
        $this->showReasonModal = false;
        $this->bank_account_id = null;
        $this->revenue_account_id = null;
        $this->tax_account_id = null;
        $this->receivable_account_id = null;
        $this->cash_account_id = null;
    }

    /**
     * Initiate department save workflow
     * 
     * For super admins: proceeds directly to save (no approval needed)
     * For employees: shows reason modal to collect approval reason
     * 
     * @return void
     */
    public function initiateSave()
    {
        // Validate form first
        try {
            $this->validate([
                'name' => 'required|string|max:255|unique:departments,name,' . $this->selectedDepartmentId,
                'category_id' => 'required|exists:department_categories,id',
                'description' => 'nullable|string',
                'bank_account_id' => 'nullable|exists:bank_accounts,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', message: 'Please fix validation errors', type: 'error');
            return;
        }

        $this->validateGlAccountType($this->revenue_account_id, ['revenue'], 'revenue_account_id');
        $this->validateGlAccountType($this->tax_account_id, ['liability', 'tax'], 'tax_account_id');
        $this->validateGlAccountType($this->receivable_account_id, ['asset'], 'receivable_account_id');
        $this->validateGlAccountType($this->cash_account_id, ['asset'], 'cash_account_id');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if (is_super_admin()) {
            // Super admin: proceed directly to save (no approval needed)
            $this->saveDepartment();
        } else {
            // Employees: must provide reason for approval workflow
            $this->showReasonModal = true;
        }
    }

    /**
     * Close the reason/description modal
     * 
     * Hides the approval workflow modal and clears reason text.
     * 
     * @return void
     */
    public function closeReasonModal()
    {
        $this->showReasonModal = false;
        $this->creationReason = '';
    }

    /**
     * Proceed with save after providing reason (Employee Workflow)
     * 
     * Called when employee submits the reason modal.
     * Validates reason length before proceeding with save.
     * 
     * @return void
     */
    public function proceedWithReasonSubmitted()
    {
        if (strlen($this->creationReason) < 5) {
            $this->toast()->error('Reason must be at least 5 characters long')->send();
            return;
        }
        
        // Close modal and proceed with save
        $this->showReasonModal = false;
        $this->saveDepartment();
    }

    /**
     * Save department (create or update)
     * 
     * Validates all input and then handles two distinct workflows:
     * 
     * SUPER ADMIN WORKFLOW:
     * - Immediately creates/updates department in database
     * - Can select any branch from dropdown
     * - Logs action as 'completed'
     * - No approval needed
     * 
     * EMPLOYEE WORKFLOW:
     * - NEVER modifies database directly
     * - Creates ApprovalAuditRequest with department data
     * - Reason is mandatory (minimum 5 characters)
     * - Logs action as 'pending'
     * - Super admin must review and approve before changes apply
     * - Employee's branch is fixed (from b_id URL parameter)
     * 
     * Validation Rules:
     * - name: Required, max 255 chars, unique (excluding current department)
     * - category_id: Required, must exist in database
     * - description: Optional, any string
     * - creationReason: Required for employees (min 5 chars), optional for super admin
     * 
     * After successful save: redirects to department list with b_id parameter
     * 
     * @return void
     */
    public function saveDepartment()
    {
        // Validate form input
        $this->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $this->selectedDepartmentId,
            'category_id' => 'required|exists:department_categories,id',
            'description' => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            // Reason is required for employees, optional for super admin
            'creationReason' => is_super_admin() ? 'nullable|string' : 'required|string|min:5',
        ]);

        // Determine branch_id based on user role:
        // - Super admin: use selected branch from dropdown
        // - Employee: use their assigned branch from URL (b_id)
        $branch_id = is_super_admin() ? $this->branch_id : $this->b_id;
        
        // Get current authenticated user (employee or super admin)
        $user = Auth::user();
        if (!$user || !is_object($user)) {
            $this->toast()->error('Authentication failed. Please log in again.')->send();
            return;
        }

        // Handle case where user is serialized as string (recovery logic)
        if (is_string($user)) {
            $userId = Auth::id();
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    Auth::setUser($user);
                } else {
                    $this->toast()->error('Authentication failed. Please log in again.')->send();
                    return;
                }
            } else {
                $this->toast()->error('Authentication failed. Please log in again.')->send();
                return;
            }
        }

        // Prepare department data for create/update
        $data = [
            'id' => $this->isEditing ? $this->selectedDepartmentId : null,
            'name' => $this->name,
            'branch_id' => $branch_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'bank_account_id' => $this->bank_account_id,
            'revenue_account_id' => $this->revenue_account_id,
            'tax_account_id' => $this->tax_account_id,
            'receivable_account_id' => $this->receivable_account_id,
            'cash_account_id' => $this->cash_account_id,
        ];

        if ($this->isEditing && $this->selectedDepartmentId) {
            // ===== EDIT MODE =====
            if (!is_super_admin()) {
                // EMPLOYEE: Create approval request for update
                $department = Department::find($this->selectedDepartmentId);
                
                // Create approval request (doesn't update department yet)
                $approvalRequest = ApprovalAuditRequest::create([
                    'branch_id' => $branch_id,
                    'requester_id' => $user->id,
                    'requester_type' => get_class($user),
                    'action' => 'update:'.\App\Models\Department::class,
                    'description' => $this->creationReason,
                    'payload' => $data,
                    'status' => 'pending',
                ]);

                // Log as pending audit entry (not yet updated)
                AuditService::log(
                    $user,
                    'update',
                    $department,
                    $this->creationReason,
                    'pending'
                );

                $message = 'Department update request submitted for approval!';
            } else {
                // SUPER ADMIN: Update immediately
                $department = Department::findOrFail($this->selectedDepartmentId);
                $department->update($data);
                
                // Log as completed audit entry
                AuditService::log(
                    $user,
                    'update',
                    $department,
                    'Department updated by super admin'
                );
                
                $message = 'Department updated successfully!';
            }
        } else {
            // ===== CREATE MODE =====
            if (!is_super_admin()) {
                // EMPLOYEE: Create approval request (don't create department)
                $approvalRequest = ApprovalAuditRequest::create([
                    'branch_id' => $branch_id,
                    'requester_id' => $user->id,
                    'requester_type' => get_class($user),
                    'action' => 'create:'.\App\Models\Department::class,
                    'description' => $this->creationReason,
                    'payload' => $data,
                    'status' => 'pending',
                ]);

                // Log as pending audit entry (no department created yet)
                AuditService::log(
                    $user,
                    'create',
                    null, // No auditable model yet (department not created)
                    $this->creationReason,
                    'pending'
                );

                $message = 'Department creation request submitted for approval!';
            } else {
                // SUPER ADMIN: Create immediately
                $department = Department::create($data);
                
                // Log as completed audit entry
                AuditService::log(
                    $user,
                    'create',
                    $department,
                    'Department created by super admin'
                );

                $message = 'Department created successfully!';
            }
        }

        // Show success message
        $this->toast()->success($message)->send();
        
        // Redirect to department list with b_id parameter (required by middleware)
        $redirectParams = ['b_id' => $branch_id];
        $this->redirect(route('branch-dashboard.branch.departments.index', $redirectParams));
    }



    /**
     * Render the department create/update form
     * 
     * Returns the appropriate view with the current component state.
     * View determines form behavior based on:
     * - isEditing: Whether form is in create or edit mode
     * - showReasonModal: Whether to show approval workflow modal
     * - is_super_admin(): Role-based form display (branch selector dropdown vs fixed branch)
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.branch-dashboard.department-module.department.create');
    }
}
