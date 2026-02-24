<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveShift
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for specific routes and user types
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        $employee = Auth::user();

        // Validate user exists and is not super admin
        if (!$employee || $this->isSuperAdminUser($employee) || (function_exists('is_super_admin') && is_super_admin())) {
            return $next($request);
        }

        // Check for active shift
        $activeShift = $this->getActiveShift($employee);

        if (!$activeShift) {
            return $this->handleInactiveShift($request, $employee);
        }

        // Add shift context to request for downstream use
        $request->merge(['active_shift' => $activeShift]);

        return $next($request);
    }

    /**
     * Determine if validation should be skipped
     */
    protected function shouldSkipValidation(Request $request): bool
    {
        // Skip for unauthenticated requests
        if (!Auth::check()) {
            return true;
        }

        // Skip for super admins
        if ($this->isSuperAdminUser(Auth::user()) || (function_exists('is_super_admin') && is_super_admin())) {
            return true;
        }

        // Skip for API routes that don't require shift validation
        $apiRoutesToSkip = [
            'api/user/profile',
            'api/notifications',
            'api/logout'
        ];

        if ($request->is($apiRoutesToSkip)) {
            return true;
        }

        // Skip for shift-related routes (to prevent infinite loops)
        $shiftRoutes = [
            'branch-dashboard.select_shift',
            'livewire/auth/shift',
            'branch-dashboard.sales-dashboard.shift-closing.index',
        ];

        if ($request->routeIs($shiftRoutes)) {
            return true;
        }

        return false;
    }

    private function isSuperAdminUser($user): bool
    {
        if (!$user) {
            return false;
        }

        if (isset($user->user_type) && $user->user_type === 'admin') {
            return true;
        }

        $role = $user->roles()->orderByDesc('level')->first();
        if ($role && isset($role->level) && (int) $role->level >= 5) {
            return true;
        }

        return $user->hasRole('Super Admin');
    }

    /**
     * Get active shift for employee
     */
    protected function getActiveShift($employee): ?Shift
    {
        return Shift::where('employee_id', $employee->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->with('branch:id,name') // Eager load for performance
            ->first();
    }

    /**
     * Handle inactive shift scenario
     */
    protected function handleInactiveShift(Request $request, $employee): Response
    {
        // Log the access attempt
        Log::info('Unauthorized access attempt - no active shift', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'route' => $request->route()?->getName(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String()
        ]);

        // For AJAX requests, return JSON error
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'error' => 'Active shift required',
                'message' => 'Please clock in to start your shift before accessing work functions.',
                'redirect' => route('branch-dashboard.select_shift', [
                    'b_id' => $request->query('b_id') ?? current_branch_id()
                ])
            ], 403);
        }

        // For regular requests, redirect to shift selection
        return redirect()->route('branch-dashboard.select_shift', [
            'b_id' => $request->query('b_id') ?? current_branch_id()
        ])->with('error', 'Please clock in to start your shift before accessing work functions.');
    }
}
