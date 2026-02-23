<?php

namespace App\Livewire\BranchDashboard\Dashboards;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\SidebarVisibilityService;

/**
 * Dashboard Router - Routes users based on DEPARTMENT + ROLE LEVEL
 *
 * Simplified routing logic:
 * 1. Super Admin (level 5) -> Admin dashboard
 * 2. Admin (level 4) -> Admin dashboard
 * 3. Others -> Route by department category (Production/Sales/Support)
 */
class Router extends Component
{
    public function mount()
    {
        $branchId = request()->query('b_id');
        $currentUser = Auth::user();

        if (!$currentUser || !($currentUser instanceof \App\Models\User)) {
            Auth::logout();
            return Redirect::route('login');
        }

        // Get role level and department info
        $roleLevel = SidebarVisibilityService::getRoleLevel($currentUser);
        $category = SidebarVisibilityService::getDepartmentCategory($currentUser);
        // Guard against mis-categorized departments (e.g., Sales marked as Support)
        $deptName = strtolower((string) ($currentUser->department?->name ?? ''));
        if ($category === 'Support') {
            if (str_contains($deptName, 'sales') || str_contains($deptName, 'marketing')) {
                $category = 'Sales';
            } elseif (str_contains($deptName, 'production') || str_contains($deptName, 'factory')) {
                $category = 'Production';
            }
        }
        $deptSlug = $currentUser->department?->slug;
        $canSuperAdmin = $currentUser->hasAnyPermission(['manage-branches', 'manage-roles', 'manage-settings']) || is_super_admin();
        // Admin dashboard should be limited to true Admin-level users
        // (HR/manage-organization should land on Support/HR dashboard instead)
        $canAdminDashboard = $roleLevel >= SidebarVisibilityService::LEVEL_ADMIN;

        \Log::info('Router: Department-based routing', [
            'user_id' => $currentUser->id,
            'user_email' => $currentUser->email,
            'role_level' => $roleLevel,
            'department' => $currentUser->department?->name,
            'category' => $category,
        ]);

        // LEVEL 5: Super Admin -> Super Admin Dashboard
        if ($canSuperAdmin || $roleLevel >= SidebarVisibilityService::LEVEL_SUPER_ADMIN) {
            return Redirect::route('branch-dashboard.dashboards.super-admin', ['b_id' => $branchId]);
        }

        // LEVEL 4: Admin -> Admin Dashboard
        if ($canAdminDashboard) {
            return Redirect::route('branch-dashboard.dashboards.admin', ['b_id' => $branchId]);
        }

        // Check shift requirement for employees
        if ($currentUser->user_type === 'employee') {
            $today = now()->startOfDay();
            $hasActiveShift = \App\Models\Shift::where('employee_id', $currentUser->id)
                ->where('shift_date', '>=', $today)
                ->where('status', 'active')
                ->exists();

            if (!$hasActiveShift) {
                return Redirect::route('branch-dashboard.select_shift', ['b_id' => $branchId]);
            }
        }

        // Route by ROLE (explicit mapping to avoid mis-categorized departments)
        $route = $this->routeByRole($currentUser, $branchId, $deptSlug);
        if ($route) {
            return $route;
        }

        // Route by DEPARTMENT CATEGORY
        return match ($category) {
            'Production' => $this->routeProduction($branchId, $deptSlug),
            'Sales' => $this->routeSales($branchId, $deptSlug),
            'Support' => $this->routeSupport($branchId, $currentUser),
            default => $this->routeFallback($branchId, $currentUser, $roleLevel, $category),
        };
    }

