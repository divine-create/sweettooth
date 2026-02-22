<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\AccountingPostingFailure;
use App\Services\GlPostingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class PostingStatusMonitor extends Component
{
    use WithPagination;

    public string $transactionType = 'sales'; // sales, purchases, payments, purchase_payments, adjustments

    public string $status = 'all'; // all, pending, posted, failed

    public int $perPage = 25;

    public bool $isBulkPosting = false;

    public int $bulkPostingProgress = 0;

    public int $bulkPostingTotal = 0;

    public function render()
    {
        $failedTransactions = [];
        $stats = [];

        switch ($this->transactionType) {
            case 'sales':
                $failedTransactions = $this->getSalesData();
                $stats = [
                    'total' => Sale::count(),
                    'posted' => Sale::where('gl_posting_status', 'posted')->count(),
                    'pending' => Sale::where('gl_posting_status', 'pending')->count(),
                    'failed' => Sale::where('gl_posting_status', 'failed')->count(),
                ];
                break;

            case 'purchases':
                $failedTransactions = $this->getPurchasesData();
                $stats = [
                    'total' => Purchase::count(),
                    'posted' => Purchase::where('gl_posting_status', 'posted')->count(),
                    'pending' => Purchase::where('gl_posting_status', 'pending')->count(),
                    'failed' => Purchase::where('gl_posting_status', 'failed')->count(),
                ];
                break;

            case 'payments':
                $failedTransactions = $this->getPaymentsData();
                $stats = [
                    'total' => Payment::count(),
                    'posted' => Payment::where('gl_posting_status', 'posted')->count(),
                    'pending' => Payment::where('gl_posting_status', 'pending')->count(),
                    'failed' => Payment::where('gl_posting_status', 'failed')->count(),
                ];
                break;
            case 'purchase_payments':
                $failedTransactions = $this->getPurchasePaymentsData();
                $stats = [
                    'total' => PurchasePayment::count(),
                    'posted' => PurchasePayment::where('gl_posting_status', 'posted')->count(),
                    'pending' => PurchasePayment::where('gl_posting_status', 'pending')->count(),
                    'failed' => PurchasePayment::where('gl_posting_status', 'failed')->count(),
                ];
                break;

            case 'adjustments':
                $failedTransactions = $this->getStockMovementsData();
                $stats = [
                    'total' => $this->adjustmentQuery()->count(),
                    'posted' => $this->adjustmentQuery()->where('gl_posting_status', 'posted')->count(),
                    'pending' => $this->adjustmentQuery()->where('gl_posting_status', 'pending')->count(),
                    'failed' => $this->adjustmentQuery()->where('gl_posting_status', 'failed')->count(),
                ];
                break;
        }

        return view('livewire.branch-dashboard.accounting.posting-status-monitor', [
            'transactions' => $failedTransactions,
            'stats' => $stats,
            'transactionType' => $this->transactionType,
            'status' => $this->status,
        ]);
    }

    private function getSalesData()
    {
        $query = Sale::query();

        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query
            ->with(['salesShift', 'branch'])
            ->orderBy('sale_time', 'desc')
            ->paginate($this->perPage);
    }

    private function getPurchasesData()
    {
        $query = Purchase::query();

        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query
            ->with(['branch'])
            ->orderBy('purchase_date', 'desc')
            ->paginate($this->perPage);
    }

    private function getPaymentsData()
    {
        $query = Payment::query();

        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query
            ->with(['sale'])
            ->orderBy('payment_time', 'desc')
            ->paginate($this->perPage);
    }

    private function getPurchasePaymentsData()
    {
        $query = PurchasePayment::query();

        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query
            ->with(['purchase', 'bankAccount'])
            ->orderBy('payment_date', 'desc')
            ->paginate($this->perPage);
    }

    private function getStockMovementsData()
    {
        $query = $this->adjustmentQuery();

        if ($this->status !== 'all') {
            $query->where('gl_posting_status', $this->status);
        }

        return $query
            ->with(['stock'])
            ->orderBy('movement_date', 'desc')
            ->paginate($this->perPage);
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

    public function retryFailed(string $transactionType, int $transactionId)
    {
        $postingService = app(GlPostingService::class);

        $model = match ($transactionType) {
            'sales' => Sale::find($transactionId),
            'purchases' => Purchase::find($transactionId),
            'payments' => Payment::find($transactionId),
            'purchase_payments' => PurchasePayment::find($transactionId),
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
                'purchase_payments' => $postingService->postPurchasePayment($model),
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
                'context' => ['source' => 'posting_status_monitor'],
                'last_seen_at' => now(),
            ]);
            $model->update([
                'gl_posting_status' => 'failed',
                'gl_posting_error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Posting retry failed: ' . $e->getMessage());
        }
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
}
