<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\ClockInModule;

use App\Livewire\BaseComponent;
use App\Models\Department;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class GeneralClockInBoard extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $selectedDepartment = null;

    public string $sortBy = 'clock_in';

    public string $sortDirection = 'desc';

    public string $searchEmployee = '';

    public int $quantity = 50;

    public function mount()
    {
        $this->b_id = current_branch_id();

        // Default: last 7 days
        $this->dateTo = $this->dateTo ?: today()->format('Y-m-d');
        $this->dateFrom = $this->dateFrom ?: today()->subDays(7)->format('Y-m-d');
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
     * Get clock-ins for date range with filters
     */
    protected function getGeneralClockIns()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $query = Shift::query()
            ->where('shifts.branch_id', $branchId)
            ->with(['employee', 'department'])
            ->whereNotNull('clock_in'); // Only those who clocked in

        // Date range filter
        if ($this->dateFrom) {
            $query->whereDate('shifts.shift_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('shifts.shift_date', '<=', $this->dateTo);
        }

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
            $query->orderBy('shifts.shift_date', $this->sortDirection)
                ->orderBy('shifts.clock_in', $this->sortDirection);
        }

        return $query;
    }

    /**
     * Format clock-in time relative to shift start
     */
    public function getTimeStatus($shift)
    {
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
     * Get statistics for date range
     */
    public function getGeneralStats()
    {
        $shifts = $this->getGeneralClockIns()->get();

        $totalClocked = $shifts->count();
        $lateCount = 0;
        $earlyCount = 0;
        $onTimeCount = 0;
        $totalMinutesWorked = 0;

        foreach ($shifts as $shift) {
            $status = $this->getTimeStatus($shift);
            match ($status['status']) {
                'late' => $lateCount++,
                'early' => $earlyCount++,
                'on_time' => $onTimeCount++,
            };

            if ($shift->clock_out) {
                $totalMinutesWorked += $shift->clock_in->diffInMinutes($shift->clock_out);
            }
        }

        return [
            'total_clocked' => $totalClocked,
            'late_count' => $lateCount,
            'early_count' => $earlyCount,
            'on_time_count' => $onTimeCount,
            'total_hours_worked' => intdiv($totalMinutesWorked, 60),
            'late_percentage' => $totalClocked > 0 ? round(($lateCount / $totalClocked) * 100, 1) : 0,
        ];
    }

    /**
     * Reset filters to default (last 7 days)
     */
    public function resetFilters()
    {
        $this->dateFrom = today()->subDays(7)->format('Y-m-d');
        $this->dateTo = today()->format('Y-m-d');
        $this->selectedDepartment = null;
        $this->searchEmployee = '';
        $this->sortBy = 'clock_in';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    /**
     * Export to CSV
     */
    public function exportCsv()
    {
        $shifts = $this->getGeneralClockIns()->get();

        $csv = "Date,Employee,Employee #,Department,Shift Type,Clock In,Clock Out,Duration (hours),Status\n";
        foreach ($shifts as $shift) {
            $status = $this->getTimeStatus($shift);
            $duration = $shift->clock_out
                ? $shift->clock_in->diffInMinutes($shift->clock_out) / 60
                : 0;

            $clockOut = $shift->clock_out ? $shift->clock_out->format('H:i') : '-';

            $csv .= "\"{$shift->shift_date->format('Y-m-d')}\",";
            $csv .= "\"{$shift->employee->name}\",";
            $csv .= "\"{$shift->employee->employee_number}\",";
            $csv .= "\"{$shift->department->name}\",";
            $csv .= "\"{$shift->shift_type}\",";
            $csv .= "\"{$shift->clock_in->format('H:i')}\",";
            $csv .= "\"{$clockOut}\",";
            $csv .= "\"{$duration}\",";
            $csv .= "\"{$status['label']}\"\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'clock-in-board-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $branchId = $this->b_id ?: current_branch_id();
        $branch = current_branch();

        $shifts = $this->getGeneralClockIns()->paginate($this->quantity);
        $stats = $this->getGeneralStats();

        // Get departments for this branch
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

        return view('livewire.branch-dashboard.employee-module.clock-in-module.general-clock-in-board', [
            'shifts' => $shifts,
            'stats' => $stats,
            'departments' => $departments,
            'branch' => $branch,
            'b_id' => $branchId,
        ]);
    }
}
