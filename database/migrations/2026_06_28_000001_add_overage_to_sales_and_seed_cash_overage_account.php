<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Over/short ("overage") support at POS.
 *
 * When a customer pays more than the bill and does not take the change, the
 * extra is booked as income to a dedicated "Cash Overage Income" account so the
 * books still balance (Accounts Receivable nets to zero per sale) and the cash
 * drawer reconciliation can separate true product-sale cash from overage cash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'amount_tendered')) {
                $table->decimal('amount_tendered', 10, 2)->nullable()->after('total');
            }
            if (! Schema::hasColumn('sales', 'change_given')) {
                $table->decimal('change_given', 10, 2)->default(0)->after('amount_tendered');
            }
            if (! Schema::hasColumn('sales', 'over_short_amount')) {
                $table->decimal('over_short_amount', 10, 2)->default(0)->after('change_given');
            }
            if (! Schema::hasColumn('sales', 'over_short_disposition')) {
                // null = no over/short; 'change' = change handed back; 'overage' = kept as income
                $table->string('over_short_disposition', 20)->nullable()->after('over_short_amount');
            }
            if (! Schema::hasColumn('sales', 'over_short_gl_status')) {
                $table->string('over_short_gl_status', 20)->nullable()->after('over_short_disposition');
            }
        });

        // Create the Cash Overage Income account (revenue) if it doesn't exist.
        $account = DB::table('gl_accounts')->where('account_number', '4020')->first();
        if (! $account) {
            $accountId = DB::table('gl_accounts')->insertGetId([
                'account_number' => '4020',
                'account_name' => 'Cash Overage Income',
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
                'is_header' => false,
                'is_active' => true,
                'allow_manual_entry' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $accountId = $account->id;
        }

        // Map the 'cash_overage' accounting key to it for every branch that already
        // has defaults configured (branch_id is NOT NULL in this schema), so existing
        // installs resolve the key without manual setup.
        $branchIds = DB::table('branch_accounting_defaults')
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $exists = DB::table('branch_accounting_defaults')
                ->where('key', 'cash_overage')
                ->where('branch_id', $branchId)
                ->exists();

            if (! $exists) {
                DB::table('branch_accounting_defaults')->insert([
                    'branch_id' => $branchId,
                    'key' => 'cash_overage',
                    'gl_account_id' => $accountId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('branch_accounting_defaults')->where('key', 'cash_overage')->delete();
        DB::table('gl_accounts')->where('account_number', '4020')->delete();

        Schema::table('sales', function (Blueprint $table) {
            foreach ([
                'over_short_gl_status',
                'over_short_disposition',
                'over_short_amount',
                'change_given',
                'amount_tendered',
            ] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
