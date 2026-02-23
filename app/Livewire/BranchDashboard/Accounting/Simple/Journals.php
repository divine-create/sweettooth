<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Journals extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    private function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public array $headers = [
        ['index' => 'entry_date', 'label' => 'Date'],
        ['index' => 'account', 'label' => 'Account'],
        ['index' => 'debit', 'label' => 'Debit'],
        ['index' => 'credit', 'label' => 'Credit'],
        ['index' => 'reference', 'label' => 'Reference'],
        ['index' => 'status', 'label' => 'Status'],
    ];

    public string $reference = '';
    public ?int $periodId = null;
    public string $description = '';
    public string $entryDate = '';
    public string $status = 'posted';
    public bool $postNow = true;
    public array $lines = [
        ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
        ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
    ];
    public int $perPage = 25;

    public ?float $quickExpenseAmount = null;
    public ?int $quickExpenseAccountId = null;
    public ?int $quickPaymentAccountId = null;
    public string $quickExpenseDescription = '';
    public string $quickExpenseDate = '';
    public bool $quickExpensePostNow = true;

    public function mount()
    {
        $this->entryDate = now()->format('Y-m-d');
        $this->periodId = AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first()?->id;
        $this->quickExpenseDate = $this->entryDate;
    }

    public function render()
    {
        $branchId = $this->getBranchId();
        $query = GlEntry::query();

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        $entries = $query
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('entry_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.branch-dashboard.accounting.simple.journals', [
            'rows' => $entries,
            'headers' => $this->headers,
            'status' => $this->status,
            'periods' => AccountingPeriod::where('status', 'open')->orderBy('period_end', 'desc')->get(),
            'accounts' => GlAccount::where('is_active', true)->orderBy('account_number')->get(['id', 'account_number', 'account_name']),
        ]);
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 2) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function submit(): void
    {
        $branchId = $this->getBranchId();
        $referenceColumn = $this->referenceColumn();

        $this->validate([
            'periodId' => 'required|exists:accounting_periods,id',
            'description' => 'required|string|max:500',
            'entryDate' => 'required|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:gl_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        foreach ($this->lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.$index.debit" => 'Use only debit or credit per line.',
                ]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.$index.debit" => 'Each line must have a debit or credit.',
                ]);
            }
        }

        $totalDebits = array_sum(array_map(fn ($line) => (float) ($line['debit'] ?? 0), $this->lines));
        $totalCredits = array_sum(array_map(fn ($line) => (float) ($line['credit'] ?? 0), $this->lines));

        if (abs($totalDebits - $totalCredits) >= 0.01) {
            throw ValidationException::withMessages(['lines' => 'Journal entry must be balanced.']);
        }

        if (trim($this->reference) === '') {
            $this->reference = $this->generateReference();
        }

        foreach ($this->lines as $line) {
            $entry = GlEntry::create([
                'accounting_period_id' => $this->periodId,
                'gl_account_id' => $line['account_id'],
                'entry_type' => 'manual',
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
                'description' => $line['description'] ?: $this->description,
                'entry_date' => $this->entryDate,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'entered_by_type' => auth()->user()?->getMorphClass() ?? \App\Models\User::class,
                'branch_id' => $branchId ?? current_branch_id(),
                $referenceColumn => $this->reference,
            ]);

            if ($this->postNow) {
                $entry->post(auth()->id() ?? 1);
            }
        }

        session()->flash('message', 'Journal entry created.');

        $this->reference = '';
        $this->description = '';
        $this->lines = [
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
        ];
    }

    public function submitQuickExpense(): void
    {
        $branchId = $this->getBranchId();
        $referenceColumn = $this->referenceColumn();

        $this->validate([
            'periodId' => 'required|exists:accounting_periods,id',
            'quickExpenseAmount' => 'required|numeric|min:0.01',
            'quickExpenseAccountId' => 'required|exists:gl_accounts,id',
            'quickPaymentAccountId' => 'required|exists:gl_accounts,id',
            'quickExpenseDescription' => 'required|string|max:500',
            'quickExpenseDate' => 'required|date',
        ]);

        if ($this->quickExpenseAccountId === $this->quickPaymentAccountId) {
            throw ValidationException::withMessages([
                'quickPaymentAccountId' => 'Expense and payment accounts must be different.',
            ]);
        }

        $amount = (float) $this->quickExpenseAmount;

        if (trim($this->reference) === '') {
            $this->reference = $this->generateReference();
        }

        $entries = [
            [
                'gl_account_id' => $this->quickExpenseAccountId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'gl_account_id' => $this->quickPaymentAccountId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ];

        foreach ($entries as $entryData) {
            $entry = GlEntry::create([
                'accounting_period_id' => $this->periodId,
                'gl_account_id' => $entryData['gl_account_id'],
                'entry_type' => 'manual',
                'debit' => $entryData['debit'],
                'credit' => $entryData['credit'],
                'description' => $this->quickExpenseDescription,
                'entry_date' => $this->quickExpenseDate,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'entered_by_type' => auth()->user()?->getMorphClass() ?? \App\Models\User::class,
                'branch_id' => $branchId ?? current_branch_id(),
                $referenceColumn => $this->reference,
            ]);

            if ($this->quickExpensePostNow) {
                $entry->post(auth()->id() ?? 1);
            }
        }

        session()->flash('message', 'Quick expense recorded.');

        $this->quickExpenseAmount = null;
        $this->quickExpenseAccountId = null;
        $this->quickPaymentAccountId = null;
        $this->quickExpenseDescription = '';
        $this->quickExpenseDate = now()->format('Y-m-d');
    }

    private function generateReference(): string
    {
        $referenceColumn = $this->referenceColumn();
        $prefix = 'JE-' . now()->format('Ym') . '-';
        $lastReference = GlEntry::withTrashed()
            ->where($referenceColumn, 'like', $prefix . '%')
            ->orderByDesc($referenceColumn)
            ->value($referenceColumn);

        $nextSequence = 1;
        if (is_string($lastReference) && preg_match('/-(\d{4})$/', $lastReference, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        $candidate = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        return $candidate;
    }

    private function referenceColumn(): string
    {
        if (Schema::hasColumn('gl_entries', 'reference_number')) {
            return 'reference_number';
        }

        return 'reference';
    }
}
