<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'recover-auth', 'setBranchContext', 'branch', 'redirect-super-admin'])->prefix('branch-dashboard')->name('branch-dashboard.')->group(function () {
    // Dashboard Router - Redirects to appropriate dashboard based on role
    Route::get('/dashboard/router', App\Livewire\BranchDashboard\Dashboards\Router::class)->name('dashboards.router');

    // New Role-Based Dashboards (Phase 3) - Protected by role.level middleware
    Route::get('/dashboards/super-admin', App\Livewire\BranchDashboard\Dashboards\SuperAdminDashboard::class)
        ->middleware('role.level:5')
        ->name('dashboards.super-admin');
    Route::get('/dashboards/admin', App\Livewire\BranchDashboard\Dashboards\AdminDashboard::class)
        ->middleware('role.level:4')
        ->name('dashboards.admin');
    Route::get('/dashboards/manager', App\Livewire\BranchDashboard\Dashboards\ManagerDashboard::class)
        ->middleware('role.level:3')
        ->name('dashboards.manager');
    Route::get('/dashboards/supervisor', App\Livewire\BranchDashboard\Dashboards\SupervisorDashboard::class)
        ->middleware('role.level:2')
        ->name('dashboards.supervisor');

    // Legacy Role-Specific Dashboards (kept for backward compatibility)
    Route::middleware('role_or_permission:view-inventory')->get('/dashboard/inventory', App\Livewire\Dashboards\InventoryDashboard::class)->name('dashboard.inventory');
    Route::middleware('role_or_permission:view-production')->get('/dashboard/production/{deptSlug?}', App\Livewire\Dashboards\ProductionDashboard::class)->name('dashboard.production');
    Route::middleware('role_or_permission:view-sales|Sales Manager|Sales Staff|Floor Manager|Assistant Shop Floor Manager|Cashier|Wait Staff|Lobby Host|Lobby Host Supervisor|Coffee Barista|Coffee Barista Trainer|Consession Attendant|Consession Supervisor|Cornerstore Supervisor|Till Supervisor')->get('/dashboard/sales/{salesDeptSlug?}', App\Livewire\Dashboards\SalesDashboard::class)->name('dashboard.sales');
    Route::middleware('role_or_permission:view-sales')->get('/dashboard/corner-store', App\Livewire\Dashboards\CornerStoreDashboard::class)->name('dashboard.corner-store');
    Route::middleware('role_or_permission:manage-organization')->get('/dashboard/hr', \App\Livewire\Dashboards\HRDashboard::class)->name('dashboard.hr');
    Route::middleware('role_or_permission:manage-branches')->get('/dashboard/admin', \App\Livewire\Dashboards\BranchAdminDashboard::class)->name('dashboard.admin');
    Route::middleware('role.level:5')->get('/dashboard/super-admin', \App\Livewire\Dashboards\SuperAdminDashboard::class)->name('dashboard.super-admin');

    // Root dashboard path - redirect to router for role-based redirect
    Route::get('/', function () {
        $branchId = request()->query('b_id') ?? current_branch_id();
        if ($branchId) {
            return redirect()->route('branch-dashboard.dashboards.router', ['b_id' => $branchId]);
        }

        return redirect()->route('branch-dashboard.dashboards.router');
    })->name('index');

    // ====== ORGANIZATION SECTION (HR Manager, HR Officer, Admin) ======
    Route::middleware('role_or_permission:manage-organization')->group(function () {
        // Employee Management
        Route::get('/employees', App\Livewire\BranchDashboard\EmployeeModule\Index::class)->name('employee.index');
        Route::get('employee/create', App\Livewire\BranchDashboard\EmployeeModule\Create::class)->name('employee.create');
        Route::get('employee/{id}/edit', \App\Livewire\BranchDashboard\EmployeeModule\Edit::class)->name('employee.edit');
        Route::get('/employee/{employee_number?}/{id}/', \App\Livewire\BranchDashboard\EmployeeModule\Details::class)->name('employee.details');

        // Employee Appraisals
        Route::get('/employee-appraisals', App\Livewire\BranchDashboard\EmployeeAppraisals::class)->name('employee-appraisals');
        Route::get('/employee-appraisal-history/{employee}', App\Livewire\BranchDashboard\EmployeeAppraisalHistory::class)->name('employee-appraisal-history');
        Route::get('/appraise-employee/{employee}', App\Livewire\BranchDashboard\AppraiseEmployee::class)->name('appraise-employee');

        // Clock-In Board Routes
        Route::prefix('clock-in-board')->name('clock-in-board.')->group(function () {
            Route::get('/', \App\Livewire\BranchDashboard\EmployeeModule\ClockInModule\TodayIndex::class)->name('today');
            Route::get('all', \App\Livewire\BranchDashboard\EmployeeModule\ClockInModule\GeneralClockInBoard::class)->name('all');
            Route::get('employee/{employee}/history', \App\Livewire\BranchDashboard\EmployeeModule\ClockInModule\EmployeeHistory::class)->name('employee-history');
        });

        // Employee Appraisals
        Route::get('/employee-appraisals', App\Livewire\BranchDashboard\EmployeeAppraisals::class)->name('employee-appraisals');
        Route::get('/appraise-employee/{employee}', App\Livewire\BranchDashboard\AppraiseEmployee::class)->name('appraise-employee');

        // HR Appraisal Management
        Route::prefix('hr')->name('hr.')->group(function () {
            Route::prefix('appraisals')->name('appraisals.')->group(function () {
                Route::get('/cycles', App\Livewire\BranchDashboard\HR\AppraisalCycles::class)->name('cycles');
             
            });
         

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::middleware('role_or_permission:manage-organization')->get(
                    '/workforce-overview',
                    \App\Livewire\BranchDashboard\HR\Reports\WorkforceOverview\Index::class
                )->name('workforce-overview');
            });
        });

        // Leave Management routes (restricted to HR/organization managers)
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/types', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\LeaveTypes::class)->name('types');
            Route::get('/approve', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\ApproveLeave::class)->name('approve');
            Route::get('/manage-allocations', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\ManageAllocations::class)->name('manage-allocations');
        });

        // DEPARTMENT / DEPARTMENT CATEGORY SECTION
        Route::get('departments', App\Livewire\BranchDashboard\DepartmentModule\Index::class)->name('branch.departments.index');
        Route::get('department/create', \App\Livewire\BranchDashboard\DepartmentModule\Department\CreateOrUpdate::class)->name('department.create');
        Route::get('department/{id}/edit', \App\Livewire\BranchDashboard\DepartmentModule\Department\CreateOrUpdate::class)->name('department.edit');
        Route::get('departments/category', \App\Livewire\BranchDashboard\DepartmentModule\Category::class)->name('branch.departments.category');
        Route::get('/department/category/create', \App\Livewire\BranchDashboard\DepartmentModule\Cartegory\Create::class)->name('department.category.create');
        Route::get('department/category/{id}/edit', \App\Livewire\BranchDashboard\DepartmentModule\Cartegory\Edit::class)->name('department.category.edit');

        // UOM MANAGEMENT FRONTEND (Organization)
        Route::prefix('organization/uom')->name('organization.uom.')->group(function () {
            Route::get('/', \App\Livewire\BranchDashboard\Organization\UomIndex::class)->name('index');
            Route::get('/units', \App\Livewire\BranchDashboard\Organization\UomUnits::class)->name('units');
            Route::get('/conversions', \App\Livewire\BranchDashboard\Organization\UomConversionsBrowser::class)->name('conversions');
            Route::get('/manage', \App\Livewire\BranchDashboard\Organization\UomConversionsManager::class)->name('manage');
        });

        // ROLE ASSIGNMENT (HR can manage roles within their branch)
        Route::get('role-assignments', \App\Livewire\BranchDashboard\EmployeeModule\RolePermission\AssignRole::class)->name('role-assignments.index');
        Route::get('/role-permisssion', \App\Livewire\BranchDashboard\EmployeeModule\RolePermission\Index::class)->name('role-permission');
    });

    // ROLE MANAGEMENT (Super Admin Only - Level 5)
    Route::middleware(['role_or_permission:manage-roles', 'role.level:5'])
        ->get('roles', \App\Livewire\BranchDashboard\Roles\Index::class)
        ->name('roles.index');

    // BRANCH MANAGEMENT (Super Admin Only - Level 5)
    Route::middleware(['role_or_permission:manage-branches', 'role.level:5'])->group(function () {
        Route::get('branches', \App\Livewire\BranchDashboard\Branches\Index::class)->name('branches.index');
        Route::get('deleted-branches', \App\Livewire\BranchDashboard\Branches\DeleteBranch::class)->name('branches.deleted');
    });

    // SETTINGS (Super Admin Only - Level 5)
    Route::middleware(['role_or_permission:manage-settings', 'role.level:5'])
        ->get('settings', \App\Livewire\BranchDashboard\Settings\Index::class)
        ->name('settings.index');
    
    // PROFILE SETTINGS (All authenticated users)
    Route::get('profile', \App\Livewire\BranchDashboard\Settings\Profile::class)
        ->name('profile');
    Route::get('profile/security', \App\Livewire\BranchDashboard\Settings\Security::class)
        ->name('profile.security');

    // Leave Management routes (available to all authenticated branch users)
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/apply', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\ApplyLeave::class)->name('apply');
        Route::get('/my-leaves', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\MyLeaves::class)->name('my-leaves');
        Route::get('/balance', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\LeaveBalance::class)->name('balance');
    });

    // MD REPORTS (Super Admin Only)
    Route::middleware('role_or_permission:view-reports')->prefix('md-reports')->name('md-reports.')->group(function () {
        Route::get('dashboard', \App\Livewire\BranchDashboard\MDReports\Dashboard\Index::class)->name('dashboard');
        Route::get('view/{id}', \App\Livewire\BranchDashboard\MDReports\ViewReport\Index::class)->name('view');
    });

    // Organization Helper
    Route::get('organization/helper', \App\Livewire\BranchDashboard\Organization\Helper::class)->name('organization.helper');

    // Inventory Helper
    Route::get('inventory/helper', \App\Livewire\BranchDashboard\Inventory\Helper::class)->name('inventory.helper');

    // Sales Helper
    Route::get('sales-dashboard/helper', \App\Livewire\BranchDashboard\SalesDashboard\Helper::class)->name('sales-dashboard.helper');

    // Production Helper
    Route::get('production/helper', \App\Livewire\BranchDashboard\Production\Helper::class)->name('production.helper');

    // Shift Selection functionality
    Route::get('auth/shift', \App\Livewire\Auth\Shift::class)->name('select_shift');

    // SHIFT MANAGEMENT (HR + Admin + Super Admin)
    Route::middleware(['role_or_permission:manage-staff-schedule,manage-organization'])->prefix('shift-management')->name('shift-management.')->group(function () {
        Route::get('/', \App\Livewire\BranchDashboard\ShiftManagement\Index::class)->name('index');
        Route::get('/configuration', \App\Livewire\BranchDashboard\ShiftManagement\ShiftConfiguration::class)->name('configuration');
        Route::get('/assignment', \App\Livewire\BranchDashboard\ShiftManagement\ShiftAssignment::class)->name('assignment');
    });

    // Routes requiring active shift (all work functions)
    Route::middleware(['require_active_shift'])->group(function () {
        // Inventory routes
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('items', \App\Livewire\BranchDashboard\Inventory\Items::class)->name('items');
            Route::get('purchases', \App\Livewire\BranchDashboard\Inventory\Purchases::class)->name('purchases');
            Route::get('stocks', \App\Livewire\BranchDashboard\Inventory\Stocks::class)->name('stocks');
            Route::get('item-requests', \App\Livewire\BranchDashboard\Inventory\ItemRequests::class)->name('item-requests');
            Route::get('item-dispatches', \App\Livewire\BranchDashboard\Inventory\ItemDispatches::class)->name('item-dispatches');
            Route::get('stock-takes', \App\Livewire\BranchDashboard\Inventory\StockTakes::class)->name('stock-takes');
            Route::get('health-checks', \App\Livewire\BranchDashboard\Inventory\HealthChecks::class)->name('health-checks');
            Route::get('reports', \App\Livewire\BranchDashboard\Inventory\Reports\Index::class)->name('reports');
            Route::get('reports/{id}', \App\Livewire\BranchDashboard\Inventory\Reports\Show::class)->name('reports.show');

            // Shift Closing - Inventory (not department-based)
            Route::get('shift-closing', \App\Livewire\BranchDashboard\Inventory\ShiftClosing\Index::class)->name('shift-closing');

            // Callbacks - Inventory reviewing production callbacks
            Route::prefix('callbacks')->name('callbacks.')->group(function () {
                Route::get('/', \App\Livewire\BranchDashboard\Inventory\Callbacks\ApproveCallbacks::class)->name('index');
            });

        });

        // Supplier Management routes
        Route::middleware('role_or_permission:view-suppliers|manage-suppliers')->prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', \App\Livewire\BranchDashboard\Supplier\SupplierIndex::class)->name('index');
            Route::middleware('role_or_permission:create-suppliers|manage-suppliers')->get('/create', \App\Livewire\BranchDashboard\Supplier\CreateSupplier::class)->name('create');
            Route::get('/{supplier}', \App\Livewire\BranchDashboard\Supplier\SupplierDetails::class)->name('show');
            Route::get('/{supplier}/performance', \App\Livewire\BranchDashboard\Supplier\SupplierPerformance::class)->name('performance');
        });

      

        // Analytics routes
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('overview', \App\Livewire\BranchDashboard\Analytics\OverallSummaryDashboard::class)->name('overview');
            Route::get('stock-level', \App\Livewire\BranchDashboard\Analytics\StockLevelAnalytics::class)->name('stock-level');
            Route::get('stock-movement', \App\Livewire\BranchDashboard\Analytics\StockMovementAnalytics::class)->name('stock-movement');
            Route::get('purchase', \App\Livewire\BranchDashboard\Analytics\PurchaseAnalytics::class)->name('purchase');
            Route::get('request-dispatch', \App\Livewire\BranchDashboard\Analytics\RequestDispatchAnalytics::class)->name('request-dispatch');
            Route::get('alerts', \App\Livewire\BranchDashboard\Analytics\AlertsDashboard::class)->name('alerts');
            Route::get('stock-valuation', \App\Livewire\BranchDashboard\Analytics\StockValuation::class)->name('stock-valuation');
        });

        // Print routes
        Route::prefix('prints')->name('prints.')->group(function () {
            Route::get('department-report/{reportId}', [\App\Http\Controllers\ReportPrintController::class, 'departmentReport'])->name('department-report');
            Route::get('compiled-report/{reportId}', [\App\Http\Controllers\ReportPrintController::class, 'compiled-report'])->name('compiled-report');
            Route::get('accounting/cash-flow-statement', [\App\Http\Controllers\ReportPrintController::class, 'cashFlowStatement'])->name('accounting.cash-flow-statement');
        });

        // Reporting Department Routes
        Route::prefix('reporting')->name('reporting.')->group(function () {
            Route::get('generate', \App\Livewire\BranchDashboard\Reporting\Generate::class)->name('generate');
            Route::get('dashboard', \App\Livewire\BranchDashboard\ReportingDepartment\Dashboard\Index::class)->name('dashboard');
            Route::get('review', \App\Livewire\BranchDashboard\ReportingDepartment\ReviewReports\Index::class)->name('review');
            Route::get('compile', \App\Livewire\BranchDashboard\ReportingDepartment\CompileReports\Index::class)->name('compile');
            Route::get('report/{id}', \App\Livewire\BranchDashboard\ReportingDepartment\ReportDetail\Index::class)->name('report.view');
            Route::get('compiled/{id}', \App\Livewire\BranchDashboard\ReportingDepartment\ViewCompiled\Index::class)->name('compiled.view');
            Route::get('send-to-md', \App\Livewire\BranchDashboard\ReportingDepartment\SendToMD\Index::class)->name('send-to-md');
        });

        // Audit Management Routes
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/', \App\Livewire\BranchDashboard\AuditManagement\Index::class)->name('index');
            Route::get('inventory-approvals', \App\Livewire\BranchDashboard\AuditManagement\InventoryApprovals::class)->name('inventory-approvals');
        });

        // Accounting Routes - Role Based Access (Super Admin, Managing Director, Accountant)
        Route::prefix('accounting')->name('accounting.')->middleware('role_or_permission:access_accounting|view_financial_reports|Accounting Manager|Accountant|Cost Accountant|Managing Director')->group(function () {
            // Accounting Home
            Route::get('/dashboard', \App\Livewire\BranchDashboard\Accounting\Simple\Home::class)->name('dashboard');

            // Accounting Overview (now home)
            Route::get('/overview', \App\Livewire\BranchDashboard\Accounting\Simple\Home::class)->name('overview');

            // Chart of Accounts Management (Super Admin, Managing Director, Admin)
            Route::middleware('role_or_permission:manage_accounts')->group(function () {
                Route::get('/accounts', \App\Livewire\BranchDashboard\Accounting\Simple\ChartOfAccounts::class)->name('accounts');
            });

            // Bank Accounts Management
            Route::middleware('role_or_permission:Super Admin,Managing Director,Admin,Accountant,Accounting Manager,view_bank_accounts,create_bank_accounts,edit_bank_accounts')->group(function () {
                Route::get('/bank-accounts', \App\Livewire\BranchDashboard\Accounting\Simple\CashBank::class)->name('bank-accounts');
            });

            // Accounting Period Management (Super Admin, Managing Director, Admin)
            Route::middleware('role_or_permission:manage_periods')->group(function () {
                Route::get('/periods', \App\Livewire\BranchDashboard\Accounting\Simple\PeriodControl::class)->name('periods');
            });

            // Manual Journal Entry (Super Admin, Managing Director, Accountant, Admin)
            Route::middleware('role_or_permission:create_journal_entries|Accounting Manager|Accountant|Cost Accountant|Managing Director|Admin|Super Admin')->group(function () {
                Route::get('/journal-entry', \App\Livewire\BranchDashboard\Accounting\Simple\Journals::class)->name('journal-entry');
            });

            // Quick Expense (single-entry)
            Route::get('/quick-expense', \App\Livewire\BranchDashboard\Accounting\Simple\QuickExpense::class)
                ->name('quick-expense');

            // Posting Status Monitor
            Route::get('/posting-status', \App\Livewire\BranchDashboard\Accounting\Simple\Transactions::class)->name('posting-status');

            // Inventory Valuation to GL
            Route::get('/inventory-valuation', \App\Livewire\BranchDashboard\Accounting\Simple\InventoryValuation::class)->name('inventory-valuation');
            Route::get('/inventory-valuation/manage', \App\Livewire\BranchDashboard\Accounting\InventoryValuationPosting::class)
                ->name('inventory-valuation-manage');

            // Bank Reconciliation
            Route::middleware('role_or_permission:reconcile_bank_accounts')->group(function () {
                Route::get('/bank-reconciliation', \App\Livewire\BranchDashboard\Accounting\Simple\CashBank::class)->name('bank-reconciliation');
                Route::get('/bank-reconciliation/manage', \App\Livewire\BranchDashboard\Accounting\BankReconciliation::class)
                    ->name('bank-reconciliation-manage');
            });

            // POS Remittances
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/pos-remittances', \App\Livewire\BranchDashboard\Accounting\Simple\PosRemittances::class)
                    ->name('pos-remittances');
            });

            // Expense Imports
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/expense-imports', \App\Livewire\BranchDashboard\Accounting\Simple\ExpenseImports::class)
                    ->name('expense-imports');
            });

            // Accounting Entries (General)
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/accounting-entries', \App\Livewire\BranchDashboard\Accounting\Simple\AccountingEntries::class)
                    ->name('accounting-entries');
            });

            // Payroll
            Route::middleware('role_or_permission:access_accounting,manage-organization,view_financial_reports')->group(function () {
                Route::get('/payroll', \App\Livewire\BranchDashboard\Accounting\Simple\Payrolls::class)
                    ->name('payroll');
            });

            // Tax Payments
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/tax-payments', \App\Livewire\BranchDashboard\Accounting\Simple\TaxPayments::class)
                    ->name('tax-payments');
            });

            // Fixed Assets
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/fixed-assets', \App\Livewire\BranchDashboard\Accounting\Simple\FixedAssets::class)
                    ->name('fixed-assets');
            });

            // Budgets
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/budgets', \App\Livewire\BranchDashboard\Accounting\Simple\Budgets::class)
                    ->name('budgets');
            });

            // Purchase Payments (AP settlement)
            Route::middleware('role_or_permission:access_accounting,view_financial_reports')->group(function () {
                Route::get('/purchase-payments', \App\Livewire\BranchDashboard\Accounting\Simple\PurchasePayments::class)
                    ->name('purchase-payments');
            });

            // Financial Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', \App\Livewire\BranchDashboard\Accounting\Report\Index::class)->name('index');
                Route::get('/general-ledger', \App\Livewire\BranchDashboard\Accounting\Report\GeneralLedgerReport::class)->name('general-ledger');
                Route::get('/trial-balance', \App\Livewire\BranchDashboard\Accounting\Report\TrialBalanceReport::class)->name('trial-balance');
                Route::get('/income-statement', \App\Livewire\BranchDashboard\Accounting\Report\IncomeStatementReport::class)->name('income-statement');
                Route::get('/balance-sheet', \App\Livewire\BranchDashboard\Accounting\Report\BalanceSheetReport::class)->name('balance-sheet');
                Route::get('/cash-flow-statement', \App\Livewire\BranchDashboard\Accounting\Report\CashFlowStatementReport::class)->name('cash-flow-statement');
            });
        });

        // Sales Dashboard routes - Modular System
        // Protected by department.scope + workflow middleware for proper step validation
    require __DIR__. '/production-sales-route.php';
    }); // Close require_active_shift middleware group
});
