<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\Stock;
use App\Models\StockTake;
use App\Models\StockTakeDetail;
use App\Services\AuditService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class StockTakes extends Component
{
    use Exportable, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $search = '';

    public $filterType = '';

    public $filterStatus = '';

    public $showModal = false;

    public $stockTakeId;

    public $stock_take_date;

    public $type = 'full';

    public $notes;

    public $stockTakeItems = [];

    protected array $bulkActions = [
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected $rules = [
        'stock_take_date' => 'required|date',
        'type' => 'required|in:full,partial,cycle',
        'notes' => 'nullable|string',
    ];

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
        $this->stock_take_date = now()->format('Y-m-d');
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

        $query = StockTake::with(['branch', 'conductor', 'verifier', 'stockTakeDetails'])
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                $q->where('stock_take_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('conductor', function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%');
                    });
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('stock_take_date', 'desc');

        $stockTakes = $query->paginate(15);

        return view('livewire.branch-dashboard.inventory.stock-takes', [
            'stockTakes' => $stockTakes,
        ]);
    }

    public function openCreateModal()
    {
        // $this->authorize('create-stock-takes'); // TODO: Enable permissions after testing
        $this->resetFields();
        $this->loadStockItems();
        $this->showModal = true;
    }

    public function loadStockItems()
    {
        $branchId = $this->getBranchId();
        $stocks = Stock::with('item')
            ->where('branch_id', $branchId)
            ->get();

        $this->stockTakeItems = [];
        foreach ($stocks as $stock) {
            $this->stockTakeItems[] = [
                'stock_id' => $stock->id,
                'item_name' => $stock->item->name,
                'system_quantity' => (float) $stock->quantity_available,
                'physical_quantity' => '',
                'uom' => $stock->item->unitOfMeasure?->symbol,
            ];
        }
    }

    public function save()
    {
        // $this->authorize('create-stock-takes'); // TODO: Enable permissions after testing
        $this->validate();

        DB::beginTransaction();
        try {
            $branchId = $this->getBranchId();
            $branch = Auth::guard('web')->user()->employee->branch;

            $stockTakeNumber = StockTake::generateStockTakeNumber($branch->code);

            $stockTake = StockTake::create([
                'branch_id' => $branchId,
                'stock_take_number' => $stockTakeNumber,
                'stock_take_date' => $this->stock_take_date,
                'type' => $this->type,
                'conducted_by' => Auth::guard('web')->id(),
                'status' => 'in_progress',
                'notes' => $this->notes,
            ]);

            $itemDetails = [];
            foreach ($this->stockTakeItems as $item) {
                if ($item['physical_quantity'] !== '' && $item['physical_quantity'] !== null) {
                    $physicalQty = (float) ($item['physical_quantity'] ?? 0);
                    $systemQty = (float) ($item['system_quantity'] ?? 0);
                    $variance = (float) ($physicalQty - $systemQty);
                    $varianceType = $variance == 0 ? 'match' : ($variance > 0 ? 'surplus' : 'shortage');

                    StockTakeDetail::create([
                        'stock_take_id' => $stockTake->id,
                        'stock_id' => $item['stock_id'],
                        'system_quantity' => $systemQty,
                        'physical_quantity' => $physicalQty,
                        'variance_quantity' => $variance,
                        'variance_type' => $varianceType,
                    ]);

                    if ($variance != 0) {
                        $itemDetails[] = "{$item['item_name']}: {$varianceType} of {$variance} {$item['uom']}";
                    }
                }
            }

            // Log the stock take creation
            AuditService::log(
                Auth::guard('web')->user(),
                'create',
                $stockTake,
                "Created {$this->type} stock take #{$stockTakeNumber} on {$this->stock_take_date}. ".
                'Items counted: '.count($this->stockTakeItems).
                '. Variances: '.(empty($itemDetails) ? 'None' : implode(', ', $itemDetails)).
                ". Notes: {$this->notes}",
                'completed'
            );

            DB::commit();
            session()->flash('success', 'Stock take created successfully.');
            $this->closeModal();
            $this->resetFields();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating stock take: '.$e->getMessage());
        }
    }

    public function completeStockTake($id)
    {
        // $this->authorize('create-stock-takes'); // TODO: Enable permissions after testing

        $stockTake = StockTake::findOrFail($id);

        if ($stockTake->branch_id !== $this->getBranchId()) {
            session()->flash('error', 'Unauthorized action.');

            return;
        }

        if ($stockTake->status !== 'in_progress') {
            session()->flash('error', 'Only in-progress stock takes can be completed.');

            return;
        }

        // Get variance summary before marking as completed
        $details = $stockTake->stockTakeDetails;
        $surpluses = $details->where('variance_type', 'surplus')->count();
        $shortages = $details->where('variance_type', 'shortage')->count();
        $matches = $details->where('variance_type', 'match')->count();

        $stockTake->markAsCompleted();

        // Log the stock take completion
        AuditService::log(
            Auth::guard('web')->user(),
            'update',
            $stockTake,
            "Completed stock take #{$stockTake->stock_take_number} (type: {$stockTake->type}). ".
            "Matched: {$matches}, Surplus: {$surpluses}, Shortage: {$shortages}",
            'completed'
        );

        session()->flash('success', 'Stock take marked as completed.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
        $this->resetValidation();
    }

    public function resetFields()
    {
        $this->stockTakeId = null;
        $this->stock_take_date = now()->format('Y-m-d');
        $this->type = 'full';
        $this->notes = '';
        $this->stockTakeItems = [];
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterStatus = '';
    }

    protected function getModelClass(): string
    {
        return StockTake::class;
    }

    protected function getAllSelectableIds(): array
    {
        $branchId = $this->getBranchId();

        return StockTake::where('branch_id', $branchId)->pluck('id')->toArray();
    }

    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No stock takes selected for export.');
            return;
        }

        $stockTakes = StockTake::whereIn('id', $this->selectedIds)
            ->with(['branch', 'conductor', 'verifier', 'stockTakeDetails.stock'])
            ->get();

        $this->export(
            'stock_takes_' . date('Y-m-d'),
            $stockTakes,
            'exports.inventory.stock_takes'
        );

        session()->flash('success', count($this->selectedIds) . ' stock takes exported successfully.');
        $this->resetBulkSelection();
    }


    /**
     * Export stock takes as CSV
     */
    public function exportCSV()
    {
        try {
            $stockTakes = $this->getFilteredStockTakes();

            if ($stockTakes->isEmpty()) {
                session()->flash('warning', 'No stock takes to export.');

                return;
            }

            $csvData = [
                ['Stock Take Number', 'Date', 'Type', 'Conductor', 'Verifier', 'Status', 'Items Counted', 'Matches', 'Surpluses', 'Shortages', 'Notes', 'Created At'],
            ];

            foreach ($stockTakes as $take) {
                $details = $take->stockTakeDetails;
                $csvData[] = [
                    $take->stock_take_number ?? 'N/A',
                    $take->stock_take_date ? \Carbon\Carbon::parse($take->stock_take_date)->format('Y-m-d') : 'N/A',
                    ucfirst($take->type ?? 'full'),
                    $take->conductor?->name ?? 'N/A',
                    $take->verifier?->name ?? 'N/A',
                    ucfirst($take->status ?? 'pending'),
                    $details?->count() ?? 0,
                    $details?->where('variance_type', 'match')->count() ?? 0,
                    $details?->where('variance_type', 'surplus')->count() ?? 0,
                    $details?->where('variance_type', 'shortage')->count() ?? 0,
                    $take->notes ?? 'N/A',
                    $take->created_at ? \Carbon\Carbon::parse($take->created_at)->format('Y-m-d H:i') : 'N/A',
                ];
            }

            $filename = 'stock-takes-'.now()->format('Y-m-d-His').'.csv';
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
            session()->flash('error', 'Export failed: '.$e->getMessage());

            return;
        }
    }

    /**
     * Get filtered stock takes based on current filters
     */
    private function getFilteredStockTakes()
    {
        $branchId = $this->getBranchId();

        return StockTake::with(['branch', 'conductor', 'verifier', 'stockTakeDetails'])
            ->where('branch_id', $branchId)
            ->when($this->search, function ($q) {
                $q->where('stock_take_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('conductor', function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%');
                    });
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('stock_take_date', 'desc')
            ->get();
    }
}
