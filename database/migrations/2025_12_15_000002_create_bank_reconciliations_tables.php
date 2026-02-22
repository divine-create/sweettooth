<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create bank reconciliation tracking tables
     */
    public function up(): void
    {
        // Bank reconciliation sessions
        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->unsignedBigInteger('bank_account_id');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
                $table->date('reconciliation_date');
                $table->decimal('bank_balance', 15, 2);
                $table->decimal('book_balance', 15, 2);
                $table->decimal('difference', 15, 2)->default(0);
                $table->enum('status', ['draft', 'in_progress', 'completed', 'verified'])->default('draft');
                $table->timestamp('completed_at')->nullable();
                $table->uuid('completed_by_id')->nullable();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['bank_account_id', 'reconciliation_date']);
                $table->index(['status']);
            });
        }

        // Matched pairs - GL entries matched with bank transactions
        if (! Schema::hasTable('bank_reconciliation_details')) {
            Schema::create('bank_reconciliation_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_reconciliation_id');
                $table->foreign('bank_reconciliation_id')->references('id')->on('bank_reconciliations')->onDelete('cascade');
                $table->unsignedBigInteger('gl_entry_id')->nullable();
                $table->foreign('gl_entry_id')->references('id')->on('gl_entries')->onDelete('set null');
                $table->unsignedBigInteger('daily_bank_transaction_id')->nullable();
                $table->foreign('daily_bank_transaction_id')->references('id')->on('daily_bank_transactions')->onDelete('set null');
                $table->decimal('matched_amount', 15, 2);
                $table->timestamp('matched_at');
                $table->uuid('matched_by_id')->nullable();
                $table->enum('match_type', ['auto', 'manual'])->default('manual');
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['bank_reconciliation_id']);
                $table->index(['gl_entry_id']);
                $table->index(['daily_bank_transaction_id']);
            });
        }

        // Unmatched items audit log
        if (! Schema::hasTable('bank_reconciliation_unmatched')) {
            Schema::create('bank_reconciliation_unmatched', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_reconciliation_id');
                $table->foreign('bank_reconciliation_id')->references('id')->on('bank_reconciliations')->onDelete('cascade');
                $table->enum('item_type', ['gl_entry', 'bank_transaction']);
                $table->unsignedBigInteger('item_id');
                $table->decimal('amount', 15, 2);
                $table->date('transaction_date');
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->text('notes')->nullable();
                $table->enum('resolution_status', ['pending', 'resolved', 'ignored'])->default('pending');
                $table->timestamp('resolved_at')->nullable();
                $table->uuid('resolved_by_id')->nullable();
                $table->timestamps();
                $table->index(['bank_reconciliation_id']);
                $table->index(['item_type']);
                $table->index(['resolution_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_unmatched');
        Schema::dropIfExists('bank_reconciliation_details');
        Schema::dropIfExists('bank_reconciliations');
    }
};
