<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inter-sales-point settlements (SALES_POINT_TRANSFER_SPEC.md §3.4, Phase 3).
 *
 * When a sale is tendered at one sales point (the "collecting" point) but a line's product
 * belongs to another sales point (its "home"), the collecting point holds cash that belongs
 * to the home point. Each such line records a settlement: the collecting point OWES the home
 * point the line amount. These feed drawer reconciliation (home point sees a receivable, the
 * collecting point a payable) and are settled net in the books — no physical cash moves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_point_settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();

            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->uuid('product_id')->nullable();

            $table->unsignedBigInteger('owed_by_department_id')->comment('Collecting point holding the cash');
            $table->unsignedBigInteger('owed_to_department_id')->comment('Home point owed the revenue');

            $table->decimal('amount', 12, 2);
            $table->enum('status', ['open', 'settled'])->default('open');
            $table->timestamp('settled_at')->nullable();

            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('owed_by_department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('owed_to_department_id')->references('id')->on('departments')->onDelete('cascade');

            $table->index(['owed_to_department_id', 'status'], 'idx_sps_owed_to_status');
            $table->index(['owed_by_department_id', 'status'], 'idx_sps_owed_by_status');
            $table->index('sale_id', 'idx_sps_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_point_settlements');
    }
};
