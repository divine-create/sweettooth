<?php

namespace App\Livewire\BranchDashboard\ShiftManagement;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftConfiguration;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ShiftAssignment extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $employees;
    public $shifts;
    public $departments;
    public $shiftConfigurations;

    // Form fields
    public $employee_id;
    public $shift_date;
    public $shift_configuration_id;
    public $department_id;
    public $notes;

    public $showAssignmentForm = false;

    public $search = '';
    public $selectedDepartment = null;
    public $selectedShiftDate = null;

    public function mount()
    {
        $this->b_id = current_branch_id();
        $this->shift_date = today()->format('Y-m-d');
        $this->loadData();
    }

    protected function getModelClass(): string
    {
        return Shift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function loadData()
    {
        $branchId = $this->b_id ?: current_branch_id();

        // Load employees for this branch
        $this->employees = Employee::where('branch_id', $branchId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedDepartment, function ($query) {
                $query->where('department_id', $this->selectedDepartment);
            })
            ->get();

        // Load shift configurations for this branch
        $this->shiftConfigurations = ShiftConfiguration::where('branch_id', $branchId)->get();

        // Load departments for this branch
        $this->departments = Department::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id');
        })->get();

        // Load shifts for the selected date
        $this->shifts = Shift::where('branch_id', $branchId)
            ->whereDate('shift_date', $this->selectedShiftDate ?: today())
            ->when($this->selectedDepartment, function ($query) {
                $query->where('department_id', $this->selectedDepartment);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_number', 'like', '%' . $this->search . '%');
                });
            })
            ->with(['employee', 'department', 'configuration'])
            ->get();
    }

    public function assignShift()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_date' => 'required|date',
            'shift_configuration_id' => 'required|exists:shift_configurations,id',
        ]);

        // Get the shift configuration to get shift type and other details
        $config = ShiftConfiguration::findOrFail($this->shift_configuration_id);

        // Check for conflicts
        $existingShift = Shift::where('employee_id', $this->employee_id)
            ->whereDate('shift_date', $this->shift_date)
            ->where('status', '!=', 'closed')
            ->first();

        if ($existingShift) {
            $this->toast()->error('Employee already has an active shift on this date!')->send();
            return;
        }

        // Create the shift assignment
        Shift::create([
            'branch_id' => $this->b_id,
            'employee_id' => $this->employee_id,
            'department_id' => $this->department_id,
            'shift_date' => $this->shift_date,
            'shift_type' => $config->shift_type,
            'status' => 'scheduled', // Scheduled until employee clocks in
            'notes' => $this->notes,
            'metadata' => [
                'assigned_by' => auth()->id(),
                'config_id' => $config->id,
            ],
        ]);

        $this->toast()->success('Shift assigned successfully!')->send();
        $this->resetForm();
        $this->showAssignmentForm = false;
        $this->loadData();
    }

    public function removeAssignment($shiftId)
    {
        $shift = Shift::findOrFail($shiftId);
        
        if ($shift->status === 'active') {
            $this->toast()->error('Cannot remove an active shift. Clock out first.')->send();
            return;
        }
        
        $shift->delete();
        $this->toast()->success('Shift assignment removed successfully!')->send();
        $this->loadData();
    }

    public function assign()
    {
        $this->resetForm();
        $this->showAssignmentForm = true;
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showAssignmentForm = false;
    }

    private function resetForm()
    {
        $this->employee_id = '';
        $this->shift_date = today()->format('Y-m-d');
        $this->shift_configuration_id = '';
        $this->department_id = '';
        $this->notes = '';
    }

    public function updatedSearch()
    {
        $this->loadData();
    }

    public function updatedSelectedShiftDate()
    {
        $this->loadData();
    }

    public function updatedSelectedDepartment()
    {
        $this->loadData();
    }

    public function render()
    {
        $branch = current_branch();
        $this->loadData();

        return view('livewire.branch-dashboard.shift-management.shift-assignment', [
            'branch' => $branch,
        ]);
    }
}
