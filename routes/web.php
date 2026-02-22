<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', App\Livewire\Auth\LoginOption::class)->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', function () {
        $branchId = request()->query('b_id')
            ?? (function_exists('current_branch_id') ? current_branch_id() : null)
            ?? auth()->user()?->branch_id
            ?? auth()->user()?->last_accessed_branch_id;

        return redirect()->to(route(
            'branch-dashboard.profile',
            $branchId ? ['b_id' => $branchId] : []
        ));
    })->name('settings.profile');
    Route::get('settings/password', function () {
        $branchId = request()->query('b_id')
            ?? (function_exists('current_branch_id') ? current_branch_id() : null)
            ?? auth()->user()?->branch_id
            ?? auth()->user()?->last_accessed_branch_id;

        return redirect()->to(route(
            'branch-dashboard.profile.security',
            $branchId ? ['b_id' => $branchId] : []
        ));
    })->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Leave Management routes accessible to all authenticated users
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/apply', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\ApplyLeave::class)->name('apply');
        Route::get('/my-leaves', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\MyLeaves::class)->name('my-leaves');
        Route::get('/balance', \App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement\LeaveBalance::class)->name('balance');
    });
});

// Dynamic asset routes
Route::get('/favicon.ico', [App\Http\Controllers\FaviconController::class, 'getFavicon']);
Route::get('/favicon.svg', [App\Http\Controllers\FaviconController::class, 'getSvgFavicon']);
Route::get('/apple-touch-icon.png', [App\Http\Controllers\FaviconController::class, 'getAppleTouchIcon']);
Route::get('/swtc.png', [App\Http\Controllers\AssetController::class, 'getSwtcPng']);
Route::get('/manifest.json', [App\Http\Controllers\AssetController::class, 'getManifest']);


require __DIR__.'/auth.php';
require __DIR__.'/super-admin.php';
require __DIR__.'/branch-route.php';
