<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use Livewire\Component;

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
        $branchId = current_branch_id() ?? request()->query('b_id');
        $today = now()->toDateString();

        $kpis = [
            'today_sales_count' => Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('sale_time', $today)
                ->where('status', 'completed')
                ->count(),
            'today_sales_revenue' => Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('sale_time', $today)
                ->where('status', 'completed')
                ->sum('total'),
            'active_shifts' => Shift::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'active')
                ->count(),
            'pending_purchases' => Purchase::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'pending')
                ->count(),
            'total_employees' => Employee::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)
                ->count(),
            'low_stock_items' => Item::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereNotNull('reorder_level')
                ->where('reorder_level', '>', 0)
                ->get()
                ->filter(fn ($item) => $item->isBelowReorderLevel($branchId))
                ->count(),
        ];

        $recentSales = Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->latest('sale_time')
            ->limit(5)
            ->get();

        return view('livewire.branch-dashboard.dashboards.admin-dashboard', [
            'kpis' => $kpis,
            'recentSales' => $recentSales,
            'branchId' => $branchId,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
