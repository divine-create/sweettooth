<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('gl_account_id');
            $table->unsignedBigInteger('accounting_period_id');
            $table->decimal('planned_amount', 14, 2);
            $table->decimal('forecast_amount', 14, 2)->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->text('description')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'accounting_period_id']);
            $table->index(['gl_account_id', 'accounting_period_id']);
            $table->index('is_approved');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->onDelete('cascade');
            $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
