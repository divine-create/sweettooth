<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->uuid('employee_id');
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('payment_date')->nullable();
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('overtime_rate', 14, 2)->default(0);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('tax_deductions', 14, 2)->default(0);
            $table->decimal('gross_salary', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
            $table->text('gl_posting_error')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->enum('gl_payment_posting_status', ['pending', 'posted', 'failed'])->default('pending');
            $table->text('gl_payment_posting_error')->nullable();
            $table->timestamp('gl_payment_posted_at')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'employee_id']);
            $table->index(['pay_period_start', 'pay_period_end']);
            $table->index('status');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
