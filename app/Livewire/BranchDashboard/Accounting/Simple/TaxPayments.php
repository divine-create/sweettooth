<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\TaxPayment;
use App\Models\BankAccount;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class TaxPayments extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $tax_type = 'general';
    public float $amount = 0;
    public string $payment_date = '';
    public ?string $bank_account_id = null;
    public ?string $reference_number = null;
    public string $status = 'paid';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->payment_date = Carbon::now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'tax_type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'status' => 'required|in:pending,approved,paid,cancelled',
        ]);

        TaxPayment::create([
            'branch_id' => current_branch_id(),
            'tax_type' => $this->tax_type,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'bank_account_id' => $this->bank_account_id,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset(['tax_type', 'amount', 'bank_account_id', 'reference_number', 'status']);
        $this->tax_type = 'general';
        $this->status = 'paid';
        $this->dispatch('notify', message: 'Tax payment recorded.');
    }

    public function exportToCsv()
    {
        $rows = TaxPayment::with('bankAccount')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($t) => [
                $t->tax_type,
                number_format((float) $t->amount, 2, '.', ''),
                $t->payment_date ? \Illuminate\Support\Carbon::parse($t->payment_date)->format('Y-m-d') : '',
                $t->bankAccount?->bank_name ?? '',
                $t->reference_number ?? '',
                ucfirst((string) $t->status),
            ]);

        return $this->streamCsv(
            $this->csvFilename('tax_payments'),
            ['Tax Type', 'Amount', 'Payment Date', 'Bank', 'Reference', 'Status'],
            $rows
        );
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.tax-payments', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
            'rows' => TaxPayment::orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
