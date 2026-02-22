<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'revenue_account_id')) {
                $table->unsignedBigInteger('revenue_account_id')->nullable();
                $table->foreign('revenue_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('departments', 'tax_account_id')) {
                $table->unsignedBigInteger('tax_account_id')->nullable();
                $table->foreign('tax_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('departments', 'receivable_account_id')) {
                $table->unsignedBigInteger('receivable_account_id')->nullable();
                $table->foreign('receivable_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('departments', 'cash_account_id')) {
                $table->unsignedBigInteger('cash_account_id')->nullable();
                $table->foreign('cash_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            foreach ([
                'revenue_account_id',
                'tax_account_id',
                'receivable_account_id',
                'cash_account_id',
            ] as $column) {
                if (! Schema::hasColumn('departments', $column)) {
                    continue;
                }

                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore missing/renamed FK constraints.
                }

                $table->dropColumn($column);
            }
        });
    }
};

