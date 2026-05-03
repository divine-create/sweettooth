<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\Department;
use App\Models\Item;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestDetail;
use App\Models\ProductionStore;
use App\Models\ProductionStoreMovement;
use App\Models\ProductionStoreStock;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\MaterialRequestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class MaterialApprovals extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $statusFilter = 'all';

    public string $search = '';

    public bool $showApprovalModal = false;

    public $selectedRequest = null;

    public array $approvalItems = [];

    public function mount()
    {
        $this->b_id = request()->query('b_id');
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?? request()->query('b_id');
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = MaterialRequest::forBranch($branchId)
            ->with('department', 'details.item', 'details.unitOfMeasure')
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->withStatus($this->statusFilter);
        }

        if ($this->search) {
            $query->where('request_number', 'like', '%'.$this->search.'%');
        }

        $requests = $query->get();

        return view('livewire.branch-dashboard.inventory.material-approvals', [
            'requests' => $requests,
        ]);
    }

    public function openApprovalModal(MaterialRequest $request)
    {
        $this->selectedRequest = $request;
        $this->approvalItems = $request->details->map(function ($detail) {
            $detailArray = [
                'id' => $detail->id,
                'item_id' => $detail->item_id,
                'item_name' => $detail->item?->name ?? 'Unknown',
                'quantity_requested' => $detail->quantity_requested,
                'quantity_approved' => $detail->quantity_requested,
                'quantity_dispatched' => $detail->quantity_dispatched,
                'uom_id' => $detail->uom_id,
                'uom_symbol' => $detail->unitOfMeasure?->symbol ?? 'N/A',
            ];

            $detailArray['is_production_dept'] = $detail->request->department?->is_production ?? false;

            return $detailArray;
        })->toArray();
        $this->showApprovalModal = true;
    }

    public function approveAndDispatch()
    {
        $actor = current_actor();
        $branchId = $this->getBranchId();
        $dispatchData = [];

        // PHASE 1: VALIDATE STOCK - Check if inventory has enough stock for each item
        $insufficientItems = [];

        foreach ($this->approvalItems as $item) {
            $quantityToDispatch = $item['quantity_approved'] - $item['quantity_dispatched'];

            if ($quantityToDispatch > 0) {
                $stock = Stock::where('branch_id', $branchId)
                    ->where('item_id', $item['item_id'])
                    ->first();

                $availableQty = $stock ? (float) $stock->quantity_available : 0;

                if ($availableQty < $quantityToDispatch) {
                    $itemModel = Item::find($item['item_id']);
                    $insufficientItems[] = $itemModel?->name ?? 'Unknown Item';
                }
            }
        }

        // If any item has insufficient stock, block approval
        if (! empty($insufficientItems)) {
            $this->toast()->error('Insufficient stock for: '.implode(', ', $insufficientItems))->send();

            return;
        }

        // PHASE 2: APPROVE AND DEPLETE STOCK
        foreach ($this->approvalItems as $item) {
            $detail = MaterialRequestDetail::find($item['id']);

            $detail->update([
                'quantity_approved' => $item['quantity_approved'],
            ]);

            $quantityToDispatch = $item['quantity_approved'] - $item['quantity_dispatched'];

            if ($quantityToDispatch > 0) {
                // Deplete inventory stock
                $stock = Stock::where('branch_id', $branchId)
                    ->where('item_id', $item['item_id'])
                    ->first();

                if ($stock) {
                    $oldQty = (float) $stock->quantity_available;
                    $newQty = $oldQty - $quantityToDispatch;
                    $stock->quantity_available = $newQty;
                    $stock->save();

                    // Record stock movement
                    StockMovement::create([
                        'stock_id' => $stock->id,
                        'branch_id' => $branchId,
                        'type' => 'out',
                        'quantity' => $quantityToDispatch,
                        'quantity_before' => $oldQty,
                        'quantity_after' => $newQty,
                        'movement_date' => now(),
                        'reference_type' => MaterialRequestDetail::class,
                        'reference_id' => $detail->id,
                        'moved_by_id' => $actor?->id,
                        'moved_by_type' => get_class($actor),
                    ]);
                }

                // Dispatch to production store if target is production department
                $targetDeptId = $this->selectedRequest->department_id;
                $targetDept = Department::with('category')->find($targetDeptId);
                $isProductionDept = $targetDept && $targetDept->category && $targetDept->category->name === 'Production';

                if ($isProductionDept) {
                    $this->dispatchToProductionStore([
                        'detail_id' => $item['id'],
                        'item_id' => $item['item_id'],
                        'quantity' => $quantityToDispatch,
                        'uom_id' => $item['uom_id'],
                        'dispatched_by_id' => $actor?->id,
                        'dispatched_by_type' => get_class($actor),
                    ]);
                } else {
                    $dispatchData[] = [
                        'detail_id' => $item['id'],
                        'item_id' => $item['item_id'],
                        'quantity' => $quantityToDispatch,
                        'uom_id' => $item['uom_id'],
                        'dispatched_by_id' => $actor?->id,
                        'dispatched_by_type' => get_class($actor),
                    ];
                }

                $detail->quantity_dispatched += $quantityToDispatch;
                $detail->save();
            }
        }

        if (! empty($dispatchData)) {
            $service = app(MaterialRequestService::class);
            $service->dispatch($dispatchData);
        }

        $this->updateRequestStatus();
        $this->toast()->success('Request approved and dispatched successfully')->send();
        $this->showApprovalModal = false;
    }

    protected function dispatchToProductionStore(array $data)
    {
        $branchId = $this->selectedRequest->branch_id;
        $departmentId = $this->selectedRequest->department_id;

        Log::info('MaterialApprovals dispatchToProductionStore - START', [
            'material_request_id' => $this->selectedRequest->id,
            'material_request_branch_id' => $branchId,
            'material_request_department_id' => $departmentId,
            'lookup_branch_id' => $this->getBranchId(),
        ]);

        // STRICT: Use only the branch_id and department_id from the stored MaterialRequest
        $productionStore = ProductionStore::where('branch_id', $branchId)
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->first();

        // If not found with exact match, check if ANY store exists for this department (for debugging)
        if (! $productionStore) {
            $anyStoreForDept = ProductionStore::where('department_id', $departmentId)->get();
            $anyStoreForBranch = ProductionStore::where('branch_id', $branchId)->get();

            Log::warning('ProductionStore NOT FOUND - STRICT validation', [
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'any_stores_for_this_dept' => $anyStoreForDept->pluck('id', 'branch_id'),
                'any_stores_for_this_branch' => $anyStoreForBranch->pluck('id', 'department_id'),
            ]);

            $this->toast()->error('No Production Store exists for this department. Please create it first.')->send();

            return;
        }

        Log::info('ProductionStore found', [
            'store_id' => $productionStore->id,
            'store_name' => $productionStore->name,
        ]);

        $existingStock = ProductionStoreStock::where('store_id', $productionStore->id)
            ->where('item_id', $data['item_id'])
            ->first();

        if ($existingStock) {
            $existingStock->quantity_available = $existingStock->quantity_available + $data['quantity'];
            $existingStock->save();
            $stock = $existingStock;
        } else {
            $stock = ProductionStoreStock::create([
                'store_id' => $productionStore->id,
                'item_id' => $data['item_id'],
                'quantity_available' => $data['quantity'],
                'quantity_reserved' => 0,
                'quantity_minimum' => 0,
            ]);
        }

        ProductionStoreMovement::create([
            'store_id' => $productionStore->id,
            'stock_id' => $stock->id,
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'uom_id' => $data['uom_id'],
            'type' => 'in',
            'reference_type' => MaterialRequestDetail::class,
            'reference_id' => $data['detail_id'],
            'created_by_id' => $data['dispatched_by_id'],
            'created_by_type' => $data['dispatched_by_type'],
        ]);

        Log::info('MaterialApprovals dispatchToProductionStore - COMPLETE', [
            'store_id' => $productionStore->id,
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
        ]);
    }

    protected function updateRequestStatus()
    {
        $details = $this->selectedRequest->details()->get();
        $allApproved = $details->every(fn ($d) => $d->quantity_approved > 0);
        $allDispatched = $details->every(fn ($d) => $d->quantity_dispatched >= $d->quantity_approved);

        $newStatus = 'pending';
        if ($allDispatched) {
            $newStatus = 'completed';
        } elseif ($allApproved) {
            $newStatus = 'approved';
        }

        $this->selectedRequest->update(['status' => $newStatus]);
    }

    public function closeModal()
    {
        $this->showApprovalModal = false;
        $this->selectedRequest = null;
        $this->approvalItems = [];
    }
}
