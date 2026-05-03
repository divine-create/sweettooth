<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->string('component_type')->nullable()->after('item_id');
            $table->unsignedBigInteger('component_id')->nullable()->after('component_type');
            $table->foreign('component_id')->references('id')->on('recipes')->onDelete('set null');
            $table->index('component_id', 'idx_recipe_ingredients_component');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropIndex('idx_recipe_ingredients_component');
            $table->dropForeign(['component_id']);
            $table->dropColumn(['component_type', 'component_id']);
        });
    }
};