<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\BankAccount;
use App\Models\DailyBankPosition;
use App\Models\DailyBankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app.branch-dashboard')]
class BankStatementImport extends Component
{
    use WithFileUploads;

    public ?int $selectedBankAccountId = null;
    public $upload;
    public array $preview = [];
    public array $columnMapping = [
        'date' => 'Date',
        'narration' => 'Narration',
        'debit' => 'Debit',
        'credit' => 'Credit',
        'balance' => 'Balance',
    ];
    public array $availableColumns = [];
    public bool $showPreview = false;
    public int $importedCount = 0;
    public array $importErrors = [];

    protected function rules()
    {
        return [
            'selectedBankAccountId' => 'required|exists:bank_accounts,id',
            'upload' => 'required|file|mimes:csv,txt|max:5120',
        ];
    }

    public function getBankAccountsProperty()
    {
        return BankAccount::where('is_active', true)->get();
    }

    public function updatedUpload()
    {
        $this->validateOnly('upload');
        $this->showPreview = false;
        $this->preview = [];
        $this->parseCsv();
    }

    protected function parseCsv()
    {
        $path = $this->upload->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError('upload', 'Could not read the file.');
            return;
        }
        $rows = [];
        $headers = [];
        $line = fgetcsv($handle);
        if ($line) {
            $headers = array_map('trim', $line);
            $this->availableColumns = $headers;
        }
        $this->autoDetectMapping($headers);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < 20) {
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? '';
            }
            $rows[] = $data;
            $count++;
        }
        fclose($handle);
        $this->preview = $rows;
        $this->showPreview = true;
    }

    protected function autoDetectMapping(array $headers)
    {
        $lowerHeaders = array_map('strtolower', $headers);
        foreach ($lowerHeaders as $index => $header) {
            $original = $headers[$index];
            if (in_array($header, ['date', 'transaction date', 'tran date', 'value date', 'posting date'])) {
                $this->columnMapping['date'] = $original;
            } elseif (in_array($header, ['narration', 'description', 'narrative', 'remarks', 'particulars'])) {
                $this->columnMapping['narration'] = $original;
            } elseif (in_array($header, ['debit', 'withdrawal', 'dr', 'withdrawals', 'outflow'])) {
                $this->columnMapping['debit'] = $original;
            } elseif (in_array($header, ['credit', 'deposit', 'cr', 'deposits', 'inflow'])) {
                $this->columnMapping['credit'] = $original;
            } elseif (in_array($header, ['balance', 'running balance', 'bal', 'available balance', 'closing balance'])) {
                $this->columnMapping['balance'] = $original;
            }
        }
    }

    public function import()
    {
        $this->validate();
        if (empty($this->preview)) {
            $this->parseCsv();
        }
        $path = $this->upload->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError('upload', 'Could not read the file.');
            return;
        }
        fgetcsv($handle);
        DB::beginTransaction();
        try {
            $bankAccount = BankAccount::findOrFail($this->selectedBankAccountId);
            $imported = 0;
            $errors = [];
            $positionsByDate = [];
            $dateCol = $this->columnMapping['date'];
            $narrationCol = $this->columnMapping['narration'];
            $debitCol = $this->columnMapping['debit'];
            $creditCol = $this->columnMapping['credit'];
            $balanceCol = $this->columnMapping['balance'];

            while (($row = fgetcsv($handle)) !== false) {
                $data = [];
                $data['date'] = trim($row[array_search($dateCol, $this->availableColumns)] ?? '');
                $data['narration'] = trim($row[array_search($narrationCol, $this->availableColumns)] ?? '');
                $data['debit'] = trim($row[array_search($debitCol, $this->availableColumns)] ?? '0');
                $data['credit'] = trim($row[array_search($creditCol, $this->availableColumns)] ?? '0');
                $data['balance'] = trim($row[array_search($balanceCol, $this->availableColumns)] ?? '0');
                if (empty($data['date'])) { continue; }
                try {
                    $transactionDate = Carbon::createFromFormat('d/m/Y', $data['date']);
                } catch (\Exception $e) {
                    try {
                        $transactionDate = Carbon::parse($data['date']);
                    } catch (\Exception $e2) {
                        $errors[] = "Skipped row: invalid date '{$data['date']}'";
                        continue;
                    }
                }
                $debitAmount = floatval(str_replace(',', '', $data['debit']));
                $creditAmount = floatval(str_replace(',', '', $data['credit']));
                $balanceAmount = floatval(str_replace(',', '', $data['balance']));
                if ($debitAmount == 0 && $creditAmount == 0) { continue; }
                $transactionType = $creditAmount > 0 ? DailyBankTransaction::INFLOW : DailyBankTransaction::OUTFLOW;
                $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
                $subtype = $this->classifyTransaction($data['narration']);
                $dateKey = $transactionDate->toDateString();

                if (!isset($positionsByDate[$dateKey])) {
                    $position = DailyBankPosition::firstOrCreate(
                        ['bank_account_id' => $bankAccount->id, 'position_date' => $dateKey],
                        ['opening_balance' => 0, 'inflows_total' => 0, 'outflows_total' => 0, 'available_balance' => $balanceAmount]
                    );
                    $positionsByDate[$dateKey] = $position;
                } else {
                    $position = $positionsByDate[$dateKey];
                }

                DailyBankTransaction::create([
                    'daily_bank_position_id' => $position->id,
                    'bank_account_id' => $bankAccount->id,
                    'transaction_type' => $transactionType,
                    'transaction_subtype' => $subtype,
                    'amount' => $amount,
                    'description' => $data['narration'],
                    'transaction_date' => $transactionDate,
                    'status' => DailyBankTransaction::CLEARED,
                ]);

                if ($transactionType === DailyBankTransaction::INFLOW) {
                    $position->increment('inflows_total', $amount);
                } else {
                    $position->increment('outflows_total', $amount);
                }
                $imported++;
            }

            foreach ($positionsByDate as $position) {
                $position->calculateBalance();
            }
            fclose($handle);
            DB::commit();
            $this->importedCount = $imported;
            $this->importErrors = $errors;
            session()->flash('message', "Successfully imported {$imported} transactions.");
            $this->upload = null;
            $this->preview = [];
            $this->showPreview = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('import', 'Import failed: ' . $e->getMessage());
        }
    }

    protected function classifyTransaction(string $narration): string
    {
        $lower = strtolower($narration);
        if (str_contains($lower, 'pos')) return DailyBankTransaction::POS_SALES;
        if (str_contains($lower, 'transfer in') || str_contains($lower, 'online payment') || str_contains($lower, 'remittance')) return DailyBankTransaction::TRANSFER_SALES;
        if (str_contains($lower, 'transfer to') || str_contains($lower, 'salary') || str_contains($lower, 'rent') || str_contains($lower, 'utility') || str_contains($lower, 'supplier')) return DailyBankTransaction::TRANSFER_EXPENSE;
        if (str_contains($lower, 'cash deposit') || str_contains($lower, 'loan repayment') || str_contains($lower, 'customer payment')) return DailyBankTransaction::OTHER_INCOME;
        if (str_contains($lower, 'withdrawal') || str_contains($lower, 'atm')) return DailyBankTransaction::CASH_WITHDRAWAL;
        if (str_contains($lower, 'bank charge') || str_contains($lower, 'sms') || str_contains($lower, 'cot') || str_contains($lower, 'service fee')) return DailyBankTransaction::BANK_CHARGE;
        if (str_contains($lower, 'chargeback')) return DailyBankTransaction::CHARGEBACK;
        if (str_contains($lower, 'reversal')) return DailyBankTransaction::REVERSED_TRANSFER;
        return 'other';
    }

    public function downloadSample()
    {
        return response()->download(
            storage_path('app/public/samples/sample_bank_statement.csv'),
            'sample_bank_statement.csv'
        );
    }

    public function render()
    {
        return view('livewire.branch-dashboard.accounting.bank-statement-import', [
            'bankAccounts' => $this->bankAccounts,
        ]);
    }
}
