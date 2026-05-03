<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\Employee;
use App\Models\EmployeeLeaveAllocation;
use App\Models\LeaveType;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ManageAllocations extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    public ?string $selectedYear;

    // Modal fields
    public $showAllocationModal = false;

    public $selectedEmployeeId = null;

    public ?Employee $selectedEmployee = null;

    public $allocations = [];

    // Table headers
    public array $headers = [
        ['index' => 'employee', 'label' => 'Employee'],
        ['index' => 'allocations', 'label' => 'Leave Allocations'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    protected function getModelClass(): string
    {
        return Employee::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return Employee::query();
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->selectedYear = now()->year;
    }

    public function getRowsProperty()
    {
        $query = Employee::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('name')->paginate((int) $this->quantity);
    }

    public function openAllocationModal($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->selectedEmployee = Employee::find($employeeId);

        if (! $this->selectedEmployee) {
            $this->toast()->error('Employee not found.')->send();

            return;
        }

        // Load existing allocations
        $this->allocations = [];
        $leaveTypes = LeaveType::active()->get();

        foreach ($leaveTypes as $leaveType) {
            $existingAllocation = EmployeeLeaveAllocation::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $this->selectedYear)
                ->first();

            $this->allocations[] = [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'default_days' => $leaveType->default_days_per_year,
                'allocated_days' => $existingAllocation ? $existingAllocation->allocated_days : $leaveType->default_days_per_year,
                'notes' => $existingAllocation ? $existingAllocation->notes : '',
                'is_allocated' => $existingAllocation ? true : false,
            ];
        }

        $this->showAllocationModal = true;
    }

    public function closeAllocationModal()
    {
        $this->showAllocationModal = false;
        $this->selectedEmployeeId = null;
        $this->selectedEmployee = null;
        $this->allocations = [];
    }

    public function saveAllocations()
    {
        try {
            $allocator = auth()->user();

            foreach ($this->allocations as $allocation) {
                if ($allocation['allocated_days'] > 0) {
                    $allocationRecord = EmployeeLeaveAllocation::allocateToEmployee(
                        $this->selectedEmployeeId,
                        $allocation['leave_type_id'],
                        $this->selectedYear,
                        $allocation['allocated_days'],
                        $allocator->id,
                        $allocation['notes']
                    );

                    // Log the allocation
                    AuditService::log(
                        $allocator,
                        'create',
                        $allocationRecord,
                        "Allocated {$allocation['allocated_days']} days of {$allocation['leave_type_name']} ".
                        "to {$this->selectedEmployee->name} for {$this->selectedYear}. Notes: {$allocation['notes']}",
                        'completed'
                    );
                }
            }

            $this->toast()->success("Leave allocations saved successfully for {$this->selectedEmployee->name}.")->send();
            $this->closeAllocationModal();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error saving allocations: '.$e->getMessage())->send();
        }
    }

    public function bulkAllocateDefaults()
    {
        try {
            $allocator = auth()->user();
            $employees = Employee::all();
            $leaveTypes = LeaveType::active()->get();
            $count = 0;

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    // Only create if not already allocated
                    $exists = EmployeeLeaveAllocation::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $this->selectedYear)
                        ->exists();

                    if (! $exists) {
                        EmployeeLeaveAllocation::allocateToEmployee(
                            $employee->id,
                            $leaveType->id,
                            $this->selectedYear,
                            $leaveType->default_days_per_year,
                            $allocator->id,
                            'Bulk allocation - default values'
                        );
                        $count++;
                    }
                }
            }

            $this->toast()->success("Bulk allocation completed! {$count} allocations created.")->send();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->toast()->error('Error during bulk allocation: '.$e->getMessage())->send();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedYear()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-module.leave-management.manage-allocations', [
            'rows' => $this->rows,
        ]);
    }
}
