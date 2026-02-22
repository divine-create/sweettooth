<?php

namespace Database\Seeders;

use App\Models\GlAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlAccountSeeder extends Seeder
{
    public function run(): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('gl_accounts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $accounts = [
            // Assets
            ['account_number' => '1000', 'account_name' => 'Assets', 'account_type' => 'asset', 'is_header' => true],
            ['account_number' => '1010', 'account_name' => 'Cash', 'account_type' => 'asset'],
            ['account_number' => '1050', 'account_name' => 'Bank', 'account_type' => 'asset'],
            ['account_number' => '1060', 'account_name' => 'POS Clearing', 'account_type' => 'asset'],
            ['account_number' => '1100', 'account_name' => 'Accounts Receivable', 'account_type' => 'asset'],
            ['account_number' => '1200', 'account_name' => 'Inventory', 'account_type' => 'asset'],
            ['account_number' => '1220', 'account_name' => 'Inventory - Sales', 'account_type' => 'asset'],
            ['account_number' => '1500', 'account_name' => 'Fixed Assets', 'account_type' => 'asset'],
            ['account_number' => '1510', 'account_name' => 'Accumulated Depreciation', 'account_type' => 'asset', 'normal_balance' => 'credit'],

            // Liabilities
            ['account_number' => '2000', 'account_name' => 'Liabilities', 'account_type' => 'liability', 'is_header' => true],
            ['account_number' => '2010', 'account_name' => 'Accounts Payable', 'account_type' => 'liability'],
            ['account_number' => '2110', 'account_name' => 'Payroll Payable', 'account_type' => 'liability'],
            ['account_number' => '2120', 'account_name' => 'Payroll Tax Payable', 'account_type' => 'liability'],
            ['account_number' => '2130', 'account_name' => 'Other Deductions Payable', 'account_type' => 'liability'],
            ['account_number' => '2020', 'account_name' => 'Sales Tax Payable', 'account_type' => 'tax'],
            ['account_number' => '2100', 'account_name' => 'Tax Payable', 'account_type' => 'tax'],

            // Revenue
            ['account_number' => '4000', 'account_name' => 'Revenue', 'account_type' => 'revenue', 'is_header' => true],
            ['account_number' => '4010', 'account_name' => 'Sales Revenue', 'account_type' => 'revenue'],

            // COGS / Expenses
            ['account_number' => '5000', 'account_name' => 'Cost of Goods Sold', 'account_type' => 'cost_of_goods_sold', 'is_header' => true],
            ['account_number' => '5010', 'account_name' => 'COGS', 'account_type' => 'cost_of_goods_sold'],
            ['account_number' => '5020', 'account_name' => 'Damage Loss', 'account_type' => 'expense'],
            ['account_number' => '5030', 'account_name' => 'Shrinkage Loss', 'account_type' => 'expense'],
            ['account_number' => '5040', 'account_name' => 'Write-off Loss', 'account_type' => 'expense'],
            ['account_number' => '5050', 'account_name' => 'Inventory Adjustment', 'account_type' => 'expense'],
            ['account_number' => '6100', 'account_name' => 'Salaries & Wages', 'account_type' => 'expense'],
            ['account_number' => '6200', 'account_name' => 'Depreciation Expense', 'account_type' => 'expense'],

            // Operating Expenses
            ['account_number' => '6300', 'account_name' => 'Travel Expenses', 'account_type' => 'expense'],
            ['account_number' => '6310', 'account_name' => 'Meals & Entertainment', 'account_type' => 'expense'],
            ['account_number' => '6320', 'account_name' => 'Office Supplies', 'account_type' => 'expense'],
            ['account_number' => '6330', 'account_name' => 'Communication', 'account_type' => 'expense'],
            ['account_number' => '6340', 'account_name' => 'Accommodation', 'account_type' => 'expense'],
            ['account_number' => '6350', 'account_name' => 'Professional Services', 'account_type' => 'expense'],
            ['account_number' => '6900', 'account_name' => 'Other Expenses', 'account_type' => 'expense'],
        ];

        $count = 0;
        foreach ($accounts as $account) {
            $normalBalance = $account['normal_balance']
                ?? (in_array($account['account_type'], ['asset', 'expense', 'cost_of_goods_sold']) ? 'debit' : 'credit');

            GlAccount::create([
                'account_number' => $account['account_number'],
                'account_name' => $account['account_name'],
                'account_type' => $account['account_type'],
                'account_category' => $account['account_category'] ?? null,
                'description' => $account['description'] ?? null,
                'normal_balance' => $normalBalance,
                'is_header' => $account['is_header'] ?? false,
                'is_active' => true,
                'allow_manual_entry' => !($account['is_header'] ?? false),
            ]);
            $count++;
        }

        return $count;
    }
}
