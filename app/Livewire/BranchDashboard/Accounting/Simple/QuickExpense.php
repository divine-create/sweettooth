<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use App\Models\GlEntry;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class QuickExpense extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $periodId = null;
    public ?float $amount = null;
    public ?int $expenseAccountId = null;
    public ?int $paymentAccountId = null;
    public string $description = '';
    public string $entryDate = '';
    public bool $postNow = true;

    private function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public function mount(): void
    {
        $this->entryDate = now()->format('Y-m-d');
        $this->periodId = AccountingPeriod::where('status', 'open')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first()?->id;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.quick-expense', [
            'periods' => AccountingPeriod::where('status', 'open')->orderBy('period_end', 'desc')->get(),
            'accounts' => GlAccount::where('is_active', true)->orderBy('account_number')->get(['id', 'account_number', 'account_name']),
        ]);
    }

    public function submit(): void
    {
        $branchId = $this->getBranchId();
        $referenceColumn = $this->referenceColumn();

        $this->validate([
            'periodId' => 'required|exists:accounting_periods,id',
            'amount' => 'required|numeric|min:0.01',
            'expenseAccountId' => 'required|exists:gl_accounts,id',
            'paymentAccountId' => 'required|exists:gl_accounts,id',
            'description' => 'required|string|max:500',
            'entryDate' => 'required|date',
        ]);

        if ($this->expenseAccountId === $this->paymentAccountId) {
            throw ValidationException::withMessages([
                'paymentAccountId' => 'Expense and payment accounts must be different.',
            ]);
        }

        $amount = (float) $this->amount;
        $reference = $this->generateReference();

        $entries = [
            ['gl_account_id' => $this->expenseAccountId, 'debit' => $amount, 'credit' => 0],
            ['gl_account_id' => $this->paymentAccountId, 'debit' => 0, 'credit' => $amount],
        ];

        foreach ($entries as $entryData) {
            $entry = GlEntry::create([
                'accounting_period_id' => $this->periodId,
                'gl_account_id' => $entryData['gl_account_id'],
                'entry_type' => 'manual',
                'debit' => $entryData['debit'],
                'credit' => $entryData['credit'],
                'description' => $this->description,
                'entry_date' => $this->entryDate,
                'status' => 'draft',
                'entered_by_id' => auth()->id(),
                'entered_by_type' => auth()->user()?->getMorphClass() ?? \App\Models\User::class,
                'branch_id' => $branchId ?? current_branch_id(),
                $referenceColumn => $reference,
            ]);

            if ($this->postNow) {
                $entry->post(auth()->id() ?? 1);
            }
        }

        session()->flash('message', 'Quick expense recorded.');

        $this->amount = null;
        $this->expenseAccountId = null;
        $this->paymentAccountId = null;
        $this->description = '';
        $this->entryDate = now()->format('Y-m-d');
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

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function referenceColumn(): string
    {
        if (Schema::hasColumn('gl_entries', 'reference_number')) {
            return 'reference_number';
        }

        return 'reference';
    }
}
