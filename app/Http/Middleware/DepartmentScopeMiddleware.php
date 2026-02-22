<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Department Scope Middleware
 *
 * Enforces that users can only access URLs matching their department.
 * Uses role LEVEL (not name) for permission checks.
 *
 * Levels:
 * 5 = Super Admin (all access)
 * 4 = Admin (all departments in branch)
 * 3 = Manager (all departments in same category)
 * 2 = Supervisor (own department only)
 * 1 = Staff (own department only)
 */
class DepartmentScopeMiddleware
{
    private const LEVEL_SUPER_ADMIN = 5;
    private const LEVEL_ADMIN = 4;
    private const LEVEL_MANAGER = 3;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get department slug from route (supports multiple param names)
        $deptSlug = $request->route('deptSlug')
                 ?? $request->route('salesDeptSlug')
                 ?? $request->route('productionDeptSlug');

        // If no department in URL, allow (non-scoped route)
        if (!$deptSlug) {
            return $next($request);
        }

        $roleLevel = $this->getUserRoleLevel($user);

        // Level 5: Super Admin bypasses all checks
        if ($roleLevel >= self::LEVEL_SUPER_ADMIN) {
            $this->setDepartmentContext($request, $deptSlug);
            return $next($request);
        }

        // Level 4: Admin can access any department in their branch
        if ($roleLevel >= self::LEVEL_ADMIN) {
            $dept = Department::where('slug', $deptSlug)
                ->where(function($query) use ($user) {
                    $query->where('branch_id', $user->branch_id)
                          ->orWhereNull('branch_id');
                })
                ->first();

            if (!$dept) {
                abort(403, 'This department is not in your branch.');
            }

            $this->setDepartmentContext($request, $deptSlug, $dept);
            return $next($request);
        }

        // Level 3: Manager can access all departments in same category
        if ($roleLevel >= self::LEVEL_MANAGER) {
            $userDept = $user->department;

            if (!$userDept) {
                abort(403, 'You are not assigned to any department.');
            }

            $targetDept = Department::where('slug', $deptSlug)->first();

            if (!$targetDept) {
                abort(404, 'Department not found.');
            }

            // Same category AND (same branch OR department belongs to all branches)
            if ($targetDept->category_id === $userDept->category_id
                && ($targetDept->branch_id === $user->branch_id || is_null($targetDept->branch_id))) {
                $this->setDepartmentContext($request, $deptSlug, $targetDept);
                return $next($request);
            }

            abort(403, 'You can only access departments in your category.');
        }

        // Level 1-2: Supervisor/Staff can only access their own department
        $userDept = $user->department;

        if (!$userDept) {
            abort(403, 'You are not assigned to any department.');
        }

        if ($userDept->slug !== $deptSlug) {
            abort(403, 'You can only access your own department.');
        }

        // Verify the department belongs to the user's branch (or is a global department)
        $targetDept = Department::where('slug', $deptSlug)->first();
        if ($targetDept && $targetDept->branch_id && $targetDept->branch_id !== $user->branch_id) {
            abort(403, 'This department is not in your branch.');
        }

        $this->setDepartmentContext($request, $deptSlug, $userDept);
        return $next($request);
    }

    /**
     * Get user's role level (1-5)
     */
    private function getUserRoleLevel(User $user): int
    {
        // Legacy fast-path: user_type admin is treated as Super Admin
        if (isset($user->user_type) && $user->user_type === 'admin') {
            return self::LEVEL_SUPER_ADMIN;
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return self::LEVEL_SUPER_ADMIN;
        }

        // Check by role level column first (new system)
        $role = $user->roles()->orderByDesc('level')->first();

        if ($role && isset($role->level)) {
            return (int) $role->level;
        }

        // Fallback: check by role name (old system compatibility)
        if ($user->hasRole('Super Admin')) return 5;
        if ($user->hasRole('Admin')) return 4;

        // Manager-level roles (old names)
        $managerRoles = [
            'Managing Director', 'Admin', 'Accounting Manager',
            'Production Manager', 'Sales Manager', 'HR Manager',
            'Inventory Manager', 'Head Chef', 'Gelato Chef', 'Floor Manager',
        ];
        if ($user->hasAnyRole($managerRoles)) return 3;

        // Supervisor-level roles (old names)
        $supervisorRoles = [
            'HR Officer', 'Accountant', 'Cost Accountant', 'Till Supervisor', 'Cornerstore Supervisor',
            'Consession Supervisor', 'Coffee Barista Trainer', 'Lobby Host Supervisor', 'Kitchen Assistant Supervisor',
            'Hot Kitchen Chef', 'Pastry Chef', 'Assistant Shop Floor Manager', 'Inventory Team Lead',
            'Procurement Officer', 'Facility Officer', 'Cleaners Supervisor', 'Chief Security Officer',
            'Social Media Manager',
        ];
        if ($user->hasAnyRole($supervisorRoles)) return 2;

        return 1; // Staff level
    }

    /**
     * Set department context for the request
     */
    private function setDepartmentContext(Request $request, string $slug, ?Department $dept = null): void
    {
        if (!$dept) {
            $dept = Department::where('slug', $slug)->first();
        }

        if ($dept) {
            $request->attributes->set('current_department', $dept);
            $request->attributes->set('current_department_id', $dept->id);
            $request->attributes->set('current_department_slug', $dept->slug);
            $request->attributes->set('current_department_category', $dept->category?->name);
            session(['current_department_id' => $dept->id]);
        }
    }
}
