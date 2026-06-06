<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting periods are scoped per branch, but the original create migration
     * never added the branch_id column (it exists in local DBs from manual edits
     * but is missing on freshly-migrated environments such as production). The
     * PeriodControl component inserts branch_id, so without this column creating a
     * period throws "Unknown column 'branch_id'".
     *
     * This also replaces the global (year, month) unique key with a per-branch
     * (branch_id, year, month) key, so different branches can hold the same period.
     */
    public function up(): void
    {
        if (! Schema::hasTable('accounting_periods')) {
            return;
        }

        if (! Schema::hasColumn('accounting_periods', 'branch_id')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                $table->uuid('branch_id')->nullable()->after('id');
                $table->index('branch_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }

        // Swap the global year/month unique for a per-branch one.
        $this->dropIndexIfExists('accounting_periods', 'accounting_periods_year_month_unique');

        if (! $this->indexExists('accounting_periods', 'accounting_periods_branch_id_year_month_unique')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                $table->unique(['branch_id', 'year', 'month'], 'accounting_periods_branch_id_year_month_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounting_periods')) {
            return;
        }

        $this->dropIndexIfExists('accounting_periods', 'accounting_periods_branch_id_year_month_unique');

        if (! $this->indexExists('accounting_periods', 'accounting_periods_year_month_unique')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                $table->unique(['year', 'month'], 'accounting_periods_year_month_unique');
            });
        }

        if (Schema::hasColumn('accounting_periods', 'branch_id')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($index);
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $tbl) use ($index) {
                $tbl->dropUnique($index);
            });
        }
    }
};
