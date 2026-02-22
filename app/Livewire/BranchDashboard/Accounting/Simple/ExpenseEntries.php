<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\BankAccount;
use App\Models\ExpenseEntry;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class ExpenseEntries extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $bankAccountId = null;
    public ?int $glAccountId = null;

    public bool $showForm = false;
    public ?string $entryDate = null;
    public ?string $description = null;
    public ?string $source = null;
    public ?string $reference = null;
    public ?string $amount = null;
    public ?string $notes = null;

    public array $headers = [
        ['index' => 'entry_date', 'label' => 'Date'],
        ['index' => 'particulars', 'label' => 'Particulars'],
        ['index' => 'source', 'label' => 'Source'],
        ['index' => 'amount', 'label' => 'Amount'],
        ['index' => 'category', 'label' => 'Category'],
        ['index' => 'bank', 'label' => 'Bank'],
    ];

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->entryDate = $today;
        $this->b_id = $this->b_id ?: request()->query('b_id');
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

    public function updatedBankAccountId(): void
    {
        $this->resetPage();
    }

    public function updatedGlAccountId(): void
    {
        $this->resetPage();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    protected function rules(): array
    {
        return [
            'entryDate' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'glAccountId' => ['required', 'exists:gl_accounts,id'],
            'bankAccountId' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        $branchId = $this->b_id ?: request()->query('b_id');

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
            $expense = ExpenseEntry::create([
                'branch_id' => $branchId,
                'bank_account_id' => $validated['bankAccountId'] ?? null,
                'gl_account_id' => $validated['glAccountId'],
                'entry_date' => $validated['entryDate'],
                'description' => $validated['description'],
                'source' => $validated['source'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'created_by_id' => auth()->id(),
            ]);

            $bankAccount = $validated['bankAccountId'] ? BankAccount::find($validated['bankAccountId']) : null;
            $bankGl = $bankAccount?->glAccount
                ?? GlAccount::where('account_number', '1050')->first();

            if (! $bankGl) {
                throw new \RuntimeException('Bank GL account not found.');
            }

            GlEntry::create([
                'gl_account_id' => $validated['glAccountId'],
                'accounting_period_id' => $period->id,
                'entry_type' => 'expense',
                'reference_type' => ExpenseEntry::class,
                'reference_id' => $expense->id,
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
                'gl_account_id' => $bankGl->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'expense',
                'reference_type' => ExpenseEntry::class,
                'reference_id' => $expense->id,
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

        $this->reset(['description', 'source', 'reference', 'amount', 'notes', 'glAccountId', 'bankAccountId']);
        $this->showForm = false;
        session()->flash('message', 'Expense saved.');
    }

    public function render()
    {
        $branchId = $this->b_id ?: request()->query('b_id');

        $rows = ExpenseEntry::query()
            ->with(['glAccount', 'bankAccount'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('source', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%");
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $this->dateTo))
            ->when($this->bankAccountId, fn ($q) => $q->where('bank_account_id', $this->bankAccountId))
            ->when($this->glAccountId, fn ($q) => $q->where('gl_account_id', $this->glAccountId))
            ->orderBy('entry_date', 'desc')
            ->paginate($this->quantity ?? 20);

        $bankAccounts = BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']);
        $glAccounts = GlAccount::where('account_type', 'expense')
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'account_name']);

        return view('livewire.branch-dashboard.accounting.simple.expense-entries', [
            'rows' => $rows,
            'headers' => $this->headers,
            'bankAccounts' => $bankAccounts,
            'glAccounts' => $glAccounts,
        ]);
    }
}
