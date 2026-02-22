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
        Schema::table('item_request_details', function (Blueprint $table) {
            // Drop the enum column
            $table->dropColumn('uom');
        });

        Schema::table('item_request_details', function (Blueprint $table) {
            // Add foreign key to units_of_measure
            $table->unsignedBigInteger('uom_id')->after('quantity_dispatched');
            $table->foreign('uom_id')
                ->references('id')
                ->on('units_of_measure')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_request_details', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['uom_id']);
            $table->dropColumn('uom_id');
        });

        Schema::table('item_request_details', function (Blueprint $table) {
            // Restore enum column
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units', 'bags', 'cartons'])->after('quantity_dispatched');
        });
    }
};
