<?php

namespace App\Livewire\Dashboards;

use Livewire\Attributes\Layout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app.branch-dashboard')]
class CornerStoreDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        // Verify user has corner store access
        $this->verifyAccess();
    }

    /**
     * Verify user has access to corner store dashboard
     */
    private function verifyAccess(): void
    {
        $user = auth()->user();
        $isAllowed = \App\Services\SidebarVisibilityService::canSeeSalesManagement($user)
            || \App\Services\SidebarVisibilityService::isAdmin($user)
            || \App\Services\SidebarVisibilityService::isSuperAdmin($user);
        
        if (!$isAllowed) {
            abort(403, 'Unauthorized access to corner store dashboard.');
        }
    }

    /**
     * Get total sales for date range
     */
    public function getTotalSales(): float
    {
        return $this->remember('corner_store_total_sales', function () {
            $range = $this->getDateRange();
            $branchId = $this->getBranchId();
            
            if (!$this->tableExists('transactions')) {
                return 0;
            }
            
            return DB::table('transactions')
                ->where('branch_id', $branchId)
                ->where('department_id', $this->getCornerStoreDepartmentId())
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->sum('total_amount') ?? 0;
        });
    }

    /**
     * Get transaction count
     */
    public function getTransactionCount(): int
    {
        return $this->remember('corner_store_transaction_count', function () {
            if (!$this->tableExists('transactions')) {
                return 0;
            }
            
            $range = $this->getDateRange();
            
            return DB::table('transactions')
                ->where('branch_id', $this->getBranchId())
                ->where('department_id', $this->getCornerStoreDepartmentId())
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->count();
        });
    }

    /**
     * Get today's sales
     */
    public function getTodaySales(): float
    {
        if (!$this->tableExists('transactions')) {
            return 0;
        }
        
        return DB::table('transactions')
            ->where('branch_id', $this->getBranchId())
            ->where('department_id', $this->getCornerStoreDepartmentId())
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount') ?? 0;
    }

    /**
     * Get today's transaction count
     */
    public function getTodayTransactionCount(): int
    {
        if (!$this->tableExists('transactions')) {
            return 0;
        }
        
        return DB::table('transactions')
            ->where('branch_id', $this->getBranchId())
            ->where('department_id', $this->getCornerStoreDepartmentId())
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
        return $this->remember('corner_store_top_items_' . $limit, function () use ($limit) {
            if (!$this->tableExists('transaction_items')) {
                return [];
            }
            
            return DB::table('transaction_items')
                ->join('products', 'transaction_items.product_id', '=', 'products.id')
                ->where('transaction_items.branch_id', $this->getBranchId())
                ->where('transaction_items.department_id', $this->getCornerStoreDepartmentId())
                ->whereDate('transaction_items.created_at', Carbon::today())
                ->groupBy('transaction_items.product_id', 'products.name')
                ->selectRaw('products.name, SUM(transaction_items.quantity) as total_qty, SUM(transaction_items.total_amount) as total_sales')
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
        return $this->remember('corner_store_recent_transactions_' . $limit, function () use ($limit) {
            if (!$this->tableExists('transactions')) {
                return [];
            }
            
            return DB::table('transactions')
                ->where('branch_id', $this->getBranchId())
                ->where('department_id', $this->getCornerStoreDepartmentId())
                ->whereDate('created_at', Carbon::today())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get Corner Store department ID
     */
    private function getCornerStoreDepartmentId(): ?int
    {
        return $this->remember('corner_store_dept_id', function () {
            return DB::table('departments')
                ->where('name', 'Corner Store')
                ->where('branch_id', $this->getBranchId())
                ->value('id');
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

    public function render()
    {
        try {
            return view('livewire.dashboards.corner-store.dashboard', [
                'totalSales' => $this->getTotalSales(),
                'transactionCount' => $this->getTransactionCount(),
                'todaySales' => $this->getTodaySales(),
                'todayTransactionCount' => $this->getTodayTransactionCount(),
                'averageTransactionValue' => $this->getAverageTransactionValue(),
                'topSellingItems' => $this->getTopSellingItems(),
                'recentTransactions' => $this->getRecentTransactions(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading corner store dashboard', $e);
            return view('livewire.dashboards.error');
        }
    }
}
