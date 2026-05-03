<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\ItemDispatch;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\UnitOfMeasure; // Add this import
use App\Services\AuditService;
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

    protected $rules = [
        'dispatchedItems.*.approve_quantity' => 'nullable|numeric|min:0',
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

        $requests = $query->paginate((int) ($this->quantity ?? 15));

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

        $request = ItemRequest::with('requestDetails.item')
            ->where('id', $requestId)
            ->firstOrFail();

        $this->requestId = $requestId;
        $this->dispatchedItems = [];

        // Show ALL items - requested, approved, and dispatched
        foreach ($request->requestDetails as $detail) {
            $remainingToApprove = (float) (($detail->quantity_requested ?? 0) - ($detail->quantity_approved ?? 0));
            $remainingToDispatch = (float) (($detail->quantity_approved ?? 0) - ($detail->quantity_dispatched ?? 0));

            // Get current stock level for this branch
            $stock = Stock::where('branch_id', $branchId)
                ->where('item_id', $detail->item_id)
                ->first();

            $stockAvailable = $stock ? (float) $stock->quantity_available : 0.0;

            $this->dispatchedItems[] = [
                'detail_id' => $detail->id,
                'item_id' => $detail->item_id,
                'item_name' => $detail->item->name,
                'quantity_requested' => $detail->quantity_requested,
                'quantity_approved' => $detail->quantity_approved,
                'quantity_dispatched' => $detail->quantity_dispatched,
                'remaining_to_approve' => $remainingToApprove,
                'remaining_to_dispatch' => $remainingToDispatch,
                'approve_quantity' => 0,
                'stock_available' => $stockAvailable,
                'uom' => $detail->item->unitOfMeasure?->symbol,
                'uom_full_name' => $detail->item->unitOfMeasure?->name, // Add full name for mapping
                'is_fully_approved' => $remainingToApprove <= 0,
                'is_fully_dispatched' => $remainingToDispatch <= 0,
                'is_partially_approved' => $detail->quantity_approved > 0 && $remainingToApprove > 0,
                'is_partially_dispatched' => $detail->quantity_dispatched > 0 && $remainingToDispatch > 0,
                'has_sufficient_stock' => $stockAvailable >= $remainingToDispatch,
            ];
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
                $this->dispatchedItems[$index]['approve_quantity'] = $item['remaining_to_approve'];
            }
        }
    }

    public function approveItems()
    {
        // $this->authorize('approve-items');
        $this->validate();

        $branchId = $this->getBranchId();

        if (empty($this->dispatchedItems) || ! is_array($this->dispatchedItems)) {
            session()->flash('error', 'No items to approve.');

            return;
        }

        try {
            DB::transaction(function () use ($branchId) {
                // Verify request belongs to this branch
                $request = ItemRequest::where('id', $this->requestId)
                    ->where('branch_id', $branchId)
                    ->firstOrFail();

                $approvedCount = 0;
                $approvalTotals = [];

                foreach ($this->dispatchedItems as $item) {
                    $approveQty = (float) ($item['approve_quantity'] ?? 0);

                    if ($approveQty <= 0) {
                        continue; // Skip items with no approval quantity
                    }

                    // Get the detail record
                    $detail = ItemRequestDetail::find($item['detail_id']);
                    if (! $detail) {
                        throw new \Exception("Request detail missing for {$item['item_name']}.");
                    }

                    $remainingToApprove = $detail->quantity_requested - $detail->quantity_approved;

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

                    $available = (float) $stock->quantity_available;
                    $alreadyApproved = (float) ($approvalTotals[$item['item_id']] ?? 0);
                    $availableForThis = $available - $alreadyApproved;

                    if ($approveQty > $availableForThis) {
                        throw new \Exception("Cannot approve {$approveQty} {$item['uom']} of {$item['item_name']}. Only {$availableForThis} {$item['uom']} available in stock.");
                    }

                    $approvalTotals[$item['item_id']] = $alreadyApproved + $approveQty;

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

            session()->flash('success', 'Items approved successfully. You can now dispatch them.');

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

            session()->flash('error', 'Error approving items: '.$e->getMessage());
        }
    }

    /**
     * Get the database-compatible UOM value from the unit symbol
     */
    private function getDatabaseUom($uomSymbol)
    {
        // Fetch all UOM records to create a dynamic mapping
        $uomMap = UnitOfMeasure::all()->pluck('name', 'symbol')->toArray();
        
        // Specific mapping for the enum values in the database
        $enumMappings = [
            'g' => 'grams',
            'gram' => 'grams',
            'grams' => 'grams',
            'kg' => 'kg',
            'kilogram' => 'kg',
            'kilograms' => 'kg',
            'L' => 'liters',
            'liter' => 'liters',
            'liters' => 'liters',
            'ml' => 'ml',
            'milliliter' => 'ml',
            'milliliters' => 'ml',
            'pcs' => 'pcs',
            'piece' => 'pcs',
            'pieces' => 'pcs',
            'unit' => 'units',
            'units' => 'units',
            'bag' => 'bags',
            'bags' => 'bags',
            'carton' => 'cartons',
            'cartons' => 'cartons',
        ];
        
        // First try the specific enum mapping
        if (isset($enumMappings[strtolower($uomSymbol)])) {
            return $enumMappings[strtolower($uomSymbol)];
        }
        
        // Then try the dynamic mapping from the database
        $lowerSymbol = strtolower($uomSymbol);
        foreach ($uomMap as $symbol => $name) {
            if (strtolower($symbol) === $lowerSymbol) {
                // Map to the closest enum value
                $enumKeys = array_keys($enumMappings);
                foreach ($enumKeys as $enumKey) {
                    if (strtolower($name) === strtolower($enumKey) || 
                        str_contains(strtolower($name), strtolower($enumKey)) ||
                        str_contains(strtolower($enumKey), strtolower($name))) {
                        return $enumMappings[$enumKey];
                    }
                }
                // Default fallback to the symbol itself if no match found
                return $uomSymbol;
            }
        }
        
        // If no match found, return the original symbol (which might cause an error)
        return $uomSymbol;
    }

    public function dispatchItems()
    {
        // $this->authorize('dispatch-items');

        $branchId = $this->getBranchId();

        if (empty($this->dispatchedItems) || ! is_array($this->dispatchedItems)) {
            // session()->flash('error', 'No items to dispatch.');
            $this->toast()->error('here')->send();

            return;
        }

        try {
            // First, check if there are any items to dispatch
            $hasItemsToDispatch = false;
            foreach ($this->dispatchedItems as $item) {
                if ($item['remaining_to_dispatch'] > 0) {
                    $hasItemsToDispatch = true;
                    break;
                }
            }

            if (! $hasItemsToDispatch) {
                $this->toast()->error('No approved items to dispatch.')->send();

                return;
            }

            // Track warnings and skipped items
            $lowStockWarnings = [];
            $insufficientItems = [];

            DB::transaction(function () use ($branchId, &$lowStockWarnings, &$insufficientItems) {
                // Verify request belongs to this branch
                $request = ItemRequest::where('id', $this->requestId)
                    ->where('branch_id', $branchId)
                    ->firstOrFail();

                $dispatchedCount = 0;
                $dispatchPlan = [];

                foreach ($this->dispatchedItems as $item) {
                    // Get fresh data from database
                    $detail = ItemRequestDetail::find($item['detail_id']);
                    if (! $detail) {
                        throw new \Exception("Request detail missing for {$item['item_name']}.");
                    }

                    $dispatchQty = $detail->quantity_approved - $detail->quantity_dispatched;

                    if ($dispatchQty <= 0) {
                        continue; // Skip items that don't need dispatching
                    }

                    // Lock stock row for concurrency safety
                    $stock = Stock::forBranch($branchId)
                        ->where('item_id', $item['item_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        throw new \Exception("Stock not found for {$item['item_name']} in this branch.");
                    }

                    // Check if stock is insufficient - BLOCK ALL dispatch
                    if ($stock->quantity_available < $dispatchQty) {
                        $insufficientItems[] = "{$item['item_name']}: Requested {$dispatchQty} {$item['uom']}, but only {$stock->quantity_available} {$item['uom']} available in stock.";
                        continue;
                    }

                    $dispatchPlan[] = [
                        'item' => $item,
                        'detail' => $detail,
                        'stock' => $stock,
                        'dispatchQty' => $dispatchQty,
                    ];
                }

                if (! empty($insufficientItems)) {
                    throw new \Exception('Dispatch blocked. Insufficient stock for: '.implode(' | ', $insufficientItems));
                }

                foreach ($dispatchPlan as $plan) {
                    $item = $plan['item'];
                    $detail = $plan['detail'];
                    $stock = $plan['stock'];
                    $dispatchQty = $plan['dispatchQty'];

                    // Save before and after quantities
                    $quantityBefore = $stock->quantity_available;
                    $stock->quantity_available -= $dispatchQty;
                    $stock->save();
                    $quantityAfter = $stock->quantity_available;

                    // Check if stock is now below reorder level (warn but still dispatch since we have enough)
                    if ($stock->item && $stock->item->reorder_level && $quantityAfter <= $stock->item->reorder_level) {
                        $lowStockWarnings[] = "{$item['item_name']}: Stock level is now {$quantityAfter} {$item['uom']}, which is at or below the reorder level of {$stock->item->reorder_level} {$item['uom']}. Please restock!";
                    }

                    // Map uom to database enum values using the dynamic method
                    $mappedUom = $this->getDatabaseUom($item['uom']);

                    // Create dispatch record
                    ItemDispatch::create([
                        'branch_id' => $branchId,
                        'request_id' => $this->requestId,
                        'item_id' => $item['item_id'],
                        'dispatched_by_id' => Auth::guard('web')->id(),
                        'dispatched_by_type' => \App\Models\Employee::class,
                        'quantity' => $dispatchQty,
                        'uom' => $mappedUom,
                        'dispatch_time' => now(),
                        'shift' => $request->shift,
                    ]);

                    // Update request detail (track total dispatched)
                    $detail->quantity_dispatched += $dispatchQty;
                    $detail->save();

                    // Record stock movement
                    StockMovement::create([
                        'stock_id' => $stock->id,
                        'type' => 'out',
                        'quantity' => -$dispatchQty,
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
                        'quantity' => $dispatchQty,
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
                        $dispatchQty = $detail->quantity_approved - $detail->quantity_dispatched;
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

    public function closeModal()
    {
        $this->showDispatchModal = false;
        $this->requestId = null;
        $this->dispatchedItems = [];
        $this->resetValidation();
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
