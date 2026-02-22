<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\ItemDispatch;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\UomConversionService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
// use TallStackUi\
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ItemDispatches extends Component
{
    use Exportable, Interactions, WithPagination;

    // Pagination
    public $quantity = 15;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $search = '';

    public $filterShift = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    public $showDispatchModal = false;

    public $requestId;

    public $dispatchedItems = [];
    public ?string $modalError = null;
    public ?string $modalWarning = null;
    public ?string $modalSuccess = null;
    public array $approvalBlockers = [];
    public array $dispatchBlockers = [];

    protected $rules = [
        'dispatchedItems.*.approve_quantity' => 'nullable|numeric|min:0',
        'dispatchedItems.*.dispatch_quantity' => 'nullable|numeric|min:0',
    ];

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        // Fetch ItemRequests instead of ItemDispatches
        $query = ItemRequest::with(['department', 'requestDetails.item', 'requester'])
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('request_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('department', function ($subQuery) {
                            $subQuery->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('requester', function ($subQuery) {
                            $subQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->filterShift, fn ($q) => $q->where('shift', $this->filterShift))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderBy('created_at', 'desc');

        $requests = $query->paginate($this->quantity ?? 15);

        // Pending requests for the accordion
        $pendingRequests = ItemRequest::with(['department', 'requestDetails.item'])
            ->where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'approved', 'partially_dispatched'])
            ->whereHas('requestDetails', function ($q) {
                $q->whereColumn('quantity_approved', '>', 'quantity_dispatched')
                    ->orWhere(function ($sub) {
                        $sub->where('quantity_approved', '=', 0)
                            ->whereColumn('quantity_requested', '>', 'quantity_dispatched');
                    });
            })
            ->latest('created_at')
            ->get();

        return view('livewire.branch-dashboard.inventory.item-dispatches', [
            'requests' => $requests,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    public function openDispatchModal($requestId)
    {
        // $this->authorize('dispatch-items'); // TODO: Enable permissions after testing
        $branchId = $this->getBranchId();

        if (! $this->showDispatchModal) {
            $this->modalError = null;
            $this->modalWarning = null;
            $this->modalSuccess = null;
            $this->approvalBlockers = [];
            $this->dispatchBlockers = [];
        }
        $request = ItemRequest::with(['requestDetails.item', 'requestDetails.unitOfMeasure'])
            ->where('id', $requestId)
            ->firstOrFail();

        $this->requestId = $requestId;
        $this->dispatchedItems = [];

        // Show ALL items - requested, approved, and dispatched
        foreach ($request->requestDetails as $detail) {
            $requestedQty = $this->getRequestedQty($detail);
            $remainingToApprove = $requestedQty - (float) ($detail->quantity_approved ?? 0);
            $remainingToDispatch = (float) (($detail->quantity_approved ?? 0) - ($detail->quantity_dispatched ?? 0));

            // Get current stock level for this branch
            $stock = Stock::where('branch_id', $branchId)
                ->where('item_id', $detail->item_id)
                ->first();

            $stockAvailableBase = $stock ? (float) $stock->quantity_available : 0.0;
            $stockAvailableInRequestUom = $this->convertStockBaseQuantityToRequestUom($detail, $stockAvailableBase);

            $requiresRequest = (bool) ($detail->requires_request ?? $detail->item?->requires_request ?? false);

            $hasRequested = $requestedQty > 0;
            $hasApproved = (float) ($detail->quantity_approved ?? 0) > 0;

            $this->dispatchedItems[] = [
                'detail_id' => $detail->id,
                'item_id' => $detail->item_id,
                'item_name' => $detail->item->name,
                'requires_request' => $requiresRequest,
                'quantity_requested' => $requestedQty,
                'quantity_approved' => $detail->quantity_approved,
                'quantity_dispatched' => $detail->quantity_dispatched,
                'remaining_to_approve' => $remainingToApprove,
                'remaining_to_dispatch' => $remainingToDispatch,
                'approve_quantity' => 0,
                'dispatch_quantity' => 0,
                'stock_available' => $stockAvailableInRequestUom,
                'uom' => $detail->unitOfMeasure?->symbol ?? $detail->item->unitOfMeasure?->symbol,
                'is_fully_approved' => $hasRequested && $remainingToApprove <= 0,
                'is_fully_dispatched' => $hasApproved && $remainingToDispatch <= 0,
                'is_partially_approved' => $detail->quantity_approved > 0 && $remainingToApprove > 0,
                'is_partially_dispatched' => $detail->quantity_dispatched > 0 && $remainingToDispatch > 0,
                'has_sufficient_stock' => $stockAvailableInRequestUom >= $remainingToDispatch,
            ];
        }

        if (collect($this->dispatchedItems)->contains(fn (array $item) => (bool) ($item['requires_request'] ?? false))) {
            $this->modalWarning = 'This request contains durable/request-required items. Dispatch must proceed through this approved request flow only.';
        }

        $this->showDispatchModal = true;
    }

    /**
     * Approve all items with their full remaining quantities
     */
    public function approveAll()
    {
        foreach ($this->dispatchedItems as $index => $item) {
            if ($item['remaining_to_approve'] > 0) {
                $this->dispatchedItems[$index]['approve_quantity'] = min(
                    (float) $item['remaining_to_approve'],
                    (float) ($item['stock_available'] ?? 0)
                );
            }
        }
    }

    public function dispatchAll()
    {
        foreach ($this->dispatchedItems as $index => $item) {
            if ($item['remaining_to_dispatch'] > 0) {
                $this->dispatchedItems[$index]['dispatch_quantity'] = min(
                    (float) $item['remaining_to_dispatch'],
                    (float) ($item['stock_available'] ?? 0)
                );
            }
        }
    }

    public function approveItems()
    {
        // $this->authorize('approve-items');
        $this->validate();

        $branchId = $this->getBranchId();
        $this->modalError = null;
        $this->modalWarning = null;
        $this->modalSuccess = null;
        $this->approvalBlockers = [];
        $this->dispatchBlockers = [];

        if (empty($this->dispatchedItems) || ! is_array($this->dispatchedItems)) {
            $this->modalError = 'No items to approve.';

            return;
        }

        $hasErrors = false;

        // Validate each approval quantity and stock availability
        foreach ($this->dispatchedItems as $item) {
            $approveQty = (float) ($item['approve_quantity'] ?? 0);
            if ($approveQty <= 0) {
                continue;
            }

            $detail = ItemRequestDetail::with(['item', 'itemRequest'])->find($item['detail_id']);
            if (! $detail) {
                $this->modalError = "{$item['item_name']}: request detail not found.";
                $this->approvalBlockers[$item['detail_id']] = true;
                $hasErrors = true;
                continue;
            }

            $requestedQty = $this->getRequestedQty($detail);
            $remainingToApprove = $requestedQty - (float) ($detail->quantity_approved ?? 0);
            if ($approveQty > $remainingToApprove) {
                $this->modalError = "{$item['item_name']}: approve quantity exceeds remaining requested.";
                $this->approvalBlockers[$item['detail_id']] = true;
                $hasErrors = true;
                continue;
            }

            $stock = Stock::forBranch($branchId)
                ->where('item_id', $item['item_id'])
                ->first();

            if (! $stock) {
                $this->modalError = "{$item['item_name']}: stock not found.";
                $this->approvalBlockers[$item['detail_id']] = true;
                $hasErrors = true;
                continue;
            }

            $approveQtyBase = $this->convertRequestQuantityToStockBase($detail, $approveQty);
            if ((float) $stock->quantity_available < $approveQtyBase) {
                $availableInRequestUom = $this->convertStockBaseQuantityToRequestUom($detail, (float) $stock->quantity_available);
                $this->modalError = "{$item['item_name']}: approve {$approveQty} {$item['uom']} exceeds stock ({$availableInRequestUom} {$item['uom']}).";
                $this->approvalBlockers[$item['detail_id']] = true;
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            if (! $this->modalError) {
                $this->modalError = 'Some items could not be approved. Adjust quantities and try again.';
            }

            return;
        }

        $approvedCount = 0;

        try {
            DB::transaction(function () use ($branchId, &$approvedCount) {
                // Verify request belongs to this branch
                $request = ItemRequest::where('id', $this->requestId)
                    ->where('branch_id', $branchId)
                    ->firstOrFail();

                $approvalTotals = [];
                foreach ($this->dispatchedItems as $item) {
                    $approveQty = (float) ($item['approve_quantity'] ?? 0);

                    if ($approveQty <= 0) {
                        continue; // Skip items with no approval quantity
                    }

                    // Get the detail record
                    $detail = ItemRequestDetail::with(['item', 'itemRequest'])->find($item['detail_id']);
                    if (! $detail) {
                        throw new \Exception("Request detail missing for {$item['item_name']}.");
                    }

                    if ((bool) ($detail->requires_request ?? $detail->item?->requires_request ?? false) && ! $detail->request_id) {
                        throw new \Exception("{$item['item_name']} requires a valid request before approval/dispatch.");
                    }

                    $requestedQty = $this->getRequestedQty($detail);
                    $remainingToApprove = $requestedQty - (float) $detail->quantity_approved;

                    if ($approveQty > $remainingToApprove) {
                        throw new \Exception("Cannot approve {$approveQty} {$item['uom']} of {$item['item_name']}. Only {$remainingToApprove} {$item['uom']} remaining to approve.");
                    }

                    // Ensure sufficient stock before approving (prevent over-approval)
                    $stock = Stock::forBranch($branchId)
                        ->where('item_id', $item['item_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        throw new \Exception("Stock not found for {$item['item_name']} in this branch.");
                    }

                    $approveQtyBase = $this->convertRequestQuantityToStockBase($detail, $approveQty);
                    $available = (float) $stock->quantity_available;
                    $alreadyApproved = (float) ($approvalTotals[$item['item_id']] ?? 0);
                    $availableForThis = $available - $alreadyApproved;

                    if ($approveQtyBase > $availableForThis) {
                        $availableRequestUom = $this->convertStockBaseQuantityToRequestUom($detail, $availableForThis);
                        throw new \Exception("Cannot approve {$approveQty} {$item['uom']} of {$item['item_name']}. Only {$availableRequestUom} {$item['uom']} available in stock.");
                    }

                    $approvalTotals[$item['item_id']] = $alreadyApproved + $approveQtyBase;

                    $detail->quantity_approved += $approveQty;
                    $detail->save();

                    $approvedCount++;

                    logger()->info('Item approved', [
                        'item_id' => $item['item_id'],
                        'item_name' => $item['item_name'],
                        'approved_quantity' => $approveQty,
                        'total_approved' => $detail->quantity_approved,
                        'request_id' => $this->requestId,
                    ]);
                }

                if ($approvedCount > 0) {
                    $request->refresh();

                    // Log the approval
                    $approvedItems = [];
                    foreach ($this->dispatchedItems as $item) {
                        if ((float) ($item['approve_quantity'] ?? 0) > 0) {
                            $approvedItems[] = "{$item['item_name']}: {$item['approve_quantity']} {$item['uom']}";
                        }
                    }

                    // Log the approval
                    AuditService::log(
                        Auth::guard('web')->user(),
                        'update',
                        $request,
                        "Approved {$approvedCount} item(s) from request #{$request->request_number}. ".
                        'Items: '.implode(', ', $approvedItems),
                        'completed'
                    );
                }

            });

            if ($approvedCount > 0) {
                $this->modalSuccess = 'Items approved successfully. You can now dispatch them.';
            } else {
                $this->modalWarning = 'No items were approved. Check quantities and stock.';
            }

            // Refresh the modal data to show updated approval status
            $this->openDispatchModal($this->requestId);

        } catch (\Exception $e) {
            logger()->error('Approval Error: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_id' => $this->requestId ?? null,
                'branch_id' => $branchId ?? null,
                'items' => $this->dispatchedItems ?? [],
            ]);

            $this->modalError = 'Error approving items: '.$e->getMessage();
        }
    }

    public function dispatchItems()
    {
        // $this->authorize('dispatch-items');

        $branchId = $this->getBranchId();
        $this->modalError = null;
        $this->modalWarning = null;
        $this->modalSuccess = null;
        $this->dispatchBlockers = [];

        if (empty($this->dispatchedItems) || ! is_array($this->dispatchedItems)) {
            // session()->flash('error', 'No items to dispatch.');
            $this->toast()->error('here')->send();

            return;
        }

        try {
            $hasItemsToDispatch = false;
            $hasErrors = false;

            // Pre-validate dispatch quantities
            foreach ($this->dispatchedItems as $item) {
                $dispatchQty = (float) ($item['dispatch_quantity'] ?? 0);
                if ($dispatchQty <= 0) {
                    continue;
                }

                $hasItemsToDispatch = true;

                $detail = ItemRequestDetail::with(['item', 'itemRequest'])->find($item['detail_id']);
                if (! $detail) {
                    $this->modalError = "{$item['item_name']}: request detail not found.";
                    $this->dispatchBlockers[$item['detail_id']] = true;
                    $hasErrors = true;
                    continue;
                }

                $remainingToDispatch = (float) ($detail->quantity_approved ?? 0) - (float) ($detail->quantity_dispatched ?? 0);
                if ($dispatchQty > $remainingToDispatch) {
                    $this->modalError = "{$item['item_name']}: dispatch quantity exceeds remaining approved.";
                    $this->dispatchBlockers[$item['detail_id']] = true;
                    $hasErrors = true;
                    continue;
                }

                $stock = Stock::forBranch($branchId)
                    ->where('item_id', $item['item_id'])
                    ->first();

                if (! $stock) {
                    $this->modalError = "{$item['item_name']}: stock not found.";
                    $this->dispatchBlockers[$item['detail_id']] = true;
                    $hasErrors = true;
                    continue;
                }

                $dispatchQtyBase = $this->convertRequestQuantityToStockBase($detail, $dispatchQty);
                if ((float) $stock->quantity_available < $dispatchQtyBase) {
                    $availableInRequestUom = $this->convertStockBaseQuantityToRequestUom($detail, (float) $stock->quantity_available);
                    $this->modalError = "{$item['item_name']}: dispatch {$dispatchQty} {$item['uom']} exceeds stock ({$availableInRequestUom} {$item['uom']}).";
                    $this->dispatchBlockers[$item['detail_id']] = true;
                    $hasErrors = true;
                }
            }

            if (! $hasItemsToDispatch) {
                $this->toast()->error('No items selected to dispatch.')->send();

                return;
            }

            if ($hasErrors) {
                if (! $this->modalError) {
                    $this->modalError = 'Some items could not be dispatched. Adjust quantities and try again.';
                }

                return;
            }

            // Track warnings and skipped items
            $lowStockWarnings = [];

            DB::transaction(function () use ($branchId, &$lowStockWarnings) {
                // Verify request belongs to this branch
                $request = ItemRequest::where('id', $this->requestId)
                    ->where('branch_id', $branchId)
                    ->firstOrFail();

                $dispatchedCount = 0;

                foreach ($this->dispatchedItems as $item) {
                    $dispatchQtyRequestUom = (float) ($item['dispatch_quantity'] ?? 0);
                    if ($dispatchQtyRequestUom <= 0) {
                        continue;
                    }

                    // Get fresh data from database
                    $detail = ItemRequestDetail::with(['item', 'unitOfMeasure', 'itemRequest'])->find($item['detail_id']);
                    if (! $detail) {
                        throw new \Exception("Request detail missing for {$item['item_name']}.");
                    }

                    if ((bool) ($detail->requires_request ?? $detail->item?->requires_request ?? false) && ! $detail->request_id) {
                        throw new \Exception("{$item['item_name']} requires a valid request before dispatch.");
                    }

                    $remainingToDispatch = (float) ($detail->quantity_approved ?? 0) - (float) ($detail->quantity_dispatched ?? 0);
                    if ($dispatchQtyRequestUom > $remainingToDispatch) {
                        throw new \Exception("Cannot dispatch {$dispatchQtyRequestUom} {$item['uom']} of {$item['item_name']}. Only {$remainingToDispatch} {$item['uom']} approved and pending dispatch.");
                    }

                    // Lock stock row for concurrency safety
                    $stock = Stock::forBranch($branchId)
                        ->where('item_id', $item['item_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        throw new \Exception("Stock not found for {$item['item_name']} in this branch.");
                    }

                    $dispatchQtyBaseUom = $this->convertRequestQuantityToStockBase($detail, $dispatchQtyRequestUom);
                    if ((float) $stock->quantity_available < $dispatchQtyBaseUom) {
                        $availableInRequestUom = $this->convertStockBaseQuantityToRequestUom($detail, (float) $stock->quantity_available);
                        throw new \Exception("Cannot dispatch {$dispatchQtyRequestUom} {$item['uom']} of {$item['item_name']}. Only {$availableInRequestUom} {$item['uom']} available in stock.");
                    }

                    // Save before and after quantities
                    $quantityBefore = $stock->quantity_available;
                    $stock->quantity_available -= $dispatchQtyBaseUom;
                    $stock->save();
                    $quantityAfter = $stock->quantity_available;

                    // Check if stock is now below reorder level (warn but still dispatch since we have enough)
                    if ($stock->item && $stock->item->reorder_level && $quantityAfter <= $stock->item->reorder_level) {
                        $itemBaseUomSymbol = $stock->item->unitOfMeasure?->symbol ?? $item['uom'];
                        $lowStockWarnings[] = "{$item['item_name']}: Stock level is now {$quantityAfter} {$itemBaseUomSymbol}, which is at or below the reorder level of {$stock->item->reorder_level} {$itemBaseUomSymbol}. Please restock!";
                    }

                    $dispatchUomId = $this->resolveDispatchUomId($detail);

                    // Create dispatch record
                    ItemDispatch::create([
                        'branch_id' => $branchId,
                        'request_id' => $this->requestId,
                        'item_id' => $item['item_id'],
                        'dispatched_by_id' => Auth::guard('web')->id(),
                        'dispatched_by_type' => \App\Models\Employee::class,
                        'received_by_id' => null,
                        'received_by_type' => null,
                        'quantity' => $dispatchQtyRequestUom,
                        'uom' => null,
                        'uom_id' => $dispatchUomId,
                        'dispatch_time' => now(),
                        'received_time' => null,
                        'shift' => $request->shift,
                        'notes' => $request->notes,
                    ]);

                    // Update request detail (track total dispatched)
                    $detail->quantity_dispatched += $dispatchQtyRequestUom;
                    $detail->save();

                    // Record stock movement
                    StockMovement::create([
                        'stock_id' => $stock->id,
                        'type' => 'out',
                        'quantity' => -$dispatchQtyBaseUom,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'movement_date' => now(),
                        'reference_type' => ItemRequest::class,
                        'reference_id' => $this->requestId,
                        'moved_by_id' => Auth::guard('web')->id(),
                        'moved_by_type' => \App\Models\Employee::class,
                        'notes' => "Dispatch for request: {$request->request_number}",
                    ]);

                    $dispatchedCount++;

                    logger()->info('Item dispatched', [
                        'item_id' => $item['item_id'],
                        'item_name' => $item['item_name'],
                        'quantity' => $dispatchQtyRequestUom,
                        'before' => $quantityBefore,
                        'after' => $quantityAfter,
                        'stock_id' => $stock->id,
                        'request_id' => $this->requestId,
                    ]);
                }

                // Update request status
                $request->refresh();
                $request->update([
                    'status' => $request->isFullyDispatched()
                        ? 'completed'
                        : 'partially_dispatched',
                ]);

                // Prepare audit description
                $dispatchedItems = [];
                foreach ($this->dispatchedItems as $item) {
                    $detail = ItemRequestDetail::find($item['detail_id']);
                    if ($detail) {
                        $dispatchQty = (float) ($item['dispatch_quantity'] ?? 0);
                        if ($dispatchQty > 0) {
                            $dispatchedItems[] = "{$item['item_name']}: {$dispatchQty} {$item['uom']}";
                        }
                    }
                }

                // Log the dispatch
                if (! empty($dispatchedItems)) {
                    AuditService::log(
                        Auth::guard('web')->user(),
                        'update',
                        $request,
                        "Dispatched items from request #{$request->request_number}. ".
                        'Items: '.implode(', ', $dispatchedItems).
                        ". Status: {$request->status}",
                        'completed'
                    );
                }
            });

            // Show appropriate message based on what happened
            if (! empty($lowStockWarnings)) {
                $message = 'Items dispatched successfully, but with reorder warnings: '.implode(' | ', $lowStockWarnings);
                $this->toast()->warning($message)->send();
            } else {
                $this->toast()->success('All approved items dispatched successfully. Stock updated')->send();
            }

            $this->closeModal();

        } catch (\Exception $e) {
            logger()->error('Dispatch Error: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_id' => $this->requestId ?? null,
                'branch_id' => $branchId ?? null,
                'dispatch_items' => $this->dispatchedItems ?? [],
            ]);
            $this->toast()->error($e->getMessage())->send();
        }
    }

    protected function convertRequestQuantityToStockBase(ItemRequestDetail $detail, float $requestQuantity): float
    {
        $requestUomId = $detail->uom_id;
        $itemBaseUomId = $detail->item?->uom_id;
        if (! $requestUomId || ! $itemBaseUomId || (int) $requestUomId === (int) $itemBaseUomId) {
            return $requestQuantity;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            $requestQuantity,
            (int) $requestUomId,
            (int) $itemBaseUomId,
            [
                'branch_id' => $detail->itemRequest?->branch_id ?? $this->getBranchId(),
                'item_id' => (int) $detail->item_id,
            ]
        );

        return $converted ?? $requestQuantity;
    }

    protected function convertStockBaseQuantityToRequestUom(ItemRequestDetail $detail, float $baseQuantity): float
    {
        $requestUomId = $detail->uom_id;
        $itemBaseUomId = $detail->item?->uom_id;
        if (! $requestUomId || ! $itemBaseUomId || (int) $requestUomId === (int) $itemBaseUomId) {
            return $baseQuantity;
        }

        $converted = app(UomConversionService::class)->tryConvert(
            $baseQuantity,
            (int) $itemBaseUomId,
            (int) $requestUomId,
            [
                'branch_id' => $detail->itemRequest?->branch_id ?? $this->getBranchId(),
                'item_id' => (int) $detail->item_id,
            ]
        );

        return $converted ?? $baseQuantity;
    }

    protected function resolveDispatchUomId(ItemRequestDetail $detail): ?int
    {
        return $detail->uom_id ?? $detail->item?->uom_id;
    }

    public function closeModal()
    {
        $this->showDispatchModal = false;
        $this->requestId = null;
        $this->dispatchedItems = [];
        $this->modalError = null;
        $this->modalWarning = null;
        $this->modalSuccess = null;
        $this->approvalBlockers = [];
        $this->dispatchBlockers = [];
        $this->resetValidation();
    }

    protected function getRequestedQty(ItemRequestDetail $detail): float
    {
        $requested = $detail->quantity_requested;
        if ($requested === null) {
            $requested = $detail->quantity ?? 0;
        }

        return (float) $requested;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterShift = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterShift()
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo()
    {
        $this->resetPage();
    }

    public function updatedFilterReceived()
    {
        $this->resetPage();
    }


    public function exportCSV()
    {
        try {
            $dispatches = ItemDispatch::with(['itemRequest.branch', 'itemRequestDetail.item'])
                ->whereHas('itemRequest', fn ($q) => $q->where('branch_id', $this->getBranchId()))
                ->when($this->search, fn ($q) => $q->whereHas('itemRequestDetail.item', fn ($sq) => $sq->where('name', 'like', '%'.$this->search.'%')))
                ->when($this->filterDateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
                ->when($this->filterDateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
                ->orderBy('created_at', 'desc')
                ->get();

            if ($dispatches->isEmpty()) {
                $this->toast()->warning('No dispatches to export.')->send();

                return;
            }

            $csvData = [
                ['Request #', 'Item Name', 'SKU', 'Requested', 'Approved', 'Dispatched', 'UOM', 'Dispatch Date', 'Status'],
            ];

            foreach ($dispatches as $dispatch) {
                $csvData[] = [
                    $dispatch->itemRequest->request_number ?? 'N/A',
                    $dispatch->itemRequestDetail->item->name ?? 'N/A',
                    $dispatch->itemRequestDetail->item->sku ?? 'N/A',
                    $dispatch->itemRequestDetail->quantity_requested ?? 0,
                    $dispatch->itemRequestDetail->quantity_approved ?? 0,
                    $dispatch->quantity_dispatched ?? 0,
                    $dispatch->itemRequestDetail->item->uom ?? 'units',
                    $dispatch->created_at ? \Carbon\Carbon::parse($dispatch->created_at)->format('Y-m-d H:i') : 'N/A',
                    $dispatch->itemRequest->status ?? 'N/A',
                ];
            }

            $filename = 'item-dispatches-'.now()->format('Y-m-d-His').'.csv';
            $handle = fopen('php://temp', 'r+');

            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            $this->toast()->error('Export failed: '.$e->getMessage())->send();

            return;
        }
    }
}
