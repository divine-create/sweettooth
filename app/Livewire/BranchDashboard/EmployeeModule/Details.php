<?php

namespace App\Livewire\BranchDashboard\EmployeeModule;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveApplication;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Details extends Component
{
    public ?Employee $employee = null;

    public $leaveBalances;

    public $recentLeaveApplications;

    public $leaveStats;

    public ?string $profilePhotoUrl = null;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public function mount($employee_number = null, $id = null)
    {
        // Set b_id from URL parameter or current branch context
        $this->b_id = request()->query('b_id') ?? current_branch_id();

        if ($employee_number) {
            $employee = Employee::with(['department', 'branch', 'roles'])
                ->where('id', $id)
                ->where('employee_number', $employee_number)
                ->firstOrFail();
        } else {
            $employee = Employee::with(['department', 'branch', 'roles'])->findOrFail($id);
        }
        $this->employee = $employee;

        // Initialize safe defaults to avoid null offsets in views
        $this->leaveBalances = collect();
        $this->recentLeaveApplications = collect();
        $this->leaveStats = [
            'total_allocated' => 0,
            'total_used' => 0,
            'total_pending' => 0,
            'total_remaining' => 0,
            'pending_applications' => 0,
            'approved_this_year' => 0,
        ];

        // Set profile photo URL if available
        if ($employee->profile_photo) {
            $path = ltrim((string) $employee->profile_photo, '/');

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $this->profilePhotoUrl = $path;
                return;
            }

            // Prefer public/uploads if file exists there
            $uploadsPath = public_path('uploads/' . ltrim($path, '/'));
            if (\Illuminate\Support\Facades\File::exists($uploadsPath)) {
                $this->profilePhotoUrl = asset('uploads/' . ltrim($path, '/'));
                return;
            }

            // Fall back to storage/ (public disk)
            if (str_starts_with($path, 'storage/')) {
                $this->profilePhotoUrl = asset($path);
                return;
            }

            $this->profilePhotoUrl = asset('storage/' . ltrim($path, '/'));
        }

        // Load leave information
        $this->loadLeaveData();
    }

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
    }

    protected function loadLeaveData()
    {
        if (! $this->employee) {
            return;
        }

        $currentYear = now()->year;

        // Get leave balances for current year
        $this->leaveBalances = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get();

        // Get recent leave applications (last 10)
        $this->recentLeaveApplications = LeaveApplication::where('employee_id', $this->employee->id)
            ->with('leaveType')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate leave statistics
        $this->leaveStats = [
            'total_allocated' => $this->leaveBalances->sum('total_days'),
            'total_used' => $this->leaveBalances->sum('used_days'),
            'total_pending' => $this->leaveBalances->sum('pending_days'),
            'total_remaining' => $this->leaveBalances->sum('remaining_days'),
            'pending_applications' => LeaveApplication::where('employee_id', $this->employee->id)
                ->where('status', 'pending')
                ->count(),
            'approved_this_year' => LeaveApplication::where('employee_id', $this->employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->count(),
        ];
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.employee-module.details', [
            'employee' => $this->employee,
            'leaveBalances' => $this->leaveBalances,
            'recentLeaveApplications' => $this->recentLeaveApplications,
            'leaveStats' => $this->leaveStats,
            'profilePhotoUrl' => $this->profilePhotoUrl,
        ]);
    }
}
