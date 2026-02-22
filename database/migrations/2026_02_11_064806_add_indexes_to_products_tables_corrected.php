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
        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            $table->index(['branch_id', 'is_active'], 'idx_products_branch_active');
            $table->index(['product_type_id', 'is_active'], 'idx_products_type_active');
            $table->index(['is_active', 'is_available'], 'idx_products_status');
            $table->index(['name', 'sku'], 'idx_products_name_sku');
            $table->index('created_at', 'idx_products_created_at');
            
            // Composite index for common filter combinations
            $table->index(['branch_id', 'product_type_id', 'is_active'], 'idx_products_branch_type_active');
        });
        
        // Add indexes to product_types table (using correct column names)
        Schema::table('product_types', function (Blueprint $table) {
            $table->index(['department_id', 'status'], 'idx_product_types_dept_status');
            $table->index('status', 'idx_product_types_status');
            $table->index('sort_order', 'idx_product_types_sort_order');
        });
        
        // Add indexes to departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->index(['slug', 'category_id'], 'idx_departments_slug_category');
        });
        
        // Add indexes to units_of_measure table
        Schema::table('units_of_measure', function (Blueprint $table) {
            $table->index('name', 'idx_uom_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['idx_products_branch_active']);
            $table->dropIndex(['idx_products_type_active']);
            $table->dropIndex(['idx_products_status']);
            $table->dropIndex(['idx_products_name_sku']);
            $table->dropIndex(['idx_products_created_at']);
            $table->dropIndex(['idx_products_branch_type_active']);
        });
        
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropIndex(['idx_product_types_dept_status']);
            $table->dropIndex(['idx_product_types_status']);
            $table->dropIndex(['idx_product_types_sort_order']);
        });
        
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['idx_departments_slug_category']);
        });
        
        Schema::table('units_of_measure', function (Blueprint $table) {
            $table->dropIndex(['idx_uom_name']);
        });
    }
};
