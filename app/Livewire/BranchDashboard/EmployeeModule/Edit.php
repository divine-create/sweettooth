<?php

namespace App\Livewire\BranchDashboard\EmployeeModule;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeApprovalService;
use App\Services\EmployeeAuditService;
use App\Traits\AuditableSyncTrait;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app.branch-dashboard')]
class Edit extends BaseComponent
{
    use AuditableSyncTrait, WithFileUploads;

    public $employeeId;

    #[Url(keep: true)]
    public $b_id;

    public string $bank_name = '';


    // Employee form fields
    public string|int|null $branch_id = null;

    public string|int|null $department_id = null;

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

    public ?string $salary = null;

    public ?string $hourly_rate = null;

    public ?string $tax_id = null;

    public ?string $bank_account = null;

    public ?string $allergies = null;

    public $profile_photo = null;

    public ?string $existing_photo = null;

    public ?string $last_performance_review_date = null;

    public ?string $performance_rating = null;

    public array $selectedRoles = [];

    // Reason modal state for employees
    public bool $showUpdateReasonModal = false;

    public string $updateReason = '';

    public bool $updatingEmployee = false;

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

    public function mount($id)
    {
        // Set b_id from URL parameter or current branch context
        $this->b_id = request()->query('b_id') ?? current_branch_id();

        $employee = Employee::find($id);

        if (! $employee) {
            $this->toast()->error('Employee not found')->send();

            return redirect()->route('branch-dashboard.employee.index', ['b_id' => $this->b_id]);
        }

        $this->employeeId = $employee->id;
        $this->branch_id = $employee->branch_id;
        $this->department_id = $employee->department_id;
        $this->employee_number = $employee->employee_number;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->address = $employee->address;
        $this->date_of_birth = $employee->date_of_birth ? (is_string($employee->date_of_birth) ? Carbon::parse($employee->date_of_birth)->format('Y-m-d') : $employee->date_of_birth->format('Y-m-d')) : null;
        $this->gender = $employee->gender;
        $this->nationality = $employee->nationality ?? 'Nigerian';
        $this->emergency_contact_name = $employee->emergency_contact_name;
        $this->emergency_contact_phone = $employee->emergency_contact_phone;
        $this->hire_date = $employee->hire_date ? (is_string($employee->hire_date) ? Carbon::parse($employee->hire_date)->format('Y-m-d') : $employee->hire_date->format('Y-m-d')) : null;
        $this->termination_date = $employee->termination_date ? (is_string($employee->termination_date) ? Carbon::parse($employee->termination_date)->format('Y-m-d') : $employee->termination_date->format('Y-m-d')) : null;
        $this->status = $employee->employment_status ?? ($employee->is_active ? 'active' : 'inactive');
        $this->probation_end_date = $employee->probation_end_date ? (is_string($employee->probation_end_date) ? Carbon::parse($employee->probation_end_date)->format('Y-m-d') : $employee->probation_end_date->format('Y-m-d')) : null;
        $this->shift_preference = $employee->shift_preference;
        $this->salary = $employee->salary;
        $this->hourly_rate = $employee->hourly_rate;
        $this->tax_id = $employee->tax_id;
        $this->bank_account = $employee->bank_account;
        $this->bank_name = $employee->bank_name ?? '';
        $this->allergies = $employee->allergies;
        $this->existing_photo = $employee->profile_photo;
        $this->last_performance_review_date = $employee->last_performance_review_date ? (is_string($employee->last_performance_review_date) ? Carbon::parse($employee->last_performance_review_date)->format('Y-m-d') : $employee->last_performance_review_date->format('Y-m-d')) : null;
        $this->performance_rating = $employee->performance_rating;
        $this->selectedRoles = $employee->roles->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        // Note: We don't reload employee data on branch change when editing
        // as we're editing a specific employee regardless of current branch context
    }

    public function updatedBranchId($value)
    {
        // Reset department when branch changes
        $this->department_id = null;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
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
        $this->dept_branch_id = null;
        $this->dept_type = 'production';
        $this->dept_description = null;
    }

