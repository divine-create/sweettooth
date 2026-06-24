<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ProductionRecord;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalesProductionRequest;
use App\Models\Shift;
use App\Services\SidebarVisibilityService;
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

        // Scope the dashboard to what the viewer is allowed to see. A level-4
        // oversight role (e.g. Operations Manager) only sees Sales + Production;
        // Inventory / HR / Accounting widgets render only for users with access.
        $user = get_user_auth();
        $canInventory  = SidebarVisibilityService::canSeeInventory($user);
        $canHr         = SidebarVisibilityService::canSeeEmployeeManagement($user);
        $canAccounting = SidebarVisibilityService::canSeeAccounting($user);
        $canProduction = SidebarVisibilityService::canSeeProduction($user);
        $canSales      = SidebarVisibilityService::canSeeSalesManagement($user);

        // Sales (everyone with this dashboard sees sales)
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
        ];

        // Production
        if ($canProduction) {
            $kpis['pending_production_requests'] = SalesProductionRequest::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', \App\Enums\SalesProductionRequestStatus::PENDING->value)
                ->count();
            $kpis['today_production_batches'] = ProductionRecord::whereDate('created_at', $today)->count();
        }

        // Inventory (hidden from oversight roles without inventory access)
        if ($canInventory) {
            $kpis['pending_purchases'] = Purchase::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'pending')
                ->count();
            $kpis['low_stock_items'] = Item::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereNotNull('reorder_level')
                ->where('reorder_level', '>', 0)
                ->get()
                ->filter(fn ($item) => $item->isBelowReorderLevel($branchId))
                ->count();
        }

        // HR
        if ($canHr) {
            $kpis['total_employees'] = Employee::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)
                ->count();
        }

        $recentSales = Sale::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->latest('sale_time')
            ->limit(5)
            ->get();

        return view('livewire.branch-dashboard.dashboards.admin-dashboard', [
            'kpis' => $kpis,
            'recentSales' => $recentSales,
            'branchId' => $branchId,
            'canInventory' => $canInventory,
            'canHr' => $canHr,
            'canAccounting' => $canAccounting,
            'canProduction' => $canProduction,
            'canSales' => $canSales,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
