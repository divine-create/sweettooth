<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Employee;
use App\Models\Department;
use App\Services\SalesStockVerificationService;
use Carbon\Carbon;

class SalesWorkflowService
{
    protected SalesStockVerificationService $verificationService;

    public function __construct(SalesStockVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Workflow states in order
     */
    const WORKFLOW_STATES = [
        'clock_in' => ['label' => 'Clock In', 'order' => 1],
        'stock_opening' => ['label' => 'Stock Opening', 'order' => 2],
        'pos' => ['label' => 'Point of Sale', 'order' => 3],
        'clock_out' => ['label' => 'Clock Out', 'order' => 4],
        'shift_closing' => ['label' => 'Shift Closing', 'order' => 5],
        'completed' => ['label' => 'Completed', 'order' => 6]
    ];

    /**
     * Get the current workflow state for an employee's shift
     */
    public function getCurrentState(string $employeeId, string|int $shiftId): string
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return 'clock_in';
        }

        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->department_id) {
            return 'clock_in';
        }

        // If workflow_state is explicitly set (e.g., 'pos'), use it
        // This allows selected products verification to grant POS access
        if (!empty($shift->workflow_state)) {
            // Validate the state is still applicable based on clock status
            $storedState = $shift->workflow_state;
            
            // 1. Check if shift is completed (clocked out AND shift closing done)
            if ($shift->clock_out && $this->isShiftClosingCompleted($shift)) {
                return 'completed';
            }

            // 2. Check if clocked out but shift closing not done
            if ($shift->clock_out && !$this->isShiftClosingCompleted($shift)) {
                return 'shift_closing';
            }

            // If clocked in and stored state is 'pos' or beyond, use stored state
            if ($shift->clock_in && in_array($storedState, ['pos', 'clock_out', 'shift_closing'])) {
                return $storedState;
            }
        }

        // 1. Check if shift is completed (clocked out AND shift closing done)
        if ($shift->clock_out && $this->isShiftClosingCompleted($shift)) {
            return 'completed';
        }

        // 2. Check if clocked out but shift closing not done
        if ($shift->clock_out && !$this->isShiftClosingCompleted($shift)) {
            return 'shift_closing';
        }

        // 3. Check if clocked in and stock verified (can access POS)
        if ($shift->clock_in && $this->verificationService->canAccessPos($employeeId, $shiftId)) {
            return 'pos';
        }

        // 4. Check if clocked in but stock not verified
        if ($shift->clock_in && !$this->verificationService->canAccessPos($employeeId, $shiftId)) {
            return 'stock_opening';
        }

        // 5. Default state
        return 'clock_in';
    }

    /**
     * Get the next required action message for the employee
     */
    public function getNextRequiredAction(string $employeeId, string|int $shiftId): ?string
    {
        $currentState = $this->getCurrentState($employeeId, $shiftId);

        return match($currentState) {
            'clock_in' => 'Please clock in to start your shift',
            'stock_opening' => 'Complete stock opening to access POS',
            'pos' => 'You can now process sales transactions',
            'clock_out' => 'Clock out when ready to end your shift',
            'shift_closing' => 'Complete shift closing to finalize your shift',
            'completed' => 'Shift completed successfully',
            default => null
        };
    }

    /**
     * Get all workflow steps with their status
     */
    public function getWorkflowSteps(string $employeeId, string|int $shiftId): array
    {
        $currentState = $this->getCurrentState($employeeId, $shiftId);

        return [
            [
                'key' => 'clock_in',
                'label' => 'Clock In',
                'icon' => 'clock',
                'completed' => in_array($currentState, ['stock_opening', 'pos', 'clock_out', 'shift_closing', 'completed']),
                'current' => $currentState === 'clock_in'
            ],
            [
                'key' => 'stock_opening',
                'label' => 'Stock Opening',
                'icon' => 'clipboard-document-list',
                'completed' => in_array($currentState, ['pos', 'clock_out', 'shift_closing', 'completed']),
                'current' => $currentState === 'stock_opening'
            ],
            [
                'key' => 'pos',
                'label' => 'Sales',
                'icon' => 'shopping-cart',
                'completed' => in_array($currentState, ['clock_out', 'shift_closing', 'completed']),
                'current' => $currentState === 'pos'
            ],
            [
                'key' => 'clock_out',
                'label' => 'Clock Out',
                'icon' => 'arrow-right-on-rectangle',
                'completed' => in_array($currentState, ['shift_closing', 'completed']),
                'current' => $currentState === 'clock_out'
            ],
            [
                'key' => 'shift_closing',
                'label' => 'Shift Close',
                'icon' => 'document-check',
                'completed' => $currentState === 'completed',
                'current' => $currentState === 'shift_closing'
            ]
        ];
    }

    /**
     * Get the progress percentage for the workflow
     */
    public function getProgressPercentage(string $employeeId, string|int $shiftId): int
    {
        $currentState = $this->getCurrentState($employeeId, $shiftId);

        return match($currentState) {
            'clock_in' => 0,
            'stock_opening' => 25,
            'pos' => 50,
            'clock_out' => 75,
            'shift_closing' => 90,
            'completed' => 100,
            default => 0
        };
    }

    /**
     * Mark a workflow step as completed
     */
    public function completeStep(string $employeeId, string|int $shiftId, string $step): bool
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return false;
        }

        // Store completion in shift metadata
        $metadata = $shift->metadata ?? [];
        $metadata['workflow_steps'] = $metadata['workflow_steps'] ?? [];
        $metadata['workflow_steps'][$step] = [
            'completed_by' => $employeeId,
            'completed_at' => now()->toIso8601String(),
        ];

        $shift->metadata = $metadata;

        // Update specific timestamps and workflow_state based on step
        switch ($step) {
            case 'stock_opening':
                if (in_array('stock_verified_at', $shift->getFillable())) {
                    $shift->stock_verified_at = now();
                }
                // Advance workflow_state to 'pos' after stock opening
                $shift->workflow_state = 'pos';
                break;
            case 'shift_closing':
                $metadata['shift_closing_completed'] = true;
                $metadata['shift_closed_at'] = now()->toIso8601String();
                $shift->metadata = $metadata;
                if (in_array('shift_closed_at', $shift->getFillable())) {
                    $shift->shift_closed_at = now();
                }
                $shift->workflow_state = 'completed';
                break;
        }

        return $shift->save();
    }

    /**
     * Validate if a state transition is allowed
     */
    public function validateStateTransition(string $fromState, string $toState): bool
    {
        $states = self::WORKFLOW_STATES;

        if (!isset($states[$fromState]) || !isset($states[$toState])) {
            return false;
        }

        $fromOrder = $states[$fromState]['order'];
        $toOrder = $states[$toState]['order'];

        // Can only move forward in workflow (same or next step)
        return $toOrder >= $fromOrder;
    }

    /**
     * Check if the current state allows access to a required state
     */
    public function canAccessState(string $currentState, string $requiredState): bool
    {
        $states = self::WORKFLOW_STATES;

        $currentLevel = $states[$currentState]['order'] ?? 0;
        $requiredLevel = $states[$requiredState]['order'] ?? 0;

        return $currentLevel >= $requiredLevel;
    }

    /**
     * Check if shift closing has been completed
     */
    protected function isShiftClosingCompleted(Shift $shift): bool
    {
        $metadata = $shift->metadata ?? [];
        return isset($metadata['shift_closing_completed']) &&
               $metadata['shift_closing_completed'] === true;
    }

    /**
     * Get the redirect route for a given workflow state
     */
    public function getRedirectRouteForState(string $state, ?string $departmentSlug, ?string $branchId): string
    {
        return match($state) {
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
    }

    /**
     * Check if employee is a sales employee
     */
    public function isSalesEmployee(Employee $employee): bool
    {
        // Super admins can access all areas
        if (is_super_admin() || can_access_all_branches()) {
            return false; // They bypass workflow checks
        }

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
     * Get active shift for employee on current date
     */
    public function getActiveShift(string $employeeId): ?Shift
    {
        return Shift::where('employee_id', $employeeId)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();
    }

    /**
     * Get employee's department slug
     */
    public function getEmployeeDepartmentSlug(string $employeeId): ?string
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }

    /**
     * Log workflow event for audit purposes
     */
    public function logWorkflowEvent(
        string $employeeId,
        string|int $shiftId,
        string $eventType,
        string $workflowStep,
        ?string $fromState = null,
        ?string $toState = null,
        ?array $eventData = null
    ): void {
        // Log to Laravel's default logger for now
        // Can be extended to log to workflow_audit_logs table when migration is run
        \Log::info('Sales workflow event', [
            'employee_id' => $employeeId,
            'shift_id' => $shiftId,
            'event_type' => $eventType,
            'workflow_step' => $workflowStep,
            'from_state' => $fromState,
            'to_state' => $toState,
            'event_data' => $eventData,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
