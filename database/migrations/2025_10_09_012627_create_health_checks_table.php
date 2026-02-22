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
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->uuid('checked_by_id');
            $table->string('checked_by_type');
            // $table->foreign('checked_by')->references('id')->on('employees')->onDelete('restrict');
            $table->date('check_date');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged', 'expired']);
            $table->decimal('quantity_affected', 12, 2)->nullable();
            $table->text('observations')->nullable();
            $table->text('action_taken')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
};
