<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\ClockInModule;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class TodayIndex extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $selectedDepartment = null;

    public string $sortBy = 'clock_in'; // clock_in, name, department

    public string $sortDirection = 'desc'; // latest first

    public string $searchEmployee = '';

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    protected function getModelClass(): string
    {
        return Shift::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    /**
     * Get today's clock-ins with filters
     */
    protected function getTodayClockIns()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $query = Shift::query()
            ->where('shifts.branch_id', $branchId)
            ->whereDate('shifts.shift_date', today())
            ->with(['employee', 'department'])
            ->whereNotNull('clock_in'); // Only those who clocked in

        // Filter by department
        if ($this->selectedDepartment) {
            $query->where('shifts.department_id', $this->selectedDepartment);
        }

        // Search by employee name or email
        if ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('email', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('employee_number', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        // Sort
        if ($this->sortBy === 'name') {
            $query->join('employees', 'shifts.employee_id', '=', 'employees.id')
                ->orderBy('employees.name', $this->sortDirection)
                ->select('shifts.*');
        } elseif ($this->sortBy === 'department') {
            $query->join('departments', 'shifts.department_id', '=', 'departments.id')
                ->orderBy('departments.name', $this->sortDirection)
                ->select('shifts.*');
        } else {
            $query->orderBy('shifts.clock_in', $this->sortDirection);
        }

        return $query->get();
    }

    /**
     * Format clock-in time relative to shift start
     */
    public function getTimeStatus($shift)
    {
        // Define expected start times (configurable)
        $expectedStartTimes = [
            'morning' => '06:00',
            'afternoon' => '14:00',
            'night' => '22:00',
        ];

        $expected = Carbon::createFromTimeString($expectedStartTimes[$shift->shift_type] ?? '06:00');
        $actual = $shift->clock_in;

        $minutesDifference = $actual->diffInMinutes($expected);

        if ($minutesDifference < 0) {
            return [
                'status' => 'early',
                'minutes' => abs($minutesDifference),
                'label' => abs($minutesDifference).' min early',
                'color' => 'green',
            ];
        } elseif ($minutesDifference === 0) {
            return [
                'status' => 'on_time',
                'minutes' => 0,
                'label' => 'On Time',
                'color' => 'blue',
            ];
        } else {
            return [
                'status' => 'late',
                'minutes' => $minutesDifference,
                'label' => $minutesDifference.' min late',
                'color' => 'red',
            ];
        }
    }

    /**
     * Get statistics for today
     */
    public function getTodayStats()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $todayShifts = Shift::where('shifts.branch_id', $branchId)
            ->whereDate('shifts.shift_date', today())
            ->whereNotNull('clock_in')
            ->get();

        $totalClocked = $todayShifts->count();
        $lateCount = 0;
        $earlyCount = 0;
        $onTimeCount = 0;

        foreach ($todayShifts as $shift) {
            $status = $this->getTimeStatus($shift);
            match ($status['status']) {
                'late' => $lateCount++,
                'early' => $earlyCount++,
                'on_time' => $onTimeCount++,
            };
        }

        return [
            'total_clocked' => $totalClocked,
            'late_count' => $lateCount,
            'early_count' => $earlyCount,
            'on_time_count' => $onTimeCount,
        ];
    }

    /**
     * Navigate to employee history
     */
    public function viewEmployeeHistory($employeeId)
    {
        return $this->redirect(
            route('branch-dashboard.clock-in-board.employee-history', [
                'b_id' => $this->b_id,
                'employee' => $employeeId,
            ])
        );
    }

    public function render()
    {
        $branchId = $this->b_id ?: current_branch_id();
        $branch = current_branch();

        $shifts = $this->getTodayClockIns();
        $stats = $this->getTodayStats();

        // Get departments - try both specific branch and null branch_id
        $departments = Department::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        })
            ->orderBy('name')
            ->get();

        // If still empty, try without branch filter
        if ($departments->isEmpty()) {
            $departments = Department::orderBy('name')->get();
        }

        return view('livewire.branch-dashboard.employee-module.clock-in-module.today-index', [
            'shifts' => $shifts,
            'stats' => $stats,
            'departments' => $departments,
            'branch' => $branch,
            'b_id' => $branchId,
        ]);
    }
}
