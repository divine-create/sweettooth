<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Models\ProductionRequest;
use App\Models\Shift;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Department;
use App\Models\Recipe;
use App\Events\ProductionRequest\RequestRejected;
use App\Events\ProductionRequest\RequestSentToStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function is_super_admin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

    public $search = '';
    public $statusFilter = 'all';
    public $shiftFilter = 'all';

    // Modal properties
    public $showViewModal = false;
    public $viewingRequest = null;
    public $requestItems = [];

    // Assign recipe modal
    public $showAssignRecipeModal = false;
    public $assigningRequestId = null;
    public $selectedRecipeId = null;
    public $availableRecipes = [];

    public function mount($deptSlug = null)
    {
        $deptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        // Super Admin can access all departments, so deptSlug is optional
        if (!is_super_admin() && !$deptSlug) {
            $userDept = auth()->user()?->department;
            if ($userDept && strtolower($userDept->category?->name ?? '') === 'production') {
                $deptSlug = $userDept->slug;
            } else {
                abort(403, 'Department access required');
            }
        }

        if ($deptSlug) {
            $this->dept_slug = $deptSlug;
            $branchId = $this->getBranchId();
            $this->department = Department::where('slug', $deptSlug)
                ->when($branchId, function ($q) use ($branchId) {
                    $q->where(function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)
                            ->orWhereNull('branch_id');
                    });
                })
                ->first();

            if (!$this->department) {
                abort(404, 'Department not found');
            }
        }
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openViewModal($requestId)
    {
        $branchId = $this->getBranchId();

        $request = ProductionRequest::with([
            'recipe',
            'shift',
            'itemRequest.requestDetails.item',
            'itemRequest.department',
            'itemRequest.requester',
            'productionDepartment',
            'createdBy'
        ])
            ->whereHas('itemRequest', function ($itemQuery) use ($branchId) {
                $itemQuery->where('branch_id', $branchId);
                if (!is_super_admin()) {
                    $itemQuery->where('department_id', $this->department->id);
                }
            })
            ->findOrFail($requestId);

        $this->viewingRequest = $request;
        $this->requestItems = [];

        foreach ($request->itemRequest->requestDetails as $detail) {
            $this->requestItems[] = [
                'item_name' => $detail->item->name ?? 'N/A',
                'quantity_requested' => $detail->quantity_requested,
                'quantity_approved' => $detail->quantity_approved,
                'quantity_dispatched' => $detail->quantity_dispatched,
                'uom' => $detail->unitOfMeasure?->symbol ?? $detail->item->unitOfMeasure?->symbol ?? '',
                'status' => $this->getItemStatus($detail),
            ];
        }

        $this->showViewModal = true;
    }

    private function getItemStatus($detail)
    {
        $requested = (float) $detail->quantity_requested;
        $approved = (float) $detail->quantity_approved;
        $dispatched = (float) $detail->quantity_dispatched;

        // Completed: requested = approved = dispatched AND not 0
        if ($requested == $approved && $approved == $dispatched && $requested > 0) {
            return ['label' => 'Completed', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'];
        }
        // Partially Dispatched: dispatched > 0 but < approved
        elseif ($dispatched > 0 && $dispatched < $approved) {
            return ['label' => 'Partially Dispatched', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'];
        }
        // Partially Approved: approved > 0 but < requested
        elseif ($approved > 0 && $approved < $requested) {
            return ['label' => 'Partially Approved', 'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'];
        }
        // Approved: requested = approved > 0, dispatched = 0
        elseif ($approved > 0 && $requested == $approved && $dispatched == 0) {
            return ['label' => 'Approved', 'class' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200'];
        }
        // Pending: approved = 0, dispatched = 0
        else {
            return ['label' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'];
        }
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingRequest = null;
        $this->requestItems = [];
    }

    /**
     * Reject a production request
     */
    public function rejectRequest($requestId, $reason = null)
    {
        $branchId = $this->getBranchId();

        try {
            DB::transaction(function () use ($requestId, $reason) {
                $request = ProductionRequest::findOrFail($requestId);

                if (!in_array($request->status, ['pending', 'approved'])) {
                    throw new \Exception('This request cannot be rejected.');
                }

                $request->update([
                    'status' => 'rejected',
                    'notes' => $request->notes . ($reason ? "\n\nRejection Reason: {$reason}" : ''),
                ]);

                // Broadcast rejection event if the class exists
                if (class_exists(RequestRejected::class)) {
                    broadcast(new RequestRejected($request))->toOthers();
                }
            });

            $this->toast()->success('Request rejected successfully.')->send();
            $this->closeViewModal();
        } catch (\Exception $e) {
            $this->toast()->error('Error rejecting request: ' . $e->getMessage())->send();
        }
    }

    /**
     * Send request to store/inventory for raw materials
     */
    public function sendToStore($requestId)
    {
        $branchId = $this->getBranchId();

        try {
            DB::transaction(function () use ($requestId, $branchId) {
                $request = ProductionRequest::with(['recipe.ingredients.item'])
                    ->findOrFail($requestId);

                if (!$request->recipe_id || !$request->recipe) {
                    throw new \Exception('Please assign a recipe before sending to store.');
                }

                if (!in_array($request->status, ['approved', 'pending'])) {
                    throw new \Exception('Request must be approved first.');
                }

                // Calculate ingredients needed based on recipe and planned quantity (batch-first)
                $yieldQuantity = (float) ($request->recipe->yield_quantity ?: 0);
                if ($yieldQuantity <= 0) {
                    throw new \Exception('Recipe yield quantity must be greater than 0.');
                }
                if ((float) $request->planned_production_quantity <= 0) {
                    throw new \Exception('Planned production quantity must be greater than 0.');
                }

                $batchSize = (int) ceil($request->planned_production_quantity / $yieldQuantity);
                $ingredientsNeeded = $request->recipe->calculateIngredientsForBatch($batchSize);

                if (empty($ingredientsNeeded)) {
                    throw new \Exception('Recipe "' . $request->recipe->product_name . '" has no ingredients defined. Please add ingredients to the recipe before sending to store.');
                }

                // Create item request to inventory/store
                $deptCode = strtoupper(substr($this->department->name ?? 'PROD', 0, 4));
                $requestNumber = ItemRequest::generateRequestNumber(
                    substr($branchId, 0, 8),
                    $deptCode
                );

                $actor = current_actor();

                $itemRequest = ItemRequest::create([
                    'branch_id' => $branchId,
                    'department_id' => $this->department->id,
                    'request_number' => $requestNumber,
                    'requested_by_id' => $actor->id,
                    'requested_by_type' => get_class($actor),
                    'request_date' => today(),
                    'status' => 'pending',
                    'notes' => "Raw materials for Production Request #{$request->id}",
                    'approved_by_id' => null,
                    'approved_by_type' => null,
                    'cancelled_by_id' => null,
                    'cancelled_by_type' => null,
                ]);

                // Add ingredients as request details
                foreach ($ingredientsNeeded as $ingredient) {
                    ItemRequestDetail::create([
                        'request_id' => $itemRequest->id,
                        'item_id' => $ingredient['item_id'],
                        'quantity_requested' => $ingredient['quantity'],
                        'uom_id' => $ingredient['uom_id'],
                        'notes' => $ingredient['notes'] ?? null,
                    ]);
                }

                // Link item request to production request
                $request->update([
                    'item_request_id' => $itemRequest->id,
                    'status' => 'in_progress',
                ]);

                // Broadcast event if the class exists
                if (class_exists(RequestSentToStore::class)) {
                    broadcast(new RequestSentToStore($request, $itemRequest))->toOthers();
                }
            });

            $this->toast()->success('Request sent to store. Raw materials request created.')->send();
            $this->closeViewModal();
        } catch (\Exception $e) {
            $this->toast()->error('Error sending to store: ' . $e->getMessage())->send();
        }
    }

    /**
     * Mark request as completed
     */
    public function completeRequest($requestId)
    {
        try {
            DB::transaction(function () use ($requestId) {
                $request = ProductionRequest::findOrFail($requestId);

                if (!in_array($request->status, ['approved', 'in_progress', 'quality_check'])) {
                    throw new \Exception('Request must be in progress to complete.');
                }

                $request->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            $this->toast()->success('Request marked as completed.')->send();
            $this->closeViewModal();
        } catch (\Exception $e) {
            $this->toast()->error('Error completing request: ' . $e->getMessage())->send();
        }
    }

    /**
     * Open recipe assignment modal
     */
    public function openAssignRecipeModal($requestId)
    {
        $this->assigningRequestId = $requestId;
        $this->selectedRecipeId = null;

        // Find the request to determine the correct department for recipes
        $request = ProductionRequest::find($requestId);

        // Use production_department_id if available, otherwise use current department
        $deptId = $request?->production_department_id ?? $this->department?->id;

        // Load available recipes for the production department
        $this->availableRecipes = Recipe::where('department_id', $deptId)
            ->where('status', 'active')
            ->orderBy('product_name')
            ->get();

        $this->showAssignRecipeModal = true;
    }

    /**
     * Assign recipe to request
     */
    public function assignRecipe()
    {
        $this->validate([
            'selectedRecipeId' => 'required|exists:recipes,id',
        ]);

        try {
            $request = ProductionRequest::findOrFail($this->assigningRequestId);
            $request->update(['recipe_id' => $this->selectedRecipeId]);

            $this->showAssignRecipeModal = false;
            $this->assigningRequestId = null;
            $this->selectedRecipeId = null;
            $this->availableRecipes = [];

            $this->toast()->success('Recipe assigned successfully.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error assigning recipe: ' . $e->getMessage())->send();
        }
    }

    public function closeAssignRecipeModal()
    {
        $this->showAssignRecipeModal = false;
        $this->assigningRequestId = null;
        $this->selectedRecipeId = null;
        $this->availableRecipes = [];
    }

    public function getDisplayStatus(ProductionRequest $request): string
    {
        if ($request->itemRequest) {
            return $request->itemRequest->status ?? ($request->status ?? 'pending');
        }

        return $request->status ?? 'pending';
    }

    public function cancelRequest($requestId)
    {
        $branchId = $this->getBranchId();

        try {
            DB::transaction(function () use ($requestId, $branchId) {
                $request = ProductionRequest::with('itemRequest')
                    ->whereHas('itemRequest', function ($itemQuery) use ($branchId) {
                        $itemQuery->where('branch_id', $branchId);
                        if (!is_super_admin()) {
                            $itemQuery->where('department_id', $this->department->id);
                        }
                    })
                    ->findOrFail($requestId);

                if (!$request->canBeCancelled()) {
                    throw new \Exception('This request cannot be cancelled.');
                }

                $request->itemRequest->update(['status' => 'cancelled']);
            });

            $this->toast()->success('Request cancelled successfully.')->send();
            $this->closeViewModal();
        } catch (\Exception $e) {
            $this->toast()->error('Error cancelling request: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = ProductionRequest::with([
                'recipe',
                'shift',
                'itemRequest.requestDetails.item',
                'productionDepartment',
                'createdBy'
            ])
            ->whereHas('itemRequest', function ($itemQuery) use ($branchId) {
                $itemQuery->where('branch_id', $branchId);
                if (!is_super_admin()) {
                    $itemQuery->where('department_id', $this->department->id);
                }
            });

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'pending') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'pending');
                });
            } elseif ($this->statusFilter === 'approved') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'approved');
                });
            } elseif ($this->statusFilter === 'completed') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'completed');
                });
            } elseif ($this->statusFilter === 'cancelled') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'cancelled');
                });
            } elseif ($this->statusFilter === 'partially_dispatched') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'partially_dispatched');
                });
            } elseif ($this->statusFilter === 'in_progress') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'in_progress');
                });
            } elseif ($this->statusFilter === 'rejected') {
                $query->whereHas('itemRequest', function ($itemQ) {
                    $itemQ->where('status', 'rejected');
                });
            }
        }

        // Apply shift filter
        if ($this->shiftFilter !== 'all') {
            $query->where('shift_id', $this->shiftFilter);
        }

        // Search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('recipe', function ($subQ) {
                    $subQ->where('product_name', 'like', '%' . $this->search . '%');
                })->orWhereHas('itemRequest', function ($subQ) {
                    $subQ->where('request_number', 'like', '%' . $this->search . '%');
                })->orWhere('notes', 'like', '%' . $this->search . '%')  // Search in notes field
                  ->orWhereHas('createdBy', function ($subQ) {
                      $subQ->where('name', 'like', '%' . $this->search . '%');
                  }); // Search in creator name
            });
        }

        $requests = $query->latest()->paginate(15);

        // Get all shifts for the department
        $allShifts = collect();
        if ($this->department) {
            $allShifts = Shift::where('branch_id', $branchId)
                ->where('department_id', $this->department->id)
                ->orderBy('shift_date', 'desc')
                ->orderBy('shift_type')
                ->limit(30) // Limit to recent shifts
                ->get();
        }

        // Get status summary
        $statusSummary = collect();
        if ($this->department) {
            $statusQuery = ProductionRequest::with('itemRequest')
                ->whereHas('itemRequest', function ($itemQ) use ($branchId) {
                    $itemQ->where('branch_id', $branchId)
                          ->where('department_id', $this->department->id);
                })
                ->get();

            $statusSummary = $statusQuery->groupBy(function ($request) {
                    return $this->getDisplayStatus($request);
                })
                ->map(function ($group) {
                    return $group->count();
                });
        }

        // Get available recipes for assignment dropdown
        $departmentRecipes = collect();
        if ($this->department) {
            $departmentRecipes = Recipe::where('department_id', $this->department->id)
                ->where('status', 'active')
                ->orderBy('product_name')
                ->get();
        }

        return view('livewire.branch-dashboard.production.request.index', [
            'requests' => $requests,
            'allShifts' => $allShifts,
            'statusSummary' => $statusSummary,
            'departmentRecipes' => $departmentRecipes,
            'dept_slug' => $this->dept_slug
        ]);
    }
}
