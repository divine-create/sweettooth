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
        Schema::create('gl_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('accounting_period_id');
            $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('cascade');
            $table->enum('entry_type', [
                'sale',
                'sale_cogs',
                'sale_tax',
                'purchase',
                'payment',
                'adjustment',
                'manual',
                'reversal',
            ]);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->dateTime('entry_date');
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->uuid('entered_by_id')->nullable();
            $table->string('entered_by_type')->nullable();
            $table->uuid('posted_by_id')->nullable();
            $table->string('posted_by_type')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->uuid('reversed_by_id')->nullable();
            $table->string('reversed_by_type')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->string('cost_center')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['gl_account_id', 'entry_date']);
            $table->index(['accounting_period_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['entry_date', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_entries');
    }
};
