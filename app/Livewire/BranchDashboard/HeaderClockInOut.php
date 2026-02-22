<?php

namespace App\Livewire\BranchDashboard;

use Livewire\Component;
use Livewire\Attributes\{On, Url};
use App\Models\{Shift as ShiftModel, Branch, Employee, Department};
use App\Services\SalesWorkflowService;
use Carbon\Carbon;
use TallStackUi\Traits\Interactions;

class HeaderClockInOut extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $currentShift = null;
    public $hasActiveShift = false;
    public $timeWorked = '0h 0m';

    public function mount()
    {
        $this->loadCurrentShift();
    }

    public function loadCurrentShift()
    {
        $employee_id = auth()->id();

        if (!$employee_id) {
            return;
        }

        // Get today's active shift for this employee
        $this->currentShift = ShiftModel::where('employee_id', $employee_id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();

        $this->hasActiveShift = $this->currentShift !== null;

        if ($this->hasActiveShift) {
            $this->calculateTimeWorked();
        }
    }

    public function calculateTimeWorked()
    {
        if (!$this->currentShift || !$this->currentShift->clock_in) {
            $this->timeWorked = '0h 0m';
            return;
        }

        $now = Carbon::now();
        $totalMinutes = (int) floor($this->currentShift->clock_in->diffInMinutes($now));
        $hours = (int) floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $this->timeWorked = "{$hours}h {$minutes}m";
    }

    public function redirectToShiftPage()
    {
        $branchId = $this->b_id ?: request()->query('b_id');

        return redirect()->route('branch-dashboard.select_shift', ['b_id' => $branchId]);
    }

    public function clockOut()
    {
        try {
            if (!$this->currentShift) {
                $this->toast()->error('No active shift found!')->send();
                return;
            }

            // Check if this is a sales employee (skip for super admins)
            $isSalesEmployee = $this->checkIfSalesEmployee();
            $isSuperAdmin = is_super_admin();
            $canAccessAllBranches = can_access_all_branches();

            \Log::info('Clock out initiated', [
                'employee_id' => auth()->id(),
                'shift_id' => $this->currentShift->id,
                'is_sales_employee' => $isSalesEmployee,
                'is_super_admin' => $isSuperAdmin,
                'can_access_all_branches' => $canAccessAllBranches,
                'will_redirect_to_shift_closing' => $isSalesEmployee && !$isSuperAdmin && !$canAccessAllBranches
            ]);

            // Update shift with clock out time
            $this->currentShift->clock_out = Carbon::now();

            // For sales employees, set to 'active' to trigger shift closing workflow
            // For other employees, complete the shift immediately
            if ($isSalesEmployee && !$isSuperAdmin && !$canAccessAllBranches) {
                $this->currentShift->status = 'active'; // Keep active until shift closing
                $this->currentShift->workflow_state = 'shift_closing';

                // Update metadata
                $metadata = $this->currentShift->metadata ?? [];
                $metadata['clock_out_at'] = now()->toIso8601String();
                $this->currentShift->metadata = $metadata;
            } else {
                $this->currentShift->status = 'closed';
                $this->currentShift->workflow_state = 'completed';
            }

            $this->currentShift->save();

            // Calculate total hours worked
            $workedMinutes = (int) floor(Carbon::parse($this->currentShift->clock_in)->diffInMinutes($this->currentShift->clock_out));
            $totalHours = (int) floor($workedMinutes / 60);
            $totalMinutes = $workedMinutes % 60;

            // Dispatch event to update other components
            $this->dispatch('shift-updated');
            $this->dispatch('workflow-state-changed');

            // For sales employees, redirect to shift closing
            if ($isSalesEmployee && !$isSuperAdmin && !$canAccessAllBranches) {
                $branchId = $this->b_id ?: current_branch_id();
                $departmentSlug = $this->getEmployeeDepartmentSlug();

                \Log::info('Redirecting sales employee to shift closing', [
                    'employee_id' => auth()->id(),
                    'branch_id' => $branchId,
                    'department_slug' => $departmentSlug
                ]);

                $this->toast()->success("Clocked out! Please complete shift closing.")->send();

                return $this->redirect(
                    route('branch-dashboard.sales-dashboard.shift-closing.index', [
                        'salesDeptSlug' => $departmentSlug,
                        'b_id' => $branchId
                    ]),
                    navigate: true
                );
            }

            $this->toast()->success("Clocked out! Total time: {$totalHours}h {$totalMinutes}m")->send();

            // Reset state
            $this->currentShift = null;
            $this->hasActiveShift = false;
            $this->timeWorked = '0h 0m';

            // Refresh the component
            $this->loadCurrentShift();

        } catch (\Exception $e) {
            $this->toast()->error('Error clocking out: ' . $e->getMessage())->send();
        }
    }

    /**
     * Check if current employee is in a sales department
     */
    protected function checkIfSalesEmployee(): bool
    {
        $employee = auth()->user();
        if (!$employee || !$employee->department_id) {
            \Log::debug('Sales check failed: No employee or department_id', [
                'employee_id' => $employee?->id,
                'department_id' => $employee?->department_id
            ]);
            return false;
        }

        $department = Department::with('category')->find($employee->department_id);
        if (!$department) {
            \Log::debug('Sales check failed: Department not found', [
                'department_id' => $employee->department_id
            ]);
            return false;
        }

        // Category-based check
        if ($department->category && strtolower($department->category->name) === 'sales') {
            \Log::debug('Sales check passed via category', [
                'department' => $department->name,
                'category' => $department->category->name
            ]);
            return true;
        }

        \Log::debug('Sales check failed: Not a sales department', [
            'department' => $department->name,
            'category' => $department->category?->name,
            'slug' => $department->slug
        ]);
        return false;
    }

    /**
     * Get employee's department slug
     */
    protected function getEmployeeDepartmentSlug(): ?string
    {
        $employee = auth()->user();
        if (!$employee || !$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }

    #[On('shift-updated')]
    public function refreshShift()
    {
        $this->loadCurrentShift();
    }

    public function render()
    {
        // Update time worked on each render
        if ($this->hasActiveShift) {
            $this->calculateTimeWorked();
        }

        return view('livewire.branch-dashboard.header-clock-in-out');
    }
}
