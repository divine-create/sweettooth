<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add bank account references to payments, sales, and other transaction models
     */
    public function up(): void
    {
        // Add bank_account_id to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->index('bank_account_id');
            }
        });

        // Add bank_account_id to sales table (for POS/bank deposits)
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('department_id');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->index('bank_account_id');
            }
        });

        // Add bank_account_id to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->index('bank_account_id');
            }
        });

        // Add bank_account_id to stock_movements table (for receiving/shipping)
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable();
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
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'bank_account_id')) {
                $table->dropForeignKeyIfExists(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'bank_account_id')) {
                $table->dropForeignKeyIfExists(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'bank_account_id')) {
                $table->dropForeignKeyIfExists(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bank_account_id')) {
                $table->dropForeignKeyIfExists(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
        });
    }
};
