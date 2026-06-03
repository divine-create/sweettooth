<?php

namespace App\Livewire\BranchDashboard\EmployeeModule;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeApprovalService;
use App\Services\EmployeeAuditService;
use App\Traits\AuditableSyncTrait;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app.branch-dashboard')]
class Create extends BaseComponent
{
    use AuditableSyncTrait, WithFileUploads;

    // Employee form
    #[Url(keep: true)]
    public $b_id;

    public string $bank_name = '';

    public ?string $branch_id = null;

    public ?string $department_id = null;

    public string $employee_number = '';

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public string $nationality = 'Nigerian';

    public ?string $emergency_contact_name = null;

    public ?string $emergency_contact_phone = null;


    public ?string $hire_date = null;

    public ?string $termination_date = null;

    public string $status = 'active';

    public ?string $probation_end_date = null;

    public ?string $shift_preference = null;

    public ?float $salary = null;

    public ?float $hourly_rate = null;

    public ?string $tax_id = null;

    public ?string $bank_account = null;

    public ?string $allergies = null;

    public $profile_photo = null;

    public ?string $last_performance_review_date = null;

    public ?float $performance_rating = null;

    public array $selectedRoles = [];

    // Reason modal state for employees
    public bool $showCreationReasonModal = false;

    public string $creationReason = '';

    public bool $creatingEmployee = false;

    // Modal states for creating branch/department
    public bool $showCreateBranchModal = false;

    public bool $showCreateDepartmentModal = false;

    // Branch form fields
    public string $branch_name = '';

    public string $branch_code = '';

    public string $branch_location = '';

    public ?string $branch_phone = null;

    public ?string $branch_email = null;

    // Department form fields
    public string $dept_name = '';

    public ?string $dept_branch_id = null;

    public string $dept_type = 'production';

    public ?string $dept_description = null;

    protected function getModelClass(): string
    {
        return Employee::class;
    }

    public function mount()
    {
        // Set b_id from URL parameter or current branch context
        $this->b_id = request()->query('b_id') ?? current_branch_id();

        $this->hire_date = date('Y-m-d');

        // Generate employee number based on selected branch
        $this->employee_number = $this->generateEmployeeNumber();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        // Regenerate employee number for new branch
        $this->employee_number = $this->generateEmployeeNumber();
        $this->department_id = null; // Reset department when branch changes
    }

    // public function updatedname($value)
    // {
    //     // Reset department when branch changes
    //     $this->department_id = null;

    //     // Generate employee number based on selected branch
    //     if ($value) {
    //         $this->employee_number = $this->generateEmployeeNumber($value);
    //     }
    // }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    // Branch creation methods
    public function openCreateBranchModal()
    {
        $this->resetBranchForm();
        $this->showCreateBranchModal = true;
    }

    public function closeCreateBranchModal()
    {
        $this->showCreateBranchModal = false;
        $this->resetBranchForm();
    }

    public function resetBranchForm()
    {
        $this->branch_name = '';
        $this->branch_code = '';
        $this->branch_location = '';
        $this->branch_phone = null;
        $this->branch_email = null;
    }

    // Department creation methods
    public function openCreateDepartmentModal()
    {
        $this->resetDepartmentForm();
        $this->showCreateDepartmentModal = true;
    }

    public function closeCreateDepartmentModal()
    {
        $this->showCreateDepartmentModal = false;
        $this->resetDepartmentForm();
    }

    public function resetDepartmentForm()
    {
        $this->dept_name = '';
        $this->dept_type = 'production';
        $this->dept_description = null;
    }

    public function saveDepartment()
    {
        $this->validate([
            'dept_name' => 'required|string|max:255',
            'dept_type' => 'required|in:production,sales',
            'dept_description' => 'nullable|string',
        ]);

        $department = Department::create([
            'name' => $this->dept_name,
            'branch_id' => $this->b_id,
            'type' => $this->dept_type,
            'description' => $this->dept_description,
        ]);

        $this->department_id = $department->id;
        $this->toast()->success('Department created successfully!')->send();
        $this->closeCreateDepartmentModal();
    }

