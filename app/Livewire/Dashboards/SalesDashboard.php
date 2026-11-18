<?php

namespace App\Livewire\Dashboards;

use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\SalesWorkflowService;
use App\Models\Shift;
use App\Models\Department;

#[Layout('components.layouts.app.branch-dashboard')]
class SalesDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        // Verify user has sales access
        $this->verifyAccess();
        // Check and redirect based on workflow state
        $this->checkWorkflowState();
    }

    /**
     * Check workflow state and redirect sales users to appropriate page
     */
    private function checkWorkflowState(): void
    {
        // Super admins bypass workflow
        if (is_super_admin() || can_access_all_branches()) {
            return;
        }

        $user = auth()->user();
        if (!$user) {
            return;
        }

        // Check if user is in a sales department
        if (!$this->isSalesEmployee($user)) {
            return;
        }

        $branchId = $this->getBranchId();
        $departmentSlug = $this->getEmployeeDepartmentSlug($user);

        // Get active shift for today
        $activeShift = Shift::where('employee_id', $user->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->first();

        // No active shift - redirect to clock-in
        if (!$activeShift) {
            $this->redirect(route('branch-dashboard.select_shift', ['b_id' => $branchId]));
            return;
        }

        // Check workflow state using the service
        $workflowService = app(SalesWorkflowService::class);
        $currentState = $workflowService->getCurrentState($user->id, $activeShift->id);

        // Redirect based on workflow state
        if ($currentState === 'stock_opening') {
            // Need to complete stock opening first
            $this->redirect(route('branch-dashboard.sales-dashboard.stock-opening.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]));
            return;
        }

        if ($currentState === 'pos') {
            // Stock verified, redirect to POS
            $this->redirect(route('branch-dashboard.sales-dashboard.pos.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]));
            return;
        }

        if ($currentState === 'shift_closing') {
            // Need to complete shift closing
            $this->redirect(route('branch-dashboard.sales-dashboard.shift-closing.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]));
            return;
        }
    }

    /**
     * Check if employee belongs to sales department
     */
    private function isSalesEmployee($user): bool
    {
        if (!$user->department_id) {
            return false;
        }

        $department = Department::find($user->department_id);
        if (!$department || !$department->category) {
            return false;
        }

        return strtolower($department->category->name) === 'sales';
    }

    /**
     * Get employee's department slug
     */
    private function getEmployeeDepartmentSlug($user): ?string
    {
        if (!$user->department_id) {
            return null;
        }

        $department = Department::find($user->department_id);
        return $department?->slug;
    }

    /**
     * Verify user has access to sales dashboard
     */
    private function verifyAccess(): void
    {
        $user = auth()->user();
        $role = $this->getUserRoleName();
        // Normalize role name to snake_case for comparison
        $isAllowed = \App\Services\SidebarVisibilityService::canSeeSalesManagement($user)
            || \App\Services\SidebarVisibilityService::isAdmin($user)
            || \App\Services\SidebarVisibilityService::isSuperAdmin($user);
        
        if (!$isAllowed) {
            abort(403, 'Unauthorized access to sales dashboard.');
        }
    }

    /**
     * Get total sales for date range
     */
    public function getTotalSales(): float
    {
        return $this->remember('total_sales', function () {
            $range = $this->getDateRange();
            
            if (!$this->tableExists('sales')) {
                return 0;
            }
            
            return DB::table('sales')
                ->where('branch_id', $this->getBranchId())
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->sum('total') ?? 0;
        });
    }

    /**
     * Get transaction count
     */
    public function getTransactionCount(): int
    {
        return $this->remember('transaction_count', function () {
            if (!$this->tableExists('sales')) {
                return 0;
            }
            
            $range = $this->getDateRange();
            
            return DB::table('sales')
                ->where('branch_id', $this->getBranchId())
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->count();
        });
    }

    /**
     * Get today's sales
     */
    public function getTodaySales(): float
    {
        if (!$this->tableExists('sales')) {
            return 0;
        }
        
        return DB::table('sales')
            ->where('branch_id', $this->getBranchId())
            ->whereDate('created_at', Carbon::today())
            ->sum('total') ?? 0;
    }

    /**
     * Get today's transaction count
     */
    public function getTodayTransactionCount(): int
    {
        if (!$this->tableExists('sales')) {
            return 0;
        }
        
        return DB::table('sales')
            ->where('branch_id', $this->getBranchId())
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Get average transaction value
     */
    public function getAverageTransactionValue(): float
    {
        $count = $this->getTodayTransactionCount();
        if ($count === 0) {
            return 0;
        }

        return $this->getTodaySales() / $count;
    }

    /**
     * Get top selling items
     */
    public function getTopSellingItems($limit = 10)
    {
        return $this->remember('top_selling_items_' . $limit, function () use ($limit) {
            if (!$this->tableExists('sale_items')) {
                return [];
            }
            
            return DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.branch_id', $this->getBranchId())
                ->whereDate('sales.created_at', Carbon::today())
                ->groupBy('sale_items.product_id', 'products.name')
                ->selectRaw('products.name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total) as total_sales')
                ->orderByDesc('total_qty')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions($limit = 10)
    {
        return $this->remember('recent_transactions_' . $limit, function () use ($limit) {
            if (!$this->tableExists('sales')) {
                return [];
            }
            
            return DB::table('sales')
                ->where('branch_id', $this->getBranchId())
                ->whereDate('created_at', Carbon::today())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get sales trend by hour
     */
    public function getSalesByHour()
    {
        return $this->remember('sales_by_hour', function () {
            if (!$this->tableExists('sales')) {
                return [];
            }
            
            return DB::table('sales')
                ->where('branch_id', $this->getBranchId())
                ->whereDate('created_at', Carbon::today())
                ->selectRaw("DATE_FORMAT(created_at, '%H:00') as hour, COUNT(*) as transactions, SUM(total) as sales")
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get();
        });
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get sales alerts
     */
    public function getSalesAlerts()
    {
        $alerts = [];
        $todaySales = $this->getTodaySales();
        
        // Example: Alert if sales are low
        if ($todaySales < 100) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Sales',
                'message' => 'Today\'s sales are below average',
            ];
        }

        return $alerts;
    }

    public function render()
    {
        try {
            return view('livewire.dashboards.sales.dashboard', [
                'totalSales' => $this->getTotalSales(),
                'transactionCount' => $this->getTransactionCount(),
                'todaySales' => $this->getTodaySales(),
                'todayTransactionCount' => $this->getTodayTransactionCount(),
                'averageTransactionValue' => $this->getAverageTransactionValue(),
                'topSellingItems' => $this->getTopSellingItems(),
                'recentTransactions' => $this->getRecentTransactions(),
                'salesByHour' => $this->getSalesByHour(),
                'salesAlerts' => $this->getSalesAlerts(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading sales dashboard', $e);
            return view('livewire.dashboards.error');
        }
    }
}
