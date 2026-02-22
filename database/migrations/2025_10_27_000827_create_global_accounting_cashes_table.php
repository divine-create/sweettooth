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
        Schema::create('global_accounting_cashes', function (Blueprint $table) {
            $table->id();
            $table->json('expenses_categories')->default(json_encode(['add', 'edit']));
            $table->string('cash_bank', 50)->default('enabled');
            $table->string('profit_loss_reports', 50)->default('by_date');
            $table->string('accounting_entries', 50)->default('auto');
            $table->timestamps();
        });

        Schema::create('branch_accounting_cashes', function (Blueprint $table) {
            $table->id();
            $table->string('local_expenses', 50)->nullable(); // 'add'
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('cash_transactions', 50)->nullable(); // 'track'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_accounting_cashes');
        Schema::dropIfExists('global_accounting_cashes');
    }
};
