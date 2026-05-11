<?php

namespace App\Livewire\BranchDashboard\Production\CustomerOrders;

use App\Enums\CustomerOrderStatus;
use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\CustomerOrder;
use App\Services\CustomerOrderService;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    public string $search = '';
    public string $statusFilter = 'all';

    public bool $showDetailModal = false;
    public ?CustomerOrder $selectedOrder = null;

    public bool $showRejectModal = false;
    public int $rejectingOrderId = 0;
    public string $rejectionReason = '';

    protected array $bulkActions = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->selectedOrder = CustomerOrder::with(['items.product', 'approver', 'rejector', 'dispatches'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedOrder = null;
    }

    public function approveOrder(int $id): void
    {
        try {
            $order = CustomerOrder::findOrFail($id);

            if (! $order->status->canApprove()) {
                $this->toast()->error('This order cannot be approved in its current state.')->send();

                return;
            }

            app(CustomerOrderService::class)->approveOrder($order, auth()->user());

            $this->closeDetail();
            $this->toast()->success("Order {$order->order_number} approved. Production requests created.")->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Failed to approve order: ' . $e->getMessage())->send();
        }
    }

    public function openRejectModal(int $id): void
    {
        $this->rejectingOrderId = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectingOrderId = 0;
        $this->rejectionReason = '';
    }

    public function confirmReject(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5',
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
            'rejectionReason.min' => 'Reason must be at least 5 characters.',
        ]);

        try {
            $order = CustomerOrder::findOrFail($this->rejectingOrderId);

            if (! $order->status->canReject()) {
                $this->toast()->error('This order cannot be rejected in its current state.')->send();

                return;
            }

            app(CustomerOrderService::class)->rejectOrder($order, auth()->user(), $this->rejectionReason);

            $this->closeRejectModal();
            $this->closeDetail();
            $this->toast()->success("Order {$order->order_number} rejected.")->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Failed to reject order: ' . $e->getMessage())->send();
        }
    }

    public function getOrderLink(): string
    {
        $branchCode = session('selected_branch_id')
            ? Branch::find(session('selected_branch_id'))?->code
            : null;

        return $branchCode
            ? route('public.customer-order', ['branchCode' => $branchCode])
            : '#';
    }

    public function render()
    {
        $branchId = session('selected_branch_id');

        $query = CustomerOrder::with(['items'])
            ->where('branch_id', $branchId);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_email', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%');
            });
        }

        $orders = $query->latest()->paginate(15);

        $statusCounts = CustomerOrder::where('branch_id', $branchId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.branch-dashboard.production.customer-orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'statuses' => CustomerOrderStatus::cases(),
            'orderLink' => $this->getOrderLink(),
        ]);
    }

    protected function getModelClass(): string
    {
        return CustomerOrder::class;
    }

    protected function getAllSelectableIds(): array
    {
        return CustomerOrder::where('branch_id', session('selected_branch_id'))
            ->pluck('id')
            ->toArray();
    }
}
