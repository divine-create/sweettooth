<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales-point transfers: move a produced PRODUCT's on-hand from one sales point
 * (Department under the "Sales" category) to another. See SALES_POINT_TRANSFER_SPEC.md.
 *
 * Effect when completed: the FROM point's ProductStock.transfer_quantity += qty (its
 * available drops) and the TO point's ProductStock.addition_quantity += qty (its available
 * rises). Both columns already feed the availability formula used across POS / stock-opening
 * / shift-closing, so no formula change is needed — only this record + the two stock writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_point_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();

            $table->unsignedBigInteger('from_department_id');
            $table->unsignedBigInteger('to_department_id');
            $table->uuid('product_id');

            $table->decimal('quantity', 12, 2)->comment('Base UOM quantity moved');
            $table->decimal('unit_cost', 15, 4)->default(0)->comment('Snapshot of product cost for valuation');

            // Sale-linked (Phase 2) transfers carry the sale; standalone ones are null.
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->enum('transfer_type', ['rebalance', 'sale', 'return'])->default('rebalance');
            $table->enum('status', ['pending', 'completed', 'rejected', 'reversed'])->default('completed');

            // The ProductStock rows / shifts actually touched, kept for reversal + audit.
            $table->unsignedBigInteger('from_shift_id')->nullable();
            $table->unsignedBigInteger('to_shift_id')->nullable();
            $table->unsignedBigInteger('from_product_stock_id')->nullable();
            $table->unsignedBigInteger('to_product_stock_id')->nullable();

            $table->text('notes')->nullable();

            $table->uuid('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('from_department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('to_department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');

            $table->index(['to_department_id', 'status'], 'idx_spt_to_dept_status');
            $table->index(['from_department_id', 'status'], 'idx_spt_from_dept_status');
            $table->index('product_id', 'idx_spt_product');
            $table->index('sale_id', 'idx_spt_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_point_transfers');
    }
};
