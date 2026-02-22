<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use App\Models\Shift;
use App\Services\SidebarVisibilityService;
use Carbon\Carbon;

class DashboardRedirectController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user's role
     * This controller handles the dashboard routing logic
     * 
     * Flow:
     * 1. Super admin -> redirect to role-based dashboard
     * 2. Regular employee -> check if clocked in today
     *    - If not clocked in -> redirect to shift selection (clock-in page)
     *    - If clocked in -> redirect to role-based dashboard
     */
    public function redirect(Request $request)
    {
        // Get current authenticated user (from either guard)
        $user = current_actor();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Not authenticated');
        }

        // Check if super admin (users table, NOT employees guard)
        $isSuperAdmin = is_super_admin();
        
        // Get branch ID for URL parameter
        $branchId = current_branch_id();
        
        // For regular employees (not super admin), check clock-in status
        if (!$isSuperAdmin && auth()->check()) {
            if (!$this->hasClockInToday($user)) {
                // Employee not clocked in - redirect to shift selection
                if ($branchId) {
                    return redirect()->route('branch-dashboard.select_shift', ['b_id' => $branchId]);
                }
                return redirect()->route('branch-dashboard.select_shift');
            }
        }

        // Route by role level + permissions
        $deptSlug = $user->department?->slug;
        $salesDeptSlug = $deptSlug;
        $dashboardRoute = null;

        if (SidebarVisibilityService::isSuperAdmin($user)) {
            $dashboardRoute = 'branch-dashboard.dashboards.super-admin';
        } elseif (SidebarVisibilityService::isAdmin($user)) {
            $dashboardRoute = 'branch-dashboard.dashboards.admin';
        } elseif (SidebarVisibilityService::canSeeAccounting($user)) {
            $dashboardRoute = 'branch-dashboard.accounting.dashboard';
        } elseif (SidebarVisibilityService::canSeeProduction($user)) {
            $dashboardRoute = 'branch-dashboard.dashboard.production';
        } elseif (SidebarVisibilityService::canSeeSalesManagement($user)) {
            $dashboardRoute = 'branch-dashboard.dashboard.sales';
        } elseif (SidebarVisibilityService::canSeeEmployeeManagement($user)) {
            $dashboardRoute = 'branch-dashboard.dashboard.hr';
        } elseif (SidebarVisibilityService::canSeeInventory($user)) {
            $dashboardRoute = 'branch-dashboard.dashboard.inventory';
        } elseif (SidebarVisibilityService::canSeeReporting($user)) {
            $dashboardRoute = 'branch-dashboard.dashboards.admin';
        }

        if (!$dashboardRoute) {
            return view('livewire.dashboards.no-role');
        }
        
        // Log the routing decision
        \Log::info('Dashboard redirect', [
            'user_id' => $user->id ?? 'unknown',
            'target_route' => $dashboardRoute,
            'branch_id' => $branchId,
            'dept_slug' => $deptSlug,
            'is_super_admin' => is_super_admin(),
        ]);
        
        // Super admin without explicit branch - show all branches or redirect to super-admin dashboard
        if (!$branchId && in_array($dashboardRoute, ['branch-dashboard.dashboard.super-admin'])) {
            return redirect()->route($dashboardRoute);
        }
        
        // Redirect to the appropriate dashboard with branch context
        if ($branchId) {
            // For production and sales dashboards, include department slug if available
            if ($dashboardRoute === 'branch-dashboard.dashboard.production' && $deptSlug) {
                return redirect()->route($dashboardRoute, ['deptSlug' => $deptSlug, 'b_id' => $branchId]);
            }
            if ($dashboardRoute === 'branch-dashboard.dashboard.sales' && $salesDeptSlug) {
                return redirect()->route($dashboardRoute, ['salesDeptSlug' => $salesDeptSlug, 'b_id' => $branchId]);
            }
            return redirect()->route($dashboardRoute, ['b_id' => $branchId]);
        }
        
        return redirect()->route($dashboardRoute);
    }

    /**
     * Check if employee has clocked in today
     */
    private function hasClockInToday(Employee|User $user): bool
    {
        if (!auth()->check()) {
            return true; // Super admin doesn't need clock-in
        }

        $employee = auth()->user();
        
        if (!$employee) {
            return false;
        }

        // Check if there's an active shift for today
        $hasShift = Shift::where('employee_id', $employee->id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->exists();

        return $hasShift;
    }

    // Role-based mapping removed in favor of permission-based routing.
}
