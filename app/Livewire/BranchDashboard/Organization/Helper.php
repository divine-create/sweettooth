<?php

namespace App\Livewire\BranchDashboard\Organization;

use Livewire\Component;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftConfiguration;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Appraisal;
use App\Models\PerformanceGoal;
use App\Models\Department;

#[Layout('components.layouts.app.branch-dashboard')]
class Helper extends Component
{
    use Interactions;

    public $b_id;

    public function mount()
    {
        $this->b_id = request()->query('b_id') ?? current_branch_id();
    }

    public function getEmployeeStatsProperty()
    {
        $branchId = $this->b_id ?: current_branch_id();
        
        return [
            'total_employees' => Employee::where('branch_id', $branchId)->count(),
            'active_employees' => Employee::where('branch_id', $branchId)
                ->where('is_active', true)
                ->count(),
            'on_leave' => Employee::where('branch_id', $branchId)
                ->where('employment_status', 'on_leave')
                ->count(),
            'on_probation' => Employee::where('branch_id', $branchId)
                ->whereNotNull('probation_end_date')
                ->whereDate('probation_end_date', '>=', now()->toDateString())
                ->count(),
            'terminated' => Employee::where('branch_id', $branchId)
                ->whereNotNull('termination_date')
                ->count(),
        ];
    }

    public function getShiftStatsProperty()
    {
        $branchId = $this->b_id ?: current_branch_id();
        
        $todayShifts = Shift::where('branch_id', $branchId)
            ->whereDate('shift_date', today())
            ->get();

        $shiftConfigs = ShiftConfiguration::where('branch_id', $branchId)->get();

        return [
            'total_configs' => $shiftConfigs->count(),
            'today_active' => $todayShifts->where('status', 'active')->count(),
            'today_total' => $todayShifts->count(),
            'upcoming_shifts' => Shift::where('branch_id', $branchId)
                ->whereBetween('shift_date', [today(), today()->addDays(7)])
                ->count(),
        ];
    }

    public function getLeaveStatsProperty()
    {
        $branchId = $this->b_id ?: current_branch_id();
        $currentYear = now()->year;

        return [
            'pending_applications' => LeaveApplication::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'pending')
                ->count(),
            'approved_this_year' => LeaveApplication::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->count(),
            'rejected_this_year' => LeaveApplication::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'rejected')
                ->whereYear('start_date', $currentYear)
                ->count(),
            'leave_types' => LeaveType::active()->count(),
        ];
    }

    public function getPerformanceStatsProperty()
    {
        $branchId = $this->b_id ?: current_branch_id();

        $hasPerformanceGoals = \Illuminate\Support\Facades\Schema::hasTable('performance_goals');

        return [
            'total_goals' => $hasPerformanceGoals
                ? PerformanceGoal::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))->count()
                : 0,
            'completed_goals' => $hasPerformanceGoals
                ? PerformanceGoal::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                    ->where('status', 'completed')
                    ->count()
                : 0,
            'in_progress_goals' => $hasPerformanceGoals
                ? PerformanceGoal::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                    ->where('status', 'in_progress')
                    ->count()
                : 0,
            'total_appraisals' => Appraisal::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))->count(),
        ];
    }

    public function render()
    {
        return view('livewire.branch-dashboard.organization.helper', [
            'employeeStats' => $this->employeeStats,
            'shiftStats' => $this->shiftStats,
            'leaveStats' => $this->leaveStats,
            'performanceStats' => $this->performanceStats,
        ]);
    }
}
