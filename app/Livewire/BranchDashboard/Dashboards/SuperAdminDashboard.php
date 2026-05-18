<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use Spatie\Permission\Models\Role;

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
        $today = now()->toDateString();

        $stats = [
            'total_branches' => Branch::count(),
            'total_roles' => Role::count(),
            'total_employees' => Employee::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'total_items' => Item::count(),
        ];

        $ops = [
            'today_sales_count' => Sale::whereDate('sale_time', $today)->where('status', 'completed')->count(),
            'today_revenue' => Sale::whereDate('sale_time', $today)->where('status', 'completed')->sum('total'),
            'active_shifts' => Shift::where('status', 'active')->count(),
            'pending_purchases' => Purchase::where('status', 'pending')->count(),
            'gl_failures' => Sale::where('gl_posting_status', 'failed')->count()
                + Purchase::where('gl_posting_status', 'failed')->count()
                + Payment::where('gl_posting_status', 'failed')->count(),
        ];

        $recentBranches = Branch::latest()->limit(5)->get();
        $recentRoles = Role::latest()->limit(5)->get();

        return view('livewire.branch-dashboard.dashboards.super-admin-dashboard', [
            'stats' => $stats,
            'ops' => $ops,
            'recentBranches' => $recentBranches,
            'recentRoles' => $recentRoles,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
