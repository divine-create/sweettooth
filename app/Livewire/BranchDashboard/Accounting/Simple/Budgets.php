<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\Budget;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class Budgets extends Component
{
    use WithPagination;

    public ?string $gl_account_id = null;
    public ?string $accounting_period_id = null;
    public float $planned_amount = 0;
    public ?float $forecast_amount = null;
    public ?string $category = null;
    public ?string $description = null;
    public bool $is_approved = true;

    public int $perPage = 20;

    public function save(): void
    {
        $this->validate([
            'gl_account_id' => 'required|exists:gl_accounts,id',
            'accounting_period_id' => 'required|exists:accounting_periods,id',
            'planned_amount' => 'required|numeric|min:0.01',
            'forecast_amount' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        Budget::create([
            'branch_id' => current_branch_id(),
            'gl_account_id' => $this->gl_account_id,
            'accounting_period_id' => $this->accounting_period_id,
            'planned_amount' => $this->planned_amount,
            'forecast_amount' => $this->forecast_amount,
            'category' => $this->category,
            'description' => $this->description,
            'is_approved' => $this->is_approved,
            'approved_by_id' => $this->is_approved ? auth()->id() : null,
            'approved_by_type' => $this->is_approved ? (auth()->user()?->getMorphClass() ?? \App\Models\User::class) : null,
            'approved_at' => $this->is_approved ? now() : null,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset([
            'gl_account_id',
            'accounting_period_id',
            'planned_amount',
            'forecast_amount',
            'category',
            'description',
            'is_approved',
        ]);
        $this->is_approved = true;
        $this->dispatch('notify', message: 'Budget saved.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.budgets', [
            'accounts' => GlAccount::where('is_active', true)->orderBy('account_number')->get(['id', 'account_number', 'account_name']),
            'periods' => AccountingPeriod::orderBy('period_end', 'desc')->get(),
            'rows' => Budget::orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
