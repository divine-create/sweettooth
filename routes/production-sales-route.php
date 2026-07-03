<?php

use Illuminate\Support\Facades\Route;

// Production routes - Modular System
// Protected by department.scope middleware for department-based access control
Route::prefix('production')->name('production.')->middleware(['department.scope'])->group(function () {

    // Helper function to register department routes
    $registerProductionDepartmentRoutes = function () {
        // Products Management
        Route::get('product-types/{deptSlug?}', \App\Livewire\BranchDashboard\Production\ProductTypes::class)->name('product-types');
        Route::get('products/{deptSlug?}', \App\Livewire\BranchDashboard\Production\Products::class)->name('products');

        // Sales Request Review (dedicated production page)
        Route::prefix('sales-requests')->name('sales-requests.')->group(function () {
            Route::get('/workflow/{deptSlug?}', \App\Livewire\BranchDashboard\Production\Request\SalesRequests::class)->name('workflow');
            Route::get('/{deptSlug?}', \App\Livewire\BranchDashboard\Production\Request\SalesRequests::class)->name('index');
        });

        // Production Records
        Route::get('records/{deptSlug?}', \App\Livewire\BranchDashboard\Production\Records\Index::class)->name('records.index');

        // Shift Closing - Production (department-based)
        Route::prefix('shift-closing')->name('shift-closing.')->group(function () {
            Route::get('/{deptSlug}', \App\Livewire\BranchDashboard\Production\ShiftClosing\Index::class)->name('index');
        });

        // Recipes
        Route::get('recipes/{deptSlug?}', App\Livewire\BranchDashboard\Production\Recipes::class)->name('recipes.index');
        Route::get('recipes/{deptSlug}/add', App\Livewire\BranchDashboard\Production\Recipes\Add::class)->name('recipes.add');
        Route::get('recipes/{deptSlug}/{id}/edit', App\Livewire\BranchDashboard\Production\Recipes\Edit::class)->name('recipes.edit');
        Route::get('recipes/{deptSlug}/{id}', App\Livewire\BranchDashboard\Production\RecipeDetail::class)->name('recipes.detail');

        // Production Store
        Route::get('store/{deptSlug}', App\Livewire\BranchDashboard\Production\Store\Stock::class)->name('store.stock');

        // Finished Goods - Daily Sheet (Opening / Produced / Sent out / Closing)
        Route::get('finished-goods/{deptSlug?}', App\Livewire\BranchDashboard\Production\FinishedGoods\Index::class)->name('finished-goods.index');

        // Quick Produce - Finished Goods
        Route::get('quick-produce/finished-good/{deptSlug}', App\Livewire\BranchDashboard\Production\QuickProduceFinishedGood::class)->name('quick-produce.finished-good');

        // Quick Produce - WIP
        Route::get('quick-produce/wip/{deptSlug}', App\Livewire\BranchDashboard\Production\QuickProduceWip::class)->name('quick-produce.wip');

        // WIP Recipe Page (for adding/editing recipe to a product)
        Route::get('wip-recipe/{deptSlug}/{productId}', App\Livewire\BranchDashboard\Production\WipProductRecipe::class)->name('wip-recipe');

        // Material Request
        Route::get('material-request/{deptSlug}', App\Livewire\BranchDashboard\Production\MaterialRequest\Index::class)->name('material-request');

        // Product Assignments
        Route::get('product-assignments/{deptSlug?}', \App\Livewire\BranchDashboard\Production\ProductAssignments::class)->name('product-assignments');

        // Raw Material Tracking
        Route::get('raw-material-tracking', \App\Livewire\BranchDashboard\Production\RawMaterialTracking::class)->name('raw-material-tracking');

        // Material Returns to Inventory
        Route::get('/material-returns/create/{deptSlug?}', \App\Livewire\BranchDashboard\Production\MaterialReturns\Create::class)->name('material-returns.create');
        Route::get('/material-returns/list/{deptSlug?}', \App\Livewire\BranchDashboard\Production\MaterialReturns\Index::class)->name('material-returns.history');

        // Store-to-Store Transfers (raw materials between production departments)
        Route::get('store-transfers/{deptSlug?}', \App\Livewire\BranchDashboard\Production\StoreTransfers\Index::class)->name('store-transfers.index');

        // Sales Return Callbacks — production side approval/receipt workflow
        Route::get('/callbacks/{deptSlug?}', \App\Livewire\BranchDashboard\Production\Callbacks\ApproveCallbacks::class)->name('callbacks.approve');
    };

    $registerProductionDepartmentRoutes();

    // Customer Orders — branch-wide, no department context required
    Route::get('/customer-orders', \App\Livewire\BranchDashboard\Production\CustomerOrders\Index::class)
        ->name('customer-orders.index');

    // Production Reports - Grouped
    Route::prefix('reports')->name('reports.')->group(function () {
        // Grouped report pages
        Route::get('/operations', \App\Livewire\BranchDashboard\Production\Reports\OperationsReports::class)->name('operations');
        Route::get('/performance', \App\Livewire\BranchDashboard\Production\Reports\PerformanceReports::class)->name('performance');

        // Individual reports (kept for backward compatibility)
        Route::get('/efficiency', \App\Livewire\BranchDashboard\Production\Reports\ProductionEfficiency\Index::class)->name('efficiency');
        Route::get('/quality', \App\Livewire\BranchDashboard\Production\Reports\QualityMetrics\Index::class)->name('quality');
        Route::get('/waste', \App\Livewire\BranchDashboard\Production\Reports\WasteAnalysis\Index::class)->name('waste');
        Route::get('/cost', \App\Livewire\BranchDashboard\Production\Reports\CostAnalysis\Index::class)->name('cost');
        Route::get('/ingredient-utilization', \App\Livewire\BranchDashboard\Production\Reports\IngredientUtilization\Index::class)->name('ingredient-utilization');
        Route::get('/pipeline', \App\Livewire\BranchDashboard\Production\Reports\PipelineStatus\Index::class)->name('pipeline');
        Route::get('/capacity', \App\Livewire\BranchDashboard\Production\Reports\CapacityPlanning\Index::class)->name('capacity');
    });

    // Production Reporting Library (generated reports)
    Route::prefix('reporting')->name('reporting.')->group(function () {
        Route::get('/', \App\Livewire\BranchDashboard\Production\Reporting\Index::class)->name('index');
        Route::get('/{id}', \App\Livewire\BranchDashboard\Production\Reporting\Show::class)->name('show');
    });
});

