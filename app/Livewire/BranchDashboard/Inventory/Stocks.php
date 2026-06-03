<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Helpers\Settings;
use App\Livewire\BaseComponent;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\CurrencyFormattingService;
use App\Services\InventoryApprovalService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Stocks extends BaseComponent
{
    use Exportable, Interactions;

    // Pagination
    public $quantity = 15;

    #[Url(keep: true)]
    public ?string $b_id = null;

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

    // Filters
    public $search = '';

    public $filterCategory = '';

    public $filterStatus = '';

    public $filterHealthStatus = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    // Edit Modal
    public $showEditModal = false;

    public $editingStockId = null;

    public $editingItemId = null;

    public $quantity_available = 0;

    public $quantity_reserved = 0;

    public $quantity_damaged = 0;

    public $average_cost = 0;

    public $health_status = 'good';

    public $expiry_date = '';

    public $notes = '';

    public $reorder_level = 0;

    public $max_stock_level = 0;

    // Audit Modal
    public $showAuditModal = false;

    public $auditReason = '';

    public $auditAction = null;

    public $pendingStockId = null;

    // Batch Panel
    public bool $showBatchPanel = false;

    public ?int $batchPanelStockId = null;

    protected $rules = [
        'quantity_available' => 'required|numeric|min:0',
        'quantity_reserved' => 'required|numeric|min:0',
        'quantity_damaged' => 'required|numeric|min:0',
        'health_status' => 'required|in:good,warning,critical,expired',
        'expiry_date' => 'nullable|date',
        'notes' => 'nullable|string|max:500',
        'reorder_level' => 'nullable|numeric|min:0',
        'max_stock_level' => 'nullable|numeric|min:0',
        'auditReason' => 'required_if:auditAction,!=null|string|min:10|max:500',
    ];

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = Stock::with(['branch', 'item'])
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                $q->whereHas('item', function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterCategory, function ($q) {
                $q->whereHas('item', function ($query) {
                    $query->where('category', $this->filterCategory);
                });
            })
            ->when($this->filterStatus, function ($q) {
                if ($this->filterStatus === 'low_stock') {
                    $q->whereHas('item', function ($query) {
                        $query->whereColumn('stocks.quantity_available', '<', 'items.reorder_level')
                            ->where('items.reorder_level', '>', 0);
                    });
                } elseif ($this->filterStatus === 'overstock') {
                    $q->whereHas('item', function ($query) {
                        $query->whereRaw('(stocks.quantity_available + stocks.quantity_reserved + stocks.quantity_damaged) > items.max_stock_level')
                            ->where('items.max_stock_level', '>', 0);
                    });
                }
            })
            ->when($this->filterHealthStatus, fn ($q) => $q->where('health_status', $this->filterHealthStatus))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('last_stock_take_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('last_stock_take_date', '<=', $this->filterDateTo))
            ->orderBy('updated_at', 'desc');

        $stocks = $query->paginate((int) ($this->quantity ?? 15));

        $batchPanelData = collect();
        $batchPanelStock = null;
        if ($this->showBatchPanel && $this->batchPanelStockId) {
            $batchPanelStock = Stock::with('item')->find($this->batchPanelStockId);
            $batchPanelData = StockBatch::where('stock_id', $this->batchPanelStockId)
                ->with('purchaseItem.purchase')
                ->orderByRaw('CASE WHEN status = "active" THEN 0 ELSE 1 END')
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date')
                ->get();
        }

        return view('livewire.branch-dashboard.inventory.stocks', [
            'stocks'           => $stocks,
            'batchPanelData'   => $batchPanelData,
            'batchPanelStock'  => $batchPanelStock,
        ]);
    }

    public function openBatchPanel(int $stockId): void
    {
        $this->batchPanelStockId = $stockId;
        $this->showBatchPanel = true;
    }

    public function closeBatchPanel(): void
    {
        $this->showBatchPanel = false;
        $this->batchPanelStockId = null;
    }

    public function openEditModal($stockId)
    {
        $branchId = $this->getBranchId();

        $stock = Stock::with('item')
            ->where('id', $stockId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $this->editingStockId = $stock->id;
        $this->editingItemId = $stock->item_id;
        $this->quantity_available = $stock->quantity_available;
        $this->quantity_reserved = $stock->quantity_reserved;
        $this->quantity_damaged = $stock->quantity_damaged;
        $this->average_cost = $stock->average_cost;
        $this->health_status = $stock->health_status;
        $this->expiry_date = $stock->expiry_date ? $stock->expiry_date->format('Y-m-d') : '';
        $this->notes = '';
        $this->reorder_level = $stock->item->reorder_level ?? 0;
        $this->max_stock_level = $stock->item->max_stock_level ?? 0;
        $this->showEditModal = true;
    }

    public function updateStock()
    {
        $this->validate([
            'quantity_available' => 'required|numeric|min:0',
            'quantity_reserved' => 'required|numeric|min:0',
            'quantity_damaged' => 'required|numeric|min:0',
            'health_status' => 'required|in:good,warning,critical,expired',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        // When approval is required (and not a super admin), show audit modal instead of updating directly
        if (! should_bypass_approval()) {
            $this->pendingStockId = $this->editingStockId;
            $this->auditAction = 'stock_adjustment';
            $this->auditReason = '';
            $this->showEditModal = false; // Hide edit modal but keep form values
            $this->showAuditModal = true;

            return;
        }

        // Super admins can update directly
        $this->applyStockUpdate();
    }

    private function applyStockUpdate()
    {
        DB::beginTransaction();
        try {
            $branchId = $this->getBranchId();

            $stock = Stock::where('id', $this->editingStockId)
                ->where('branch_id', $branchId)
                ->firstOrFail();

            $item = $stock->item;

            $oldQuantityAvailable = (float) $stock->quantity_available;
            $oldQuantityReserved = (float) $stock->quantity_reserved;
            $oldQuantityDamaged = (float) $stock->quantity_damaged;
            $oldAverageCost = (float) $stock->average_cost;
            $oldHealthStatus = $stock->health_status;
            $oldReorderLevel = (float) $item->reorder_level;
            $oldMaxStockLevel = (float) $item->max_stock_level;

            $stock->update([
                'quantity_available' => (float) $this->quantity_available,
                'quantity_reserved' => (float) $this->quantity_reserved,
                'quantity_damaged' => (float) $this->quantity_damaged,
                'health_status' => $this->health_status,
                'expiry_date' => $this->expiry_date ?: null,
                'last_stock_take_date' => now(),
            ]);

            // Update item reorder and max stock levels if changed
            if ((float) $oldReorderLevel !== (float) $this->reorder_level ||
                (float) $oldMaxStockLevel !== (float) $this->max_stock_level) {
                $item->update([
                    'reorder_level' => (float) $this->reorder_level ?? 0,
                    'max_stock_level' => (float) $this->max_stock_level ?? 0,
                ]);
            }

            // Record stock movement if quantity changed
            $unitCost = (float) $stock->average_cost;
            $actorId = Auth::guard('web')->id();

            if ((float) $oldQuantityAvailable != (float) $this->quantity_available) {
                $quantityDiff = (float) $this->quantity_available - (float) $oldQuantityAvailable;

                StockMovement::create([
                    'stock_id' => $stock->id,
                    'type' => 'adjustment',
                    'adjustment_reason' => 'adjustment',
                    'quantity' => (float) abs($quantityDiff),
                    'quantity_before' => (float) $oldQuantityAvailable,
                    'quantity_after' => (float) $this->quantity_available,
                    'unit_cost' => $unitCost,
                    'cost_impact' => (float) abs($quantityDiff) * $unitCost,
                    'reference_type' => null,
                    'reference_id' => null,
                    'moved_by_id' => $actorId,
                    'moved_by_type' => \App\Models\Employee::class,
                    'approved_by_id' => $actorId,
                    'approved_by_type' => \App\Models\Employee::class,
                    'approved_at' => now(),
                    'movement_date' => now(),
                    'notes' => $this->notes ?: 'Manual stock adjustment from Stocks page',
                ]);
            }

            if ((float) $oldQuantityDamaged != (float) $this->quantity_damaged) {
                $damagedDiff = (float) $this->quantity_damaged - (float) $oldQuantityDamaged;

                StockMovement::create([
                    'stock_id' => $stock->id,
                    'type' => 'damaged',
                    'adjustment_reason' => 'damage',
                    'quantity' => (float) abs($damagedDiff),
                    'quantity_before' => (float) $oldQuantityDamaged,
                    'quantity_after' => (float) $this->quantity_damaged,
                    'unit_cost' => $unitCost,
                    'cost_impact' => (float) abs($damagedDiff) * $unitCost,
                    'reference_type' => null,
                    'reference_id' => null,
                    'moved_by_id' => $actorId,
                    'moved_by_type' => \App\Models\Employee::class,
                    'approved_by_id' => $actorId,
                    'approved_by_type' => \App\Models\Employee::class,
                    'approved_at' => now(),
                    'movement_date' => now(),
                    'notes' => $this->notes ?: 'Manual damage adjustment from Stocks page',
                ]);
            }

            // Log the stock update
            $changes = [];
            if ($oldQuantityAvailable !== (float) $this->quantity_available) {
                $changes[] = "Available: {$oldQuantityAvailable} → {$this->quantity_available}";
            }
            if ($oldQuantityReserved !== (float) $this->quantity_reserved) {
                $changes[] = "Reserved: {$oldQuantityReserved} → {$this->quantity_reserved}";
            }
            if ($oldQuantityDamaged !== (float) $this->quantity_damaged) {
                $changes[] = "Damaged: {$oldQuantityDamaged} → {$this->quantity_damaged}";
            }
            if ($oldAverageCost !== (float) $this->average_cost) {
                $changes[] = "Cost: {$oldAverageCost} → {$this->average_cost}";
            }
            if ($oldHealthStatus !== $this->health_status) {
                $changes[] = "Health: {$oldHealthStatus} → {$this->health_status}";
            }
            if ($oldReorderLevel !== (float) $this->reorder_level) {
                $changes[] = "Reorder Level: {$oldReorderLevel} → {$this->reorder_level}";
            }
            if ($oldMaxStockLevel !== (float) $this->max_stock_level) {
                $changes[] = "Max Stock Level: {$oldMaxStockLevel} → {$this->max_stock_level}";
            }

            if (! empty($changes)) {
                AuditService::log(
                    current_actor(),
                    'update',
                    $stock,
                    "Updated stock for item '{$stock->item->name}'. Changes: ".implode(', ', $changes).
                    ". Notes: {$this->notes}",
                    'completed'
                );
            }

            DB::commit();
            session()->flash('success', 'Stock updated successfully.');
            $this->closeEditModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error updating stock: '.$e->getMessage());
        }
    }

    public function proceedWithStockAdjustment(): void
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ], [
            'auditReason.required' => 'Please provide a reason for the stock adjustment.',
            'auditReason.min' => 'Reason must be at least 10 characters.',
        ]);

        try {
            $branchId = $this->getBranchId();

            $stock = Stock::where('id', $this->pendingStockId)
                ->where('branch_id', $branchId)
                ->firstOrFail();

            $actor = current_actor();

            // Create approval request with adjustment data
            // NOTE: InventoryApprovalService::requestStockAdjustment() already logs this request
            // via AuditService::log() internally, so we don't duplicate logging here
            $request = InventoryApprovalService::requestStockAdjustment(
                $actor,
                $stock->id,
                [
                    'quantity_available' => (float) $this->quantity_available,
                    'quantity_reserved' => (float) $this->quantity_reserved,
                    'quantity_damaged' => (float) $this->quantity_damaged,
                    'health_status' => $this->health_status ?? 'good',
                    'expiry_date' => $this->expiry_date ?: null,
                    'reorder_level' => (float) ($this->reorder_level ?? 0),
                    'max_stock_level' => (float) ($this->max_stock_level ?? 0),
                    'notes' => $this->notes ?? '',
                ],
                $this->auditReason
            );

            // Verify the request was created successfully
            if (! $request || ! $request->id) {
                throw new \Exception('Failed to create approval request');
            }

            session()->flash('success', 'Stock adjustment request submitted for approval! Request ID: '.$request->id);
            $this->toast()->success('Request submitted for approval. Navigate to Audit > Inventory Approvals to view.')->send();
            $this->closeAuditModal();
            $this->closeEditModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit stock adjustment: '.$e->getMessage());
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;
        $this->pendingStockId = null;
        $this->resetValidation();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingStockId = null;
        $this->resetValidation();
        $this->resetEditFields();
    }

    public function resetEditFields()
    {
        $this->quantity_available = 0;
        $this->quantity_reserved = 0;
        $this->quantity_damaged = 0;
        $this->average_cost = 0;
        $this->health_status = 'good';
        $this->expiry_date = '';
        $this->notes = '';
        $this->reorder_level = 0;
        $this->max_stock_level = 0;
        $this->editingItemId = null;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterCategory = '';
        $this->filterStatus = '';
        $this->filterHealthStatus = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategory()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterHealthStatus()
    {
        $this->resetPage();
    }

    protected function getModelClass(): string
    {
        return Stock::class;
    }

    protected function getAllSelectableIds(): array
    {
        $branchId = $this->getBranchId();

        return Stock::where('branch_id', $branchId)->pluck('id')->toArray();
    }


    /**
     * Export stock records as CSV
     */
    public function exportCSV()
    {
        try {
            $stocks = $this->getFilteredStocks();

            if ($stocks->isEmpty()) {
                $this->toast()->warning('No stocks to export.')->send();

                return;
            }

            $csvData = [
                ['SKU', 'Item Name', 'Category', 'Available', 'Reserved', 'Damaged', 'Total', 'UOM', 'Avg Cost', 'Total Value', 'Reorder Level', 'Max Level', 'Health Status', 'Expiry Date', 'Last Updated'],
            ];

            foreach ($stocks as $stock) {
                $csvData[] = [
                    $stock->item->sku ?? 'N/A',
                    $stock->item->name ?? 'N/A',
                    $stock->item->category ?? 'N/A',
                    $stock->quantity_available ?? 0,
                    $stock->quantity_reserved ?? 0,
                    $stock->quantity_damaged ?? 0,
                    ($stock->quantity_available ?? 0) + ($stock->quantity_reserved ?? 0) + ($stock->quantity_damaged ?? 0),
                    $stock->item->uom ?? 'units',
                    number_format($stock->average_cost ?? 0, 2),
                    number_format(($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0), 2),
                    $stock->reorder_level ?? 0,
                    $stock->max_stock_level ?? 0,
                    ucfirst($stock->health_status ?? 'good'),
                    $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('Y-m-d') : 'N/A',
                    $stock->updated_at ? \Carbon\Carbon::parse($stock->updated_at)->format('Y-m-d H:i') : 'N/A',
                ];
            }

            $filename = 'inventory-stocks-'.now()->format('Y-m-d-His').'.csv';
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

    /**
     * Get filtered stocks based on current filters
     */
    private function getFilteredStocks()
    {
        $branchId = $this->getBranchId();

        return Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                return $q->whereHas('item', function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterCategory, function ($q) {
                return $q->whereHas('item', function ($query) {
                    $query->where('category', $this->filterCategory);
                });
            })
            ->when($this->filterStatus, function ($q) {
                return $q->whereHas('item', function ($query) {
                    $query->where('status', $this->filterStatus);
                });
            })
            ->when($this->filterHealthStatus, function ($q) {
                return $q->where('health_status', $this->filterHealthStatus);
            })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Format currency value for stock valuation
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    /**
     * Get currency symbol for display
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService;
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');

        return $service->getSymbol($currency);
    }
}