    /**
     * Explicit role-based routing (highest priority for non-admin users).
     */
    private function routeByRole($user, string $branchId, ?string $deptSlug)
    {
        if (! $user) {
            return null;
        }

        $salesRoles = [
            'Sales Manager', 'Sales Staff', 'Floor Manager', 'Assistant Shop Floor Manager',
            'Cashier', 'Wait Staff', 'Lobby Host', 'Lobby Host Supervisor',
            'Coffee Barista', 'Coffee Barista Trainer', 'Consession Attendant',
            'Consession Supervisor', 'Cornerstore Supervisor', 'Till Supervisor',
        ];

        $productionRoles = [
            'Production Manager', 'Production Staff', 'Head Chef', 'Gelato Chef',
            'Hot Kitchen Chef', 'Pastry Chef', 'Kitchen Assistant', 'Kitchen Assistant Supervisor',
        ];

        $inventoryRoles = [
            'Inventory Manager', 'Inventory Team Lead', 'Inventory Staff', 'Store Keeper', 'Procurement Officer',
        ];

        $hrRoles = [
            'HR Manager', 'HR Officer', 'Data Processor',
        ];

        $accountingRoles = [
            'Accounting Manager', 'Accountant', 'Cost Accountant',
        ];

        $supportRoles = [
            'Chief Security Officer', 'Security Officer', 'Facility Officer', 'Cleaners Supervisor',
            'Driver', 'Social Media Manager',
        ];

        if ($user->hasAnyRole($salesRoles)) {
            return $this->routeSales($branchId, $deptSlug);
        }

        if ($user->hasAnyRole($productionRoles)) {
            return $this->routeProduction($branchId, $deptSlug);
        }

        if ($user->hasAnyRole($inventoryRoles)) {
            return Redirect::route('branch-dashboard.dashboard.inventory', ['b_id' => $branchId]);
        }

        if ($user->hasAnyRole($hrRoles)) {
            return Redirect::route('branch-dashboard.dashboard.hr', ['b_id' => $branchId]);
        }

        if ($user->hasAnyRole($accountingRoles)) {
            return Redirect::route('branch-dashboard.accounting.dashboard', ['b_id' => $branchId]);
        }

        if ($user->hasAnyRole($supportRoles)) {
            return $this->routeSupport($branchId, $user);
        }

        return null;
    }

    /**
     * Route Production department users
     */
    private function routeProduction(string $branchId, ?string $deptSlug)
    {
        // If we have a department slug, route to department-specific dashboard
        if ($deptSlug) {
            return Redirect::route('branch-dashboard.dashboard.production', [
                'b_id' => $branchId,
                // 'deptSlug' => $deptSlug  // Uncomment when routes support dept slug
            ]);
        }

        return Redirect::route('branch-dashboard.dashboard.production', ['b_id' => $branchId]);
    }

    /**
     * Route Sales department users
     */
    private function routeSales(string $branchId, ?string $deptSlug)
    {
        return Redirect::route('branch-dashboard.dashboard.sales', ['b_id' => $branchId]);
    }

    /**
     * Route Support department users (HR, Inventory, Accounting)
     */
    private function routeSupport(string $branchId, $user)
    {
        if (SidebarVisibilityService::canSeeInventory($user)) {
            return Redirect::route('branch-dashboard.dashboard.inventory', ['b_id' => $branchId]);
        }

        if (SidebarVisibilityService::canSeeEmployeeManagement($user)) {
            return Redirect::route('branch-dashboard.dashboard.hr', ['b_id' => $branchId]);
        }

        if (SidebarVisibilityService::canSeeAccounting($user)) {
            return Redirect::route('branch-dashboard.accounting.dashboard', ['b_id' => $branchId]);
        }

        // Default support -> HR dashboard
        return Redirect::route('branch-dashboard.dashboard.hr', ['b_id' => $branchId]);
    }

    /**
     * Fallback routing when no department category matches
     */
    private function routeFallback(string $branchId, $user, int $roleLevel, ?string $category)
    {
        \Log::warning('Router: Fallback routing triggered', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'role_level' => $roleLevel,
            'category' => $category,
            'department' => $user->department?->name,
        ]);

        // Super Admin/Admin without department -> Admin dashboard
        // (Super Admin doesn't need a department assignment)
        if ($roleLevel >= SidebarVisibilityService::LEVEL_ADMIN) {
            return Redirect::route('branch-dashboard.dashboards.admin', ['b_id' => $branchId]);
        }

        // Manager+ needs a department
        if ($roleLevel >= SidebarVisibilityService::LEVEL_MANAGER) {
            if (!$user->department_id) {
                abort(403, 'You are not assigned to any department. Please contact your administrator.');
            }
            return Redirect::route('branch-dashboard.dashboards.manager', ['b_id' => $branchId]);
        }

        // Lower level users need a department
        if (!$user->department_id) {
            abort(403, 'You are not assigned to any department. Please contact your administrator.');
        }

        // Last resort - generic error
        abort(403, 'Your account does not have access to any dashboard. Please contact your administrator.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.dashboards.router');
    }
}
