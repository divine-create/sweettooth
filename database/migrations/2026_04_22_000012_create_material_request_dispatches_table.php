<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_dispatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('item_id');
            $table->uuid('dispatched_by_id')->nullable();
            $table->string('dispatched_by_type')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->timestamp('dispatch_time');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('material_requests')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->index('request_id', 'idx_mrdisp_request');
            $table->index('item_id', 'idx_mrdisp_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_dispatches');
    }
};