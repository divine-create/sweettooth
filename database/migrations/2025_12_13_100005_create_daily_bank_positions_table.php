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
        Schema::create('daily_bank_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->date('position_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('inflows_total', 15, 2)->default(0);
            $table->decimal('outflows_total', 15, 2)->default(0);
            $table->decimal('unavailable_balance', 15, 2)->default(0);
            $table->decimal('unreflected_transfers_bf', 15, 2)->default(0);
            $table->decimal('unreflected_pos_bf', 15, 2)->default(0);
            $table->decimal('unreflected_transfers_cd', 15, 2)->default(0);
            $table->decimal('unreflected_pos_cd', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->text('variance_notes')->nullable();
            $table->boolean('reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['bank_account_id', 'position_date']);
            $table->index(['position_date', 'reconciled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_bank_positions');
    }
};
