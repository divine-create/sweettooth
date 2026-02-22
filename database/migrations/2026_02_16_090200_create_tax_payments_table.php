<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->string('tax_type')->default('general');
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
            $table->text('gl_posting_error')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'payment_date']);
            $table->index(['tax_type', 'status']);

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_payments');
    }
};
