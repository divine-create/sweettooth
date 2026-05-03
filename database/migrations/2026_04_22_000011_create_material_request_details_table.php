<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_requested', 12, 2);
            $table->decimal('quantity_approved', 12, 2)->default(0);
            $table->decimal('quantity_dispatched', 12, 2)->default(0);
            $table->unsignedBigInteger('uom_id');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('material_requests')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->index('request_id', 'idx_mrd_request');
            $table->index('item_id', 'idx_mrd_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_details');
    }
};