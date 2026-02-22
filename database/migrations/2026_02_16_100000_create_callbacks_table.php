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
        Schema::create('callbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('reason', 50);
            $table->date('callback_date')->nullable();
            $table->string('shift_type')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();

            $table->index(['branch_id', 'callback_date']);
            $table->index(['department_id', 'callback_date']);
            $table->index('product_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callbacks');
    }
};
