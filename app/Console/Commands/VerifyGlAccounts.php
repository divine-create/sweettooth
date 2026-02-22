<?php

namespace App\Console\Commands;

use App\Models\GlAccount;
use Illuminate\Console\Command;

class VerifyGlAccounts extends Command
{
    protected $signature = 'accounting:verify-accounts';
    protected $description = 'Verify all required GL accounts exist';

    protected $requiredAccounts = [
        // Assets
        '1010' => ['name' => 'Cash', 'type' => 'asset'],
        '1020' => ['name' => 'Bank Account', 'type' => 'asset'],
        '1220' => ['name' => 'Inventory', 'type' => 'asset'],

        // Liabilities
        '2010' => ['name' => 'Accounts Payable', 'type' => 'liability'],
        '2020' => ['name' => 'Sales Tax Payable', 'type' => 'liability'],

        // Revenue
        '4010' => ['name' => 'Sales Revenue', 'type' => 'revenue'],
        '4020' => ['name' => 'Other Income', 'type' => 'other_income'],

        // COGS & Expenses
        '5010' => ['name' => 'Cost of Goods Sold', 'type' => 'cost_of_goods_sold'],
        '6010' => ['name' => 'Salaries & Wages', 'type' => 'expense'],
        '6020' => ['name' => 'Rent Expense', 'type' => 'expense'],
        '6030' => ['name' => 'Utilities', 'type' => 'expense'],
        '6040' => ['name' => 'Inventory Loss', 'type' => 'expense'],
    ];

    public function handle()
    {
        $this->info("\n📋 Verifying GL Accounts...\n");

        $missing = [];
        $found = [];

        foreach ($this->requiredAccounts as $number => $details) {
            $account = GlAccount::where('account_number', $number)->first();

            if ($account) {
                $found[] = "$number - {$details['name']}";
                $this->line("  ✓ $number - {$details['name']}");
            } else {
                $missing[] = ['number' => $number, 'details' => $details];
                $this->warn("  ✗ $number - {$details['name']} (MISSING)");
            }
        }

        if (!empty($missing)) {
            $this->error("\n⚠️  Missing " . count($missing) . " required accounts\n");
            
            if ($this->confirm('Create missing accounts now?')) {
                $this->createMissingAccounts($missing);
            }
        } else {
            $this->info("\n✓ All required accounts exist!");
        }

        $this->info("\n📊 Summary:");
        $this->info("  Found: " . count($found));
        $this->info("  Missing: " . count($missing));

        return 0;
    }

    private function createMissingAccounts(array $missing)
    {
        $bar = $this->output->createProgressBar(count($missing));

        foreach ($missing as $account) {
            GlAccount::create([
                'account_number' => $account['number'],
                'account_name' => $account['details']['name'],
                'account_type' => $account['details']['type'],
                'is_active' => true,
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✓ Created " . count($missing) . " accounts");
    }
}
