<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveApplication;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class LeaveBalance extends BaseComponent
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $selectedYear;

    public $yearOptions = [];

    protected function getModelClass(): string
    {
        return EmployeeLeaveBalance::class;
    }

    public function getAllSelectableIds(): array
    {
        return [];
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->generateYearOptions();
        $this->initializeLeaveBalances();
    }

    protected function generateYearOptions()
    {
        $currentYear = now()->year;
        $this->yearOptions = [];

        for ($i = -1; $i <= 1; $i++) {
            $year = $currentYear + $i;
            $this->yearOptions[] = $year;
        }
    }

    protected function initializeLeaveBalances()
    {
        $employee = auth()->user();
        EmployeeLeaveBalance::initializeForEmployee($employee->id, $this->selectedYear);
    }

    public function updatedSelectedYear()
    {
        $this->initializeLeaveBalances();
    }

    public function render()
    {
        $employee = auth()->user();

        $balances = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('year', $this->selectedYear)
            ->with('leaveType')
            ->get();

        // Get leave history for selected year
        $leaveHistory = LeaveApplication::where('employee_id', $employee->id)
            ->whereYear('start_date', $this->selectedYear)
            ->with('leaveType')
            ->orderBy('start_date', 'desc')
            ->get();

        // Get statistics
        $totalAllocated = $balances->sum('total_days');
        $totalUsed = $balances->sum('used_days');
        $totalPending = $balances->sum('pending_days');
        $totalRemaining = $balances->sum('remaining_days');

        return view('livewire.branch-dashboard.employee-module.leave-management.leave-balance', [
            'balances' => $balances,
            'leaveHistory' => $leaveHistory,
            'totalAllocated' => $totalAllocated,
            'totalUsed' => $totalUsed,
            'totalPending' => $totalPending,
            'totalRemaining' => $totalRemaining,
        ]);
    }
}
