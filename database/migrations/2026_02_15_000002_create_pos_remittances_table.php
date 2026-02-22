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
        Schema::create('pos_remittances', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->timestamp('remitted_at');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('bank_account_id');
            $table->index('remitted_at');
            $table->index('created_by_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_remittances');
    }
};
