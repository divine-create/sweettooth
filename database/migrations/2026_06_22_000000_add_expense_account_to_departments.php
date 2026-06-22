<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-department expense account for material/consumable dispatches to
 * non-production departments (e.g. Cleaning consuming cleaning agents).
 *
 * Also back-fills a global `consumables_expense` default in
 * branch_accounting_defaults (mirroring whatever `general_expense` resolves to)
 * so dispatch posting always resolves an account even before a department is
 * mapped — keeping the system turnkey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'expense_account_id')) {
                $table->unsignedBigInteger('expense_account_id')->nullable()->after('cash_account_id');
                $table->foreign('expense_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            }
        });

        // Seed a fallback `consumables_expense` default per existing
        // `general_expense` mapping (branch-specific and global rows), only when
        // one isn't already present. Done with raw rows so it works on prod.
        if (Schema::hasTable('branch_accounting_defaults')) {
            $generalDefaults = DB::table('branch_accounting_defaults')
                ->where('key', 'general_expense')
                ->get();

            foreach ($generalDefaults as $row) {
                $exists = DB::table('branch_accounting_defaults')
                    ->where('key', 'consumables_expense')
                    ->where('branch_id', $row->branch_id)
                    ->exists();

                if (! $exists && $row->gl_account_id) {
                    DB::table('branch_accounting_defaults')->insert([
                        'branch_id' => $row->branch_id,
                        'key' => 'consumables_expense',
                        'gl_account_id' => $row->gl_account_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branch_accounting_defaults')) {
            DB::table('branch_accounting_defaults')->where('key', 'consumables_expense')->delete();
        }

        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'expense_account_id')) {
                $table->dropForeign(['expense_account_id']);
                $table->dropColumn('expense_account_id');
            }
        });
    }
};
