<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetBranchContext;
use App\Http\Middleware\ProtectCoreRoles;
use App\Http\Middleware\RedirectSuperAdminToDashboard;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\ValidateSalesWorkflow;
use App\Http\Middleware\ValidateSalesDepartmentContext;
use App\Http\Middleware\RecoverAuthUser;
use App\Http\Middleware\RequireActiveShift;
use App\Http\Middleware\DepartmentScopeMiddleware;
use App\Http\Middleware\RoleLevelMiddleware;
use App\Http\Middleware\TrackPageActivity;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isAdmin' => IsAdmin::class,
            'branch'  => BranchMiddleware::class,
            'setBranchContext' => SetBranchContext::class,
            'guest' => RedirectIfAuthenticated::class,
            'role' => RequireRole::class,
            'permission' => RequirePermission::class,
            'role_or_permission' => \App\Http\Middleware\SuperAdminOrPermission::class,
            'protect-roles' => ProtectCoreRoles::class,
            'redirect-super-admin' => RedirectSuperAdminToDashboard::class,
            'validate-sales-workflow' => ValidateSalesWorkflow::class,
            'validate-sales-department-context' => ValidateSalesDepartmentContext::class,
            'recover-auth' => RecoverAuthUser::class,
            'require_active_shift' => RequireActiveShift::class,
            'department.scope' => DepartmentScopeMiddleware::class,
            'role.level'          => RoleLevelMiddleware::class,
            'track-page-activity' => TrackPageActivity::class,
        ]);

        // Apply SetBranchContext and page activity tracking to web middleware group
        $middleware->web(append: [
            SetBranchContext::class,
            TrackPageActivity::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // === SHIFT SYSTEM AUTOMATION ===

        // Auto clock out expired shifts every 5 minutes during business hours
        $schedule->command('shifts:auto-clock-out')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // 10 minute timeout
            ->runInBackground()
            ->evenInMaintenanceMode()
            ->onFailure(function () {
                \Log::error('Auto clock out command failed');
            });

        // Send shift reminders every hour during business hours
        $schedule->command('shifts:send-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old shift data weekly
        $schedule->command('shifts:cleanup-old-data')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->runInBackground();

        // Generate shift reports daily
        $schedule->command('shifts:generate-daily-reports')
            ->daily()
            ->at('23:30')
            ->runInBackground();

        // Monitor shift system health
        $schedule->command('shifts:health-check')
            ->everyTenMinutes()
            ->runInBackground();

        // === END SHIFT SYSTEM AUTOMATION ===

        // === ACTIVITY LOG RETENTION ===
        $schedule->command('activity:prune --days=90')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->runInBackground();
        // === END ACTIVITY LOG RETENTION ===

        // === GL POSTING AUTOMATION ===
        $schedule->job(new \App\Jobs\RetryFailedGlPostingsJob)
            ->everyFifteenMinutes()
            ->withoutOverlapping(10);
        // === END GL POSTING AUTOMATION ===

        // === INVENTORY EXPIRY AUTOMATION ===
        $schedule->command('inventory:check-batch-expiry')
            ->dailyAt('00:05')
            ->withoutOverlapping(10)
            ->runInBackground();
        // === END INVENTORY EXPIRY AUTOMATION ===
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
