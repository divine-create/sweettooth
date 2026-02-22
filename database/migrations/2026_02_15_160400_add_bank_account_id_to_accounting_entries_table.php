<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('accounting_entries', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('credit_gl_account_id');
                $table->index('bank_account_id');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_entries', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropIndex(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });
    }
};
