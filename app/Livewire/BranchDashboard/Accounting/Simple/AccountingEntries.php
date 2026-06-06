<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\AccountingEntry;
use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use App\Models\GlEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class AccountingEntries extends Component
{
    use WithPagination;
    use ExportsCsv;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $entryTypeFilter = null;
    public ?int $debitFilter = null;
    public ?int $creditFilter = null;

    public bool $showForm = false;
    public ?string $entryDate = null;
    public string $entryType = 'expense';
    public ?string $description = null;
    public ?string $amount = null;
    public ?int $debitGlAccountId = null;
    public ?int $creditGlAccountId = null;
    public ?string $source = null;
    public ?string $reference = null;
    public ?string $notes = null;
    public string $entryTypeHelper = '';

    public array $headers = [
        ['index' => 'entry_date', 'label' => 'Date'],
        ['index' => 'entry_type', 'label' => 'Type'],
        ['index' => 'description', 'label' => 'Description'],
        ['index' => 'amount', 'label' => 'Amount'],
        ['index' => 'debit', 'label' => 'Debit'],
        ['index' => 'credit', 'label' => 'Credit'],
    ];

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->entryDate = $today;
        $this->b_id = $this->b_id ?: request()->query('b_id');
        $this->updateEntryTypeHelper();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQuantity(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedEntryTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDebitFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCreditFilter(): void
    {
        $this->resetPage();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function updatedEntryType(): void
    {
        $this->updateEntryTypeHelper();
    }

    protected function rules(): array
    {
        return [
            'entryDate' => ['required', 'date'],
            'entryType' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'debitGlAccountId' => ['required', 'exists:gl_accounts,id'],
            'creditGlAccountId' => ['required', 'exists:gl_accounts,id'],
            'source' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($validated['debitGlAccountId'] === $validated['creditGlAccountId']) {
            throw ValidationException::withMessages([
                'creditGlAccountId' => 'Debit and credit accounts must be different.',
            ]);
        }

        $branchId = $this->b_id ?: request()->query('b_id') ?: current_branch_id();

        if (! $branchId) {
            session()->flash('error', 'Branch context is required.');
            return;
        }

        $period = AccountingPeriod::current()->first();
        if (! $period) {
            session()->flash('error', 'No open accounting period.');
            return;
        }

        DB::beginTransaction();
        try {
            $entry = AccountingEntry::create([
                'branch_id' => $branchId,
                'accounting_period_id' => $period->id,
                'entry_type' => $validated['entryType'],
                'entry_date' => $validated['entryDate'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
                'amount' => $validated['amount'],
                'debit_gl_account_id' => $validated['debitGlAccountId'],
                'credit_gl_account_id' => $validated['creditGlAccountId'],
                'source' => $validated['source'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by_id' => auth()->id(),
            ]);

            GlEntry::create([
                'gl_account_id' => $validated['debitGlAccountId'],
                'accounting_period_id' => $period->id,
                'entry_type' => $validated['entryType'],
                'reference_type' => AccountingEntry::class,
                'reference_id' => $entry->id,
                'reference_number' => $validated['reference'] ?? null,
                'description' => $validated['description'],
                'debit' => $validated['amount'],
                'credit' => 0,
                'entry_date' => $validated['entryDate'],
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'branch_id' => $branchId,
            ])->post(auth()->id());

            GlEntry::create([
                'gl_account_id' => $validated['creditGlAccountId'],
                'accounting_period_id' => $period->id,
                'entry_type' => $validated['entryType'],
                'reference_type' => AccountingEntry::class,
                'reference_id' => $entry->id,
                'reference_number' => $validated['reference'] ?? null,
                'description' => $validated['description'],
                'debit' => 0,
                'credit' => $validated['amount'],
                'entry_date' => $validated['entryDate'],
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'branch_id' => $branchId,
            ])->post(auth()->id());

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save: ' . $e->getMessage());
            return;
        }

        $this->reset(['description', 'amount', 'debitGlAccountId', 'creditGlAccountId', 'source', 'reference', 'notes']);
        $this->showForm = false;
        $this->resetPage();
        session()->flash('message', 'Entry saved.');
    }

    public function exportToCsv()
    {
        $branchId = $this->b_id ?: request()->query('b_id') ?: current_branch_id();

        $rows = AccountingEntry::query()
            ->with(['debitGlAccount', 'creditGlAccount'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('source', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%");
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $this->dateTo))
            ->when($this->entryTypeFilter, fn ($q) => $q->where('entry_type', $this->entryTypeFilter))
            ->when($this->debitFilter, fn ($q) => $q->where('debit_gl_account_id', $this->debitFilter))
            ->when($this->creditFilter, fn ($q) => $q->where('credit_gl_account_id', $this->creditFilter))
            ->orderBy('entry_date', 'desc')
            ->get()
            ->map(fn ($e) => [
                $e->entry_date ? \Illuminate\Support\Carbon::parse($e->entry_date)->format('Y-m-d') : '',
                $e->entry_type,
                $e->description,
                number_format((float) $e->amount, 2, '.', ''),
                $e->debitGlAccount?->account_name ?? '',
                $e->creditGlAccount?->account_name ?? '',
            ]);

        return $this->streamCsv(
            $this->csvFilename('accounting_entries'),
            ['Date', 'Type', 'Description', 'Amount', 'Debit Account', 'Credit Account'],
            $rows
        );
    }

    public function render()
    {
        $branchId = $this->b_id ?: request()->query('b_id') ?: current_branch_id();

        $rows = AccountingEntry::query()
            ->with(['debitGlAccount', 'creditGlAccount'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('source', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%");
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $this->dateTo))
            ->when($this->entryTypeFilter, fn ($q) => $q->where('entry_type', $this->entryTypeFilter))
            ->when($this->debitFilter, fn ($q) => $q->where('debit_gl_account_id', $this->debitFilter))
            ->when($this->creditFilter, fn ($q) => $q->where('credit_gl_account_id', $this->creditFilter))
            ->orderBy('entry_date', 'desc')
            ->paginate((int) ($this->quantity ?? 20));

        $accounts = GlAccount::where('is_active', true)
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'account_name', 'account_type']);

        return view('livewire.branch-dashboard.accounting.simple.accounting-entries', [
            'rows' => $rows,
            'headers' => $this->headers,
            'accounts' => $accounts,
            'entryTypes' => $this->entryTypes(),
        ]);
    }

    private function entryTypes(): array
    {
        return [
            'expense' => 'Expense',
            'income' => 'Income',
            'transfer' => 'Transfer',
            'adjustment' => 'Adjustment',
            'journal' => 'Journal',
        ];
    }

    private function updateEntryTypeHelper(): void
    {
        $helpers = [
            'expense' => 'Expense: debit Expense (e.g. 6000), credit Bank/Cash (e.g. 1050).',
            'income' => 'Income: debit Bank/Cash (e.g. 1050), credit Income (e.g. 4000).',
            'transfer' => 'Transfer: debit Receiving Bank, credit Sending Bank.',
            'adjustment' => 'Adjustment: debit and credit any accounts as needed.',
            'journal' => 'Journal: general two-line entry.',
        ];

        $this->entryTypeHelper = $helpers[$this->entryType] ?? '';
    }
}
