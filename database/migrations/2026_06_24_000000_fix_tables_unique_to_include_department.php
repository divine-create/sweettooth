<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table numbers are unique per branch+department in the application (POS and
     * Table Management both check branch_id + department_id + table_number), but the
     * original unique index only covered (branch_id, table_number). That caused a
     * 1062 duplicate-entry error when the same table number existed in another
     * department of the same branch. Re-scope the unique index to include department.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tables')) {
            return;
        }

        if ($this->indexExists('tables', 'tables_branch_id_table_number_unique')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->dropUnique('tables_branch_id_table_number_unique');
            });
        }

        if (! $this->indexExists('tables', 'tables_branch_id_department_id_table_number_unique')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->unique(['branch_id', 'department_id', 'table_number']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tables')) {
            return;
        }

        if ($this->indexExists('tables', 'tables_branch_id_department_id_table_number_unique')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->dropUnique('tables_branch_id_department_id_table_number_unique');
            });
        }

        if (! $this->indexExists('tables', 'tables_branch_id_table_number_unique')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->unique(['branch_id', 'table_number']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
