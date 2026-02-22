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
        Schema::create('branch_inventory_managements', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('local_stock', 50)->nullable(); // 'add,edit,view'
            $table->string('stock_adjustment', 50)->nullable(); // 'local,approval'
            $table->string('purchase_returns', 50)->nullable(); // 'submit'
            $table->string('supplier_link', 50)->nullable(); // 'view'
            $table->string('low_stock_alert', 50)->nullable(); // 'customize'
            $table->string('csv_import', 50)->nullable(); // 'local'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_inventory_managements');
    }
};
