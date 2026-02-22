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
        Schema::create('item_dispatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->foreign('request_id')->references('id')->on('item_requests')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->uuid('dispatched_by_id');
            $table->string('dispatched_by_type');
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->uuid('received_by_id')->nullable();
            $table->string('received_by_type')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units', 'bags', 'cartons']);
            $table->timestamp('dispatch_time');
            $table->timestamp('received_time')->nullable();
            $table->enum('shift', ['morning', 'afternoon', 'night'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_dispatches');
    }
};
