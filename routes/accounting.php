<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting Routes
|--------------------------------------------------------------------------
|
| Here are all the routes related to the accounting system
| These routes now point to the existing Livewire components in the branch dashboard
|
*/

Route::middleware(['auth', 'accounting'])->prefix('accounting')->group(function () {

    // Dashboard - Use the branch dashboard layout directly
    Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\Home::class)
        ->name('accounting.dashboard');

    // GL Accounts Management - Use branch dashboard components with proper layout
    Route::prefix('gl-accounts')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\ChartOfAccounts::class)
            ->name('accounting.gl-accounts.index');
    });

    // Bank Accounts Management
    Route::prefix('bank-accounts')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\CashBank::class)
            ->name('accounting.bank-accounts.index');
    });

    // Journal Entries - Use branch dashboard components with proper layout
    Route::prefix('journal-entries')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\Journals::class)
            ->name('accounting.journal-entries.index');

        Route::get('create', App\Livewire\BranchDashboard\Accounting\Simple\Journals::class)
            ->name('accounting.journal-entries.create');
    });

    // Accounting Periods - Use branch dashboard components with proper layout
    Route::prefix('periods')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\PeriodControl::class)
            ->name('accounting.periods.index');
    });

    // Reports - Use branch dashboard components with proper layout
    Route::prefix('reports')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Report\Index::class)
            ->name('accounting.reports.index');

        Route::get('balance-sheet', App\Livewire\BranchDashboard\Accounting\Report\BalanceSheetReport::class)
            ->name('accounting.reports.balance-sheet');

        Route::get('income-statement', App\Livewire\BranchDashboard\Accounting\Report\IncomeStatementReport::class)
            ->name('accounting.reports.income-statement');

        Route::get('trial-balance', App\Livewire\BranchDashboard\Accounting\Report\TrialBalanceReport::class)
            ->name('accounting.reports.trial-balance');

        Route::get('general-ledger', App\Livewire\BranchDashboard\Accounting\Report\GeneralLedgerReport::class)
            ->name('accounting.reports.general-ledger');

        Route::get('cash-flow', App\Livewire\BranchDashboard\Accounting\Report\CashFlowStatementReport::class)
            ->name('accounting.reports.cash-flow');
    });

    // Bank Reconciliation - Use branch dashboard components with proper layout
    Route::prefix('bank-reconciliation')->group(function () {
        Route::get('/', App\Livewire\BranchDashboard\Accounting\Simple\CashBank::class)
            ->name('accounting.bank-reconciliation.index');
        Route::get('/manage', App\Livewire\BranchDashboard\Accounting\BankReconciliation::class)
            ->name('accounting.bank-reconciliation.manage');
    });

    // Overview/Audit - Use branch dashboard components with proper layout
    Route::get('overview', App\Livewire\BranchDashboard\Accounting\Simple\Home::class)
        ->name('accounting.overview');

    // Posting Status - Use branch dashboard components with proper layout
    Route::get('posting-status', App\Livewire\BranchDashboard\Accounting\Simple\Transactions::class)
        ->name('accounting.posting-status');

    Route::get('inventory-valuation', App\Livewire\BranchDashboard\Accounting\Simple\InventoryValuation::class)
        ->name('accounting.inventory-valuation.index');
    Route::get('inventory-valuation/manage', App\Livewire\BranchDashboard\Accounting\InventoryValuationPosting::class)
        ->name('accounting.inventory-valuation.manage');
});

// API Routes for Accounting - These remain as they likely connect to services rather than controllers
Route::middleware(['auth', 'api', 'accounting'])->prefix('api/accounting')->group(function () {

    // These API routes would need to be implemented separately if needed
    // For now, leaving them commented as placeholders
    /*
    // GL Accounts API
    Route::get('/gl-accounts', [SomeApiController::class, 'index']);
    Route::get('/gl-accounts/{account}', [SomeApiController::class, 'show']);
    Route::get('/gl-accounts/{account}/balance', [SomeApiController::class, 'balance']);

    // Journal Entries API
    Route::get('/journal-entries', [SomeApiController::class, 'index']);
    Route::post('/journal-entries', [SomeApiController::class, 'store']);
    Route::get('/journal-entries/{entry}', [SomeApiController::class, 'show']);
    Route::post('/journal-entries/{entry}/post', [SomeApiController::class, 'post']);

    // Accounting Periods API
    Route::get('/periods', [SomeApiController::class, 'index']);
    Route::get('/periods/{period}', [SomeApiController::class, 'show']);
    Route::get('/periods/current', [SomeApiController::class, 'current']);

    // Reports API
    Route::get('/reports/balance-sheet', [SomeApiController::class, 'balanceSheet']);
    Route::get('/reports/income-statement', [SomeApiController::class, 'incomeStatement']);
    Route::get('/reports/trial-balance', [SomeApiController::class, 'trialBalance']);
    Route::get('/reports/general-ledger', [SomeApiController::class, 'generalLedger']);

    // Bank Reconciliation API
    Route::get('/bank-reconciliation/{account}', [SomeApiController::class, 'reconcile']);
    Route::post('/bank-reconciliation/{account}/match', [SomeApiController::class, 'match']);

    // Audit Trail API
    Route::get('/audit-trail', [SomeApiController::class, 'trail']);
    Route::get('/audit-trail/{model}', [SomeApiController::class, 'modelTrail']);
    */
});
