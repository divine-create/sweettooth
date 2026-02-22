<?php

namespace App\Livewire\Concerns;

use App\Models\Department;
use App\Models\Branch;
use Livewire\Attributes\Url;

trait SalesDepartmentContext
{
    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    public string $departmentName = '';
    public string $branchName = '';

    /**
     * Initialize the department context
     * Call this in your component's mount() method
     */
    protected function initializeDepartmentContext(): void
    {
        $this->loadBranchContext();

        $requestedSalesDeptSlug = request()->route('salesDeptSlug')
            ?? request()->query('sales_dept_slug')
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug')
            ?? request()->query('salesDeptSlug');

        // Always prioritize explicit URL context over carried component state.
        if ($requestedSalesDeptSlug) {
            $this->salesDeptSlug = $requestedSalesDeptSlug;
        }

        if (!$this->salesDeptSlug) {
            $this->salesDeptSlug = $this->getEmployeeDepartmentSlug();
        }

        $this->loadDepartmentData();
    }

    /**
     * Load branch context from request or helper
     */
    protected function loadBranchContext(): void
    {
        // Load branch - use helper for super admin support
        if (!$this->branchId) {
            $this->branchId = request('b_id') ?? current_branch_id();
        }

        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        // Validate branch access
        if (!$this->branchId) {
            if (method_exists($this, 'toast')) {
                $this->toast()->error('Branch not specified.')->send();
            }
        }
    }

    /**
     * Get the employee's department slug
     */
    protected function getEmployeeDepartmentSlug(): ?string
    {
        $employee = auth()->user();
        if (!$employee || !$employee->department_id) {
            return null;
        }

        $department = Department::find($employee->department_id);
        return $department?->slug;
    }

    /**
     * Load department data from slug
     */
    protected function loadDepartmentData(): void
    {
        if (!$this->salesDeptSlug) {
            // If no slug provided, handle differently for super admins vs regular employees
            if (is_super_admin() || can_access_all_branches()) {
                // For super admins, redirect to a page to select a department
                if (method_exists($this, 'toast')) {
                    $this->toast()->error('Please select a sales department to view.')->send();
                }

                // Redirect to a page that lists available sales departments
                $this->redirect(route('branch-dashboard.sales-dashboard.helper', [
                    'b_id' => $this->getBranchId()
                ]));
                return;
            } else {
                // For regular employees, use their assigned department
                $employee = auth()->user();
                if ($employee && $employee->department_id) {
                    $department = Department::find($employee->department_id);
                    if ($department) {
                        // Check if this is a sales department
                        if ($this->isSalesDepartment($department)) {
                            $this->departmentId = $department->id;
                            $this->departmentName = $department->name;
                            $this->salesDeptSlug = $department->slug;
                        } else {
                            // If employee's department is not a sales department, show error
                            if (method_exists($this, 'toast')) {
                                $this->toast()->error('You do not have access to sales department pages.')->send();
                            }
                            return;
                        }
                    }
                }
            }
            return;
        }

        // First try to find branch-specific department
        $department = Department::where('slug', $this->salesDeptSlug)
            ->where('branch_id', $this->branchId)
            ->first();

        // If not found, try to find global department
        if (!$department) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->whereNull('branch_id')
                ->first();
        }

        if ($department) {
            $this->departmentId = $department->id;
            $this->departmentName = $department->name;
        } else {
            if (method_exists($this, 'toast')) {
                $this->toast()->error('Department not found. Please contact administrator.')->send();
            }
        }
    }

    /**
     * Validate that the employee has access to this department
     */
    protected function validateDepartmentAccess(): bool
    {
        // Super admins can access any department
        if (is_super_admin() || can_access_all_branches()) {
            return true;
        }

        if (!$this->departmentId) {
            return false;
        }

        $employee = auth()->user();
        return $employee && $employee->department_id === $this->departmentId;
    }

    /**
     * Get route parameters for department-aware routes
     */
    protected function getDepartmentRouteParameters(): array
    {
        return [
            'salesDeptSlug' => $this->salesDeptSlug,
            'b_id' => $this->getBranchId()
        ];
    }

    /**
     * Get branch ID (helper method that can be overridden)
     */
    public function getBranchId(): ?string
    {
        return $this->branchId ?? request()->query('b_id') ?? current_branch_id();
    }

    /**
     * Redirect to POS with department context
     */
    protected function redirectToPos(): void
    {
        $this->redirectRoute('branch-dashboard.sales-dashboard.pos.index',
            $this->getDepartmentRouteParameters()
        );
    }

    /**
     * Redirect to Stock Opening with department context
     */
    protected function redirectToStockOpening(): void
    {
        $this->redirectRoute('branch-dashboard.sales-dashboard.stock-opening.index',
            $this->getDepartmentRouteParameters()
        );
    }

    /**
     * Redirect to Shift Closing with department context
     */
    protected function redirectToShiftClosing(): void
    {
        $this->redirectRoute('branch-dashboard.sales-dashboard.shift-closing.index',
            $this->getDepartmentRouteParameters()
        );
    }

    /**
     * Redirect to Clock In with branch context
     */
    protected function redirectToClockIn(): void
    {
        $this->redirectRoute('branch-dashboard.clock-in-board.today', [
            'b_id' => $this->getBranchId()
        ]);
    }

    /**
     * Get the current department model
     */
    protected function getCurrentDepartment(): ?Department
    {
        if (!$this->departmentId) {
            return null;
        }
        return Department::find($this->departmentId);
    }

    /**
     * Check if the current employee belongs to a sales department
     */
    protected function isEmployeeInSalesDepartment(): bool
    {
        $department = $this->getCurrentDepartment();
        if (!$department || !$department->category) {
            return false;
        }

        return strtolower($department->category->name) === 'sales';
    }

    /**
     * Check if a department is a sales department
     */
    protected function isSalesDepartment($department): bool
    {
        if (!$department) {
            return false;
        }

        return strtolower($department->category?->name ?? '') === 'sales';
    }

    /**
     * Get all available sales departments for the current branch
     */
    protected function getAvailableSalesDepartments(): \Illuminate\Support\Collection
    {
        return Department::where('branch_id', $this->branchId)
            ->whereHas('category', function($subQuery) {
                $subQuery->where('name', 'Sales');
            })
            ->get();
    }
}
