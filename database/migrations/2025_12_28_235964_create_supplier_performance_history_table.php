<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_performance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->decimal('on_time_delivery_percentage', 5, 2)->nullable();
            $table->decimal('quality_rating', 3, 1)->nullable(); // 0-5 star rating
            $table->decimal('price_competitiveness', 3, 1)->nullable(); // 0-5 star rating
            $table->decimal('communication_rating', 3, 1)->nullable(); // 0-5 star rating
            $table->integer('lead_time_days')->nullable();
            $table->decimal('overall_score', 5, 2)->nullable(); // Calculated overall score
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('supplier_id');
            $table->index('record_date');
            $table->unique(['supplier_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_history');
    }
};
