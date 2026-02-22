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
        Schema::create('cash_positions', function (Blueprint $table) {
            $table->id();
            $table->enum('cash_type', ['sales_cash', 'petty_cash', 'drawer_cash']);
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->date('position_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('sales_receipts', 15, 2)->default(0);
            $table->decimal('withdrawals', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->decimal('physical_count', 15, 2)->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->text('variance_notes')->nullable();
            $table->uuid('counted_by_id')->nullable();
            $table->string('counted_by_type')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['cash_type', 'branch_id', 'position_date']);
            $table->index(['position_date', 'cash_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_positions');
    }
};
