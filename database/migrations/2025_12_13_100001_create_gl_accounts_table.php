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
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('account_name');
            $table->enum('account_type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'cost_of_goods_sold',
                'expense',
                'other_income',
                'other_expense',
                'tax',
            ]);
            $table->string('account_category')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit_balance', 15, 2)->default(0);
            $table->decimal('credit_balance', 15, 2)->default(0);
            $table->enum('normal_balance', ['debit', 'credit'])->default('debit');
            $table->boolean('is_header')->default(false);
            $table->unsignedBigInteger('parent_account_id')->nullable();
            $table->foreign('parent_account_id')->references('id')->on('gl_accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_entry')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['account_type', 'is_active']);
            $table->index(['parent_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_accounts');
    }
};
