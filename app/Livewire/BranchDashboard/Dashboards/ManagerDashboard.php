<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Department;
use App\Services\SidebarVisibilityService;

/**
 * Manager Dashboard - Manager level view (Level 3)
 *
 * Provides Manager roles with:
 * - Team/department statistics
 * - Employee performance overview
 * - Departmental reports
 * - Team-level operations access
 */
class ManagerDashboard extends Component
{
    public function mount()
    {
        $currentUser = get_user_auth();
        $roleLevel = SidebarVisibilityService::getRoleLevel($currentUser);

        // Level 3 (Manager) or higher can access
        if ($roleLevel < SidebarVisibilityService::LEVEL_MANAGER) {
            abort(403, 'This dashboard requires Manager access or higher.');
        }
    }

    public function render()
    {
        $currentUser = get_user_auth();
        $currentBranchId = request()->query('b_id');
        
        // If employee is logged in, get their department
        $userDepartmentId = $currentUser?->department_id;

        $stats = [
            'team_size' => Employee::when($userDepartmentId, function($q) use ($userDepartmentId) {
                return $q->where('department_id', $userDepartmentId);
            })->when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
            'active_team_members' => Employee::where('is_active', true)->when($userDepartmentId, function($q) use ($userDepartmentId) {
                return $q->where('department_id', $userDepartmentId);
            })->when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
        ];

        $teamMembers = Employee::when($userDepartmentId, function($q) use ($userDepartmentId) {
            return $q->where('department_id', $userDepartmentId);
        })->when($currentBranchId, function($q) use ($currentBranchId) {
            return $q->where('branch_id', $currentBranchId);
        })->latest()->limit(10)->get();

        return view('livewire.branch-dashboard.dashboards.manager-dashboard', [
            'stats' => $stats,
            'teamMembers' => $teamMembers,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
