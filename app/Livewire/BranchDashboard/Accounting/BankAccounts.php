<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\BankAccount;
use App\Models\GlAccount;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class BankAccounts extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $sortBy = 'bank_name';
    public string $sortDirection = 'asc';

    public ?int $editingId = null;

    public string $bank_name = '';
    public ?string $bank_code = null;
    public string $account_number = '';
    public string $account_type = 'checking';
    public ?int $gl_account_id = null;
    public ?string $opening_balance = null;
    public ?string $interest_rate = null;
    public bool $is_active = true;

    protected $queryString = ['search', 'filterStatus', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function bankAccounts()
    {
        return BankAccount::query()
            ->when($this->search, function ($q) {
                return $q->where('bank_name', 'like', "%{$this->search}%")
                    ->orWhere('account_number', 'like', "%{$this->search}%")
                    ->orWhere('bank_code', 'like', "%{$this->search}%");
            })
            ->when($this->filterStatus, function ($q) {
                return $q->where('is_active', $this->filterStatus === 'active');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function glAccounts()
    {
        return GlAccount::query()
            ->where('is_active', true)
            ->where('account_type', 'asset')
            ->orderBy('account_number')
            ->get();
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:50'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'account_number')->ignore($this->editingId),
            ],
            'account_type' => ['required', Rule::in(['checking', 'savings', 'money_market'])],
            'gl_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->bank_name = '';
        $this->bank_code = null;
        $this->account_number = '';
        $this->account_type = 'checking';
        $this->gl_account_id = null;
        $this->opening_balance = null;
        $this->interest_rate = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function edit(int $bankAccountId): void
    {
        $account = BankAccount::findOrFail($bankAccountId);

        $this->editingId = $account->id;
        $this->bank_name = $account->bank_name;
        $this->bank_code = $account->bank_code;
        $this->account_number = $account->account_number;
        $this->account_type = $account->account_type;
        $this->gl_account_id = $account->gl_account_id;
        $this->opening_balance = (string) $account->opening_balance;
        $this->interest_rate = (string) $account->interest_rate;
        $this->is_active = (bool) $account->is_active;
    }

    public function save(): void
    {
        $user = auth()->user();
        if ($this->editingId) {
            if (! $this->canEditBankAccounts($user)) {
                $this->addError('form', 'Unauthorized to edit bank accounts.');
                return;
            }
        } else {
            if (! $this->canCreateBankAccounts($user)) {
                $this->addError('form', 'Unauthorized to create bank accounts.');
                return;
            }
        }

        $this->normalizeFormValues();
        $validated = $this->validate();

        if ($this->editingId) {
            $account = BankAccount::findOrFail($this->editingId);
            $account->update($validated);
            session()->flash('success', 'Bank account updated successfully.');
        } else {
            BankAccount::create($validated);
            session()->flash('success', 'Bank account created successfully.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(int $bankAccountId): void
    {
        $user = auth()->user();
        if (! $this->canEditBankAccounts($user)) {
            $this->addError('form', 'Unauthorized to edit bank accounts.');
            return;
        }

        $account = BankAccount::findOrFail($bankAccountId);
        $account->update(['is_active' => ! $account->is_active]);
        session()->flash('success', 'Bank account status updated.');
    }

    private function normalizeFormValues(): void
    {
        // DB columns are non-nullable decimals with default 0; send 0 when inputs are left blank.
        $this->opening_balance = $this->opening_balance === '' || $this->opening_balance === null ? '0' : $this->opening_balance;
        $this->interest_rate = $this->interest_rate === '' || $this->interest_rate === null ? '0' : $this->interest_rate;
        $this->gl_account_id = $this->gl_account_id === '' ? null : $this->gl_account_id;
    }

    private function canCreateBankAccounts($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Managing Director', 'Admin', 'Accountant', 'Accounting Manager'])
            || $user->can('create_bank_accounts')
            || $user->can('edit_bank_accounts');
    }

    private function canEditBankAccounts($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Managing Director', 'Admin', 'Accountant', 'Accounting Manager'])
            || $user->can('edit_bank_accounts');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.bank-accounts');
    }
}
