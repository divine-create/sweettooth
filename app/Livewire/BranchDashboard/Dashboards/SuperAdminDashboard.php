<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use App\Models\Employee;
use App\Models\Item;

/**
 * Super Admin Dashboard - System-wide administrative view
 * 
 * Provides Super Admins with:
 * - System statistics and metrics
 * - Branch management overview
 * - Role and permission management access
 * - User and employee management
 * - System settings access
 */
class SuperAdminDashboard extends Component
{
    public function mount()
    {
        // Use unified AuthService to check super admin status
        \App\Services\AuthService::requireSuperAdmin();
    }

    public function render()
    {
        $stats = [
            'total_branches' => Branch::count(),
            'total_roles' => Role::count(),
            'total_employees' => Employee::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'total_items' => Item::count(),
        ];

        $recentBranches = Branch::latest()->limit(5)->get();
        $recentRoles = Role::latest()->limit(5)->get();

        return view('livewire.branch-dashboard.dashboards.super-admin-dashboard', [
            'stats' => $stats,
            'recentBranches' => $recentBranches,
            'recentRoles' => $recentRoles,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
