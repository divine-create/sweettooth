<?php

namespace App\Livewire\Dashboards;

use App\Models\ApprovalAuditRequest;
use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app.branch-dashboard')]
class BranchAdminDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        $this->verifyAccess();
    }

    private function verifyAccess(): void
    {
        $role = $this->getUserRoleName();
        $user = auth()->user();
        $isAllowed = \App\Services\SidebarVisibilityService::isAdmin($user)
            || \App\Services\SidebarVisibilityService::isSuperAdmin($user);

        if (! $isAllowed) {
            abort(403, 'Unauthorized access to admin dashboard');
        }
    }

    public function getBranchRevenue(): float
    {
        return $this->remember('branch_revenue', function () {
            return DB::table('transactions')
                ->where('branch_id', $this->getBranchId())
                ->whereDate('created_at', '>=', Carbon::now()->startOfMonth())
                ->sum('total_amount') ?? 0;
        });
    }

    public function getTotalInventoryValue(): float
    {
        return $this->remember('total_inventory_value', function () {
            return DB::table('stocks')
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->where('stocks.branch_id', $this->getBranchId())
                ->selectRaw('SUM((stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) * items.last_unit_price) as total')
                ->value('total') ?? 0;
        });
    }

    public function getTotalStaff(): int
    {
        return $this->remember('total_staff', function () {
            return DB::table('employees')
                ->where('branch_id', $this->getBranchId())
                ->where('status', 'active')
                ->count();
        });
    }

    public function getSystemAlerts(): int
    {
        return $this->remember('system_alerts', function () {
            $lowStock = DB::table('stocks')
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->where('stocks.branch_id', $this->getBranchId())
                ->whereRaw('(stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) <= items.reorder_point')
                ->count();

            $pendingApprovals = ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->where('status', 'pending')
                ->count();

            return $lowStock + $pendingApprovals;
        });
    }

    public function getPendingApprovals()
    {
        return $this->remember('pending_approvals', function () {
            return ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });
    }

    public function getRecentAuditLog()
    {
        return $this->remember('recent_audit_log', function () {
            return ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
        });
    }

    public function getModuleStatus()
    {
        return [
            'production' => [
                'label' => 'Production',
                'icon' => 'factory',
                'pending' => DB::table('daily_produces')
                    ->where('branch_id', $this->getBranchId())
                    ->whereDate('date', Carbon::today())
                    ->where('status', 'pending')
                    ->count(),
            ],
            'inventory' => [
                'label' => 'Inventory',
                'icon' => 'box',
                'pending' => DB::table('stocks')
                    ->join('items', 'stocks.item_id', '=', 'items.id')
                    ->where('stocks.branch_id', $this->getBranchId())
                    ->whereRaw('(stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) <= items.reorder_point')
                    ->count(),
            ],
            'sales' => [
                'label' => 'Sales',
                'icon' => 'shopping-cart',
                'pending' => DB::table('transactions')
                    ->where('branch_id', $this->getBranchId())
                    ->whereDate('created_at', Carbon::today())
                    ->count(),
            ],
            'hr' => [
                'label' => 'HR',
                'icon' => 'users',
                'pending' => DB::table('leaves')
                    ->where('branch_id', $this->getBranchId())
                    ->where('status', 'pending')
                    ->count(),
            ],
        ];
    }

    public function render()
    {
        try {
            return view('livewire.dashboards.admin.dashboard', [
                'branchRevenue' => $this->getBranchRevenue(),
                'totalInventoryValue' => $this->getTotalInventoryValue(),
                'totalStaff' => $this->getTotalStaff(),
                'systemAlerts' => $this->getSystemAlerts(),
                'pendingApprovals' => $this->getPendingApprovals(),
                'recentAuditLog' => $this->getRecentAuditLog(),
                'moduleStatus' => $this->getModuleStatus(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading admin dashboard', $e);
            return view('livewire.dashboards.error');
        }
    }
}
