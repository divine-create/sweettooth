<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\FixedAsset;
use App\Models\BankAccount;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class FixedAssets extends Component
{
    use WithPagination;

    public string $asset_name = '';
    public ?string $asset_tag = null;
    public float $asset_cost = 0;
    public float $salvage_value = 0;
    public int $useful_life_months = 36;
    public string $depreciation_method = 'straight_line';
    public string $acquisition_date = '';
    public string $funding_source = 'cash';
    public ?string $bank_account_id = null;

    public int $perPage = 20;

    public function mount(): void
    {
        $this->acquisition_date = Carbon::now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'asset_name' => 'required|string|max:255',
            'asset_tag' => 'nullable|string|max:100',
            'asset_cost' => 'required|numeric|min:0.01',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'depreciation_method' => 'required|in:straight_line,reducing_balance',
            'acquisition_date' => 'required|date',
            'funding_source' => 'required|in:cash,ap',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        FixedAsset::create([
            'branch_id' => current_branch_id(),
            'asset_name' => $this->asset_name,
            'asset_tag' => $this->asset_tag,
            'asset_cost' => $this->asset_cost,
            'salvage_value' => $this->salvage_value,
            'useful_life_months' => $this->useful_life_months,
            'depreciation_method' => $this->depreciation_method,
            'acquisition_date' => $this->acquisition_date,
            'funding_source' => $this->funding_source,
            'bank_account_id' => $this->bank_account_id,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset([
            'asset_name',
            'asset_tag',
            'asset_cost',
            'salvage_value',
            'useful_life_months',
            'depreciation_method',
            'funding_source',
            'bank_account_id',
        ]);

        $this->useful_life_months = 36;
        $this->depreciation_method = 'straight_line';
        $this->funding_source = 'cash';
        $this->dispatch('notify', message: 'Asset recorded.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.simple.fixed-assets', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(['id', 'bank_name', 'account_number']),
            'rows' => FixedAsset::orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
