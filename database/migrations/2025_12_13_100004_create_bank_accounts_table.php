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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('bank_code')->nullable();
            $table->string('account_number')->unique();
            $table->enum('account_type', ['checking', 'savings', 'money_market'])->default('checking');
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->onDelete('set null');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_active', 'bank_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
