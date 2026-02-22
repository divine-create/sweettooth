<?php

namespace App\Livewire\Dashboards;

use App\Models\Employee;
use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Layout('components.layouts.app.branch-dashboard')]
class HRDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        $this->verifyAccess();
    }

    private function verifyAccess(): void
    {
        $user = auth()->user();
        $role = $this->getUserRoleName();
        
        // Log for debugging
        $allRoles = [];
        if ($user && is_object($user) && method_exists($user, 'getRoleNames')) {
            $allRoles = $user->getRoleNames()->toArray();
        }
        
        \Log::info('HRDashboard access check', [
            'user_id' => $user?->id ?? 'null',
            'user_email' => $user?->email ?? 'null',
            'primary_role_from_method' => $role,
            'all_roles_from_getRoleNames' => $allRoles,
        ]);
        
        $isAllowed = \App\Services\SidebarVisibilityService::canSeeEmployeeManagement($user)
            || \App\Services\SidebarVisibilityService::isAdmin($user)
            || \App\Services\SidebarVisibilityService::isSuperAdmin($user);
        
        if (!$isAllowed) {
            \Log::warning('Unauthorized HR dashboard access', [
                'user_id' => $user?->id ?? 'null',
                'primary_role' => $role,
                'all_roles' => $allRoles,
            ]);
            abort(403, 'Unauthorized access to HR dashboard');
        }
    }

    public function getTotalEmployees(): int
    {
        return $this->remember('total_employees', function () {
            $query = Employee::where('is_active', true);

            $branchId = $this->getBranchId();
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            return $query->count();
        });
    }

    public function getOnDutyToday(): int
    {
        return $this->remember('on_duty_today', function () {
            try {
                $query = DB::table('shifts')
                    ->where('shift_date', Carbon::today())
                    ->where('status', 'active')
                    ->whereNotNull('clock_in');

                // Filter by branch
                $branchId = $this->getBranchId();
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                return $query->distinct('employee_id')->count('employee_id');
            } catch (\Exception $e) {
                // Table doesn't exist or query failed, return 0
                return 0;
            }
        });
    }

    public function getOnLeaveToday(): int
    {
        return $this->remember('on_leave_today', function () {
            try {
                $query = DB::table('leave_applications')
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', Carbon::today())
                    ->whereDate('end_date', '>=', Carbon::today());

                // Only filter by branch if the column exists and branch_id is set
                $branchId = $this->getBranchId();
                if (Schema::hasColumn('leave_applications', 'branch_id') && $branchId) {
                    $query->where('leave_applications.branch_id', $branchId);
                }

                return $query->distinct('employee_id')->count('employee_id');
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    public function getPendingLeaveRequests(): int
    {
        return $this->remember('pending_leave_requests', function () {
            try {
                $query = DB::table('leave_applications')
                    ->where('status', 'pending');

                // Only filter by branch if the column exists and branch_id is set
                $branchId = $this->getBranchId();
                if (Schema::hasColumn('leave_applications', 'branch_id') && $branchId) {
                    $query->where('branch_id', $branchId);
                }

                return $query->count();
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    public function getAttendancePercentage(): float
    {
        return $this->remember('attendance_percentage', function () {
            $totalEmployees = $this->getTotalEmployees();
            if ($totalEmployees === 0) {
                return 0;
            }

            $onDuty = $this->getOnDutyToday();
            return ($onDuty / $totalEmployees) * 100;
        });
    }

    public function getEmployeesByDepartment()
    {
        return $this->remember('employees_by_department', function () {
            try {
                $query = DB::table('users')
                    ->join('departments', 'users.department_id', '=', 'departments.id')
                    ->where('users.user_type', 'employee')
                    ->where('users.is_active', true);

                $branchId = $this->getBranchId();
                if ($branchId) {
                    $query->where('users.branch_id', $branchId);
                }

                return $query->groupBy('departments.id', 'departments.name')
                    ->selectRaw('departments.name, COUNT(users.id) as count')
                    ->get();
            } catch (\Exception $e) {
                return collect();
            }
        });
    }

    public function getPendingLeaves()
    {
        return $this->remember('pending_leaves', function () {
            try {
                $query = DB::table('leave_applications')
                    ->join('users', 'leave_applications.employee_id', '=', 'users.id')
                    ->where('leave_applications.status', 'pending');

                // Only filter by branch if the column exists and branch_id is set
                $branchId = $this->getBranchId();
                if (Schema::hasColumn('leave_applications', 'branch_id') && $branchId) {
                    $query->where('branch_id', $branchId);
                }

                return $query->selectRaw('leave_applications.*, users.name as employee_name, leave_applications.start_date as date, leave_applications.reason as leave_type')
                    ->orderBy('leave_applications.created_at', 'desc')
                    ->limit(10)
                    ->get();
            } catch (\Exception $e) {
                return collect();
            }
        });
    }

    public function getHRAlerts()
    {
        $alerts = [];
        $pendingLeaves = $this->getPendingLeaveRequests();

        if ($pendingLeaves > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Pending Leave Requests',
                'message' => "{$pendingLeaves} leave request(s) awaiting approval",
            ];
        }

        return $alerts;
    }

    public function render()
    {
        try {
            return view('livewire.dashboards.hr.dashboard', [
                'totalEmployees' => $this->getTotalEmployees(),
                'onDutyToday' => $this->getOnDutyToday(),
                'onLeaveToday' => $this->getOnLeaveToday(),
                'pendingLeaveRequests' => $this->getPendingLeaveRequests(),
                'attendancePercentage' => $this->getAttendancePercentage(),
                'employeesByDepartment' => $this->getEmployeesByDepartment(),
                'pendingLeaves' => $this->getPendingLeaves(),
                'hrAlerts' => $this->getHRAlerts(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading HR dashboard', $e);
            return view('livewire.dashboards.error');
        }
    }
}
