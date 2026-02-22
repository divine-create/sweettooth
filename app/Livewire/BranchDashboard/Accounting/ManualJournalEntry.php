<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Helpers\Settings;
use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Services\CurrencyFormattingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class ManualJournalEntry extends Component
{
    public string $reference = '';

    public ?int $periodId = null;

    public string $description = '';

    public string $entryDate = '';

    public string $status = 'draft';

    public array $lines = [
        ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
        ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->entryDate = now()->format('Y-m-d');
        $this->reference = $this->generateReference();
        $this->description = '';
        $this->status = 'draft';
        $this->lines = [
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
        ];
        $this->periodId = AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first()?->id;
    }

    public function addLine()
    {
        $this->lines[] = ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''];
    }

    public function removeLine($index)
    {
        if (count($this->lines) > 2) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    #[Computed]
    public function periods()
    {
        return AccountingPeriod::where('status', 'open')
            ->orderBy('period_end', 'desc')
            ->get();
    }

    #[Computed]
    public function glAccounts()
    {
        return GlAccount::where('is_active', true)
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'account_name', 'account_type']);
    }

    #[Computed]
    public function totalDebits()
    {
        return array_sum(array_map(
            fn ($line) => (float) ($line['debit'] ?? 0),
            $this->lines
        ));
    }

    #[Computed]
    public function totalCredits()
    {
        return array_sum(array_map(
            fn ($line) => (float) ($line['credit'] ?? 0),
            $this->lines
        ));
    }

    #[Computed]
    public function imbalanceAmount()
    {
        return abs($this->totalDebits - $this->totalCredits);
    }

    #[Computed]
    public function isBalanced()
    {
        return abs($this->totalDebits - $this->totalCredits) < 0.01;
    }

    #[Computed]
    public function canSubmit()
    {
        return $this->isBalanced && $this->totalDebits > 0 && $this->totalCredits > 0;
    }

    public function submit()
    {
        if (trim($this->reference) === '') {
            $this->reference = $this->generateReference();
        }

        $referenceColumn = $this->referenceColumn();

        $this->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('gl_entries', $referenceColumn)],
            'periodId' => 'required|exists:accounting_periods,id',
            'description' => 'required|string|max:500',
            'entryDate' => 'required|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:gl_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'status' => 'in:draft,posted',
        ]);

        foreach ($this->lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.$index.debit" => 'Enter amount on only one side: debit or credit.',
                ]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.$index.debit" => 'Each line must have a debit or credit amount.',
                ]);
            }
        }

        if ($this->totalDebits <= 0 || $this->totalCredits <= 0) {
            throw ValidationException::withMessages(['lines' => 'Add at least one debit and one credit amount.']);
        }

        if (! $this->isBalanced) {
            throw ValidationException::withMessages(['lines' => 'Journal entry must be balanced (Debits = Credits)']);
        }

        try {
            $period = AccountingPeriod::findOrFail($this->periodId);

            foreach ($this->lines as $line) {
                if ($line['account_id']) {
                    $payload = [
                        'accounting_period_id' => $period->id,
                        'gl_account_id' => $line['account_id'],
                        'entry_type' => 'manual',
                        'debit' => (float) ($line['debit'] ?? 0),
                        'credit' => (float) ($line['credit'] ?? 0),
                        'description' => $line['description'] ?: $this->description,
                        'entry_date' => $this->entryDate,
                        'status' => $this->status,
                        'entered_by_id' => auth()->id(),
                        'entered_by_type' => auth()->user()?->getMorphClass() ?? \App\Models\User::class,
                        'branch_id' => current_branch_id(),
                    ];

                    $payload[$referenceColumn] = $this->reference;

                    GlEntry::create($payload);
                }
            }

            session()->flash('success', 'Journal entry created successfully');
            $this->resetForm();
            $this->redirect(route('branch-dashboard.accounting.journal-entry'));
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['general' => $e->getMessage()]);
        }
    }

    private function generateReference(): string
    {
        $referenceColumn = $this->referenceColumn();
        $prefix = 'JE-' . now()->format('Ym') . '-';

        $columnsToTry = array_values(array_unique([
            $referenceColumn,
            'reference_number',
            'reference',
        ]));

        $activeReferenceColumn = $referenceColumn;
        $lastReference = null;

        foreach ($columnsToTry as $column) {
            try {
                $lastReference = GlEntry::withTrashed()
                    ->where($column, 'like', $prefix . '%')
                    ->orderByDesc($column)
                    ->value($column);
                $activeReferenceColumn = $column;
                break;
            } catch (QueryException $e) {
                if ($this->isUnknownColumnError($e)) {
                    continue;
                }

                throw $e;
            }
        }

        $nextSequence = 1;
        if (is_string($lastReference) && preg_match('/-(\d{4})$/', $lastReference, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $nextSequence++;
        } while (GlEntry::withTrashed()->where($activeReferenceColumn, $candidate)->exists());

        return $candidate;
    }

    private function referenceColumn(): string
    {
        try {
            if (Schema::hasColumn('gl_entries', 'reference_number')) {
                return 'reference_number';
            }

            if (Schema::hasColumn('gl_entries', 'reference')) {
                return 'reference';
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return 'reference_number';
    }

    private function isUnknownColumnError(QueryException $e): bool
    {
        return str_contains(strtolower($e->getMessage()), 'unknown column');
    }

    /**
     * Format currency value for journal entry display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    /**
     * Get currency symbol for GL entries
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService;
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');

        return $service->getSymbol($currency);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.manual-journal-entry');
    }
}
