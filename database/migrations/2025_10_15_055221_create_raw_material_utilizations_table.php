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
        Schema::create('raw_material_utilizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->unsignedBigInteger('recipe_id');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->decimal('quantity_required', 12, 4); // Per recipe unit
            $table->decimal('quantity_used', 12, 4); // Actual used
            $table->decimal('units_produced', 12, 2); // How many recipe units produced
            $table->decimal('variance', 12, 4)->default(0); // Difference
            $table->enum('variance_type', ['within_tolerance', 'over_used', 'under_used'])->default('within_tolerance');
            $table->decimal('cost_impact', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['shift_id', 'recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_utilizations');
    }
};
