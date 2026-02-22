<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\RolePermission;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app.branch-dashboard')]
class AssignRole extends BaseComponent
{
    public ?int $quantity = 10;

    public ?string $search = null;

    public string $searchEmployee = '';

    public string $filterBranch = '';

    public string $filterDepartment = '';

    public bool $showRemoveRoleModal = false;

    public ?Employee $selectedEmployee = null;

    #[Url(keep: true)]
    public ?string $b_id = null;

    protected $queryString = ['searchEmployee', 'filterBranch', 'filterDepartment'];

    public function mount()
    {
        if ($this->b_id) {
            $this->filterBranch = Branch::where('id', $this->b_id)->firstOrFail()->id;

        }
    }

    protected function getModelClass(): string
    {
        return Employee::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->searchEmployee = '';
        $this->filterBranch = '';
        $this->filterDepartment = '';
        $this->resetPage();
    }

    protected function getFilteredQuery()
    {
        return Employee::query()
            ->with(['branch', 'department', 'roles'])
            ->has('roles') // Only get employees with at least one role
            ->when($this->searchEmployee, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchEmployee.'%')
                        ->orWhere('employee_number', 'like', '%'.$this->searchEmployee.'%')
                        ->orWhere('email', 'like', '%'.$this->searchEmployee.'%');
                });
            })
            ->when($this->filterBranch, function ($query) {
                $query->where('branch_id', $this->filterBranch);
            })
            ->when($this->filterDepartment, function ($query) {
                $query->where('department_id', $this->filterDepartment);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_number', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            });
    }

    public function openRemoveRoleModal($employeeId)
    {
        $this->selectedEmployee = Employee::with(['roles', 'branch', 'department'])->find($employeeId);
        $this->showRemoveRoleModal = true;
    }

    public function closeRemoveRoleModal()
    {
        $this->showRemoveRoleModal = false;
        $this->selectedEmployee = null;
    }

    public function removeRole($employeeId, $roleName)
    {
        $employee = Employee::findOrFail($employeeId);
        $employee->removeRole($roleName);

        // Refresh the selected employee data
        $this->selectedEmployee = Employee::with(['roles', 'branch', 'department'])->find($employeeId);

        $this->toast()->success('Role removed successfully from '.$employee->name)->send();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 10);
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();

        return view('livewire.branch-dashboard.employee-module.role-permission.assign-role', [
            'headers' => [
                ['index' => 'name', 'label' => 'Employee'],
                ['index' => 'branch', 'label' => 'Branch'],
                ['index' => 'department', 'label' => 'Department'],
                ['index' => 'roles', 'label' => 'Current Roles'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'branches' => $branches,
            'departments' => $departments,
        ]);
    }
}
