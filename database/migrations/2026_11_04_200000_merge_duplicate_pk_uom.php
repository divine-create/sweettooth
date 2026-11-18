<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $uom15 = DB::table('units_of_measure')->where('id', 15)->first();
        $uom32 = DB::table('units_of_measure')->where('id', 32)->first();

        if ($uom15 && $uom32) {
            // Update items
            if (Schema::hasTable('items') && Schema::hasColumn('items', 'uom_id')) {
                DB::table('items')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update products
            if (Schema::hasTable('products') && Schema::hasColumn('products', 'uom_id')) {
                DB::table('products')->where('uom_id', 32)->update(['uom_id' => 15]);
            }
            if (Schema::hasTable('products') && Schema::hasColumn('products', 'sales_uom_id')) {
                DB::table('products')->where('sales_uom_id', 32)->update(['sales_uom_id' => 15]);
            }

            // Update recipe_ingredients
            if (Schema::hasTable('recipe_ingredients') && Schema::hasColumn('recipe_ingredients', 'uom_id')) {
                DB::table('recipe_ingredients')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update recipes
            if (Schema::hasTable('recipes') && Schema::hasColumn('recipes', 'uom_id')) {
                DB::table('recipes')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update material_request_details
            if (Schema::hasTable('material_request_details') && Schema::hasColumn('material_request_details', 'uom_id')) {
                DB::table('material_request_details')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update material_request_dispatches
            if (Schema::hasTable('material_request_dispatches') && Schema::hasColumn('material_request_dispatches', 'uom_id')) {
                DB::table('material_request_dispatches')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update sale_items
            if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'sales_uom_id')) {
                DB::table('sale_items')->where('sales_uom_id', 32)->update(['sales_uom_id' => 15]);
            }

            // Update purchase_items if uom_id column exists
            if (Schema::hasTable('purchase_items') && Schema::hasColumn('purchase_items', 'uom_id')) {
                DB::table('purchase_items')->where('uom_id', 32)->update(['uom_id' => 15]);
            }

            // Update uom_conversions if any
            if (Schema::hasTable('uom_conversions')) {
                if (Schema::hasColumn('uom_conversions', 'from_uom_id')) {
                    DB::table('uom_conversions')->where('from_uom_id', 32)->update(['from_uom_id' => 15]);
                }
                if (Schema::hasColumn('uom_conversions', 'to_uom_id')) {
                    DB::table('uom_conversions')->where('to_uom_id', 32)->update(['to_uom_id' => 15]);
                }
            }

            // Deactivate and rename duplicate UOM 32
            DB::table('units_of_measure')->where('id', 32)->update([
                'is_active' => false,
                'code' => 'pk_merged_15',
                'description' => 'Merged into UOM ID 15 (Packs)',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert UOM 32 active status
        DB::table('units_of_measure')->where('id', 32)->update([
            'is_active' => true,
            'code' => 'pk',
            'description' => null,
            'updated_at' => now(),
        ]);
    }
};
