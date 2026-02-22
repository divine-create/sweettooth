<?php

namespace App\Livewire\Dashboards;

use App\Models\Branch;
use App\Models\ApprovalAuditRequest;
use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app.branch-dashboard')]
class SuperAdminDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        $this->verifyAccess();
    }

    private function verifyAccess(): void
    {
        if (! is_super_admin()) {
            abort(403, 'Unauthorized access to admin dashboard');
        }
    }

    /**
     * Get current branch revenue
     */
    public function getBranchRevenue(): float
    {
        return $this->remember('branch_revenue', function () {
            return DB::table('transactions')
                ->where('branch_id', $this->getBranchId())
                ->whereDate('created_at', '>=', Carbon::now()->startOfMonth())
                ->sum('total_amount') ?? 0;
        });
    }

    /**
     * Get current branch inventory value
     */
    public function getBranchInventoryValue(): float
    {
        return $this->remember('branch_inventory_value', function () {
            return DB::table('stocks')
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->where('stocks.branch_id', $this->getBranchId())
                ->selectRaw('SUM((stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) * items.last_unit_price) as total')
                ->value('total') ?? 0;
        });
    }

    /**
     * Get current branch staff count
     */
    public function getBranchStaffCount(): int
    {
        return $this->remember('branch_staff_count', function () {
            return DB::table('employees')
                ->where('branch_id', $this->getBranchId())
                ->where('status', 'active')
                ->count();
        });
    }

    /**
     * Get branch alerts (pending approvals, low stock)
     */
    public function getBranchAlerts(): int
    {
        return $this->remember('branch_alerts', function () {
            return ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->where('status', 'pending')
                ->count();
        });
    }

    /**
     * Get all branch metrics for comparison
     */
    public function getAllBranchMetrics()
    {
        return $this->remember('all_branch_metrics', function () {
            return Branch::where('is_active', true)
                ->get()
                ->map(function ($branch) {
                    $revenue = DB::table('transactions')
                        ->where('branch_id', $branch->id)
                        ->whereDate('created_at', '>=', Carbon::now()->startOfMonth())
                        ->sum('total_amount') ?? 0;

                    $inventory = DB::table('stocks')
                        ->join('items', 'stocks.item_id', '=', 'items.id')
                        ->where('stocks.branch_id', $branch->id)
                        ->selectRaw('SUM((stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) * items.last_unit_price) as total')
                        ->value('total') ?? 0;

                    $staff = DB::table('employees')
                        ->where('branch_id', $branch->id)
                        ->where('status', 'active')
                        ->count();

                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'revenue' => $revenue,
                        'inventory' => $inventory,
                        'staff' => $staff,
                    ];
                });
        });
    }

    /**
     * Get pending approvals for current branch
     */
    public function getPendingApprovals()
    {
        return $this->remember('pending_approvals', function () {
            return ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->where('status', 'pending')
                ->with('requester')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });
    }

    /**
     * Get recent audit log for current branch
     */
    public function getAuditLog()
    {
        return $this->remember('audit_log', function () {
            return ApprovalAuditRequest::where('branch_id', $this->getBranchId())
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
        });
    }

    public function render()
    {
        try {
            return view('livewire.dashboards.super-admin.dashboard', [
                'branchRevenue' => $this->getBranchRevenue(),
                'branchInventoryValue' => $this->getBranchInventoryValue(),
                'branchStaffCount' => $this->getBranchStaffCount(),
                'branchAlerts' => $this->getBranchAlerts(),
                'allBranchMetrics' => $this->getAllBranchMetrics(),
                'pendingApprovals' => $this->getPendingApprovals(),
                'auditLog' => $this->getAuditLog(),
                'branches' => Branch::where('is_active', true)->get(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading admin dashboard', $e);
            return view('livewire.dashboards.error');
        }
    }
}
