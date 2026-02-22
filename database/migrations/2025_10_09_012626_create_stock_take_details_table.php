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
        Schema::create('stock_take_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_take_id');
            $table->foreign('stock_take_id')->references('id')->on('stock_takes')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('physical_quantity', 12, 2);
            $table->decimal('variance', 12, 2);
            $table->enum('variance_type', ['surplus', 'shortage', 'match'])->default('match');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_take_details');
    }
};
