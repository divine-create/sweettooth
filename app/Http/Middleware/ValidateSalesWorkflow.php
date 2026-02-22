<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SalesWorkflowService;
use App\Models\Shift;
use App\Models\Department;
use Carbon\Carbon;

class ValidateSalesWorkflow
{
    protected SalesWorkflowService $workflowService;

    public function __construct(SalesWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super admins bypass workflow validation
        if (is_super_admin() || can_access_all_branches()) {
            return $next($request);
        }

        $employee = auth()->user();

        // Only apply to authenticated sales employees
        if (!$employee || !$this->isSalesEmployee($employee)) {
            return $next($request);
        }

        $currentRoute = $request->route();
        $requiredState = $this->getRequiredWorkflowState($currentRoute);

        if ($requiredState) {
            $activeShift = $this->getActiveShift($employee, $requiredState);

            if (!$activeShift) {
                // No active shift - redirect to clock-in
                return $this->redirectToClockIn($request);
            }

            $currentState = $this->workflowService->getCurrentState(
                $employee->id,
                $activeShift->id
            );

            // Log workflow access attempt
            $this->logWorkflowAccess($employee, $currentRoute, $currentState, $requiredState);

            if (!$this->canAccessState($currentState, $requiredState)) {
                // Redirect to appropriate step
                return $this->redirectToCorrectStep($request, $currentState, $employee);
            }
        }

        return $next($request);
    }

    /**
     * Check if employee belongs to sales department
     */
    protected function isSalesEmployee($employee): bool
    {
        if (!$employee->department_id) {
            return false;
        }

        $department = Department::find($employee->department_id);
        if (!$department || !$department->category) {
            return false;
        }

        return strtolower($department->category->name) === 'sales';
    }

    /**
     * Get required workflow state for route
     */
    protected function getRequiredWorkflowState($route): ?string
    {
        if (!$route) {
            return null;
        }

        $routeName = $route->getName();

        // Define route to workflow state mappings
        $routeMappings = [
            'stock_opening' => [
                'branch-dashboard.sales-dashboard.stock-opening.index'
            ],
            'pos' => [
                'branch-dashboard.sales-dashboard.pos.index'
            ],
            'shift_closing' => [
                'branch-dashboard.sales-dashboard.shift-closing.index'
            ]
        ];

        foreach ($routeMappings as $state => $routes) {
            if (in_array($routeName, $routes)) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Check if current state allows access to required state
     */
    protected function canAccessState(string $currentState, string $requiredState): bool
    {
        $stateHierarchy = [
            'clock_in' => 1,
            'stock_opening' => 2,
            'pos' => 3,
            'clock_out' => 4,
            'shift_closing' => 5,
            'completed' => 6
        ];

        $currentLevel = $stateHierarchy[$currentState] ?? 0;
        $requiredLevel = $stateHierarchy[$requiredState] ?? 0;

        return $currentLevel >= $requiredLevel;
    }

    /**
     * Get active shift for employee
     */
    protected function getActiveShift($employee, ?string $requiredState = null): ?Shift
    {
        $query = Shift::where('employee_id', $employee->id)
            ->where('shift_date', Carbon::today())
            ->where(function ($q) use ($requiredState) {
                $q->where(function ($active) {
                    $active->where('status', 'active')
                        ->whereNull('clock_out');
                });

                // Allow employees who already clocked out to access shift closing.
                if ($requiredState === 'shift_closing') {
                    $q->orWhere('workflow_state', 'shift_closing');
                }
            });

        return $query->latest('clock_in')->first();
    }

    /**
     * Redirect to clock-in page
     */
    protected function redirectToClockIn(Request $request): Response
    {
        $branchId = $request->query('b_id') ?? current_branch_id();
        return redirect()->route('branch-dashboard.clock-in-board.today', [
            'b_id' => $branchId
        ])->with('error', 'Please clock in to start your shift.');
    }

    /**
     * Redirect to correct workflow step
     */
    protected function redirectToCorrectStep(Request $request, string $currentState, $employee): Response
    {
        $branchId = $request->query('b_id') ?? current_branch_id();
        $departmentSlug = $this->getEmployeeDepartmentSlug($employee);

        $redirectRoute = match($currentState) {
            'clock_in' => route('branch-dashboard.clock-in-board.today', ['b_id' => $branchId]),
            'stock_opening' => route('branch-dashboard.sales-dashboard.stock-opening.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]),
            'pos' => route('branch-dashboard.sales-dashboard.pos.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]),
            'shift_closing' => route('branch-dashboard.sales-dashboard.shift-closing.index', [
                'salesDeptSlug' => $departmentSlug,
                'b_id' => $branchId
            ]),
            default => route('branch-dashboard.index', ['b_id' => $branchId])
        };

        return redirect($redirectRoute)->with('warning',
            'Please complete the required workflow steps in order.');
    }

    /**
     * Get employee's department slug
     */
    protected function getEmployeeDepartmentSlug($employee): ?string
    {
        if (!$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }

    /**
     * Log workflow access attempts
     */
    protected function logWorkflowAccess($employee, $route, string $currentState, string $requiredState): void
    {
        Log::info('Sales workflow access attempt', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'route' => $route?->getName(),
            'current_state' => $currentState,
            'required_state' => $requiredState,
            'access_granted' => $this->canAccessState($currentState, $requiredState),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);
    }
}
