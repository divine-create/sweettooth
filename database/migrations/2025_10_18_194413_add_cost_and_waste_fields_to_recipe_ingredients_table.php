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
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 10, 4)->default(0)->after('uom');
            $table->decimal('waste_percentage', 5, 2)->default(0)->after('cost_per_unit')->comment('Percentage of ingredient lost during preparation');
            $table->text('preparation_notes')->nullable()->after('notes')->comment('Specific prep instructions for this ingredient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'waste_percentage', 'preparation_notes']);
        });
    }
};
