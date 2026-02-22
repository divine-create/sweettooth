<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\BankAccount;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class PurchasePayments extends Component
{
    use WithPagination;

    public ?string $purchase_id = null;
    public float $amount = 0;
    public float $purchase_total = 0;
    public float $purchase_balance = 0;
    public string $payment_date = '';
    public ?string $bank_account_id = null;
    public ?string $reference_number = null;
    public string $status = 'pending';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->payment_date = Carbon::now()->format('Y-m-d');
    }

    public function updatedPurchaseId(): void
    {
        $this->syncPurchaseAmounts();
    }

    public function syncPurchaseAmounts(): void
    {
        $this->purchase_total = 0;
        $this->purchase_balance = 0;
        $this->amount = 0;

        if (! $this->purchase_id) {
            return;
        }

        $purchase = Purchase::with(['purchasePayments', 'purchaseItems'])->find($this->purchase_id);
        if (! $purchase) {
            return;
        }

        $total = (float) ($purchase->landing_cost ?? 0);
        if ($total <= 0) {
            $total = (float) ($purchase->total_cost ?? 0);
        }
        if ($total <= 0) {
            $total = (float) $purchase->calculateTotalCost();
        }
        if ($total <= 0 && $purchase->relationLoaded('purchaseItems')) {
            $total = (float) $purchase->purchaseItems->sum(function ($item) {
                $lineTotal = (float) ($item->total_cost ?? 0);
                if ($lineTotal > 0) {
                    return $lineTotal;
                }
                $lineTotal = (float) ($item->landing_cost ?? 0);
                if ($lineTotal > 0) {
                    return $lineTotal;
                }
                $qty = (float) ($item->quantity ?? 0);
                $unit = (float) ($item->fob_ngn ?? 0);
                $other = (float) ($item->other_costs ?? 0);
                return ($unit * $qty) + $other;
            });
        }

        $paid = (float) $purchase->purchasePayments()
            ->where('status', 'paid')
            ->sum('amount');
        $balance = max(0, $total - $paid);

        $this->purchase_total = $total;
        $this->purchase_balance = $balance;
        $this->amount = $balance;
    }

    public function save(): void
    {
        $this->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'payment_date' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,approved,paid,cancelled',
        ]);

        $purchase = Purchase::findOrFail($this->purchase_id);
        $this->syncPurchaseAmounts();

        if ($this->purchase_balance <= 0) {
            $this->dispatch('notify', message: 'This purchase is already fully settled.');
            return;
        }

        $amount = $this->purchase_balance;

        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'branch_id' => $purchase->branch_id,
            'amount' => $amount,
            'payment_date' => $this->payment_date,
            'bank_account_id' => $this->bank_account_id,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset(['purchase_id', 'amount', 'purchase_total', 'purchase_balance', 'bank_account_id', 'reference_number', 'status']);
        $this->status = 'pending';
        $this->dispatch('notify', message: 'Purchase payment recorded.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.purchase-payments', [
            'purchases' => Purchase::orderByDesc('purchase_date')->limit(200)->get(['id', 'purchase_number', 'supplier_name']),
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
            'rows' => PurchasePayment::orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
