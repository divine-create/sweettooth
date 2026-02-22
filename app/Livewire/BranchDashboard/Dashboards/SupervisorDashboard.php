<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use App\Models\Employee;
use App\Services\SidebarVisibilityService;

/**
 * Supervisor Dashboard - Supervisor level view (Level 2)
 *
 * Provides Supervisor roles with:
 * - Shift/operation monitoring
 * - Staff oversight
 * - Daily operations reporting
 * - Team performance metrics
 */
class SupervisorDashboard extends Component
{
    public function mount()
    {
        $currentUser = get_user_auth();
        $roleLevel = SidebarVisibilityService::getRoleLevel($currentUser);

        // Level 2 (Supervisor) or higher can access
        if ($roleLevel < SidebarVisibilityService::LEVEL_SUPERVISOR) {
            abort(403, 'This dashboard requires Supervisor access or higher.');
        }
    }

    public function render()
    {
        $currentUser = get_user_auth();
        $currentBranchId = request()->query('b_id');
        $userDepartmentId = $currentUser?->department_id;

        $stats = [
            'supervised_staff' => Employee::when($userDepartmentId, function($q) use ($userDepartmentId) {
                return $q->where('department_id', $userDepartmentId);
            })->when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
            'on_shift_today' => Employee::where('status', 'active')->when($userDepartmentId, function($q) use ($userDepartmentId) {
                return $q->where('department_id', $userDepartmentId);
            })->when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
        ];

        $staff = Employee::when($userDepartmentId, function($q) use ($userDepartmentId) {
            return $q->where('department_id', $userDepartmentId);
        })->when($currentBranchId, function($q) use ($currentBranchId) {
            return $q->where('branch_id', $currentBranchId);
        })->latest()->limit(10)->get();

        return view('livewire.branch-dashboard.dashboards.supervisor-dashboard', [
            'stats' => $stats,
            'staff' => $staff,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
