<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\GlPostingService;
use App\Models\AccountingPostingFailure;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Transactions extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    private function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public array $headers = [
        ['index' => 'id', 'label' => 'ID'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'amount', 'label' => 'Amount'],
        ['index' => 'date', 'label' => 'Date'],
        ['index' => 'action', 'label' => 'Action', 'display' => true],
    ];

    public string $transactionType = 'sales';
    public string $status = 'all';
    public int $perPage = 25;
    public ?int $quantity = 25;
    public ?string $search = null;
    public bool $showDetail = false;
    public array $detail = [];

    public function render()
    {
        $transactions = $this->getTransactions();
        $transactions->getCollection()->transform(function ($row) {
            $row->display_amount = $this->resolveAmount($row);
            $row->display_date = $this->resolveDate($row);
            $row->display_status = $row->gl_posting_status ?? '-';

            return $row;
        });

        return view('livewire.branch-dashboard.accounting.simple.transactions', [
            'rows' => $transactions,
            'headers' => $this->headers,
            'transactionType' => $this->transactionType,
            'status' => $this->status,
        ]);
    }

    private function getTransactions()
    {
        $branchId = $this->getBranchId();
        return match ($this->transactionType) {
            'sales' => $this->applyStatusFilter(
                Sale::query()->when($this->search, function ($query) {
                    $query->where('id', 'like', '%' . $this->search . '%')
                        ->orWhere('sale_number', 'like', '%' . $this->search . '%');
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            )->orderBy('sale_time', 'desc')->paginate((int) ($this->quantity ?? 25)),
            'purchases' => $this->applyStatusFilter(
                Purchase::query()->when($this->search, function ($query) {
                    $query->where('id', 'like', '%' . $this->search . '%')
                        ->orWhere('purchase_number', 'like', '%' . $this->search . '%')
                        ->orWhere('supplier_name', 'like', '%' . $this->search . '%');
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            )->orderBy('purchase_date', 'desc')->paginate((int) ($this->quantity ?? 25)),
            'payments' => $this->applyStatusFilter(
                Payment::query()->when($this->search, function ($query) {
                    $query->where('id', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_number', 'like', '%' . $this->search . '%');
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            )->orderBy('payment_time', 'desc')->paginate((int) ($this->quantity ?? 25)),
            'adjustments' => $this->applyStatusFilter(
                $this->adjustmentQuery()->when($this->search, function ($query) {
                    $query->where('id', 'like', '%' . $this->search . '%');
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            )->orderBy('movement_date', 'desc')->paginate((int) ($this->quantity ?? 25)),
            default => $this->applyStatusFilter(
                Sale::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            )->orderBy('sale_time', 'desc')->paginate((int) ($this->quantity ?? 25)),
        };
    }

    private function applyStatusFilter($query)
    {
        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query;
    }

    private function adjustmentQuery()
    {
        return StockMovement::query()
            ->where(function ($query) {
                $query
                    ->where('type', 'damaged')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'adjustment')
                            ->whereNotNull('adjustment_reason');
                    });
            });
    }

    public function changeTransactionType(string $type)
    {
        $this->transactionType = $type;
        $this->resetPage();
    }

    public function changeStatus(string $status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function updatedQuantity(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function viewTransaction(int $id): void
    {
        $model = match ($this->transactionType) {
            'sales' => Sale::with('glEntry')->find($id),
            'purchases' => Purchase::with('glEntry')->find($id),
            'payments' => Payment::with('glEntry')->find($id),
            'adjustments' => StockMovement::with('glEntry')->find($id),
            default => null,
        };

        if (! $model) {
            session()->flash('error', 'Transaction not found.');
            return;
        }

        $this->detail = [
            'id' => $model->id,
            'type' => $this->transactionType,
            'status' => $model->gl_posting_status ?? '-',
            'amount' => $this->resolveAmount($model),
            'date' => $this->resolveDate($model),
            'reference' => $model->reference_number
                ?? $model->purchase_number
                ?? $model->sale_number
                ?? null,
            'gl_entry_id' => $model->gl_entry_id,
            'gl_posting_error' => $model->gl_posting_error,
        ];
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detail = [];
    }

    private function resolveAmount($row): float
    {
        return match ($this->transactionType) {
            'sales' => (float) ($row->total ?? $row->subtotal ?? 0),
            'purchases' => (float) ($row->landing_cost ?? (($row->total_fob_ngn ?? 0) + ($row->other_costs ?? 0))),
            'payments' => (float) ($row->amount ?? 0),
            'adjustments' => (float) ($row->quantity ?? 0),
            default => 0,
        };
    }

    private function resolveDate($row): ?string
    {
        return match ($this->transactionType) {
            'sales' => optional($row->sale_time)->format('Y-m-d H:i'),
            'purchases' => optional($row->purchase_date)->format('Y-m-d'),
            'payments' => optional($row->payment_time)->format('Y-m-d H:i'),
            'adjustments' => optional($row->movement_date)->format('Y-m-d H:i'),
            default => null,
        };
    }

    public function retryFailed(string $transactionType, int $transactionId)
    {
        $postingService = app(GlPostingService::class);

        $model = match ($transactionType) {
            'sales' => Sale::find($transactionId),
            'purchases' => Purchase::find($transactionId),
            'payments' => Payment::find($transactionId),
            'adjustments' => StockMovement::find($transactionId),
            default => null,
        };

        if (! $model || $model->gl_posting_status !== 'failed') {
            session()->flash('error', 'Transaction not found or not in failed state');
            return;
        }

        $model->update(['gl_posting_status' => 'pending', 'gl_posting_error' => null]);

        try {
            match ($transactionType) {
                'sales' => $postingService->postSaleTransaction($model),
                'purchases' => $postingService->postPurchaseTransaction($model),
                'payments' => $postingService->postPaymentTransaction($model),
                'adjustments' => $postingService->postInventoryAdjustment($model),
                default => null,
            };

            $model->update(['gl_posting_status' => 'posted', 'gl_posted_at' => now()]);
            session()->flash('message', 'Posting retry completed successfully.');
        } catch (\Throwable $e) {
            AccountingPostingFailure::create([
                'reference_type' => get_class($model),
                'reference_id' => $model->id,
                'entry_type' => $transactionType,
                'error_message' => $e->getMessage(),
                'context' => ['source' => 'simple_transactions'],
                'last_seen_at' => now(),
            ]);
            $model->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Posting retry failed: ' . $e->getMessage());
        }
    }
}
