<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use App\Models\Branch;
use App\Models\Employee;

/**
 * Admin Dashboard - Administrative/Managing Director level view
 * 
 * Provides Admin/Managing Director roles with:
 * - Branch-wide statistics
 * - Employee management overview
 * - Report summaries
 * - System configuration access (limited)
 */
class AdminDashboard extends Component
{
    public function mount()
    {
        // Check if user has admin role
        $currentUser = get_user_auth();
        if (!\App\Services\SidebarVisibilityService::isAdmin($currentUser)
            && !\App\Services\SidebarVisibilityService::isSuperAdmin($currentUser)) {
            abort(403, 'Only Admins can access this dashboard');
        }
    }

    public function render()
    {
        $currentBranchId = request()->query('b_id');
        
        $stats = [
            'total_employees' => Employee::when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
            'active_employees' => Employee::where('is_active', true)->when($currentBranchId, function($q) use ($currentBranchId) {
                return $q->where('branch_id', $currentBranchId);
            })->count(),
            'branches_managed' => Branch::count(),
        ];

        $recentEmployees = Employee::when($currentBranchId, function($q) use ($currentBranchId) {
            return $q->where('branch_id', $currentBranchId);
        })->latest()->limit(10)->get();

        return view('livewire.branch-dashboard.dashboards.admin-dashboard', [
            'stats' => $stats,
            'recentEmployees' => $recentEmployees,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
