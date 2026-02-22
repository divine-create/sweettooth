<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\ClockInModule;

use App\Livewire\BaseComponent;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class EmployeeHistory extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?int $employee = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $selectedDepartment = null;

    public int $quantity = 20;

    public ?Employee $currentEmployee = null;

    public function mount()
    {
        $this->b_id = current_branch_id();

        if ($this->employee) {
            $this->currentEmployee = Employee::find($this->employee);
            if (! $this->currentEmployee) {
                abort(404, 'Employee not found');
            }
        }

        // Default: last 30 days
        $this->dateTo = $this->dateTo ?: today()->format('Y-m-d');
        $this->dateFrom = $this->dateFrom ?: today()->subDays(30)->format('Y-m-d');
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
     * Get employee's clock-in history
     */
    protected function getHistoryQuery()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $query = Shift::query()
            ->where('shifts.branch_id', $branchId)
            ->where('shifts.employee_id', $this->employee)
            ->with(['employee', 'department'])
            ->whereNotNull('clock_in')
            ->orderByDesc('shifts.shift_date')
            ->orderByDesc('shifts.clock_in');

        // Date range filter
        if ($this->dateFrom) {
            $query->whereDate('shifts.shift_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('shifts.shift_date', '<=', $this->dateTo);
        }

        // Department filter
        if ($this->selectedDepartment) {
            $query->where('shifts.department_id', $this->selectedDepartment);
        }

        return $query;
    }

    /**
     * Determine if clock-in was early or late
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
            ];
        } elseif ($minutesDifference === 0) {
            return [
                'status' => 'on_time',
                'minutes' => 0,
                'label' => 'On Time',
            ];
        } else {
            return [
                'status' => 'late',
                'minutes' => $minutesDifference,
                'label' => $minutesDifference.' min late',
            ];
        }
    }

    /**
     * Get summary statistics
     */
    public function getStats()
    {
        $shifts = $this->getHistoryQuery()->get();

        $totalShifts = $shifts->count();
        $totalHoursWorked = 0;
        $lateCount = 0;
        $earlyCount = 0;
        $onTimeCount = 0;

        foreach ($shifts as $shift) {
            // Count early/late
            $status = $this->getTimeStatus($shift);
            match ($status['status']) {
                'late' => $lateCount++,
                'early' => $earlyCount++,
                'on_time' => $onTimeCount++,
            };

            // Sum hours
            if ($shift->clock_out) {
                $totalHoursWorked += $shift->clock_in->diffInMinutes($shift->clock_out);
            }
        }

        return [
            'total_shifts' => $totalShifts,
            'total_hours_worked' => intdiv($totalHoursWorked, 60),
            'total_minutes_worked' => $totalHoursWorked % 60,
            'average_hours_per_shift' => $totalShifts > 0 ? round($totalHoursWorked / 60 / $totalShifts, 1) : 0,
            'late_count' => $lateCount,
            'early_count' => $earlyCount,
            'on_time_count' => $onTimeCount,
            'late_percentage' => $totalShifts > 0 ? round(($lateCount / $totalShifts) * 100, 1) : 0,
        ];
    }

    /**
     * Reset filters to default (last 30 days)
     */
    public function resetFilters()
    {
        $this->dateFrom = today()->subDays(30)->format('Y-m-d');
        $this->dateTo = today()->format('Y-m-d');
        $this->selectedDepartment = null;
        $this->resetPage();
    }

    /**
     * Export to CSV
     */
    public function exportCsv()
    {
        $shifts = $this->getHistoryQuery()->get();

        $csv = "Date,Clock In,Clock Out,Duration (hours),Shift Type,Department,Status\n";
        foreach ($shifts as $shift) {
            $status = $this->getTimeStatus($shift);
            $duration = $shift->clock_out
                ? $shift->clock_in->diffInMinutes($shift->clock_out) / 60
                : 0;

            $clockOut = $shift->clock_out ? $shift->clock_out->format('H:i') : '-';

            $csv .= "\"{$shift->shift_date->format('Y-m-d')}\",";
            $csv .= "\"{$shift->clock_in->format('H:i')}\",";
            $csv .= "\"{$clockOut}\",";
            $csv .= "\"{$duration}\",";
            $csv .= "\"{$shift->shift_type}\",";
            $csv .= "\"{$shift->department->name}\",";
            $csv .= "\"{$status['label']}\"\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'clock-in-history-'.$this->currentEmployee->name.'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $shifts = $this->getHistoryQuery()->paginate($this->quantity);
        $stats = $this->getStats();

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

        return view('livewire.branch-dashboard.employee-module.clock-in-module.employee-history', [
            'shifts' => $shifts,
            'stats' => $stats,
            'departments' => $departments,
            'currentEmployee' => $this->currentEmployee,
            'b_id' => $branchId,
        ]);
    }
}
