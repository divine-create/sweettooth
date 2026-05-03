<?php

namespace App\Livewire\BranchDashboard;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift as ShiftModel;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
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

        if (! $employee_id) {
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
        if (! $this->currentShift || ! $this->currentShift->clock_in) {
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
            if (! $this->currentShift) {
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
            ]);

            // Update shift with clock out time
            $this->currentShift->clock_out = Carbon::now();

            // For sales employees, auto-complete shift closing
            // For other employees, complete the shift immediately
            if ($isSalesEmployee && ! $isSuperAdmin && ! $canAccessAllBranches) {
                // Auto-complete shift closing - use expected values as actual
                $this->currentShift->status = 'closed';
                $this->currentShift->workflow_state = 'completed';

                // Update metadata with auto-closing info
                $metadata = $this->currentShift->metadata ?? [];
                $metadata['clock_out_at'] = now()->toIso8601String();
                $metadata['shift_closing_completed'] = true;
                $metadata['shift_closed_at'] = now()->toIso8601String();
                $metadata['shift_closing_type'] = 'automatic';
                $this->currentShift->metadata = $metadata;

                // Calculate total hours worked
                $workedMinutes = (int) floor(Carbon::parse($this->currentShift->clock_in)->diffInMinutes($this->currentShift->clock_out));
                $totalHours = (int) floor($workedMinutes / 60);
                $totalMinutes = $workedMinutes % 60;

                $this->currentShift->save();

                // Dispatch event to update other components
                $this->dispatch('shift-updated');
                $this->dispatch('workflow-state-changed');

                $this->toast()->success("Clocked out! Shift completed automatically. Total time: {$totalHours}h {$totalMinutes}m")->send();

                // Reset state
                $this->currentShift = null;
                $this->hasActiveShift = false;
                $this->timeWorked = '0h 0m';
                $this->loadCurrentShift();

                // Redirect to dashboard
                return $this->redirect(route('branch-dashboard.index', ['b_id' => current_branch_id()]), navigate: true);
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

            $this->toast()->success("Clocked out! Total time: {$totalHours}h {$totalMinutes}m")->send();

            // Reset state
            $this->currentShift = null;
            $this->hasActiveShift = false;
            $this->timeWorked = '0h 0m';

            // Refresh the component
            $this->loadCurrentShift();

        } catch (\Exception $e) {
            $this->toast()->error('Error clocking out: '.$e->getMessage())->send();
        }
    }

    /**
     * Check if current employee is in a sales department
     */
    protected function checkIfSalesEmployee(): bool
    {
        $employee = auth()->user();
        if (! $employee || ! $employee->department_id) {
            \Log::debug('Sales check failed: No employee or department_id', [
                'employee_id' => $employee?->id,
                'department_id' => $employee?->department_id,
            ]);

            return false;
        }

        $department = Department::with('category')->find($employee->department_id);
        if (! $department) {
            \Log::debug('Sales check failed: Department not found', [
                'department_id' => $employee->department_id,
            ]);

            return false;
        }

        // Category-based check
        if ($department->category && strtolower($department->category->name) === 'sales') {
            \Log::debug('Sales check passed via category', [
                'department' => $department->name,
                'category' => $department->category->name,
            ]);

            return true;
        }

        \Log::debug('Sales check failed: Not a sales department', [
            'department' => $department->name,
            'category' => $department->category?->name,
            'slug' => $department->slug,
        ]);

        return false;
    }

    /**
     * Get employee's department slug
     */
    protected function getEmployeeDepartmentSlug(): ?string
    {
        $employee = auth()->user();
        if (! $employee || ! $employee->department_id) {
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