    /**
     * Initiate employee creation workflow
     *
     * For super admins: Proceeds directly to save
     * For employees: Shows reason modal first
     */
    public function initiateSave()
    {
        // Validate form first
        try {
            $this->validate([
                'department_id' => 'required|exists:departments,id',
                'employee_number' => 'required|string|unique:users,employee_number',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:50',
                'hire_date' => 'required|date',
                'termination_date' => 'nullable|date',
                'status' => 'required|in:active,inactive,terminated,on_probation,on_leave',
                'probation_end_date' => 'nullable|date',
                'shift_preference' => 'nullable|in:morning,afternoon,night,rotating,flexible',
                'salary' => 'nullable|numeric|min:0',
                'hourly_rate' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|string|max:50',
                'bank_account' => 'nullable|string|max:100',
                'bank_name' => 'nullable|string|max:100',
                'allergies' => 'nullable|string',
                'profile_photo' => 'nullable|image|max:2048',
                'last_performance_review_date' => 'nullable|date',
                'performance_rating' => 'nullable|numeric|min:0|max:5',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return;
        }

        if (should_bypass_approval()) {
            // Bypass approval (super admin, or approvals disabled): proceed directly
            $this->saveEmployee();
        } else {
            // Employee: show reason modal
            $this->showCreationReasonModal = true;
        }
    }

    /**
     * Close the reason modal
     */
    public function closeCreationReasonModal()
    {
        $this->showCreationReasonModal = false;
        $this->creationReason = '';
    }

    /**
     * Proceed with creation after providing reason
     */
    public function proceedWithCreationReason()
    {
        if (strlen($this->creationReason) < 5) {
            $this->toast()->error('Reason must be at least 5 characters long')->send();

            return;
        }

        $this->showCreationReasonModal = false;
        $this->saveEmployee();
    }

    public function saveEmployee()
    {
        if ($this->creatingEmployee) {
            return;
        } // Prevent double-click

        $this->creatingEmployee = true;

        try {
            // Validate form input
            $this->validate([
                'department_id' => 'required|exists:departments,id',
                'employee_number' => 'required|string|unique:users,employee_number',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:50',
                'hire_date' => 'required|date',
                'termination_date' => 'nullable|date',
                'status' => 'required|in:active,inactive,terminated,on_probation,on_leave',
                'probation_end_date' => 'nullable|date',
                'shift_preference' => 'nullable|in:morning,afternoon,night,rotating,flexible',
                'salary' => 'nullable|numeric|min:0',
                'hourly_rate' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|string|max:50',
                'bank_account' => 'nullable|string|max:11',                
                'bank_name' => 'nullable|string|max:100',

                'allergies' => 'nullable|string',
                'profile_photo' => 'nullable|image|max:2048',
                'last_performance_review_date' => 'nullable|date',
                'performance_rating' => 'nullable|numeric|min:0|max:5',
                // Reason required for employees (unless approval is bypassed)
                'creationReason' => should_bypass_approval() ? 'nullable|string' : 'required|string|min:5',
            ]);

            $data = [
                'branch_id' => $this->b_id,
                'department_id' => $this->department_id,
                'employee_number' => $this->employee_number,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'date_of_birth' => $this->date_of_birth,
                'gender' => $this->gender,
                'nationality' => $this->nationality,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'hire_date' => $this->hire_date,
                'termination_date' => $this->termination_date,
                'employment_status' => $this->status,
                'is_active' => $this->status === 'active',
                'probation_end_date' => $this->probation_end_date,
                'shift_preference' => $this->shift_preference,
                'salary' => $this->salary,
                'hourly_rate' => $this->hourly_rate,
                'tax_id' => $this->tax_id,
                'bank_account' => $this->bank_account,
                'bank_name' => $this->bank_name, 
                'allergies' => $this->allergies,
                'last_performance_review_date' => $this->last_performance_review_date,
                'performance_rating' => $this->performance_rating,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                // Ensure new staff are not treated as legacy admins
                'user_type' => 'employee',
            ];

            if ($this->profile_photo) {
                $data['profile_photo'] = $this->profile_photo->store('employee-photos', 'public');
            }

            $user = current_actor();

            $selectedRolesNames = Role::where('guard_name', 'web')
                ->whereIn('id', (array) $this->selectedRoles)
                ->pluck('name')
                ->toArray();

            if (! should_bypass_approval()) {
                // EMPLOYEE: Create approval request using EmployeeApprovalService
                $approvalPayload = array_merge($data, [
                    'branch_id' => $this->b_id,
                    'selectedRoles' => $selectedRolesNames,
                ]);

                EmployeeApprovalService::requestCreate($approvalPayload, $this->creationReason);

                $this->toast()->success('Employee creation request submitted for approval!')->send();
                $this->redirectRoute('branch-dashboard.employee.index', ['b_id' => $this->b_id]);

                return;
            }

            // SUPER ADMIN: Create immediately
            $employee = Employee::create($data);

            // Sync roles with audit
            if (! empty($selectedRolesNames)) {
                $oldRoles = [];
                $employee->syncRoles($selectedRolesNames);
                EmployeeAuditService::logRoleChange(
                    $employee,
                    $oldRoles,
                    $selectedRolesNames,
                    'Initial role assignment during employee creation',
                    $user
                );
            }

            // Log employee creation
            EmployeeAuditService::logEmployeeCreation($employee, $user);

            $this->toast()->success('Employee created successfully!')->send();

            return redirect()->route('branch-dashboard.employee.index', ['b_id' => $this->b_id]);

        } finally {
            $this->creatingEmployee = false;
        }
    }

    private function generateEmployeeNumber()
    {
        $branch = Branch::find($this->b_id);
        if (! $branch) {
            return '';
        }

        // Get branch code without hyphens (e.g., LHO-001 becomes LHO001)
        $branchCode = str_replace('-', '', strtoupper($branch->code));

        // Get the last employee for this branch
        $lastEmployee = Employee::where('branch_id', $this->b_id)
            ->where('employee_number', 'like', 'EMP-'.$branchCode.'-%')
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract the last 4 digits from the employee number
            $lastNumber = intval(substr($lastEmployee->employee_number, -4));

            return 'EMP-'.$branchCode.'-'.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return 'EMP-'.$branchCode.'-0001';
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)->get();
        $departments = $this->branch_id
            ? Department::where('branch_id', $this->branch_id)->orWhereNull('branch_id')->get()
            : Department::all();
        $roles = Role::where('guard_name', 'web')->get();

        return view('livewire.branch-dashboard.employee-module.create', [
            'branches' => $branches,
            'departments' => $departments,
            'roles' => $roles,
        ]);
    }
}
