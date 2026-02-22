<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Models\RawMaterialUtilization;
use App\Models\Shift;
use App\Models\Recipe;
use App\Models\Item;
use App\Models\Department;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\ProductionRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, On, Url};
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class RawMaterialTracking extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
        $this->loadCurrentShift();
    }

    public $selectedShiftId = null;
    public $filterRecipe = '';
    public $filterVarianceType = '';
    public $currentShift = null;
    public $viewMode = 'utilization'; // 'utilization' or 'dispatches'

    // Date range filters
    public $filterStartDate = null;
    public $filterEndDate = null;
    public $filterMode = 'shift'; // 'shift' or 'date_range'


    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public function mount($deptSlug = null){
        // Initialize view mode
        $this->viewMode = 'utilization';

        // Get dept_slug from parameter or URL query
        $this->dept_slug = $deptSlug ?? request()->query('dept_slug') ?? request()->query('deptSlug');

        if ($this->dept_slug) {
            $this->department = Department::where('slug', $this->dept_slug)->first();
        }

        // If no department found via slug, try to get default production department for this branch
        if (!$this->department) {
            $branchId = $this->getBranchId();
            $this->department = Department::where('branch_id', $branchId)
                ->whereHas('category', function($q) {
                    $q->where('name', 'Production');
                })
                ->first();
        }

        if (!$this->department) {
            abort(404, 'Department not found. Please ensure you have a Production department set up.');
        }

        $this->loadCurrentShift();
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function loadCurrentShift()
    {
        $branchId = $this->getBranchId();

        // Get today's shift for the department based on dept_slug
        $this->currentShift = Shift::where('branch_id', $branchId)
            ->where('department_id', $this->department->id)
            ->where('shift_date', today())
            ->orderBy('shift_type')
            ->first();

        if ($this->currentShift) {
            $this->selectedShiftId = $this->currentShift->id;
        }

        // Initialize date filters to today
        if (!$this->filterStartDate) {
            $this->filterStartDate = today()->format('Y-m-d');
        }
        if (!$this->filterEndDate) {
            $this->filterEndDate = today()->format('Y-m-d');
        }
    }

    public function updatedSelectedShiftId()
    {
        $this->resetPage();
    }

    public function updatedFilterRecipe()
    {
        $this->resetPage();
    }

    public function updatedFilterVarianceType()
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate()
    {
        $this->resetPage();
    }

    public function updatedFilterMode()
    {
        $this->resetPage();
    }

    public function updatedViewMode()
    {
        $this->resetPage();
        // Reset filters that don't apply to the new view mode
        if ($this->viewMode === 'dispatches') {
            $this->filterVarianceType = '';
        }
    }

    public function resetFilters()
    {
        $this->filterRecipe = '';
        $this->filterVarianceType = '';
        $this->filterStartDate = today()->format('Y-m-d');
        $this->filterEndDate = today()->format('Y-m-d');
        $this->filterMode = 'shift';
        $this->viewMode = 'utilization';
        $this->selectedShiftId = $this->currentShift?->id;
        $this->resetPage();
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        // Get available shifts for this department based on dept_slug
        $availableShifts = Shift::where('branch_id', $branchId)
            ->where('department_id', $this->department->id)
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type')
            ->limit(30)
            ->get();

        // Get recipes for filter - filtered by department
        $recipes = Recipe::where('branch_id', $branchId)
            ->where('department_id', $this->department->id)
            ->where('status', 'active')
            ->orderBy('product_name')
            ->get();

        // Query data based on view mode
        if ($this->viewMode === 'utilization') {
            // Query raw material utilizations - DEPARTMENT BASED on dept_slug
            $query = RawMaterialUtilization::with(['shift', 'recipe', 'item'])
                ->whereHas('shift', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->where('department_id', $this->department->id);
                });

            // Apply filters based on mode
            if ($this->filterMode === 'shift' && $this->selectedShiftId) {
                // Shift-based filtering
                $query->where('shift_id', $this->selectedShiftId);
            } elseif ($this->filterMode === 'date_range' && $this->filterStartDate && $this->filterEndDate) {
                // Date range filtering
                $query->whereHas('shift', function($q) {
                    $q->whereBetween('shift_date', [$this->filterStartDate, $this->filterEndDate]);
                });
            }

            // Apply other filters
            $query->when($this->filterRecipe, fn($q) => $q->where('recipe_id', $this->filterRecipe))
                  ->when($this->filterVarianceType, fn($q) => $q->where('variance_type', $this->filterVarianceType))
                  ->orderBy('created_at', 'desc');

            $data = $query->paginate(15);
            $dataType = 'utilizations';
        } else {
            // Query dispatched items for production - DEPARTMENT BASED on dept_slug
            $query = ItemRequestDetail::with([
                'item',
                'itemRequest',
                'itemRequest.productionRequests.recipe',
                'itemRequest.productionRequests.shift'
            ])
            ->whereHas('itemRequest', function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->where('department_id', $this->department->id);
            })
            ->where('quantity_dispatched', '>', 0);

            // Apply filters based on mode
            if ($this->filterMode === 'shift' && $this->selectedShiftId) {
                // Filter by production requests for this shift
                $query->whereHas('itemRequest.productionRequests', function($q) {
                    $q->where('shift_id', $this->selectedShiftId);
                });
            } elseif ($this->filterMode === 'date_range' && $this->filterStartDate && $this->filterEndDate) {
                // Filter by shift date range through production requests
                $query->whereHas('itemRequest.productionRequests.shift', function($q) {
                    $q->whereBetween('shift_date', [$this->filterStartDate, $this->filterEndDate]);
                });
            }

            // Apply recipe filter (through production requests)
            $query->when($this->filterRecipe, function($q) {
                $q->whereHas('itemRequest.productionRequests', function($subQ) {
                    $subQ->where('recipe_id', $this->filterRecipe);
                });
            });

            $query->orderBy('updated_at', 'desc');

            $data = $query->paginate(15);
            $dataType = 'dispatches';
        }

        // Calculate summary statistics based on view mode
        if ($this->viewMode === 'utilization') {
            $summary = [
                'total_items' => 0,
                'within_tolerance' => 0,
                'over_used' => 0,
                'under_used' => 0,
                'total_cost_impact' => 0,
                'efficiency_percentage' => 0,
            ];

            // Build summary query based on filter mode
            $summaryQuery = RawMaterialUtilization::whereHas('shift', function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->where('department_id', $this->department->id);
            });

            if ($this->filterMode === 'shift' && $this->selectedShiftId) {
                $summaryQuery->where('shift_id', $this->selectedShiftId);
            } elseif ($this->filterMode === 'date_range' && $this->filterStartDate && $this->filterEndDate) {
                $summaryQuery->whereHas('shift', function($q) {
                    $q->whereBetween('shift_date', [$this->filterStartDate, $this->filterEndDate]);
                });
            }

            $allUtilizations = $summaryQuery->get();

            if ($allUtilizations->count() > 0) {
                $summary['total_items'] = $allUtilizations->count();
                $summary['within_tolerance'] = $allUtilizations->where('variance_type', 'within_tolerance')->count();
                $summary['over_used'] = $allUtilizations->where('variance_type', 'over_used')->count();
                $summary['under_used'] = $allUtilizations->where('variance_type', 'under_used')->count();
                $summary['total_cost_impact'] = $allUtilizations->sum('cost_impact');

                $totalRequired = $allUtilizations->sum('quantity_required');
                $totalUsed = $allUtilizations->sum('quantity_used');

                if ($totalRequired > 0) {
                    $summary['efficiency_percentage'] = (($totalRequired - abs($totalUsed - $totalRequired)) / $totalRequired) * 100;
                }
            }
        } else {
            // Summary for dispatched items
            $summary = [
                'total_items' => 0,
                'total_dispatched' => 0,
                'total_value' => 0,
                'unique_products' => 0,
            ];

            // Build summary query for dispatched items
            $summaryQuery = ItemRequestDetail::whereHas('itemRequest', function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->where('department_id', $this->department->id);
            })
            ->where('quantity_dispatched', '>', 0);

            if ($this->filterMode === 'shift' && $this->selectedShiftId) {
                $summaryQuery->whereHas('itemRequest.productionRequests', function($q) {
                    $q->where('shift_id', $this->selectedShiftId);
                });
            } elseif ($this->filterMode === 'date_range' && $this->filterStartDate && $this->filterEndDate) {
                $summaryQuery->whereHas('itemRequest.productionRequests.shift', function($q) {
                    $q->whereBetween('shift_date', [$this->filterStartDate, $this->filterEndDate]);
                });
            }

            $allDispatches = $summaryQuery->with('item')->get();

            if ($allDispatches->count() > 0) {
                $summary['total_items'] = $allDispatches->count();
                $summary['total_dispatched'] = $allDispatches->sum('quantity_dispatched');
                $summary['total_value'] = $allDispatches->sum(function($detail) {
                    return $detail->quantity_dispatched * ($detail->item->cost_per_unit ?? 0);
                });

                // Count unique products (through production requests)
                $productIds = [];
                foreach ($allDispatches as $dispatch) {
                    if ($dispatch->itemRequest && $dispatch->itemRequest->productionRequests) {
                        foreach ($dispatch->itemRequest->productionRequests as $prodRequest) {
                            if ($prodRequest->recipe) {
                                $productIds[] = $prodRequest->recipe->product_id;
                            }
                        }
                    }
                }
                $summary['unique_products'] = count(array_unique($productIds));
            }
        }

        return view('livewire.branch-dashboard.production.raw-material-tracking', [
            'data' => $data,
            'dataType' => $dataType,
            'availableShifts' => $availableShifts,
            'recipes' => $recipes,
            'summary' => $summary,
        ]);
    }
}
