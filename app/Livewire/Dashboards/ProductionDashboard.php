<?php

namespace App\Livewire\Dashboards;

use App\Models\DailyProduce;
use App\Models\Recipe;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class ProductionDashboard extends BaseDashboard
{
    public ?string $deptSlug = null;

    protected ?int $departmentId = null;

    public function mount(?string $deptSlug = null)
    {
        parent::mount();
        $this->deptSlug = $deptSlug;

        // Resolve department from slug
        if ($deptSlug) {
            $department = \App\Models\Department::where('slug', $deptSlug)->first();
            if ($department) {
                $this->departmentId = $department->id;
            }
        } else {
            // If no department slug provided, try to auto-assign from user's department
            $userDepartment = $this->getUserDepartment();
            if ($userDepartment) {
                $this->departmentId = $userDepartment->id;
                $this->deptSlug = $userDepartment->slug;
            } else {
                // If no department is provided and user doesn't have a department, show error for non-super admins
                if (!is_super_admin()) {
                    abort(403, 'Department access required. Please select a department.');
                }
                // For super admin, allow to continue without department but show message in UI
            }
        }

        // Verify user has production access
        $this->verifyAccess();
    }

    /**
     * Get department ID for filtering
     */
    protected function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    /**
     * Verify user has access to production dashboard
     */
    private function verifyAccess(): void
    {
        // Allow super admin
        if (is_super_admin()) {
            return;
        }

        // Check permission-based access (new system)
        if ($this->user && method_exists($this->user, 'can')) {
            if ($this->user->can('view-production') || $this->user->can('manage-production')) {
                return;
            }
        }

        abort(403, 'Unauthorized access to production dashboard');
    }

    /**
     * Get user's department (production staff assigned to department)
     */
    private function getUserDepartment()
    {
        if (! $this->user) {
            return null;
        }

        if (method_exists($this->user, 'department')) {
            return $this->user->department;
        }

        return null;
    }

    /**
     * Get production queue for today
     */
    public function getTodayQueue()
    {
        return $this->remember('today_queue', function () {
            $query = DailyProduce::whereDate('produce_date', Carbon::today())
                ->with('recipe.product', 'shift');

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            return $query->orderBy('id', 'asc')->get();
        });
    }

    /**
     * Get completed items today
     */
    public function getTodayCompleted(): int
    {
        return $this->remember('today_completed', function () {
            $query = DailyProduce::whereDate('produce_date', Carbon::today())
                ->where('status', 'completed');

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            return $query->count();
        });
    }

    /**
     * Get pending items today
     */
    public function getTodayPending(): int
    {
        return $this->remember('today_pending', function () {
            $query = DailyProduce::whereDate('produce_date', Carbon::today())
                ->whereIn('status', ['pending', 'in_progress']);

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            return $query->count();
        });
    }

    /**
     * Get total production quantity today
     */
    public function getTodayQuantity(): float
    {
        return $this->remember('today_quantity', function () {
            $query = DailyProduce::whereDate('produce_date', Carbon::today());

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            return $query->sum('produced_quantity') ?? 0;
        });
    }

    /**
     * Get active recipes in production
     */
    public function getActiveRecipes()
    {
        return $this->remember('active_recipes', function () {
            $query = DailyProduce::whereDate('produce_date', Carbon::today())
                ->where('status', 'in_progress')
                ->with('recipe', 'product', 'shift')
                ->distinct()
                ->selectRaw('recipe_id');

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            return Recipe::whereIn('id', $query->pluck('recipe_id'))->with('product')->get();
        });
    }

    /**
     * Get staff assigned today
     */
    public function getStaffAssigned(): int
    {
        return $this->remember('staff_assigned_today', function () {
            // Count unique employees with active shifts today
            $query = \DB::table('shifts')
                ->whereDate('shift_date', Carbon::today())
                ->where('status', 'active');

            if ($this->getDepartmentId()) {
                $query->where('department_id', $this->getDepartmentId());
            }

            return $query->distinct('employee_id')->count('employee_id');
        });
    }

    /**
     * Get production timeline for today (by hour)
     */
    public function getProductionTimeline()
    {
        return $this->remember('production_timeline', function () {
            $timeline = [];

            $query = DailyProduce::whereDate('produce_date', Carbon::today())
                ->with('recipe', 'shift');

            if ($this->getDepartmentId()) {
                $query->whereHas('shift', function ($q) {
                    $q->where('department_id', $this->getDepartmentId());
                });
            }

            $produces = $query->get();

            foreach ($produces as $produce) {
                $hour = Carbon::parse($produce->produce_date)->format('H:00');

                if (! isset($timeline[$hour])) {
                    $timeline[$hour] = [
                        'hour' => $hour,
                        'pending' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                    ];
                }

                if ($produce->status === 'pending') {
                    $timeline[$hour]['pending']++;
                } elseif ($produce->status === 'in_progress') {
                    $timeline[$hour]['in_progress']++;
                } elseif ($produce->status === 'completed') {
                    $timeline[$hour]['completed']++;
                }
            }

            return collect($timeline)->sortKeys();
        });
    }

    /**
     * Get production alerts
     */
    public function getProductionAlerts()
    {
        $alerts = [];

        $pending = $this->getTodayPending();
        if ($pending > 5) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Queue',
                'message' => "{$pending} items pending in production queue",
            ];
        }

        // Check for overdue items
        $query = DailyProduce::whereDate('produce_date', '<', Carbon::today())
            ->whereIn('status', ['pending', 'in_progress']);

        if ($this->getDepartmentId()) {
            $query->whereHas('shift', function ($q) {
                $q->where('department_id', $this->getDepartmentId());
            });
        }

        $overdue = $query->count();

        if ($overdue > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Overdue Items',
                'message' => "{$overdue} items from previous days not completed",
            ];
        }

        return $alerts;
    }

    public function render()
    {
        try {
            // Check if no department is selected by a super admin
            if (is_super_admin() && !$this->deptSlug) {
                return view('livewire.dashboards.production.dashboard', [
                    'todayQueue' => collect([]),
                    'todayCompleted' => 0,
                    'todayPending' => 0,
                    'todayQuantity' => 0,
                    'activeRecipes' => collect([]),
                    'staffAssigned' => 0,
                    'productionTimeline' => collect([]),
                    'productionAlerts' => [],
                    'userDepartment' => null,
                    'no_department_selected' => true,
                ]);
            }

            return view('livewire.dashboards.production.dashboard', [
                'todayQueue' => $this->getTodayQueue(),
                'todayCompleted' => $this->getTodayCompleted(),
                'todayPending' => $this->getTodayPending(),
                'todayQuantity' => $this->getTodayQuantity(),
                'activeRecipes' => $this->getActiveRecipes(),
                'staffAssigned' => $this->getStaffAssigned(),
                'productionTimeline' => $this->getProductionTimeline(),
                'productionAlerts' => $this->getProductionAlerts(),
                'userDepartment' => $this->getUserDepartment(),
            ]);
        } catch (\Exception $e) {
            $this->handleError('loading production dashboard', $e);

            return view('livewire.dashboards.error');
        }
    }
}