Route::prefix('sales-dashboard')->name('sales-dashboard.')->middleware([
    'department.scope',
    'validate-sales-department-context',
])->group(function () {

    // Helper function to register sales department routes with workflow protection
    $registerSalesDepartmentRoutes = function () {
        // POS Routes - Requires stock verification to be completed
        Route::prefix('pos')->name('pos.')->middleware(['validate-sales-workflow'])->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Pos\Index::class)->name('index');
        });

        // Bill - Print a customer bill before payment (no workflow validation; print-only, no DB writes)
        Route::prefix('bill')->name('bill.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Bill\Index::class)->name('index');
        });

        // Analytics - Less restrictive, no workflow validation needed
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Analytics\Index::class)->name('index');
        });

        // My Sales - Personal Sales Dashboard, less restrictive
        Route::prefix('my-sales')->name('my-sales.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\MySales\Index::class)->name('index');
        });

        // Sales Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales-performance/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Reports\SalesPerformance\Index::class)
                ->name('sales-performance');
        });

        // Shift Closing - Sales (department-based) - Protected by workflow
        Route::prefix('shift-closing')->name('shift-closing.')->middleware(['validate-sales-workflow'])->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\ShiftClosing\Index::class)->name('index');
        });

        // Quotations / draft orders - no workflow validation (no stock/GL impact)
        Route::prefix('quotations')->name('quotations.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Quotations\Index::class)->name('index');
        });

        // Callbacks - Sales callbacks management
        Route::prefix('callbacks')->name('callbacks.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Callbacks\Index::class)->name('index');
            Route::get('/dispatch-callbacks/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Callbacks\CreateDispatchCallback::class)->name('dispatch-callbacks');
        });

        // Variance Resolution - Supervisor+ only
        Route::prefix('variance-resolution')->name('variance-resolution.')->middleware(['role.level:2'])->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\VarianceResolution\Index::class)->name('index');
        });

        // Sales-point transfers - move product stock between sales points (Phase 1).
        // Cashiers may transfer directly (no manager approval) per SALES_POINT_TRANSFER_SPEC.md §8.2.
        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Transfers\Index::class)->name('index');
        });
    };

    // Expiry Alerts - shown after clock-in
    Route::get('/expiry-alerts', \App\Livewire\BranchDashboard\SalesDashboard\ExpiryAlerts::class)->name('expiry-alerts');

    // Stock Opening - Entry point for workflow, protected by department context
    Route::prefix('stock-opening')->name('stock-opening.')->middleware(['validate-sales-workflow'])->group(function () {
        Route::get('/select/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Select\Index::class)->name('select.index');
        Route::get('/entry/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Entry\Index::class)->name('entry.index');
        Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\StockOpening\Select\Index::class)->name('index');
    });

    Route::prefix('dispatches')->name('dispatches.')->group(function () {
        Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\Dispatches\Index::class)->name('index');
    });

    Route::prefix('production-requests')->name('production-requests.')->group(function () {
        Route::get('/workflow/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests\Index::class)->name('workflow');
        Route::get('/{salesDeptSlug}/create', \App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests\Create::class)->name('create');
        Route::get('/{salesDeptSlug?}', \App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests\Index::class)->name('index');
    });

    Route::get('/stock-monitor', \App\Livewire\BranchDashboard\SalesDashboard\StockMonitor::class)->name('stock-monitor');

    $registerSalesDepartmentRoutes();
});
