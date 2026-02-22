<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_production_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_production_request_items', 'recipe_id')) {
                $table->unsignedBigInteger('recipe_id')->nullable()->after('production_department_id');
                $table->foreign('recipe_id')
                    ->references('id')
                    ->on('recipes')
                    ->onDelete('set null');
                $table->index('recipe_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_production_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_production_request_items', 'recipe_id')) {
                $table->dropForeign(['recipe_id']);
                $table->dropIndex(['recipe_id']);
                $table->dropColumn('recipe_id');
            }
        });
    }
};

