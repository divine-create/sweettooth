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
        Schema::create('daily_bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_bank_position_id');
            $table->foreign('daily_bank_position_id')->references('id')->on('daily_bank_positions')->onDelete('cascade');
            $table->unsignedBigInteger('bank_account_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->enum('transaction_type', ['inflow', 'outflow']);
            $table->enum('transaction_subtype', [
                'pos_sales',
                'transfer_sales',
                'reversed_transfer',
                'other_income',
                'cash_deposit',
                'transfer_expense',
                'cash_withdrawal',
                'chargeback',
                'bank_charge',
                'other',
            ]);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->dateTime('transaction_date');
            $table->dateTime('cleared_date')->nullable();
            $table->enum('status', ['pending', 'cleared', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['transaction_type', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_bank_transactions');
    }
};
