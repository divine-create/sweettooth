<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\HealthCheck;
use App\Models\Stock;
use App\Services\AuditService;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class HealthChecks extends Component
{
    use Exportable, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public $search = '';

    public $filterCondition = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    public $filterActionTaken = '';

    public $showModal = false;

    public $isEditing = false;

    public $healthCheckId;

    public $stock_id = '';

    public $check_date;

    public $condition = '';

    public $quantity_affected;

    public $observations;

    public $action_taken;

    protected $rules = [
        'stock_id' => 'required|exists:stocks,id',
        'check_date' => 'required|date',
        'condition' => 'required|in:good,fair,poor,damaged,expired',
        'quantity_affected' => 'nullable|numeric|min:0.01',
        'observations' => 'nullable|string',
        'action_taken' => 'nullable|string',
    ];

    protected $messages = [
        'quantity_affected.numeric' => 'Quantity affected must be a number.',
        'quantity_affected.min' => 'Quantity affected must be greater than 0.01.',
    ];

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function mount()
    {
        $this->check_date = now()->format('Y-m-d');
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = HealthCheck::with(['stock.item', 'stock.branch', 'checker'])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('stock.item', function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('check_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('check_date', '<=', $this->filterDateTo))
            ->when($this->filterActionTaken !== '', function ($q) {
                if ($this->filterActionTaken === '1') {
                    $q->whereNotNull('action_taken')->where('action_taken', '!=', '');
                } else {
                    $q->where(function ($query) {
                        $query->whereNull('action_taken')->orWhere('action_taken', '');
                    });
                }
            })
            ->orderBy('check_date', 'desc');

        $healthChecks = $query->paginate(15);
        $stocks = collect();
        if ($this->showModal) {
            $stocks = Stock::with(['item:id,name,sku,uom_id', 'item.unitOfMeasure:id,symbol'])
                ->select(['id', 'branch_id', 'item_id', 'quantity_available'])
                ->where('branch_id', $branchId)
                ->orderBy('id', 'desc')
                ->get()
                ->map(fn($stock) => [
                    'label' => ($stock->item?->name ?? 'Unknown') . ' - ' . ($stock->item?->sku ?? 'N/A') . ' (' . number_format($stock->quantity_available, 2) . ' ' . ($stock->item?->unitOfMeasure?->symbol ?? 'N/A') . ')',
                    'value' => (string) $stock->id,
                    'quantity_available' => (float) $stock->quantity_available,
                    'uom' => $stock->item?->unitOfMeasure?->symbol ?? 'N/A',
                ]);
        }

        return view('livewire.branch-dashboard.inventory.health-checks', [
            'healthChecks' => $healthChecks,
            'stocks' => $stocks,
        ]);
    }

    public function openCreateModal()
    {
        // $this->authorize('create-health-checks'); // TODO: Enable permissions after testing
        $this->resetFields();
        $this->showModal = true;
    }

    public function save()
    {
        // $this->authorize('create-health-checks'); // TODO: Enable permissions after testing
        $this->validate();

        // Verify stock belongs to branch
        $stock = Stock::findOrFail($this->stock_id);
        if ($stock->branch_id !== $this->getBranchId()) {
            session()->flash('error', 'Invalid stock selection.');

            return;
        }

        // Validate quantity affected does not exceed available quantity
        if ($this->quantity_affected && $this->quantity_affected > $stock->quantity_available) {
            $this->addError('quantity_affected', "Quantity affected ({$this->quantity_affected}) cannot exceed available quantity ({$stock->quantity_available}).");

            return;
        }

        $actor = current_actor();

        if ($this->isEditing) {
            $healthCheck = HealthCheck::findOrFail($this->healthCheckId);
            $healthCheck->update([
                'stock_id' => $this->stock_id,
                'check_date' => $this->check_date,
                'condition' => $this->condition,
                'quantity_affected' => $this->quantity_affected,
                'observations' => $this->observations,
                'action_taken' => $this->action_taken,
            ]);

            AuditService::log(
                $actor,
                'update',
                $healthCheck,
                "Updated health check for item '{$stock->item->name}'. ".
                "Condition: {$this->condition}, Qty Affected: {$this->quantity_affected} {$stock->item->unitOfMeasure?->symbol}. ".
                "Observations: {$this->observations}. Action: {$this->action_taken}",
                'completed'
            );

            session()->flash('success', 'Health check updated successfully.');
        } else {
            $healthCheck = HealthCheck::create([
                'stock_id' => $this->stock_id,
                'checked_by_id' => $actor->id,
                'checked_by_type' => get_class($actor),
                'check_date' => $this->check_date,
                'condition' => $this->condition,
                'quantity_affected' => $this->quantity_affected,
                'observations' => $this->observations,
                'action_taken' => $this->action_taken,
            ]);

            AuditService::log(
                $actor,
                'create',
                $healthCheck,
                "Created health check for item '{$stock->item->name}'. ".
                "Condition: {$this->condition}, Qty Affected: {$this->quantity_affected} {$stock->item->unitOfMeasure?->symbol}. ".
                "Observations: {$this->observations}. Action: {$this->action_taken}",
                'completed'
            );

            session()->flash('success', 'Health check recorded successfully.');
        }

        $this->closeModal();
        $this->resetFields();
    }

    public function editHealthCheck($id)
    {
        $healthCheck = HealthCheck::with('stock')->findOrFail($id);

        if ($healthCheck->stock?->branch_id !== $this->getBranchId()) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->healthCheckId = $healthCheck->id;
        $this->stock_id = (string) $healthCheck->stock_id;
        $this->check_date = $healthCheck->check_date->format('Y-m-d');
        $this->condition = $healthCheck->condition;
        $this->quantity_affected = $healthCheck->quantity_affected;
        $this->observations = $healthCheck->observations;
        $this->action_taken = $healthCheck->action_taken;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function deleteHealthCheck($id)
    {
        $healthCheck = HealthCheck::with('stock.item')->findOrFail($id);

        if ($healthCheck->stock?->branch_id !== $this->getBranchId()) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $actor = current_actor();
        $itemName = $healthCheck->stock?->item?->name ?? 'Unknown';

        AuditService::log(
            $actor,
            'delete',
            $healthCheck,
            "Deleted health check for item '{$itemName}' on {$healthCheck->check_date}. Condition: {$healthCheck->condition}",
            'completed'
        );

        $healthCheck->delete();
        session()->flash('success', 'Health check deleted successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
        $this->resetValidation();
    }

    public function resetFields()
    {
        $this->healthCheckId = null;
        $this->isEditing = false;
        $this->stock_id = '';
        $this->check_date = now()->format('Y-m-d');
        $this->condition = '';
        $this->quantity_affected = null;
        $this->observations = '';
        $this->action_taken = '';
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterCondition = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterActionTaken = '';
    }

    protected function getModelClass(): string
    {
        return HealthCheck::class;
    }

    protected function getAllSelectableIds(): array
    {
        $branchId = $this->getBranchId();

        return HealthCheck::whereHas('stock', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->pluck('id')->toArray();
    }


    /**
     * Export health checks as CSV
     */
    public function exportCSV()
    {
        try {
            $healthChecks = $this->getFilteredHealthChecks();

            if ($healthChecks->isEmpty()) {
                session()->flash('warning', 'No health checks to export.');

                return;
            }

            $csvData = [
                ['Item Name', 'SKU', 'Check Date', 'Condition', 'Quantity Affected', 'UOM', 'Observations', 'Action Taken', 'Checked By', 'Created At'],
            ];

            foreach ($healthChecks as $check) {
                $csvData[] = [
                    $check->stock?->item?->name ?? 'N/A',
                    $check->stock?->item?->sku ?? 'N/A',
                    $check->check_date ? \Carbon\Carbon::parse($check->check_date)->format('Y-m-d') : 'N/A',
                    ucfirst($check->condition ?? 'good'),
                    $check->quantity_affected ?? 0,
                    $check->stock?->item?->unitOfMeasure?->symbol ?? 'units',
                    $check->observations ?? 'N/A',
                    $check->action_taken ?? 'N/A',
                    $check->checker?->name ?? 'N/A',
                    $check->created_at ? \Carbon\Carbon::parse($check->created_at)->format('Y-m-d H:i') : 'N/A',
                ];
            }

            $filename = 'health-checks-'.now()->format('Y-m-d-His').'.csv';
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
     * Get filtered health checks based on current filters
     */
    private function getFilteredHealthChecks()
    {
        $branchId = $this->getBranchId();

        return HealthCheck::with(['stock.item', 'stock.branch', 'checker'])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('stock.item', function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('check_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('check_date', '<=', $this->filterDateTo))
            ->when($this->filterActionTaken !== '', function ($q) {
                if ($this->filterActionTaken === '1') {
                    $q->whereNotNull('action_taken')->where('action_taken', '!=', '');
                } else {
                    $q->where(function ($query) {
                        $query->whereNull('action_taken')->orWhere('action_taken', '');
                    });
                }
            })
            ->orderBy('check_date', 'desc')
            ->get();
    }
}