    public function saveDepartment()
    {
        $this->validate([
            'dept_name' => 'required|string|max:255',
            'dept_branch_id' => 'nullable|exists:branches,id',
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

    public function initiateSave()
    {
        try {
            $this->validate([
                'department_id' => 'required|exists:departments,id',
                'employee_number' => 'required|string|unique:users,employee_number,'.$this->employeeId,
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$this->employeeId,
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
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return;
        }

        if (is_super_admin()) {
            $this->saveEmployee();
        } else {
            $this->showUpdateReasonModal = true;
        }
    }

    public function closeUpdateReasonModal()
    {
        $this->showUpdateReasonModal = false;
        $this->updateReason = '';
    }

    public function proceedWithUpdateReason()
    {
        if (strlen($this->updateReason) < 5) {
            $this->toast()->error('Reason must be at least 5 characters long')->send();

            return;
        }

        $this->showUpdateReasonModal = false;
        $this->saveEmployee();
    }

    public function saveEmployee()
    {
        if ($this->updatingEmployee) {
            return;
        }

        $this->updatingEmployee = true;

        try {
            $this->validate([
                'department_id' => 'required|exists:departments,id',
                'employee_number' => 'required|string|unique:users,employee_number,'.$this->employeeId,
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$this->employeeId,
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
                'updateReason' => is_super_admin() ? 'nullable|string' : 'required|string|min:5',
            ]);

            $employee = Employee::findOrFail($this->employeeId);

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
            ];

            if ($this->profile_photo) {
                $data['profile_photo'] = $this->profile_photo->store('employee-photos', 'public');
            }

            $user = current_actor();

            if (! is_super_admin()) {
                // EMPLOYEE: Create approval request using EmployeeApprovalService
                $selectedRolesNames = Role::where('guard_name', 'web')
                    ->whereIn('id', (array) $this->selectedRoles)
                    ->pluck('name')
                    ->toArray();

                EmployeeApprovalService::requestUpdate(
                    $employee,
                    $data,
                    $this->updateReason,
                    ['roles' => $selectedRolesNames]
                );

                $this->toast()->success('Employee update request submitted for approval!')->send();
                $this->redirectRoute('branch-dashboard.employee.index', ['b_id' => $this->b_id]);

                return;
            }

            // SUPER ADMIN: Update immediately
            $originalData = $employee->getOriginal();
            $employee->update($data);

            // Track changes for audit
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($originalData[$key]) && $originalData[$key] != $value) {
                    $changes[$key] = ['old' => $originalData[$key], 'new' => $value];
                }
            }

            // Sync roles with audit (selectedRoles comes as IDs; syncRoles expects names)
            $oldRoles = $employee->roles->pluck('name')->toArray();
            $selectedRolesNames = Role::where('guard_name', 'web')
                ->whereIn('id', (array) $this->selectedRoles)
                ->pluck('name')
                ->toArray();

            $employee->syncRoles($selectedRolesNames);

            if ($oldRoles != $selectedRolesNames) {
                EmployeeAuditService::logRoleChange(
                    $employee,
                    $oldRoles,
                    $selectedRolesNames,
                    'Roles updated during employee edit',
                    $user
                );
            }

            // Log employee update
            EmployeeAuditService::logEmployeeUpdate($employee, $changes, $user);

            $this->toast()->success('Employee updated successfully!')->send();
            $this->redirectRoute('branch-dashboard.employee.index', ['b_id' => $this->b_id]);

        } finally {
            $this->updatingEmployee = false;
        }
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)->get();
        $departments = $this->branch_id
            ? Department::where('branch_id', $this->branch_id)->orWhereNull('branch_id')->get()
            : Department::all();
        $roles = Role::where('guard_name', 'web')->get();

        return view('livewire.branch-dashboard.employee-module.edit', [
            'departments' => $departments,
            'roles' => $roles,
        ]);
    }
}
