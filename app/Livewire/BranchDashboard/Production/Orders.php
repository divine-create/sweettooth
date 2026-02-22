<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\ProductionOrder;
use App\Models\Department;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class Orders extends BaseComponent
{
    use WithPagination, Interactions, Exportable;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?int $filterDepartmentId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Table headers
    public array $headers = [
        ['index' => 'id', 'label' => 'Order ID'],
        ['index' => 'order_number', 'label' => 'Order Number'],
        ['index' => 'product', 'label' => 'Product/Item'],
        ['index' => 'quantity', 'label' => 'Quantity'],
        ['index' => 'unit', 'label' => 'Unit'],
        ['index' => 'department', 'label' => 'Department'],
        ['index' => 'start_date', 'label' => 'Start Date'],
        ['index' => 'due_date', 'label' => 'Due Date'],
        ['index' => 'progress', 'label' => 'Progress'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Actions', 'display' => true],
    ];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected function getModelClass(): string
    {
        return ProductionOrder::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return ProductionOrder::query()
            ->when($this->b_id, function ($query) {
                $query->where('branch_id', $this->b_id);
            })
            ->with(['department', 'product'])
            ->when($this->search, function ($query) {
                $query->where('order_number', 'like', '%' . $this->search . '%')
                      ->orWhere('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterDepartmentId, function ($query) {
                $query->where('department_id', $this->filterDepartmentId);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('start_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('due_date', '<=', $this->dateTo);
            })
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::today()->addDays(30)->format('Y-m-d');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterStatus = null;
        $this->filterDepartmentId = null;
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::today()->addDays(30)->format('Y-m-d');
        $this->resetPage();
    }

    public function bulkDelete(string $message): void
    {
        ProductionOrder::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' production order(s)?')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' order(s) deleted successfully!')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Bulk delete cancelled')
            ->send();
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->info('Cancelled', $message)->send();
    }

    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No production orders selected for export.');
            return;
        }

        $orders = ProductionOrder::whereIn('id', $this->selectedIds)
            ->with(['department', 'product'])
            ->get();

        $this->export(
            'production_orders_' . date('Y-m-d'),
            $orders,
            'exports.production.orders'
        );

        session()->flash('success', count($this->selectedIds) . ' production orders exported successfully.');
        $this->resetBulkSelection();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 20);
        $departments = Department::orderBy('name')->get();

        return view('livewire.branch-dashboard.production.orders', [
            'headers' => $this->headers,
            'rows' => $rows,
            'departments' => $departments,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
