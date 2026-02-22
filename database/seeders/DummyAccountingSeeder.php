<?php

namespace Database\Seeders;

use App\Models\AccountingEntry;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->first();
        if (! $branch) {
            $branch = Branch::create([
                'name' => 'Demo Branch',
                'code' => 'DUM-001',
                'location' => 'Demo Location',
                'phone' => '000-000-0000',
                'email' => 'demo-branch@example.test',
                'description' => 'Auto-created by DummyAccountingSeeder',
                'country' => 'Nigeria',
                'state' => 'Rivers',
                'city' => 'Port Harcourt',
                'timezone' => 'Africa/Lagos',
                'is_active' => true,
            ]);
        }

        $period = AccountingPeriod::current()->first();
        if (! $period) {
            $now = now();
            $period = AccountingPeriod::create([
                'year' => (int) $now->format('Y'),
                'month' => (int) $now->format('m'),
                'period_start' => $now->copy()->startOfMonth()->toDateString(),
                'period_end' => $now->copy()->endOfMonth()->toDateString(),
                'status' => 'open',
            ]);
        }

        $userId = User::query()->value('id');

        $cash = $this->ensureGlAccount('1010', 'Cash', 'asset');
        $bank = $this->ensureGlAccount('1050', 'Bank', 'asset');
        $revenue = $this->ensureGlAccount('4010', 'Sales Revenue', 'revenue');
        $this->ensureGlAccount('1060', 'POS Clearing', 'asset');

        $expenseCategories = [
            'Premises Maintenance',
            'Fuel',
            'Diesel',
            'PHED',
            'Cooking Gas',
            'Purchases / Market List',
            'CSR',
            'Pensions',
            'Salaries and Wages',
            'PAYE',
            'Consumption Tax',
            'Carriage Inward',
            'HRM Subscription',
            'Subscriptions / Renewals',
            'Repairs and Maintenance - Hiace Bus',
            'Repairs and Maintenance - Bike',
            'Repairs and Maintenance - Transformer',
            'Repairs and Maintenance - Generator',
            'Repairs and Maintenance - Cooling Van',
            'Repairs and Maintenance - Electrical DB',
            'Repairs and Maintenance - Mixer',
            'Repairs and Maintenance - A/C',
            'Repairs and Maintenance - Oven',
            'Repairs and Maintenance - Deep Fryer',
            'Repairs and Maintenance - Freezers',
            'Sundry Repairs',
            'Fixtures and Fittings Maintenance',
            'Advertisement',
            'Postage and Telephone',
            'Repairs of Cooking Gas',
            'Events',
            'Bank Charges',
            'Chargebacks',
            'Refunds to Customers',
            'Refunds to Directors',
            'Staff Welfare',
            'Staff Loan',
            'Transportation',
            'Legal Fee',
            'Sundry Expenses',
            'Purchase / Installation of Assets',
            'Security',
            "Director's Drawings",
            'Staff Accommodation',
            'Intercompany Transfers',
            'Capital WIP',
        ];

        $expenseAccounts = $this->ensureExpenseAccounts($expenseCategories);

        $bankAccounts = $this->ensureBankAccounts($bank, $cash);

        $entries = [
            ['date' => '2026-02-01', 'particulars' => 'Bank Charges', 'amount' => 22264.53, 'category' => 'Bank Charges', 'bank' => 'All Banks'],
            ['date' => '2026-02-02', 'particulars' => 'Logistics', 'amount' => 515000.00, 'category' => 'Transportation', 'bank' => 'GTB'],
            ['date' => '2026-02-02', 'particulars' => 'Cheese Purchase', 'amount' => 1545000.00, 'category' => 'Purchases / Market List', 'bank' => 'GTB'],
            ['date' => '2026-02-03', 'particulars' => 'Fuel for Hiace Bus', 'amount' => 60000.00, 'category' => 'Fuel', 'bank' => 'Zenith'],
            ['date' => '2026-02-03', 'particulars' => 'Emergency Purchase of Lemon', 'amount' => 1500.00, 'category' => 'Purchases / Market List', 'bank' => 'Petty'],
            ['date' => '2026-02-04', 'particulars' => 'Medical Relief Materials', 'amount' => 38020.00, 'category' => 'Staff Welfare', 'bank' => 'Petty'],
            ['date' => '2026-02-05', 'particulars' => 'Diesel (500 Litres)', 'amount' => 530500.00, 'category' => 'Diesel', 'bank' => 'GTB'],
            ['date' => '2026-02-06', 'particulars' => 'January Salaries', 'amount' => 10153623.23, 'category' => 'Salaries and Wages', 'bank' => 'Zenith'],
            ['date' => '2026-02-07', 'particulars' => 'Hiace Bus Maintenance', 'amount' => 111900.00, 'category' => 'Repairs and Maintenance - Hiace Bus', 'bank' => 'Stanbic'],
            ['date' => '2026-02-10', 'particulars' => 'Consumption Tax January', 'amount' => 395846.80, 'category' => 'Consumption Tax', 'bank' => 'UBA'],
            ['date' => '2026-02-10', 'particulars' => 'PAYE January', 'amount' => 320752.00, 'category' => 'PAYE', 'bank' => 'UBA'],
            ['date' => '2026-02-12', 'particulars' => 'Logistics for Honey', 'amount' => 3000.00, 'category' => 'Transportation', 'bank' => 'Petty'],
        ];

        DB::beginTransaction();
        try {
            foreach ($entries as $entry) {
                $bankAccount = $this->resolveBankAccount($bankAccounts, $entry['bank']);
                $creditGl = $bankAccount?->glAccount ?? $bank;
                $debitGl = $expenseAccounts[$entry['category']] ?? $this->ensureExpenseAccounts([$entry['category']])[$entry['category']];

                $accountingEntry = AccountingEntry::create([
                    'branch_id' => $branch->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'expense',
                    'entry_date' => Carbon::parse($entry['date'])->toDateString(),
                    'description' => $entry['particulars'],
                    'reference' => null,
                    'amount' => $entry['amount'],
                    'debit_gl_account_id' => $debitGl->id,
                    'credit_gl_account_id' => $creditGl->id,
                    'bank_account_id' => $bankAccount?->id,
                    'source' => $entry['bank'],
                    'notes' => $entry['category'],
                    'created_by_id' => $userId,
                ]);

                $debitEntry = GlEntry::create([
                    'gl_account_id' => $debitGl->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'expense',
                    'reference_type' => AccountingEntry::class,
                    'reference_id' => $accountingEntry->id,
                    'reference_number' => null,
                    'description' => $entry['particulars'],
                    'debit' => $entry['amount'],
                    'credit' => 0,
                    'entry_date' => Carbon::parse($entry['date'])->toDateString(),
                    'status' => 'draft',
                    'entered_by_id' => $userId,
                    'branch_id' => $branch->id,
                ]);
                $debitEntry->post($userId ?? 1);

                $creditEntry = GlEntry::create([
                    'gl_account_id' => $creditGl->id,
                    'accounting_period_id' => $period->id,
                    'entry_type' => 'expense',
                    'reference_type' => AccountingEntry::class,
                    'reference_id' => $accountingEntry->id,
                    'reference_number' => null,
                    'description' => $entry['particulars'],
                    'debit' => 0,
                    'credit' => $entry['amount'],
                    'entry_date' => Carbon::parse($entry['date'])->toDateString(),
                    'status' => 'draft',
                    'entered_by_id' => $userId,
                    'branch_id' => $branch->id,
                ]);
                $creditEntry->post($userId ?? 1);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function ensureGlAccount(string $number, string $name, string $type): GlAccount
    {
        return GlAccount::firstOrCreate(
            ['account_number' => $number],
            [
                'account_name' => $name,
                'account_type' => $type,
                'account_category' => null,
                'description' => null,
                'normal_balance' => in_array($type, ['asset', 'expense', 'cost_of_goods_sold']) ? 'debit' : 'credit',
                'is_header' => false,
                'is_active' => true,
                'allow_manual_entry' => true,
            ]
        );
    }

    private function ensureExpenseAccounts(array $categories): array
    {
        $accounts = [];
        $maxNumber = GlAccount::where('account_type', 'expense')->max('account_number');
        $nextNumber = $this->nextAccountNumber($maxNumber ?: '6000');

        foreach ($categories as $category) {
            $existing = GlAccount::where('account_name', $category)->first();
            if ($existing) {
                $accounts[$category] = $existing;
                continue;
            }

            $number = (string) $nextNumber;
            $nextNumber += 10;

            $accounts[$category] = GlAccount::create([
                'account_number' => $number,
                'account_name' => $category,
                'account_type' => 'expense',
                'account_category' => null,
                'description' => null,
                'normal_balance' => 'debit',
                'is_header' => false,
                'is_active' => true,
                'allow_manual_entry' => true,
            ]);
        }

        return $accounts;
    }

    private function nextAccountNumber(string $current): int
    {
        $numeric = preg_replace('/\D+/', '', $current);
        $base = $numeric !== '' ? (int) $numeric : 6000;

        if ($base < 6000) {
            return 6000;
        }

        return $base + 10;
    }

    private function ensureBankAccounts(GlAccount $bank, GlAccount $cash): array
    {
        $data = [
            ['bank_name' => 'Access Bank', 'account_number' => '8767890987', 'gl_account_id' => $bank->id],
            ['bank_name' => 'GTB', 'account_number' => '1000000001', 'gl_account_id' => $bank->id],
            ['bank_name' => 'Zenith', 'account_number' => '1000000002', 'gl_account_id' => $bank->id],
            ['bank_name' => 'UBA', 'account_number' => '1000000003', 'gl_account_id' => $bank->id],
            ['bank_name' => 'Stanbic', 'account_number' => '1000000004', 'gl_account_id' => $bank->id],
            ['bank_name' => 'Petty', 'account_number' => '1000000005', 'gl_account_id' => $cash->id],
        ];

        $accounts = [];
        foreach ($data as $row) {
            $accounts[] = BankAccount::firstOrCreate(
                ['account_number' => $row['account_number']],
                [
                    'bank_name' => $row['bank_name'],
                    'bank_code' => Str::upper(Str::substr($row['bank_name'], 0, 3)),
                    'account_type' => 'checking',
                    'gl_account_id' => $row['gl_account_id'],
                    'opening_balance' => 0,
                    'interest_rate' => 0,
                    'is_active' => true,
                ]
            );
        }

        return $accounts;
    }

    private function resolveBankAccount(array $bankAccounts, string $name): ?BankAccount
    {
        if (strcasecmp($name, 'all banks') === 0) {
            return $bankAccounts[0] ?? null;
        }

        foreach ($bankAccounts as $account) {
            if (strcasecmp($account->bank_name, $name) === 0) {
                return $account;
            }
        }

        return null;
    }
}
