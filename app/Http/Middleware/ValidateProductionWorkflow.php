<?php

namespace App\Http\Middleware;

use App\Models\Department;
use App\Models\Shift;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateProductionWorkflow
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Super Admins bypass workflow validation
        if (is_super_admin() || can_access_all_branches()) {
            return $next($request);
        }

        $employee = Auth::user();
        if (!$employee) {
            return $next($request);
        }

        $currentRoute = $request->route();
        $routeName = $currentRoute ? $currentRoute->getName() : null;

        // Resolve branch and department context
        $branchId = $request->query('b_id') ?? current_branch_id();
        $deptSlug = $request->route('deptSlug') ?? $request->route('dept_slug') ?? $request->query('deptSlug') ?? $this->getEmployeeDepartmentSlug($employee);

        $department = null;
        if ($deptSlug) {
            $department = Department::where('slug', $deptSlug)->first();
        } elseif ($employee->department_id) {
            $department = Department::find($employee->department_id);
            $deptSlug = $department?->slug;
        }

        // Only enforce for production departments
        if ($department && $department->category && strtolower($department->category->name) !== 'production') {
            return $next($request);
        }

        // 2. Resolve active shift today
        $activeShift = $this->getActiveShift($employee, $department?->id);

        // If trying to access stock opening page
        $isStockOpeningRoute = in_array($routeName, [
            'production.stock-opening.index',
            'branch-dashboard.production.stock-opening.index',
        ]);

        $isShiftClosingRoute = in_array($routeName, [
            'production.shift-closing.index',
            'branch-dashboard.production.shift-closing.index',
        ]);

        // Operational routes that strictly require opening stock verification
        $isOperationalRoute = in_array($routeName, [
            'production.quick-produce.finished-good',
            'branch-dashboard.production.quick-produce.finished-good',
            'production.quick-produce.wip',
            'branch-dashboard.production.quick-produce.wip',
            'production.records.index',
            'branch-dashboard.production.records.index',
            'production.finished-goods.index',
            'branch-dashboard.production.finished-goods.index',
            'production.store-transfers.index',
            'branch-dashboard.production.store-transfers.index',
            'production.material-returns.create',
            'branch-dashboard.production.material-returns.create',
        ]);

        // 3. If no active shift
        if (!$activeShift) {
            if ($isOperationalRoute || $isStockOpeningRoute) {
                return redirect()->route('branch-dashboard.select_shift', [
                    'b_id' => $branchId,
                ])->with('warning', 'Please clock in to start your production shift before accessing this page.');
            }
            return $next($request);
        }

        // 4. If active shift exists, but Opening Stock is NOT verified
        $isStockVerified = !is_null($activeShift->stock_verified_at) || in_array($activeShift->workflow_state, ['pos', 'production_unlocked', 'completed']);

        if (!$isStockVerified && $isOperationalRoute) {
            Log::info('Production operational access blocked: Opening stock unverified', [
                'employee_id' => $employee->id,
                'shift_id' => $activeShift->id,
                'department_id' => $department?->id,
                'route' => $routeName,
            ]);

            return redirect()->route('branch-dashboard.production.stock-opening.index', [
                'deptSlug' => $deptSlug,
                'b_id' => $branchId,
            ])->with('warning', 'Opening stock must be physically counted and verified before kitchen production operations can proceed.');
        }

        return $next($request);
    }

    /**
     * Resolve active shift for current employee or department today.
     */
    protected function getActiveShift($employee, ?int $departmentId): ?Shift
    {
        $today = Carbon::today()->toDateString();

        $query = Shift::where('shift_date', $today)
            ->where('status', 'active')
            ->whereNull('clock_out');

        if ($departmentId) {
            $query->where(function ($q) use ($employee, $departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhere('employee_id', $employee->id);
            });
        } else {
            $query->where('employee_id', $employee->id);
        }

        return $query->latest('clock_in')->first();
    }

    /**
     * Get employee's department slug.
     */
    protected function getEmployeeDepartmentSlug($employee): ?string
    {
        if (!$employee || !$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }
}
