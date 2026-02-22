<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\BankAccount;
use App\Models\GlAccount;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class CashBank extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 10;
    public ?string $search = null;
    public bool $showForm = true;

    public array $headers = [
        ['index' => 'bank_name', 'label' => 'Account'],
        ['index' => 'account_number', 'label' => 'Account Number'],
        ['index' => 'account_type', 'label' => 'Type'],
        ['index' => 'balance', 'label' => 'Balance'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Action', 'display' => true],
    ];

    public ?int $editingId = null;
    public string $bank_name = '';
    public string $account_number = '';
    public string $account_type = 'checking';
    public ?int $gl_account_id = null;
    public ?string $opening_balance = null;
    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQuantity(): void
    {
        $this->resetPage();
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'account_number')->ignore($this->editingId),
            ],
            'account_type' => ['required', Rule::in(['checking', 'savings', 'money_market'])],
            'gl_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function edit(int $bankAccountId): void
    {
        $account = BankAccount::findOrFail($bankAccountId);
        $this->editingId = $account->id;
        $this->showForm = true;
        $this->bank_name = $account->bank_name;
        $this->account_number = $account->account_number;
        $this->account_type = $account->account_type;
        $this->gl_account_id = $account->gl_account_id;
        $this->opening_balance = (string) $account->opening_balance;
        $this->is_active = (bool) $account->is_active;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->bank_name = '';
        $this->account_number = '';
        $this->account_type = 'checking';
        $this->gl_account_id = null;
        $this->opening_balance = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function openNewAccount(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;

        if ($this->editingId) {
            BankAccount::findOrFail($this->editingId)->update($validated);
        } else {
            BankAccount::create($validated);
        }

        $this->resetForm();
        session()->flash('message', 'Bank account saved.');
    }

    public function render()
    {
        $accounts = BankAccount::query()
            ->with('glAccount')
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('bank_name', 'like', '%' . $this->search . '%')
                        ->orWhere('account_number', 'like', '%' . $this->search . '%')
                        ->orWhere('account_type', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('bank_name')
            ->paginate($this->quantity ?? 10);

        $glAccounts = GlAccount::where('is_active', true)
            ->where('account_type', 'asset')
            ->where('is_header', false)
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'account_name']);

        return view('livewire.branch-dashboard.accounting.simple.cash-bank', [
            'rows' => $accounts,
            'headers' => $this->headers,
            'glAccounts' => $glAccounts,
        ]);
    }
}
