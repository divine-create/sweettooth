<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Helpers\Settings;
use App\Livewire\Concerns\ExportsCsv;
use App\Models\BankAccount;
use App\Models\DailyBankPosition as DailyBankPositionModel;
use App\Models\DailyBankTransaction;
use App\Services\CurrencyFormattingService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class DailyBankPosition extends Component
{
    use ExportsCsv;

    public ?int $selectedBankAccountId = null;

    public ?string $selectedDate = null;

    public array $bankAccounts = [];

    public array $positions = [];

    public array $transactions = [];

    public array $summary = [];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadBankAccounts();
        if ($this->selectedBankAccountId) {
            $this->loadPositions();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.daily-bank-position', [
            'bankAccounts' => $this->bankAccounts,
            'positions' => $this->positions,
            'transactions' => $this->transactions,
            'summary' => $this->summary,
        ]);
    }

    public function exportToCsv()
    {
        $this->loadPositions();

        $rows = array_map(fn ($t) => [
            $t['date'],
            $t['type'],
            $t['subtype'] ?? '',
            number_format((float) $t['amount'], 2, '.', ''),
            $t['description'] ?? '',
            $t['reference'] ?? '',
            $t['status'] ?? '',
            ! empty($t['reconciled']) ? 'Yes' : 'No',
        ], $this->transactions);

        return $this->streamCsv(
            'bank_transactions_'.($this->selectedDate ?? now()->format('Y-m-d')),
            ['Date', 'Type', 'Subtype', 'Amount', 'Description', 'Reference', 'Status', 'Reconciled'],
            $rows
        );
    }

    public function loadBankAccounts()
    {
        $this->bankAccounts = BankAccount::where('is_active', true)
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
            ])
            ->all();

        if (count($this->bankAccounts) > 0 && ! $this->selectedBankAccountId) {
            $this->selectedBankAccountId = $this->bankAccounts[0]['id'];
        }
    }

    public function loadPositions()
    {
        if (! $this->selectedBankAccountId || ! $this->selectedDate) {
            return;
        }

        try {
            $bankAccount = BankAccount::find($this->selectedBankAccountId);
            $date = Carbon::createFromFormat('Y-m-d', $this->selectedDate);

            // Get today's bank position
            $position = DailyBankPositionModel::where('bank_account_id', $this->selectedBankAccountId)
                ->where('position_date', $date)
                ->with(['bankAccount'])
                ->first();

            if ($position) {
                $this->positions = [[
                    'id' => $position->id,
                    'date' => $position->position_date->format('Y-m-d'),
                    'opening_balance' => $position->opening_balance,
                    'closing_balance' => $position->closing_balance,
                    'total_inflow' => $position->total_inflow,
                    'total_outflow' => $position->total_outflow,
                    'reconciled' => $position->is_reconciled,
                ]];
            }

            // Get transactions for this date
            $this->loadTransactions($this->selectedBankAccountId, $date);
            $this->calculateSummary();
        } catch (\Exception $e) {
            $this->addError('form', 'Error loading positions: '.$e->getMessage());
        }
    }

    private function loadTransactions($bankAccountId, Carbon $date)
    {
        $dailyTransactions = DailyBankTransaction::where('bank_account_id', $bankAccountId)
            ->whereDate('transaction_date', $date)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $this->transactions = $dailyTransactions->map(fn ($transaction) => [
            'id' => $transaction->id,
            'date' => $transaction->transaction_date->format('Y-m-d H:i'),
            'type' => $transaction->transaction_type,
            'subtype' => $transaction->transaction_subtype,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'reference' => $transaction->reference_number,
            'status' => $transaction->status,
            'reconciled' => $transaction->reconciled,
        ])->all();
    }

    private function calculateSummary()
    {
        $inflows = collect($this->transactions)
            ->filter(fn ($t) => $t['type'] === 'inflow')
            ->sum('amount');

        $outflows = collect($this->transactions)
            ->filter(fn ($t) => $t['type'] === 'outflow')
            ->sum('amount');

        $reconciled = collect($this->transactions)
            ->filter(fn ($t) => $t['reconciled'] === true)
            ->sum('amount');

        $unreconciled = collect($this->transactions)
            ->filter(fn ($t) => $t['reconciled'] === false)
            ->sum('amount');

        $this->summary = [
            'total_inflows' => $inflows,
            'total_outflows' => $outflows,
            'net_movement' => $inflows - $outflows,
            'reconciled_amount' => $reconciled,
            'unreconciled_amount' => $unreconciled,
            'total_transactions' => count($this->transactions),
            'reconciled_count' => collect($this->transactions)->where('reconciled', true)->count(),
            'unreconciled_count' => collect($this->transactions)->where('reconciled', false)->count(),
        ];
    }

    public function updateDate($date)
    {
        $this->selectedDate = $date;
        $this->loadPositions();
    }

    public function updateBankAccount($bankAccountId)
    {
        $this->selectedBankAccountId = $bankAccountId;
        $this->loadPositions();
    }

    /**
     * Format currency value for bank position display
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService;

        return $service->format($amount);
    }

    /**
     * Get currency symbol for bank transactions
     */
    protected function getCurrencySymbol(?string $currency = null): string
    {
        $service = new CurrencyFormattingService;
        $currency = $currency ?? Settings::currencyLocalization('primary_currency', 'NGN');

        return $service->getSymbol($currency);
    }
}
