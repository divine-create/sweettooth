<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Dispatches;

use App\Livewire\BaseComponent;
use App\Models\ProductDispatch;
use App\Models\ProductStock;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Shift;
use App\Services\SalesProductionDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    /** @var array<int> */
    public array $departmentIds = [];
    public string $departmentName = 'Production Dispatch Receiving';
    public string $branchName = '';

    // Filter options
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $filterDate = null;

    // Receiving modal data
    public $selectedDispatch = null;
    public $receivedQuantity = null;
    public $receivingNotes = '';
    public $showReceivingModal = false;

    // Table headers
    public array $headers = [
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'dispatched_quantity', 'label' => 'Dispatched Qty'],
        ['index' => 'production_date', 'label' => 'Production Date'],
        ['index' => 'dispatched_by', 'label' => 'Dispatched By'],
        ['index' => 'dispatch_time', 'label' => 'Dispatch Time'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'variance', 'label' => 'Variance', 'collapsible' => true],
        ['index' => 'actions', 'label' => 'Actions'],
    ];

    protected function getModelClass(): string
    {
        return ProductDispatch::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id
            ?: request()->query('b_id')
            ?: current_branch_id()
            ?: auth()->user()?->branch_id;
    }

    public function mount()
    {
        $this->mountBase();

        if (! $this->salesDeptSlug) {
            $this->salesDeptSlug = request()->route('salesDeptSlug')
                ?? request()->query('sales_dept_slug')
                ?? request()->query('dept_slug')
                ?? request()->query('salesDeptSlug');
        }

        $this->loadBranchAndDepartment();
    }

    protected function loadBranchAndDepartment(): void
    {
        // Load branch
        $this->branchId = $this->getBranchId();
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->branchName = $branch?->name ?? 'Unknown Branch';
        }

        // Load department from slug
        if ($this->salesDeptSlug) {
            // First try to find branch-specific department
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            // If not found, try to find global department (branch_id is null)
            if (!$department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
                $this->departmentIds = $this->resolveEquivalentSalesDepartmentIds($department);
                $this->departmentName = $department->name . ' - Production Dispatch Receiving';
                $this->salesDeptSlug = $this->salesDeptSlug ?: $department->slug;
            } else {
                $this->toast()->error('Department not found.')->send();
            }
        } else {
            $department = $this->resolveDefaultSalesDepartment();
            if ($department) {
                $this->departmentId = (int) $department->id;
                $this->departmentIds = $this->resolveEquivalentSalesDepartmentIds($department);
                $this->departmentName = $department->name . ' - Production Dispatch Receiving';
                $this->salesDeptSlug = $this->salesDeptSlug ?: $department->slug;
            }
        }

        // Validate branch access
        if (!$this->branchId) {
            $this->toast()->error('Branch not specified.')->send();
        }

        if (! $this->departmentId) {
            $this->toast()->warning('Sales department context not resolved. Add department slug in URL.')->send();
        }
    }

    /**
     * Get filtered query for dispatches
     */
    protected function getFilteredQuery()
    {
        $departmentFilterIds = ! empty($this->departmentIds)
            ? $this->departmentIds
            : ($this->departmentId ? [(int) $this->departmentId] : []);

        $query = ProductDispatch::query()
            ->when($this->branchId, function ($query) {
                $query->where('branch_id', $this->branchId);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterDate, function ($query) {
                $query->whereDate('dispatch_date', $this->filterDate);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->with([
                'product',
                'dispatchedBy',
                'receivedBy',
                'dailyProduce',
                'productionRequest.productionDepartment',
                'salesProductionRequestItem.request',
            ])
            ->orderBy('dispatch_time', 'desc');

        if (! empty($departmentFilterIds)) {
            $query->where(function ($departmentQuery) use ($departmentFilterIds) {
                $departmentQuery->whereIn('sales_department_id', $departmentFilterIds)
                    // Fallback path for sales-request-driven dispatches where
                    // dispatch row department and request row department differ.
                    ->orWhereHas('salesProductionRequestItem.request', function ($requestQuery) use ($departmentFilterIds) {
                        $requestQuery->whereIn('sales_department_id', $departmentFilterIds);
                    });
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Open receiving modal for a dispatch
     */
    public function openReceivingModal($dispatchId)
    {
        $this->selectedDispatch = ProductDispatch::with('product')->find($dispatchId);

        if (!$this->selectedDispatch) {
            $this->toast()->error('Dispatch not found.')->send();
            return;
        }

        if (!in_array($this->selectedDispatch->status, ['pending_verification', 'accepted'], true)) {
            $this->toast()->warning('This dispatch has already been processed.')->send();
            return;
        }

        // Pre-fill with dispatched quantity
        $this->receivedQuantity = $this->selectedDispatch->quantity;
        $this->receivingNotes = '';
        $this->showReceivingModal = true;
    }

    /**
     * Receive dispatch and update stock
     */
    public function receiveDispatch()
    {
        // Validate
        $this->validate([
            'receivedQuantity' => 'required|numeric|min:0',
            'receivingNotes' => 'nullable|string|max:500',
        ]);

        if (!$this->selectedDispatch) {
            $this->toast()->error('No dispatch selected.')->send();
            return;
        }

        DB::beginTransaction();
        try {
            $employee = auth()->user();
            /** @var ProductDispatch|null $dispatch */
            $dispatch = ProductDispatch::with('productionRequest')
                ->lockForUpdate()
                ->find($this->selectedDispatch->id);

            if (! $dispatch) {
                throw new \RuntimeException('Dispatch no longer exists.');
            }

            if (!in_array($dispatch->status, ['pending_verification', 'accepted'], true)) {
                throw new \RuntimeException('This dispatch has already been processed.');
            }

            /** @var SalesProductionDispatchService $dispatchService */
            $dispatchService = app(SalesProductionDispatchService::class);

            // Update dispatch status
            $dispatch->update([
                'status' => 'received',
                'received_quantity' => $this->receivedQuantity,
                'received_by_id' => $employee->id,
                'received_by_type' => $employee ? get_class($employee) : null,
                'received_at' => now(),
                'notes' => $this->receivingNotes,
            ]);

            $linkedSalesItem = $dispatchService->attachSalesItemLink($dispatch);
            if ($linkedSalesItem) {
                $dispatchService->markItemAsReceivedBySales($linkedSalesItem);
            }

            // Get current active shift in the generic shifts table.
            // IMPORTANT: product_stocks.sales_shift_id references sales_shifts.id,
            // so we must not store this generic shift ID in sales_shift_id.
            $currentShift = Shift::where('employee_id', $employee->id)
                ->where('shift_date', Carbon::today())
                ->where('status', 'active')
                ->first();

            // Get production date from the daily produce or use today
            $productionDate = $dispatch->dailyProduce?->production_date ?? Carbon::today();

            // Calculate expiry date based on product shelf life
            $expiryDate = null;
            if ($dispatch->product && $dispatch->product->shelf_life_days > 0) {
                $expiryDate = Carbon::parse($productionDate)->addDays($dispatch->product->shelf_life_days);
            }

            // Update or create ProductStock record
            // Use receive day for POS availability; production date remains tracked separately.
            $stockDate = Carbon::today();
            $shiftType = (string) ($currentShift?->shift_type ?: $dispatch->shift_type ?: 'morning');
            if (! in_array($shiftType, ['morning', 'afternoon'], true)) {
                $shiftType = 'morning';
            }
            $hasDepartmentColumn = Schema::hasColumn('product_stocks', 'department_id');
            // Use current sales context first so stock is visible to this department in POS.
            $salesDepartmentId = (int) ($this->departmentId ?: $dispatch->sales_department_id);

            if ($hasDepartmentColumn && $salesDepartmentId <= 0) {
                throw new \RuntimeException('Sales department is required for stock receiving.');
            }

            $productStock = ProductStock::where('product_id', $dispatch->product_id)
                ->whereDate('stock_date', $stockDate->format('Y-m-d'))
                ->where('shift_type', $shiftType)
                ->when($hasDepartmentColumn, function ($query) use ($salesDepartmentId) {
                    $query->where('department_id', $salesDepartmentId);
                })
                ->first();

            if ($productStock) {
                // Update existing stock - add to addition_quantity
                $productStock->addition_quantity = ($productStock->addition_quantity ?? 0) + $this->receivedQuantity;
                $productStock->production_date = $productionDate;
                $productStock->expiry_date = $expiryDate;
                if ($hasDepartmentColumn) {
                    $productStock->department_id = $salesDepartmentId;
                }
                $productStock->save();
            } else {
                // Create new stock record
                $payload = [
                    // Keep null because this flow uses generic shifts + department_id scoping.
                    // Setting generic Shift::id here causes FK violation on sales_shifts.
                    'sales_shift_id' => null,
                    'product_id' => $dispatch->product_id,
                    'stock_date' => $stockDate->format('Y-m-d'),
                    'shift_type' => $shiftType,
                    'opening_quantity' => 0,
                    'addition_quantity' => $this->receivedQuantity,
                    'production_date' => $productionDate,
                    'expiry_date' => $expiryDate,
                    'callback_quantity' => 0,
                    'redress_quantity' => 0,
                    'total_available' => $this->receivedQuantity,
                    'transfer_quantity' => 0,
                    'glovo_quantity' => 0,
                    'quantity_sold' => 0,
                    'closing_quantity' => $this->receivedQuantity,
                    'notes' => 'Received from production dispatch',
                ];

                if ($hasDepartmentColumn) {
                    $payload['department_id'] = $salesDepartmentId;
                }

                ProductStock::create($payload);
            }

            DB::commit();

            // Check for variance
            $variance = $this->receivedQuantity - $dispatch->quantity;
            if ($variance != 0) {
                $varianceMessage = $variance > 0
                    ? "Received {$variance} more than dispatched"
                    : "Received " . abs($variance) . " less than dispatched";
                $this->toast()->warning("Dispatch received with variance: {$varianceMessage}")->send();
            } else {
                $this->toast()->success('Dispatch received successfully!')->send();
            }

            // Reset form
            $this->closeReceivingModal();
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error receiving dispatch: ' . $e->getMessage())->send();
        }
    }

    /**
     * Reject a dispatch
     */
    public function rejectDispatch($dispatchId, $reason = '')
    {
        DB::beginTransaction();
        try {
            $employee = auth()->user();
            $dispatch = ProductDispatch::find($dispatchId);

            if (!$dispatch) {
                $this->toast()->error('Dispatch not found.')->send();
                return;
            }

            if (!in_array($dispatch->status, ['pending_verification', 'accepted'], true)) {
                $this->toast()->warning('This dispatch has already been processed.')->send();
                return;
            }

            $dispatch->update([
                'status' => 'rejected',
                'received_quantity' => 0,
                'received_by_id' => $employee->id,
                'received_by_type' => $employee ? get_class($employee) : null,
                'received_at' => now(),
                'notes' => 'Rejected: ' . $reason,
            ]);

            DB::commit();
            $this->toast()->success('Dispatch rejected successfully.')->send();
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error rejecting dispatch: ' . $e->getMessage())->send();
        }
    }

    /**
     * Close receiving modal
     */
    public function closeReceivingModal()
    {
        $this->showReceivingModal = false;
        $this->selectedDispatch = null;
        $this->receivedQuantity = null;
        $this->receivingNotes = '';
        $this->resetValidation();
    }

    /**
     * Update search filter
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Update status filter
     */
    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    /**
     * Update date filter
     */
    public function updatedFilterDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $dispatches = $this->getFilteredQuery()->paginate(20);

        return view('livewire.branch-dashboard.sales-dashboard.dispatches.index', [
            'dispatches' => $dispatches,
        ]);
    }

    /**
     * Resolve equivalent sales department records across branch/global scopes.
     *
     * @return array<int>
     */
    protected function resolveEquivalentSalesDepartmentIds(Department $department): array
    {
        $branchId = $this->branchId;
        $targetNameKey = $this->normalizeDepartmentKey($department->name);
        $targetSlugKey = $this->normalizeDepartmentKey((string) $department->slug);

        $ids = Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get(['id', 'name', 'slug'])
            ->filter(function (Department $candidate) use ($department, $targetNameKey, $targetSlugKey): bool {
                if ((int) $candidate->id === (int) $department->id) {
                    return true;
                }

                $candidateNameKey = $this->normalizeDepartmentKey($candidate->name);
                $candidateSlugKey = $this->normalizeDepartmentKey((string) $candidate->slug);

                return $this->areEquivalentDepartmentIdentities(
                    $targetNameKey,
                    $targetSlugKey,
                    $candidateNameKey,
                    $candidateSlugKey,
                );
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $department->id, $ids, true)) {
            $ids[] = (int) $department->id;
        }

        return $ids;
    }

    protected function normalizeDepartmentKey(string $value): string
    {
        $normalized = strtolower($value);
        $normalized = preg_replace('/[-_]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    /**
     * @return array<int, string>
     */
    protected function departmentIdentityTokens(string $value): array
    {
        $normalized = $this->normalizeDepartmentKey($value);
        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $normalized)));
        $stopWords = ['sales', 'sale', 'department', 'dept'];

        return array_values(array_filter($tokens, function (string $token) use ($stopWords): bool {
            return $token !== '' && ! in_array($token, $stopWords, true);
        }));
    }

    protected function areEquivalentDepartmentIdentities(
        string $targetNameKey,
        string $targetSlugKey,
        string $candidateNameKey,
        string $candidateSlugKey,
    ): bool {
        if (
            ($targetNameKey !== '' && $candidateNameKey === $targetNameKey)
            || ($targetSlugKey !== '' && $candidateSlugKey === $targetSlugKey)
            || ($targetNameKey !== '' && $candidateSlugKey === $targetNameKey)
            || ($targetSlugKey !== '' && $candidateNameKey === $targetSlugKey)
        ) {
            return true;
        }

        $targetTokens = $this->departmentIdentityTokens($targetNameKey . ' ' . $targetSlugKey);
        $candidateTokens = $this->departmentIdentityTokens($candidateNameKey . ' ' . $candidateSlugKey);
        if (empty($targetTokens) || empty($candidateTokens)) {
            return false;
        }

        $sharedTokens = array_intersect($targetTokens, $candidateTokens);
        $shorterTokenCount = min(count($targetTokens), count($candidateTokens));

        return count($sharedTokens) === $shorterTokenCount;
    }

    protected function isSalesDepartment(?Department $department): bool
    {
        if (! $department) {
            return false;
        }

        return strtolower((string) $department->category?->name) === 'sales';
    }

    protected function resolveDefaultSalesDepartment(): ?Department
    {
        /** @var Department|null $scopedDepartment */
        $scopedDepartment = request()->get('current_department');
        if ($this->isSalesDepartment($scopedDepartment)) {
            return $scopedDepartment;
        }

        $sessionDepartmentId = session('current_department_id');
        if ($sessionDepartmentId) {
            $sessionDepartment = Department::find($sessionDepartmentId);
            if (
                $this->isSalesDepartment($sessionDepartment)
                && (! $this->branchId || ! $sessionDepartment->branch_id || (string) $sessionDepartment->branch_id === (string) $this->branchId)
            ) {
                return $sessionDepartment;
            }
        }

        $employee = auth()->user();
        if ($employee && $employee->department_id) {
            $employeeDepartment = Department::find($employee->department_id);
            if ($this->isSalesDepartment($employeeDepartment)) {
                return $employeeDepartment;
            }
        }

        if (! (is_super_admin() || can_access_all_branches())) {
            return null;
        }

        return Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->when($this->branchId, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('branch_id', $this->branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->orderBy('name')
            ->first();
    }
}
