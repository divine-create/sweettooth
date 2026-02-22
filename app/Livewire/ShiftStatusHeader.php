<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Shift;
use App\Models\Department;
use App\Services\ShiftNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ShiftStatusHeader extends Component
{
    public $hasActiveShift = false;
    public $shiftType = null;
    public $timeWorked = '0h 0m';
    public $shiftWarnings = false;
    public $shiftEndingSoon = false;
    public $autoClockOutWarning = false;
    public $minutesToEnd = 0;
    public $minutesToAutoClock = 0;

    protected $listeners = [
        'shift-updated' => 'refreshShiftStatus',
        'refresh-shift-status' => 'refreshShiftStatus'
    ];

    public function mount()
    {
        $this->refreshShiftStatus();
    }

    public function refreshShiftStatus()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Get active shift
        $activeShift = Shift::where('employee_id', $user->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->with('configuration')
            ->first();

        if ($activeShift) {
            $this->hasActiveShift = true;
            $this->shiftType = ucfirst(str_replace('_', ' ', $activeShift->shift_type));

            // Calculate time worked
            if ($activeShift->clock_in) {
                $endTime = $activeShift->clock_out ?? Carbon::now();
                $totalMinutes = (int) floor($activeShift->clock_in->diffInMinutes($endTime));
                $hours = (int) floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $this->timeWorked = "{$hours}h {$minutes}m";
            }

            // Check for warnings
            $this->checkShiftWarnings($activeShift);
        } else {
            $this->hasActiveShift = false;
            $this->shiftType = null;
            $this->timeWorked = '0h 0m';
            $this->shiftWarnings = false;
            $this->shiftEndingSoon = false;
            $this->autoClockOutWarning = false;
        }
    }

    protected function checkShiftWarnings(Shift $shift)
    {
        if (!$shift->configuration) {
            $this->shiftWarnings = false;
            return;
        }

        $config = $shift->configuration;
        $now = Carbon::now();

        // Calculate expected end time
        $expectedEnd = Carbon::createFromTimeString($config->end_time)
            ->setDateFrom($shift->shift_date);

        // Calculate auto clock out time
        $autoClockOutTime = $expectedEnd->copy()->addMinutes($config->auto_clock_out_minutes);

        $minutesToEnd = $now->diffInMinutes($expectedEnd, false);
        $minutesToAutoClock = $now->diffInMinutes($autoClockOutTime, false);

        // Check if warnings are needed
        $this->shiftEndingSoon = $minutesToEnd > 0 && $minutesToEnd <= 30;
        $this->autoClockOutWarning = $minutesToAutoClock > 0 && $minutesToAutoClock <= 10;
        $this->shiftWarnings = $this->shiftEndingSoon || $this->autoClockOutWarning;

        $this->minutesToEnd = max(0, $minutesToEnd);
        $this->minutesToAutoClock = max(0, $minutesToAutoClock);
    }

    public function clockOut()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $activeShift = Shift::where('employee_id', $user->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();

        if ($activeShift) {
            // Check if this is a sales employee
            $isSalesEmployee = $this->checkIfSalesEmployee($user);
            $isSuperAdmin = is_super_admin();
            $canAccessAllBranches = can_access_all_branches();

            \Log::info('ShiftStatusHeader: Clock out initiated', [
                'employee_id' => $user->id,
                'shift_id' => $activeShift->id,
                'is_sales_employee' => $isSalesEmployee,
                'is_super_admin' => $isSuperAdmin,
                'will_redirect_to_shift_closing' => $isSalesEmployee && !$isSuperAdmin && !$canAccessAllBranches
            ]);

            $activeShift->clock_out = Carbon::now();

            // For sales employees, keep shift active for shift closing workflow
            if ($isSalesEmployee && !$isSuperAdmin && !$canAccessAllBranches) {
                $activeShift->status = 'active'; // Keep active until shift closing
                $activeShift->workflow_state = 'shift_closing';

                // Update metadata
                $metadata = $activeShift->metadata ?? [];
                $metadata['clock_out_at'] = now()->toIso8601String();
                $activeShift->metadata = $metadata;
                $activeShift->save();

                $this->dispatch('shift-updated');
                $this->dispatch('workflow-state-changed');

                // Redirect to shift closing
                $departmentSlug = $this->getEmployeeDepartmentSlug($user);
                $branchId = current_branch_id();

                \Log::info('ShiftStatusHeader: Redirecting to shift closing', [
                    'employee_id' => $user->id,
                    'branch_id' => $branchId,
                    'department_slug' => $departmentSlug
                ]);

                session()->flash('info', 'Clocked out! Please complete shift closing.');

                return $this->redirect(
                    route('branch-dashboard.sales-dashboard.shift-closing.index', [
                        'salesDeptSlug' => $departmentSlug,
                        'b_id' => $branchId
                    ]),
                    navigate: true
                );
            }

            // Non-sales employees: close immediately
            $activeShift->status = 'closed';
            $activeShift->workflow_state = 'completed';
            $activeShift->save();

            // Send completion notification
            $notificationService = app(ShiftNotificationService::class);
            $notificationService->sendShiftCompletedNotification($activeShift, 'manual');

            $this->dispatch('shift-updated');
            $this->refreshShiftStatus();

            session()->flash('success', 'Successfully clocked out!');
        }
    }

    /**
     * Check if employee is in a sales department
     */
    protected function checkIfSalesEmployee($employee): bool
    {
        if (!$employee || !$employee->department_id) {
            return false;
        }

        $department = Department::with('category')->find($employee->department_id);
        if (!$department) {
            return false;
        }

        return $department->category && strtolower($department->category->name) === 'sales';
    }

    /**
     * Get employee's department slug
     */
    protected function getEmployeeDepartmentSlug($employee): ?string
    {
        if (!$employee || !$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }

    public function render()
    {
        return view('livewire.shift-status-header');
    }
}
