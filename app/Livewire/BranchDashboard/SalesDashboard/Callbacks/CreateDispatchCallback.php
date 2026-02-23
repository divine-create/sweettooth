<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Callbacks;

use App\Livewire\BaseComponent;
use App\Models\ProductDispatch;
use App\Models\ProductDispatchCallback;
use App\Models\SalesShift;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use function is_super_admin;

#[Layout('components.layouts.app.branch-dashboard')]
class CreateDispatchCallback extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;

    public ?string $currentSalesShiftId = null;
    public ?string $selectedSalesShiftId = null;
    public $availableShifts = [];
    public $stockDate;
    public bool $isSuperAdmin = false;

    // Callback form
    public $showCallbackModal = false;
    public $selectedDispatch = null;
    public $callbackQuantity = 0;
    public $callbackReason = '';
    public $callbackNotes = '';

    // Reason options
    public array $reasonOptions = [
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'quality_issue' => 'Quality Issue',
        'customer_return' => 'Customer Return',
        'over_received' => 'Over Received',
        'wrong_item' => 'Wrong Item',
        'other' => 'Other',
    ];

    // Table headers
    public array $headers = [
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'dispatch_date', 'label' => 'Dispatch Date'],
        ['index' => 'received_qty', 'label' => 'Received Qty'],
        ['index' => 'returned_qty', 'label' => 'Returned Qty'],
        ['index' => 'available_to_return', 'label' => 'Available to Return'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    protected function getModelClass(): string
    {
        return ProductDispatch::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $shiftId = $this->selectedSalesShiftId ?? $this->currentSalesShiftId;

        return ProductDispatch::query()
            ->where('branch_id', $this->getBranchId())
            ->when($this->departmentId, fn ($q) => $q->where('sales_department_id', $this->departmentId))
            ->when($shiftId, fn ($q) => $q->where('sales_shift_id', $shiftId))
            ->where('status', 'received');
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->stockDate = \Carbon\Carbon::today()->format('Y-m-d');
        $this->isSuperAdmin = is_super_admin();
        $this->loadBranchAndDepartment();
        $this->loadAvailableShifts();
        $this->loadCurrentSalesShift();

        // Set selected shift to current shift if available
        if ($this->currentSalesShiftId) {
            $this->selectedSalesShiftId = $this->currentSalesShiftId;
        } elseif (!empty($this->availableShifts)) {
            // If no active shift, select the most recent one
            // $this->selectedSalesShiftId = $this->availableShifts[0]->id;
        }
    }

    protected function loadAvailableShifts()
    {
        $branchId = $this->getBranchId();

        // Get sales shifts from last 30 days
        $this->availableShifts = SalesShift::where('branch_id', $branchId)
            ->where('shift_date', '>=', now()->subDays(30))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->with('department')
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    protected function loadBranchAndDepartment(): void
    {
        $this->branchId = $this->getBranchId();
        if ($this->salesDeptSlug) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $this->branchId)
                ->first();

            if (! $department) {
                $department = Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();
            }

            if ($department) {
                $this->departmentId = $department->id;
            }
        }
    }

    protected function loadCurrentSalesShift()
    {
        if ($this->isSuperAdmin) {
            return;
        }

        $employee = auth()->user();

        // First try to find active sales shift for this employee
        $activeShift = SalesShift::where('branch_id', $this->getBranchId())
            ->where('shift_date', \Carbon\Carbon::today())
            ->where('status', 'active')
            ->where('employee_id', $employee->id)
            ->first();

        // If not found, try to find any active sales shift in the employee's department
        if (!$activeShift && $employee->department_id) {
            $activeShift = SalesShift::where('branch_id', $this->getBranchId())
                ->where('shift_date', \Carbon\Carbon::today())
                ->where('status', 'active')
                ->where('department_id', $employee->department_id)
                ->first();
        }

        // If still not found, try to find any active sales shift in the branch
        if (!$activeShift) {
            $activeShift = SalesShift::where('branch_id', $this->getBranchId())
                ->where('shift_date', \Carbon\Carbon::today())
                ->where('status', 'active')
                ->whereHas('department.category', function ($q) {
                    $q->where('name', 'Sales');
                })
                ->first();
        }

        if ($activeShift) {
            $this->currentSalesShiftId = $activeShift->id;
        }
    }

    public function updatedSelectedSalesShiftId($shiftId = null)
    {
        // Refresh data when shift selection changes
        $this->resetPage();
    }

    public function getRowsProperty()
    {
        $shiftId = $this->selectedSalesShiftId ?? $this->currentSalesShiftId;

        $query = ProductDispatch::with(['product', 'shift', 'productDispatchCallbacks'])
            ->where('branch_id', $this->getBranchId())
            ->when($this->departmentId, fn ($q) => $q->where('sales_department_id', $this->departmentId))
            ->when($shiftId, fn ($q) => $q->where('sales_shift_id', $shiftId))
            ->where('status', 'received');

        // Search filter
        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('dispatch_date', 'desc')->paginate($this->quantity);
    }

    public function openCallbackModal($dispatchId)
    {
        $this->selectedDispatch = ProductDispatch::with(['product', 'productDispatchCallbacks'])->find($dispatchId);

        if (!$this->selectedDispatch) {
            $this->toast()->error('Dispatch not found.')->send();
            return;
        }

        $this->callbackQuantity = 0;
        $this->callbackReason = '';
        $this->callbackNotes = '';
        $this->showCallbackModal = true;
    }

    public function closeCallbackModal()
    {
        $this->showCallbackModal = false;
        $this->selectedDispatch = null;
        $this->callbackQuantity = 0;
        $this->callbackReason = '';
        $this->callbackNotes = '';
    }

    public function getAvailableQuantity($dispatch)
    {
        $totalCallbacks = $dispatch->productDispatchCallbacks()
            ->whereIn('status', ['pending', 'approved_by_production', 'received_by_production', 'completed'])
            ->sum('quantity');

        return $dispatch->received_quantity - $totalCallbacks;
    }

    public function submitCallback()
    {
        $this->validate([
            'callbackQuantity' => 'required|numeric|min:0.01',
            'callbackReason' => 'required|in:expired,damaged,quality_issue,customer_return,over_received,wrong_item,other',
        ], [
            'callbackQuantity.required' => 'Callback quantity is required',
            'callbackQuantity.min' => 'Callback quantity must be greater than 0',
            'callbackReason.required' => 'Please select a callback reason',
        ]);

        try {
            DB::beginTransaction();

            if (!$this->selectedDispatch) {
                throw new \Exception('Dispatch not found');
            }

            // Validate callback quantity doesn't exceed available
            $availableQty = $this->getAvailableQuantity($this->selectedDispatch);
            if ($this->callbackQuantity > $availableQty) {
                $this->toast()->error("Callback quantity cannot exceed available quantity ({$availableQty}).")->send();
                return;
            }

            $employee = auth()->user();

            $shiftId = $this->selectedDispatch->sales_shift_id
                ?? $this->selectedSalesShiftId
                ?? $this->currentSalesShiftId;

            if (! $shiftId) {
                $this->toast()->error('No sales shift found for this dispatch. Please ensure the dispatch is tied to an active sales shift.')->send();
                return;
            }

            // Create callback record
            $callback = ProductDispatchCallback::create([
                'product_dispatch_id' => $this->selectedDispatch->id,
                'sales_shift_id' => $shiftId,
                'product_id' => $this->selectedDispatch->product_id,
                'recorded_by_id' => $employee->id,
                'recorded_by_type' => get_class($employee),
                'quantity' => $this->callbackQuantity,
                'uom' => $this->selectedDispatch->uom,
                'reason' => $this->callbackReason,
                'status' => 'pending',
                'notes' => $this->callbackNotes,
                'callback_time' => now(),
            ]);

            // Immediately reflect callback in sales stock
            $callback->syncProductStock();

            DB::commit();

            $this->toast()->success('Dispatch callback created successfully! Awaiting production approval.')->send();
            $this->closeCallbackModal();
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Error creating callback: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        $shiftId = $this->selectedSalesShiftId ?? $this->currentSalesShiftId;

        return view('livewire.branch-dashboard.sales-dashboard.callbacks.create-dispatch-callback', [
            'rows' => $this->rows,
            'currentSalesShift' => $this->currentSalesShiftId ? SalesShift::find($this->currentSalesShiftId) : null,
            'selectedSalesShift' => $shiftId ? SalesShift::find($shiftId) : null,
        ]);
    }
}
