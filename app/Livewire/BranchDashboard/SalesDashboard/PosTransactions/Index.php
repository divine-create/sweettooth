<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\PosTransactions;

use App\Livewire\BaseComponent;
use App\Models\Sale;
use App\Models\Branch;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions, Exportable;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $filterOrderType = null;
    public ?string $filterPaymentMethod = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Table headers
    public array $headers = [
        ['index' => 'id', 'label' => 'Transaction ID'],
        ['index' => 'sale_number', 'label' => 'Receipt #'],
        ['index' => 'sale_time', 'label' => 'Date & Time'],
        ['index' => 'order_type', 'label' => 'Order Type'],
        ['index' => 'items_count', 'label' => 'Items'],
        ['index' => 'subtotal', 'label' => 'Subtotal'],
        ['index' => 'tax', 'label' => 'Tax'],
        ['index' => 'total', 'label' => 'Total'],
        ['index' => 'payment_method', 'label' => 'Payment'],
        ['index' => 'cashier', 'label' => 'Cashier'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Actions', 'display' => true],
    ];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected function getModelClass(): string
    {
        return Sale::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return Sale::query()
            ->where('branch_id', $this->getBranchId())
            ->where('status', '!=', 'draft')
            ->where('status', '!=', 'hold')
            ->with(['saleItems.product', 'payments'])
            ->when($this->search, function ($query) {
                $query->where('sale_number', 'like', '%' . $this->search . '%')
                      ->orWhere('customer_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterOrderType, function ($query) {
                $query->where('order_type', $this->filterOrderType);
            })
            ->when($this->filterPaymentMethod, function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->where('payment_method', $this->filterPaymentMethod);
                });
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('sale_time', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('sale_time', '<=', $this->dateTo);
            })
            ->orderBy('sale_time', 'desc');
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
        $this->dateFrom = Carbon::today()->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterStatus = null;
        $this->filterOrderType = null;
        $this->filterPaymentMethod = null;
        $this->dateFrom = Carbon::today()->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->resetPage();
    }

    public function bulkDelete(string $message): void
    {
        Sale::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' transaction(s)?')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' transaction(s) deleted successfully!')
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
            session()->flash('info', 'No POS transactions selected for export.');
            return;
        }

        $sales = Sale::whereIn('id', $this->selectedIds)
            ->with(['saleItems.product', 'payments'])
            ->get();

        $this->export(
            'pos_transactions_' . date('Y-m-d'),
            $sales,
            'exports.pos.transactions'
        );

        session()->flash('success', count($this->selectedIds) . ' POS transactions exported successfully.');
        $this->resetBulkSelection();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 20);

        return view('livewire.branch-dashboard.sales-dashboard.pos-transactions.index', [
            'headers' => $this->headers,
            'rows' => $rows,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
