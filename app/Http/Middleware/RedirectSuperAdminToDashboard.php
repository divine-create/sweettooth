<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuthService;

class RedirectSuperAdminToDashboard
{
    /**
     * Handle an incoming request.
     * 
     * If the user is a Super Admin, redirect them directly to the super-admin dashboard
     * instead of going through the shift selection flow.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super admin = web guard authenticated
        if (AuthService::isSuperAdmin()) {
            // Check if they're trying to access the shift selection page
            if ($request->routeIs('branch-dashboard.select_shift', 'branch-dashboard.dashboards.router', 'branch-dashboard.index')) {
                $branchId = $request->query('b_id');
                return redirect()->route('branch-dashboard.dashboards.super-admin', ['b_id' => $branchId]);
            }
        }

        return $next($request);
    }
}
