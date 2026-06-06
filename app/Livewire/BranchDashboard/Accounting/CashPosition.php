<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\BankAccount;
use App\Models\CashPosition as CashPositionModel;
use App\Models\DailyBankPosition;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class CashPosition extends Component
{
    use Interactions;
    use ExportsCsv;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $selectedDate = null;

    public ?string $selectedCashType = null;

    public string $viewMode = 'summary'; // summary, detailed

    // Cash position data
    public array $cashPositions = [];

    public array $bankPositions = [];

    public array $summary = [];

    // Cash types from model constants
    public array $cashTypes = [
        CashPositionModel::SALES_CASH => 'Sales Cash',
        CashPositionModel::PETTY_CASH => 'Petty Cash',
        CashPositionModel::DRAWER_CASH => 'Drawer Cash',
    ];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function getBranchId()
    {
        return $this->b_id ?: current_branch_id();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->loadData();
    }

    public function loadData()
    {
        $this->loadCashPositions();
        $this->loadBankPositions();
        $this->calculateSummary();
    }

    public function exportToCsv()
    {
        $this->loadData();

        $header = ['Type', 'Name', 'Account/Date', 'Opening Balance', 'Inflows', 'Outflows', 'Closing/Available Balance'];
        $rows = [];

        foreach ($this->cashPositions as $pos) {
            $rows[] = [
                'Cash',
                $pos['cash_type_label'],
                $pos['position_date'],
                number_format($pos['opening_balance'], 2, '.', ''),
                number_format($pos['sales_receipts'], 2, '.', ''),
                number_format($pos['withdrawals'], 2, '.', ''),
                number_format($pos['closing_balance'], 2, '.', ''),
            ];
        }

        foreach ($this->bankPositions as $pos) {
            $rows[] = [
                'Bank',
                $pos['bank_name'],
                $pos['account_number'],
                number_format($pos['opening_balance'], 2, '.', ''),
                number_format($pos['inflows_total'], 2, '.', ''),
                number_format($pos['outflows_total'], 2, '.', ''),
                number_format($pos['available_balance'], 2, '.', ''),
            ];
        }

        return $this->streamCsv(
            'cash_position_'.($this->selectedDate ?? now()->format('Y-m-d')),
            $header,
            $rows
        );
    }

    private function loadCashPositions()
    {
        $branchId = $this->getBranchId();
        $date = Carbon::parse($this->selectedDate);

        $query = CashPositionModel::byBranch($branchId)
            ->byDate($date);

        if ($this->selectedCashType) {
            $query->byType($this->selectedCashType);
        }

        $positions = $query->get();

        $this->cashPositions = $positions->map(fn ($pos) => [
            'id' => $pos->id,
            'cash_type' => $pos->cash_type,
            'cash_type_label' => $this->cashTypes[$pos->cash_type] ?? ucfirst($pos->cash_type),
            'position_date' => $pos->position_date->format('Y-m-d'),
            'opening_balance' => (float) $pos->opening_balance,
            'sales_receipts' => (float) $pos->sales_receipts,
            'withdrawals' => (float) $pos->withdrawals,
            'closing_balance' => (float) $pos->closing_balance,
            'physical_count' => $pos->physical_count ? (float) $pos->physical_count : null,
            'counted_at' => $pos->counted_at?->format('Y-m-d H:i'),
            'variance_amount' => (float) $pos->variance_amount,
            'variance_notes' => $pos->variance_notes,
            'is_balanced' => $pos->isBalanced(),
            'has_variance' => abs((float) $pos->variance_amount) > 0.01,
        ])->toArray();
    }

    private function loadBankPositions()
    {
        $date = Carbon::parse($this->selectedDate);

        // Get all active bank accounts with their positions
        $bankAccounts = BankAccount::where('is_active', true)->get();

        $this->bankPositions = $bankAccounts->map(function ($account) use ($date) {
            $position = DailyBankPosition::where('bank_account_id', $account->id)
                ->where('position_date', '<=', $date)
                ->orderBy('position_date', 'desc')
                ->first();

            return [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'opening_balance' => $position ? (float) $position->opening_balance : (float) $account->opening_balance,
                'inflows_total' => $position ? (float) $position->inflows_total : 0,
                'outflows_total' => $position ? (float) $position->outflows_total : 0,
                'available_balance' => $position ? (float) $position->available_balance : (float) $account->opening_balance,
                'reconciled' => $position?->reconciled ?? false,
                'reconciled_at' => $position?->reconciled_at?->format('Y-m-d H:i'),
                'position_date' => $position?->position_date?->format('Y-m-d'),
            ];
        })->toArray();
    }

    private function calculateSummary()
    {
        // Cash positions summary
        $totalCashOpening = collect($this->cashPositions)->sum('opening_balance');
        $totalCashReceipts = collect($this->cashPositions)->sum('sales_receipts');
        $totalCashWithdrawals = collect($this->cashPositions)->sum('withdrawals');
        $totalCashClosing = collect($this->cashPositions)->sum('closing_balance');
        $totalCashVariance = collect($this->cashPositions)->sum('variance_amount');

        // Bank positions summary
        $totalBankBalance = collect($this->bankPositions)->sum('available_balance');
        $totalBankInflows = collect($this->bankPositions)->sum('inflows_total');
        $totalBankOutflows = collect($this->bankPositions)->sum('outflows_total');

        // Combined totals
        $this->summary = [
            // Cash summary
            'total_cash_opening' => $totalCashOpening,
            'total_cash_receipts' => $totalCashReceipts,
            'total_cash_withdrawals' => $totalCashWithdrawals,
            'total_cash_closing' => $totalCashClosing,
            'total_cash_variance' => $totalCashVariance,
            'cash_positions_count' => count($this->cashPositions),
            'cash_with_variance' => collect($this->cashPositions)->where('has_variance', true)->count(),

            // Bank summary
            'total_bank_balance' => $totalBankBalance,
            'total_bank_inflows' => $totalBankInflows,
            'total_bank_outflows' => $totalBankOutflows,
            'bank_accounts_count' => count($this->bankPositions),
            'reconciled_accounts' => collect($this->bankPositions)->where('reconciled', true)->count(),

            // Combined
            'total_cash_position' => $totalCashClosing + $totalBankBalance,
            'has_variance' => abs($totalCashVariance) > 0.01,
        ];
    }

    public function updatedSelectedDate()
    {
        $this->loadData();
    }

    public function updatedSelectedCashType()
    {
        $this->loadCashPositions();
        $this->calculateSummary();
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function refreshData()
    {
        $this->loadData();
        $this->toast()->success('Cash position data refreshed')->send();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.cash-position', [
            'cashPositions' => $this->cashPositions,
            'bankPositions' => $this->bankPositions,
            'summary' => $this->summary,
            'cashTypes' => $this->cashTypes,
        ]);
    }
}
