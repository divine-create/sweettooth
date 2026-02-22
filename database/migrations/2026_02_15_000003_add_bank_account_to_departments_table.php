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
            if (! Schema::hasColumn('departments', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('cash_account_id');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->index('bank_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'bank_account_id')) {
                $table->dropForeignKeyIfExists(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });
    }
};
