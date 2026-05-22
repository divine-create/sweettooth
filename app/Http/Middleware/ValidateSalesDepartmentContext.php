<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Department;

class ValidateSalesDepartmentContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $salesDeptSlug = $request->route('salesDeptSlug');
        $branchId = $request->query('b_id') ?? current_branch_id();

        if ($salesDeptSlug) {
            $department = Department::where('slug', $salesDeptSlug)
                ->where(function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->first();

            if (!$department) {
                Log::warning('Invalid department access attempt', [
                    'slug' => $salesDeptSlug,
                    'branch_id' => $branchId,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);

                abort(404, 'Sales department not found.');
            }

            // Super admins can access any department
            if (is_super_admin() || can_access_all_branches()) {
                $request->merge(['current_department' => $department]);
                return $next($request);
            }

            // Validate employee has access to this department
            $employee = auth()->user();
            
            if ($employee) {
                $userDept = $employee->department;
                
                // Allow if user is assigned to this department
                if ($employee->department_id === $department->id) {
                    $request->merge(['current_department' => $department]);
                    return $next($request);
                }
                
                // Sales managers can access all sales departments in the category.
                // Other roles are restricted to their assigned sales department.
                $userLevel = $this->getUserRoleLevel($employee);
                $salesRoles = [
                    'Sales Manager', 'Sales Staff', 'Floor Manager', 'Assistant Shop Floor Manager',
                    'Cashier', 'Wait Staff', 'Lobby Host', 'Lobby Host Supervisor',
                    'Coffee Barista', 'Coffee Barista Trainer', 'Consession Attendant',
                    'Consession Supervisor', 'Cornerstore Supervisor', 'Till Supervisor',
                ];
                $isSalesManager = $employee->hasAnyRole(['Sales Manager', 'Floor Manager']);
                $isSalesStaff = $employee->hasAnyRole($salesRoles);
                $sameCategory = $userDept && $userDept->category_id === $department->category_id;
                $isSalesDepartment = $department->category?->name === 'Sales';
                if (!$isSalesDepartment && $department->name) {
                    $isSalesDepartment = str_contains(strtolower($department->name), 'sales');
                }

                if (($userLevel >= 3 && $sameCategory && $isSalesDepartment) || ($isSalesManager && $isSalesDepartment)) {
                    $request->merge(['current_department' => $department]);
                    return $next($request);
                }

                // Allow sales staff access to sales departments in the same category
                if ($isSalesStaff && $isSalesDepartment && ($sameCategory || !$userDept)) {
                    $request->merge(['current_department' => $department]);
                    return $next($request);
                }
                
                Log::warning('Unauthorized department access attempt', [
                    'employee_id' => $employee->id,
                    'employee_department' => $employee->department_id,
                    'requested_department' => $department->id,
                    'user_level' => $userLevel ?? 1,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);

                abort(403, 'You are not authorized to access this sales department.');
            }

            // Store department context in request for use in controllers/components
            $request->merge(['current_department' => $department]);
        }

        return $next($request);
    }

    /**
     * Get user's role level (1-5)
     */
    private function getUserRoleLevel($user): int
    {
        // Check by role level column first (new system)
        $role = $user->roles()->orderByDesc('level')->first();

        if ($role && isset($role->level)) {
            return (int) $role->level;
        }

        // Fallback: check by role name (old system compatibility)
        if ($user->hasRole('Super Admin')) return 5;
        if ($user->hasRole('Admin')) return 4;

        // Manager-level roles
        $managerRoles = [
            'Managing Director', 'Admin', 'Accounting Manager',
            'Production Manager', 'Sales Manager', 'HR', 'HR Manager',
            'Inventory Manager', 'Head Chef', 'Gelato Chef', 'Floor Manager',
        ];
        if ($user->hasAnyRole($managerRoles)) return 3;

        // Supervisor-level roles
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
}
