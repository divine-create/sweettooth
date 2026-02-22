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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_shift_id')->nullable();
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('set null');
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->uuid('sold_by_id');
            $table->string('sold_by_type');
            $table->string('sale_number')->unique();
            $table->timestamp('sale_time');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded', 'hold'])->default('completed');
            $table->enum('order_type', ['dine-in', 'takeaway', 'delivery', 'dine_in', 'glovo', 'transfer'])->default('dine_in');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'department_id', 'sale_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
