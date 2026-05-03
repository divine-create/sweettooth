<?php

namespace App\Livewire\BranchDashboard\Inventory\Callbacks;

use App\Livewire\BaseComponent;
use App\Models\ProductionMaterialReturn;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ApproveCallbacks extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;

    public ?string $search = null;

    public ?string $filterStatus = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    // Modal for viewing details
    public $selectedReturn = null;

    public $showDetailsModal = false;

    // Rejection modal
    public $showRejectModal = false;

    public $rejectReason = '';

    public $returnToRejectId = null;

    protected function getModelClass(): string
    {
        return ProductionMaterialReturn::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $query = ProductionMaterialReturn::query()
            ->with(['shift', 'item', 'department', 'returnedBy', 'approvedBy'])
            ->where('branch_id', $this->getBranchId());

        return $query;
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->startDate = \Carbon\Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::today()->format('Y-m-d');
    }

    public function getRowsProperty()
    {
        $query = $this->getFilteredQuery();

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('item', function ($itemQuery) {
                    $itemQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                })
                ->orWhereHas('returnedBy', function ($employeeQuery) {
                    $employeeQuery->where('name', 'like', '%'.$this->search.'%');
                });
            });
        }

        // Status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Date range filter
        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate((int) $this->quantity);
    }

    public function viewDetails($id)
    {
        $this->selectedReturn = ProductionMaterialReturn::with([
            'shift',
            'item',
            'department',
            'returnedBy',
            'approvedBy',
            'itemDispatch'
        ])->find($id);

        if (! $this->selectedReturn) {
            $this->toast()->error('Record not found.')->send();

            return;
        }

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedReturn = null;
    }

    public function approveReturn($id)
    {
        try {
            DB::beginTransaction();

            $return = ProductionMaterialReturn::with('item')->find($id);

            if (! $return || $return->status !== 'pending_approval') {
                $this->toast()->error('Return record not found or not pending.')->send();
                return;
            }

            $actor = current_actor();
            
            // 1. Deduct from Production Store
            $productionStore = \App\Models\ProductionStore::where('branch_id', $return->branch_id)
                ->where('department_id', $return->department_id)
                ->first();

            if ($productionStore) {
                $prodStock = \App\Models\ProductionStoreStock::where('store_id', $productionStore->id)
                    ->where('item_id', $return->item_id)
                    ->first();
                
                if ($prodStock) {
                    $oldQty = (float) $prodStock->quantity_available;
                    $prodStock->quantity_available = max(0, $oldQty - $return->quantity);
                    $prodStock->save();

                    \App\Models\ProductionStoreMovement::create([
                        'store_id' => $productionStore->id,
                        'stock_id' => $prodStock->id,
                        'item_id' => $return->item_id,
                        'type' => 'out',
                        'quantity' => $return->quantity,
                        'quantity_before' => $oldQty,
                        'quantity_after' => $prodStock->quantity_available,
                        'reference_type' => ProductionMaterialReturn::class,
                        'reference_id' => $return->id,
                        'created_by_id' => $actor->id,
                        'created_by_type' => get_class($actor),
                        'notes' => 'Returned to Inventory: ' . $return->reason,
                    ]);
                }
            }

            // 2. Handle Main Inventory Stock
            $mainStock = \App\Models\Stock::where('item_id', $return->item_id)
                ->where('branch_id', $return->branch_id)
                ->first();

            if ($mainStock) {
                $beforeQty = (float) $mainStock->quantity_available;
                $unitCost = (float) ($return->item->unit_price ?? 0);

                if (in_array($return->reason, ['excess_return', 'wrong_item'])) {
                    // Item is perfectly good, goes back to available stock
                    $mainStock->quantity_available += $return->quantity;
                    $mainStock->save();

                    \App\Models\StockMovement::create([
                        'stock_id' => $mainStock->id,
                        'branch_id' => $return->branch_id,
                        'type' => 'return',
                        'quantity' => $return->quantity,
                        'quantity_before' => $beforeQty,
                        'quantity_after' => $mainStock->quantity_available,
                        'unit_cost' => $unitCost,
                        'cost_impact' => $return->quantity * $unitCost,
                        'reference_type' => ProductionMaterialReturn::class,
                        'reference_id' => $return->id,
                        'notes' => 'Raw material returned from Production: ' . $return->reason,
                        'movement_date' => now(),
                        'moved_by_id' => $actor->id,
                        'moved_by_type' => get_class($actor),
                    ]);
                } else {
                    // Item is damaged/expired/wrong, so it's written off/logged as damaged
                    // (It doesn't affect available stock because it was already deducted when dispatched)
                    $mainStock->quantity_damaged += $return->quantity;
                    $mainStock->save();

                    \App\Models\StockMovement::create([
                        'stock_id' => $mainStock->id,
                        'branch_id' => $return->branch_id,
                        'type' => 'damaged',
                        'quantity' => 0, // No change to available
                        'quantity_before' => $beforeQty,
                        'quantity_after' => $beforeQty, // Remains the same
                        'unit_cost' => $unitCost,
                        'cost_impact' => 0,
                        'reference_type' => ProductionMaterialReturn::class,
                        'reference_id' => $return->id,
                        'notes' => 'Spoilt material returned from Production: ' . $return->reason,
                        'movement_date' => now(),
                        'moved_by_id' => $actor->id,
                        'moved_by_type' => get_class($actor),
                    ]);
                }
            }

            // Update status
            $return->update([
                'status' => 'approved',
                'approved_by_id' => $actor->id,
                'approved_by_type' => get_class($actor),
                'approved_at' => now(),
            ]);

            DB::commit();

            $this->toast()->success('Material return approved successfully!')->send();
            
            if ($this->showDetailsModal) {
                $this->closeDetailsModal();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Failed to approve: '.$e->getMessage())->send();
        }
    }

    public function openRejectModal($id)
    {
        $this->returnToRejectId = $id;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->returnToRejectId = null;
        $this->rejectReason = '';
    }

    public function rejectReturn()
    {
        if (empty($this->rejectReason)) {
            $this->toast()->error('Please provide a reason for rejection.')->send();
            return;
        }

        try {
            DB::beginTransaction();

            $return = ProductionMaterialReturn::find($this->returnToRejectId);

            if (! $return || $return->status !== 'pending_approval') {
                $this->toast()->error('Return record not found or not pending.')->send();
                return;
            }

            $actor = current_actor();
            
            $return->update([
                'status' => 'rejected',
                'notes' => $this->rejectReason, // Store rejection reason in notes
                'approved_by_id' => $actor->id,
                'approved_by_type' => get_class($actor),
                'approved_at' => now(),
            ]);

            DB::commit();

            $this->toast()->success('Return request rejected.');
            $this->closeRejectModal();
            
            if ($this->showDetailsModal) {
                $this->closeDetailsModal();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast()->error('Failed to reject: '.$e->getMessage())->send();
        }
    }

    public function render()
    {
        $branchId = $this->getBranchId();
        $stats = [
            'total' => ProductionMaterialReturn::where('branch_id', $branchId)->count(),
            'pending' => ProductionMaterialReturn::where('branch_id', $branchId)->where('status', 'pending_approval')->count(),
            'approved' => ProductionMaterialReturn::where('branch_id', $branchId)->where('status', 'approved')->count(),
            'rejected' => ProductionMaterialReturn::where('branch_id', $branchId)->where('status', 'rejected')->count(),
        ];

        return view('livewire.branch-dashboard.inventory.callbacks.approve-callbacks', [
            'rows' => $this->rows,
            'stats' => $stats,
        ]);
    }
}
