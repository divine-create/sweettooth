<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('accounting_period_id');
            $table->string('entry_type')->default('general');
            $table->date('entry_date');
            $table->string('description');
            $table->string('reference')->nullable();
            $table->decimal('amount', 14, 2);
            $table->unsignedBigInteger('debit_gl_account_id');
            $table->unsignedBigInteger('credit_gl_account_id');
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('entry_date');
            $table->index('entry_type');
            $table->index('debit_gl_account_id');
            $table->index('credit_gl_account_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('restrict');
            $table->foreign('debit_gl_account_id')->references('id')->on('gl_accounts')->onDelete('restrict');
            $table->foreign('credit_gl_account_id')->references('id')->on('gl_accounts')->onDelete('restrict');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
