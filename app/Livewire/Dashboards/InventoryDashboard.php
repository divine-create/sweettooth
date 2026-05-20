<?php

namespace App\Livewire\Dashboards;

use App\Helpers\Settings;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockBatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class InventoryDashboard extends BaseDashboard
{
    public function mount()
    {
        parent::mount();
        // Verify user has inventory access
        $this->verifyAccess();
    }

    /**
     * Verify user has access to inventory dashboard
     */
    private function verifyAccess(): void
    {
        $user = Auth::user();
        $isAllowed = \App\Services\SidebarVisibilityService::canSeeInventory($user)
            || \App\Services\SidebarVisibilityService::isAdmin($user)
            || \App\Services\SidebarVisibilityService::isSuperAdmin($user);

        if (! $isAllowed) {
            abort(403, 'Unauthorized access to inventory dashboard');
        }
    }

    /**
     * Get total stock value
     */
    public function getTotalStockValue(): float
    {
        return $this->remember('total_stock_value_v4', function () {
            $branchId = $this->getBranchId();
            $stocks = Stock::where('branch_id', $branchId)->get();
            $total = 0;

            foreach ($stocks as $stock) {
                // Use average_cost from stock record, fallback to purchase price if needed
                $unitPrice = $stock->average_cost ?? $this->getLastUnitPrice($stock->item_id);
                $total += $stock->quantity_available * $unitPrice;
            }

            return (float) $total;
        });
    }

    /**
     * Get last unit price for an item from most recent purchase
     */
    private function getLastUnitPrice($itemId): float
    {
        $itemUnitPrice = (float) (Item::whereKey($itemId)->value('unit_price') ?? 0);
        if ($itemUnitPrice > 0) {
            return $itemUnitPrice;
        }

        return \App\Models\PurchaseItem::where('item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->value('cost_per_unit') ?? 0;
    }

    /**
     * Get total items in stock
     */
    public function getTotalItems(): int
    {
        return $this->remember('total_items', function () {
            return Stock::where('branch_id', $this->getBranchId())
                ->where('quantity_available', '>', 0)
                ->count();
        });
    }

    /**
     * Get count of low stock items
     */
    public function getLowStockCount(): int
    {
        return $this->remember('low_stock_count', function () {
            return Stock::where('stocks.branch_id', $this->getBranchId())
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->whereRaw('stocks.quantity_available <= items.reorder_level')
                ->count();
        });
    }

    /**
     * Get recent stock movements
     */
    public function getRecentMovements($limit = 10)
    {
        return $this->remember('recent_movements_'.$limit, function () use ($limit) {
            return StockMovement::with(['stock.item', 'mover'])
                ->where('branch_id', $this->getBranchId())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($limit = 15)
    {
        return $this->remember('low_stock_items_'.$limit, function () use ($limit) {
            $items = Stock::where('stocks.branch_id', $this->getBranchId())
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->selectRaw('items.id, items.name, items.sku, items.reorder_level as reorder_point, stocks.quantity_available as quantity, stocks.average_cost')
                ->whereRaw('stocks.quantity_available <= items.reorder_level')
                ->orderBy('stocks.quantity_available', 'asc')
                ->limit($limit)
                ->get();

            // Add last_unit_price to each item (use average cost from stock)
            foreach ($items as $item) {
                $item->last_unit_price = $item->average_cost ?? $this->getLastUnitPrice($item->id);
            }

            return $items;
        });
    }

    /**
     * Get total stock movements count today
     */
    public function getTodayMovementsCount(): int
    {
        return $this->remember('today_movements_count', function () {
            return StockMovement::join('stocks', 'stock_movements.stock_id', '=', 'stocks.id')
                ->where('stocks.branch_id', $this->getBranchId())
                ->whereDate('stock_movements.created_at', Carbon::today())
                ->count();
        });
    }

    /**
     * Get stock health check status
     */
    public function getHealthStatus(): string
    {
        $lowStockCount = $this->getLowStockCount();
        $totalItems = $this->getTotalItems();

        if ($totalItems === 0) {
            return 'warning';
        }

        $percentage = ($lowStockCount / $totalItems) * 100;

        if ($percentage > 30) {
            return 'critical';
        } elseif ($percentage > 10) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get items by category (for chart)
     */
    public function getItemsByCategory()
    {
        return $this->remember('items_by_category', function () {
            return Stock::where('stocks.branch_id', $this->getBranchId())
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->groupBy('items.category_id')
                ->selectRaw('items.category_id, COUNT(DISTINCT stocks.item_id) as count, SUM(stocks.quantity_available) as total_qty')
                ->get();
        });
    }

    /**
     * Get critical alerts
     */
    public function getCriticalAlerts()
    {
        $alerts = [];
        $lowStockCount = $this->getLowStockCount();

        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => "{$lowStockCount} items are below reorder point",
                'action' => 'view-low-stock',
            ];
        }

        $outOfStockCount = Stock::where('branch_id', $this->getBranchId())
            ->where('quantity_available', 0)
            ->count();

        if ($outOfStockCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Out of Stock',
                'message' => "{$outOfStockCount} items are out of stock",
                'action' => 'view-out-of-stock',
            ];
        }

        $batchService = app(StockBatchService::class);
        $branchId = $this->getBranchId();
        $warningDays = (int) Settings::inventoryManagement('expiry_warning_days', 7);

        $expiredCount = $batchService->getExpiredBatchesWithStock($branchId)->count();
        if ($expiredCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Expired Batches',
                'message' => "{$expiredCount} batch(es) have expired with remaining stock — action required",
                'action' => 'view-expired-batches',
            ];
        }

        $expiringCount = $batchService->getExpiringBatches($branchId, $warningDays)->count();
        if ($expiringCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Expiring Soon',
                'message' => "{$expiringCount} batch(es) expire within {$warningDays} days",
                'action' => 'view-expiring-batches',
            ];
        }

        return $alerts;
    }

    public function getExpiringBatches(int $limit = 10)
    {
        return $this->remember('expiring_batches_'.$limit, function () use ($limit) {
            $branchId = $this->getBranchId();
            $warningDays = (int) Settings::inventoryManagement('expiry_warning_days', 7);
            return app(StockBatchService::class)
                ->getExpiringBatches($branchId, $warningDays)
                ->take($limit);
        });
    }

    public function getExpiredBatches(int $limit = 10)
    {
        return $this->remember('expired_batches_'.$limit, function () use ($limit) {
            return app(StockBatchService::class)
                ->getExpiredBatchesWithStock($this->getBranchId())
                ->take($limit);
        });
    }

    public function getExpiryWarningDays(): int
    {
        return $this->remember('expiry_warning_days', function () {
            return (int) Settings::inventoryManagement('expiry_warning_days', 7);
        });
    }

    public function render()
    {
        try {
            return view('livewire.dashboards.inventory.dashboard', [
                'totalStockValue' => $this->getTotalStockValue(),
                'totalItems' => $this->getTotalItems(),
                'lowStockCount' => $this->getLowStockCount(),
                'todayMovementsCount' => $this->getTodayMovementsCount(),
                'healthStatus' => $this->getHealthStatus(),
                'recentMovements' => $this->getRecentMovements(),
                'lowStockItems' => $this->getLowStockItems(),
                'criticalAlerts' => $this->getCriticalAlerts(),
                'expiringBatches' => $this->getExpiringBatches(),
                'expiredBatches' => $this->getExpiredBatches(),
                'expiryWarningDays' => $this->getExpiryWarningDays(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading inventory dashboard', $e);

            return view('livewire.dashboards.error');
        }
    }
}
