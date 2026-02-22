<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\Payment;
use App\Models\PosRemittance;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class PosRemittances extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 10;
    public ?string $search = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $departmentId = null;

    public bool $showForm = false;
    public ?string $remittanceAmount = null;
    public ?string $remittedAt = null;
    public ?int $remittanceBankAccountId = null;
    public ?string $remittanceReference = null;
    public ?string $remittanceNotes = null;

    public array $headers = [
        ['index' => 'remitted_at', 'label' => 'Date'],
        ['index' => 'department', 'label' => 'Department'],
        ['index' => 'bank', 'label' => 'Bank'],
        ['index' => 'amount', 'label' => 'Amount'],
        ['index' => 'reference', 'label' => 'Reference'],
    ];

    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->remittedAt = Carbon::now()->format('Y-m-d\TH:i');
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

    public function updatedDepartmentId(): void
    {
        $this->resetPage();
        $this->syncDepartmentBank();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    protected function remittanceRules(): array
    {
        return [
            'departmentId' => ['required', 'exists:departments,id'],
            'remittanceAmount' => ['required', 'numeric', 'min:0.01'],
            'remittedAt' => ['required', 'date'],
            'remittanceReference' => ['nullable', 'string', 'max:255'],
            'remittanceNotes' => ['nullable', 'string'],
        ];
    }

    public function saveRemittance(): void
    {
        $validated = $this->validate($this->remittanceRules());
        $period = AccountingPeriod::current()->first();

        if (! $period) {
            session()->flash('error', 'No open accounting period found.');
            return;
        }

        $branchId = $this->b_id ?: request()->query('b_id');
        if (! $branchId) {
            session()->flash('error', 'Branch context is required.');
            return;
        }

        DB::beginTransaction();
        try {
            $department = Department::findOrFail($validated['departmentId']);
            if (! $department->bank_account_id) {
                session()->flash('error', 'Selected department has no bank account linked.');
                return;
            }

            $bankAccountId = $department->bank_account_id;

            $remittance = PosRemittance::create([
                'branch_id' => $branchId,
                'department_id' => $validated['departmentId'],
                'bank_account_id' => $bankAccountId,
                'amount' => $validated['remittanceAmount'],
                'remitted_at' => $validated['remittedAt'],
                'reference' => $validated['remittanceReference'] ?? null,
                'notes' => $validated['remittanceNotes'] ?? null,
                'created_by_id' => auth()->id(),
            ]);

            $bankAccount = BankAccount::find($bankAccountId);
            $bankGl = $bankAccount?->glAccount
                ?? GlAccount::where('account_number', '1050')->firstOrFail();
            $posClearing = GlAccount::where('account_number', '1060')->firstOrFail();

            GlEntry::create([
                'gl_account_id' => $bankGl->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'pos_remittance',
                'reference_type' => PosRemittance::class,
                'reference_id' => $remittance->id,
                'reference_number' => $validated['remittanceReference'] ?? "POS-REM-{$remittance->id}",
                'description' => 'POS Remittance to bank',
                'debit' => $validated['remittanceAmount'],
                'credit' => 0,
                'entry_date' => $validated['remittedAt'],
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'branch_id' => $branchId,
            ])->post(auth()->id());

            GlEntry::create([
                'gl_account_id' => $posClearing->id,
                'accounting_period_id' => $period->id,
                'entry_type' => 'pos_remittance',
                'reference_type' => PosRemittance::class,
                'reference_id' => $remittance->id,
                'reference_number' => $validated['remittanceReference'] ?? "POS-REM-{$remittance->id}",
                'description' => 'POS Clearing settlement',
                'debit' => 0,
                'credit' => $validated['remittanceAmount'],
                'entry_date' => $validated['remittedAt'],
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'branch_id' => $branchId,
            ])->post(auth()->id());

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to record remittance: ' . $e->getMessage());
            return;
        }

        $this->remittanceAmount = null;
        $this->remittedAt = Carbon::now()->format('Y-m-d\TH:i');
        $this->remittanceBankAccountId = null;
        $this->remittanceReference = null;
        $this->remittanceNotes = null;
        $this->showForm = false;

        session()->flash('message', 'POS remittance recorded.');
    }

    #[Computed]
    public function bankAccounts()
    {
        return BankAccount::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'account_number']);
    }

    #[Computed]
    public function departments()
    {
        $branchId = $this->b_id ?: request()->query('b_id');

        return Department::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'bank_account_id']);
    }

    #[Computed]
    public function posCollectedTotal()
    {
        $branchId = $this->b_id ?: request()->query('b_id');
        $query = Payment::query()
            ->where('payment_method', 'pos')
            ->where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->dateFrom) {
            $query->whereDate('payment_time', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('payment_time', '<=', $this->dateTo);
        }

        if ($this->departmentId) {
            $query->whereHas('sale', fn ($q) => $q->where('department_id', $this->departmentId));
        }

        return (float) $query->sum('amount');
    }

    #[Computed]
    public function posRemittedTotal()
    {
        $branchId = $this->b_id ?: request()->query('b_id');
        $query = PosRemittance::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->dateFrom) {
            $query->whereDate('remitted_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('remitted_at', '<=', $this->dateTo);
        }

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        return (float) $query->sum('amount');
    }

    #[Computed]
    public function posOutstandingTotal()
    {
        return max(0, $this->posCollectedTotal - $this->posRemittedTotal);
    }

    protected function getRemittancesQuery()
    {
        $branchId = $this->b_id ?: request()->query('b_id');

        return PosRemittance::query()
            ->with(['bankAccount', 'department'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('remitted_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('remitted_at', '<=', $this->dateTo))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->orderBy('remitted_at', 'desc');
    }

    protected function syncDepartmentBank(): void
    {
        if (! $this->departmentId) {
            $this->remittanceBankAccountId = null;
            return;
        }

        $department = Department::find($this->departmentId);
        $this->remittanceBankAccountId = $department?->bank_account_id;
    }

    public function exportCSV()
    {
        $rows = $this->getRemittancesQuery()->get();

        $csv = "Date,Department,Bank,Amount,Reference,Notes\n";
        foreach ($rows as $row) {
            $notes = str_replace('"', '""', $row->notes ?? '');
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                optional($row->remitted_at)->format('Y-m-d H:i'),
                $row->department?->name ?? '',
                $row->bankAccount?->bank_name ?? '',
                number_format($row->amount, 2, '.', ''),
                $row->reference ?? '',
                $notes
            );
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'pos_remittances_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $rows = $this->getRemittancesQuery()->paginate($this->quantity ?? 10);

        return view('livewire.branch-dashboard.accounting.simple.pos-remittances', [
            'rows' => $rows,
            'headers' => $this->headers,
        ]);
    }
}
