<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\GlAccount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class ChartOfAccounts extends Component
{
    use WithPagination;
    use ExportsCsv;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $search = '';
    public string $type = 'all';
    public int $perPage = 25;
    public bool $showCreate = false;

    public string $account_number = '';
    public string $account_name = '';
    public string $account_type = '';
    public ?string $account_category = null;
    public ?string $description = null;
    public string $normal_balance = '';
    public bool $is_header = false;
    public ?int $parent_account_id = null;
    public bool $is_active = true;
    public bool $allow_manual_entry = true;

    public function toggleCreate(): void
    {
        $this->showCreate = ! $this->showCreate;
    }

    public function createAccount(): void
    {
        $this->validate([
            'account_number' => 'required|string|max:50|unique:gl_accounts,account_number',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|string|in:asset,liability,equity,revenue,cost_of_goods_sold,expense,tax',
            'normal_balance' => 'nullable|string|in:debit,credit',
            'parent_account_id' => 'nullable|integer|exists:gl_accounts,id',
        ]);

        $normalBalance = $this->normal_balance;
        if ($normalBalance === '') {
            $normalBalance = in_array($this->account_type, ['asset', 'expense', 'cost_of_goods_sold'], true)
                ? 'debit'
                : 'credit';
        }

        $isHeader = (bool) $this->is_header;

        GlAccount::create([
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'account_category' => $this->account_category,
            'description' => $this->description,
            'normal_balance' => $normalBalance,
            'is_header' => $isHeader,
            'parent_account_id' => $this->parent_account_id,
            'is_active' => $this->is_active,
            'allow_manual_entry' => $isHeader ? false : $this->allow_manual_entry,
        ]);

        $this->reset([
            'account_number',
            'account_name',
            'account_type',
            'account_category',
            'description',
            'normal_balance',
            'is_header',
            'parent_account_id',
            'is_active',
            'allow_manual_entry',
        ]);

        $this->showCreate = false;
        $this->resetPage();
        session()->flash('success', 'GL account created.');
    }

    private function accountsQuery()
    {
        $query = GlAccount::query();

        if ($this->type !== 'all') {
            $query->where('account_type', $this->type);
        }

        if ($this->search !== '') {
            $query->where(function ($subQuery) {
                $subQuery->where('account_number', 'like', '%' . $this->search . '%')
                    ->orWhere('account_name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('account_number');
    }

    public function exportToCsv()
    {
        $rows = $this->accountsQuery()->get()->map(fn ($a) => [
            $a->account_number,
            $a->account_name,
            $a->account_type,
            $a->account_category ?? '',
            $a->normal_balance ?? '',
            $a->is_header ? 'Yes' : 'No',
            $a->is_active ? 'Yes' : 'No',
        ]);

        return $this->streamCsv(
            $this->csvFilename('chart_of_accounts'),
            ['Account Number', 'Account Name', 'Type', 'Category', 'Normal Balance', 'Is Header', 'Active'],
            $rows
        );
    }

    public function render()
    {
        $accounts = $this->accountsQuery()->paginate($this->perPage);

        return view('livewire.branch-dashboard.accounting.simple.chart-of-accounts', [
            'accounts' => $accounts,
            'type' => $this->type,
            'search' => $this->search,
            'headers' => GlAccount::where('is_header', true)->orderBy('account_number')->get(['id', 'account_number', 'account_name']),
        ]);
    }
}
