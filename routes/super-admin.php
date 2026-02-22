<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('super-admin')->name('super-admin.')->group(function () {
    // Redirect all old super-admin routes to branch-dashboard
    Route::redirect('roles', '/branch-dashboard/roles');
    Route::redirect('branches', '/branch-dashboard/branches');
    Route::redirect('deleted-branch', '/branch-dashboard/deleted-branches');
    Route::redirect('settings', '/branch-dashboard/settings');
    Route::redirect('settings/*', '/branch-dashboard/settings');
    Route::redirect('md-reports', '/branch-dashboard/md-reports/dashboard');
    Route::redirect('md-reports/*', '/branch-dashboard/md-reports/{1}');
});
