<?php

namespace App\Livewire\BranchDashboard\Production\MaterialReturns;

use App\Livewire\BaseComponent;
use App\Models\ProductionMaterialReturn;
use App\Models\ItemDispatch;
use App\Models\Department;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Create extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    public ?int $perPage = 10;
    public ?string $search = null;

    public ?int $currentShiftId = null;
    public ?int $selectedShiftId = null;
    public $availableShifts = [];

    // Return form
    public bool $showReturnModal = false;
    public ?string $selectedDispatchId = null;
    public $returnQuantity = 0;
    public $returnReason = '';
    public $returnNotes = '';

    public array $reasonOptions = [
        'damaged' => 'Damaged/Spoilt',
        'expired' => 'Expired',
        'quality_issue' => 'Quality Issue',
        'wrong_item' => 'Wrong Item Sent',
        'excess_return' => 'Excess Stock (Unused)',
    ];

    /**
     * Computed property to get the selected dispatch.
     */
    public function getSelectedDispatchProperty()
    {
        if (!$this->selectedDispatchId) {
            return null;
        }

        return ItemDispatch::with('item')->find($this->selectedDispatchId);
    }

    protected function getModelClass(): string
    {
        return \App\Models\ItemDispatch::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function mount($deptSlug = null)
    {
        if ($deptSlug) {
            $this->deptSlug = $deptSlug;
        }
        
        $this->loadAvailableShifts();
        $this->loadCurrentShift();

        if ($this->currentShiftId) {
            $this->selectedShiftId = $this->currentShiftId;
        } elseif (isset($this->availableShifts) && count($this->availableShifts) > 0) {
            $this->selectedShiftId = $this->availableShifts->first()->id;
        }
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    protected function loadAvailableShifts()
    {
        $query = Shift::where('branch_id', $this->getBranchId())
            ->where('shift_date', '>=', now()->subDays(7))
            ->whereHas('department.category', fn($q) => $q->where('name', 'Production'));

        if ($this->deptSlug) {
            $query->whereHas('department', fn($q) => $q->where('slug', $this->deptSlug));
        }

        $this->availableShifts = $query->orderBy('shift_date', 'desc')->get();
    }

    protected function loadCurrentShift()
    {
        $employee = auth()->user();
        $query = Shift::where('branch_id', $this->getBranchId())
            ->where('shift_date', \Carbon\Carbon::today())
            ->where('status', 'active')
            ->where('employee_id', $employee->id);

        if ($this->deptSlug) {
            $query->whereHas('department', fn($q) => $q->where('slug', $this->deptSlug));
        }

        $activeShift = $query->first();
        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
        }
    }

    /**
     * Computed property to get the active production store.
     */
    public function getProductionStoreProperty()
    {
        return \App\Models\ProductionStore::where('branch_id', $this->getBranchId())
            ->whereHas('department', fn($q) => $q->where('slug', $this->deptSlug))
            ->first();
    }

    public function getEligibleStockProperty()
    {
        $store = $this->productionStore;
        if (!$store) return collect([]);

        // Get the branch ID once
        $branchId = $this->getBranchId();

        // Query production store stocks, filtering for those with net available > 0
        $query = \App\Models\ProductionStoreStock::with(['item', 'item.unitOfMeasure'])
            ->select('production_store_stocks.*')
            ->where('store_id', $store->id)
            ->whereHas('item') // Only include raw materials (Items), exclude WIPs (Products)
            ->whereRaw('(quantity_available - COALESCE((
                SELECT SUM(quantity) 
                FROM production_material_returns 
                WHERE production_material_returns.item_id = production_store_stocks.item_id 
                AND production_material_returns.department_id = ? 
                AND production_material_returns.branch_id = ? 
                AND production_material_returns.status = "pending_approval"
            ), 0)) > 0', [$store->department_id, $branchId]);

        if ($this->search) {
            $query->whereHas('item', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $paginated = $query->paginate($this->perPage);
        
        // Transform the collection to include the net_available attribute for display
        $paginated->getCollection()->transform(function ($stock) use ($store, $branchId) {
            $pendingReturns = \App\Models\ProductionMaterialReturn::where('branch_id', $branchId)
                ->where('department_id', $store->department_id)
                ->where('item_id', $stock->item_id)
                ->where('status', 'pending_approval')
                ->sum('quantity');
            
            $stock->net_available = max(0, (float)$stock->quantity_available - (float)$pendingReturns);
            return $stock;
        });

        return $paginated;
    }

    public function openReturnModal($stockId)
    {
        \Log::info('🟢 [MATERIAL RETURN] Opening modal', ['stockId' => $stockId]);
        
        $stock = \App\Models\ProductionStoreStock::with('item')->find($stockId);
        if (!$stock) {
            \Log::error('🔴 [MATERIAL RETURN] Stock record not found', ['stockId' => $stockId]);
            $this->toast()->error('Stock record not found.')->send();
            return;
        }

        $store = $this->productionStore;
        $pendingReturns = \App\Models\ProductionMaterialReturn::where('branch_id', $this->getBranchId())
            ->where('department_id', $store->department_id)
            ->where('item_id', $stock->item_id)
            ->where('status', 'pending_approval')
            ->sum('quantity');
            
        $netAvailable = max(0, (float)$stock->quantity_available - (float)$pendingReturns);

        if ($netAvailable <= 0) {
            $this->toast()->error('This item has no more available stock. Existing stock is pending return approval.')->send();
            return;
        }

        $this->selectedDispatchId = (string) $stock->id; // Using dispatchId variable to hold stockId for modal
        $this->returnQuantity = (float) $netAvailable;
        $this->returnReason = '';
        $this->returnNotes = '';
        $this->showReturnModal = true;
    }

    public function submitReturn()
    {
        $stock = \App\Models\ProductionStoreStock::with('item')->find($this->selectedDispatchId);

        if (!$stock) {
            $this->toast()->error('Stock record lost. Please try again.')->send();
            return;
        }

        $store = $this->productionStore;
        $pendingReturns = \App\Models\ProductionMaterialReturn::where('branch_id', $this->getBranchId())
            ->where('department_id', $store->department_id)
            ->where('item_id', $stock->item_id)
            ->where('status', 'pending_approval')
            ->sum('quantity');
            
        $netAvailable = max(0, (float)$stock->quantity_available - (float)$pendingReturns);

        $this->validate([
            'returnQuantity' => 'required|numeric|min:0.01|max:' . $netAvailable,
            'returnReason' => 'required|in:' . implode(',', array_keys($this->reasonOptions)),
            'returnNotes' => 'required|string|min:5|max:500',
            'selectedShiftId' => 'required|exists:shifts,id',
        ], [
            'returnQuantity.max' => 'You cannot return more than is currently available (' . $netAvailable . ').',
            'returnNotes.required' => 'Please provide details about why you are returning this material.',
            'selectedShiftId.required' => 'Please select an active production shift.',
        ]);

        try {
            DB::beginTransaction();

            $actor = current_actor();
            $department = Department::where('slug', $this->deptSlug)->first();

            if (!$department) {
                throw new \Exception('Department not found: ' . $this->deptSlug);
            }

            ProductionMaterialReturn::create([
                'branch_id' => $this->getBranchId(),
                'department_id' => $department->id,
                'shift_id' => $this->selectedShiftId,
                'item_dispatch_id' => null, // No longer strictly tied to a single dispatch
                'item_id' => $stock->item_id,
                'quantity' => $this->returnQuantity,
                'uom' => $stock->item?->unitOfMeasure?->symbol ?? 'units',
                'reason' => $this->returnReason,
                'status' => 'pending_approval',
                'notes' => $this->returnNotes,
                'returned_by_id' => $actor->id,
                'returned_by_type' => get_class($actor),
            ]);

            DB::commit();
            \Log::info('✅ [MATERIAL RETURN] Created successfully');

            $this->toast()->success('Material return request submitted! Awaiting inventory approval.')->send();
            $this->showReturnModal = false;
            $this->reset(['selectedDispatchId', 'returnQuantity', 'returnReason', 'returnNotes']);
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('🔴 [MATERIAL RETURN] Failure', ['error' => $e->getMessage()]);
            $this->toast()->error('Failed to submit return: ' . $e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.material-returns.create', [
            'dispatches' => $this->eligibleStock,
        ]);
    }
}
