<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('gl_account_id');
            $table->date('entry_date');
            $table->string('description');
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('department_id');
            $table->index('bank_account_id');
            $table->index('gl_account_id');
            $table->index('entry_date');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->onDelete('restrict');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_entries');
    }
};
