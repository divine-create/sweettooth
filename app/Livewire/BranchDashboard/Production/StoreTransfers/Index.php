<?php

namespace App\Livewire\BranchDashboard\Production\StoreTransfers;

use App\Models\Department;
use App\Models\ProductionStore;
use App\Models\ProductionStoreTransfer;
use App\Services\ProductionStoreService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    /** incoming | sent | all */
    public string $tab = 'incoming';

    public int $perPage = 15;

    public ?Department $department = null;

    // --- Confirm receipt modal ---
    public bool $showConfirmModal = false;

    public ?int $confirmTransferId = null;

    public string $confirmItemName = '';

    public string $confirmUom = '';

    public float $confirmSent = 0.0;

    public $confirmReceivedQuantity = null;

    public function mount($deptSlug = null)
    {
        $this->deptSlug = $deptSlug ?? $this->deptSlug;
        $this->b_id = request()->query('b_id', $this->b_id);

        $this->department = Department::where('slug', $this->deptSlug)->first();

        if (! $this->department) {
            abort(404, 'Department not found');
        }
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id');
    }

    protected function getStore(): ?ProductionStore
    {
        return ProductionStore::forBranch($this->getBranchId())
            ->forDepartment($this->department->id)
            ->active()
            ->first();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['incoming', 'sent', 'all'], true) ? $tab : 'incoming';
        $this->resetPage();
    }

    protected function rowsQuery(?ProductionStore $store)
    {
        if (! $store) {
            return ProductionStoreTransfer::whereRaw('1 = 0');
        }

        $query = ProductionStoreTransfer::with(['item', 'fromDepartment', 'toDepartment', 'sentBy', 'receivedBy'])
            ->where('branch_id', $this->getBranchId());

        return match ($this->tab) {
            'sent' => $query->where('from_store_id', $store->id),
            'all' => $query->where(fn ($q) => $q->where('to_store_id', $store->id)->orWhere('from_store_id', $store->id)),
            default => $query->where('to_store_id', $store->id),
        };
    }

    public function openConfirmModal(int $transferId): void
    {
        $store = $this->getStore();
        $transfer = ProductionStoreTransfer::with('item')->find($transferId);

        if (! $transfer || ! $store || $transfer->to_store_id !== $store->id) {
            $this->toast()->error('Transfer not found for this store.')->send();

            return;
        }

        if ($transfer->status !== 'pending_receipt') {
            $this->toast()->warning('This transfer is no longer pending receipt.')->send();

            return;
        }

        $this->confirmTransferId = $transfer->id;
        $this->confirmItemName = $transfer->item?->name ?? 'Unknown item';
        $this->confirmUom = $transfer->uom ?? '';
        $this->confirmSent = (float) $transfer->quantity;
        $this->confirmReceivedQuantity = (float) $transfer->quantity;
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->confirmTransferId = null;
        $this->confirmItemName = '';
        $this->confirmUom = '';
        $this->confirmSent = 0.0;
        $this->confirmReceivedQuantity = null;
    }

    public function confirmReceipt(): void
    {
        $this->validate([
            'confirmReceivedQuantity' => 'required|numeric|min:0|max:'.$this->confirmSent,
        ], [], ['confirmReceivedQuantity' => 'received quantity']);

        $store = $this->getStore();
        $transfer = ProductionStoreTransfer::find($this->confirmTransferId);

        if (! $transfer || ! $store || $transfer->to_store_id !== $store->id) {
            $this->toast()->error('Transfer not found for this store.')->send();

            return;
        }

        try {
            app(ProductionStoreService::class)->confirmStoreTransfer(
                $transfer,
                (float) $this->confirmReceivedQuantity,
                current_actor()
            );
        } catch (\InvalidArgumentException $e) {
            $this->toast()->error($e->getMessage())->send();

            return;
        }

        $this->toast()->success('Transfer received and added to your store.')->send();
        $this->closeConfirmModal();
    }

    /** Receiving department rejects an incoming pending transfer (returns to source). */
    public function rejectTransfer(int $transferId): void
    {
        $store = $this->getStore();
        $transfer = ProductionStoreTransfer::find($transferId);

        if (! $transfer || ! $store || $transfer->to_store_id !== $store->id) {
            $this->toast()->error('Transfer not found for this store.')->send();

            return;
        }

        $this->cancel($transfer, 'Transfer rejected — returned to sender.');
    }

    /** Sender cancels their own outgoing pending transfer (returns to source). */
    public function cancelTransfer(int $transferId): void
    {
        $store = $this->getStore();
        $transfer = ProductionStoreTransfer::find($transferId);

        if (! $transfer || ! $store || $transfer->from_store_id !== $store->id) {
            $this->toast()->error('Transfer not found for this store.')->send();

            return;
        }

        $this->cancel($transfer, 'Transfer cancelled — stock returned.');
    }

    protected function cancel(ProductionStoreTransfer $transfer, string $message): void
    {
        try {
            app(ProductionStoreService::class)->cancelStoreTransfer($transfer, current_actor());
        } catch (\InvalidArgumentException $e) {
            $this->toast()->error($e->getMessage())->send();

            return;
        }

        $this->toast()->success($message)->send();
    }

    public function render()
    {
        $store = $this->getStore();

        $rows = $this->rowsQuery($store)
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $counts = ['incoming' => 0, 'sent' => 0];
        if ($store) {
            $counts['incoming'] = ProductionStoreTransfer::where('branch_id', $this->getBranchId())
                ->where('to_store_id', $store->id)
                ->where('status', 'pending_receipt')
                ->count();
            $counts['sent'] = ProductionStoreTransfer::where('branch_id', $this->getBranchId())
                ->where('from_store_id', $store->id)
                ->where('status', 'pending_receipt')
                ->count();
        }

        return view('livewire.branch-dashboard.production.store-transfers.index', [
            'store' => $store,
            'rows' => $rows,
            'counts' => $counts,
        ]);
    }
}
