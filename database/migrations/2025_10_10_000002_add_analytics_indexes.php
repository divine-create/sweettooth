<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds strategic indexes to optimize analytics queries.
     * Addresses performance issues with filtering by date, type, shift, department.
     */
    public function up(): void
    {
        // StockMovement indexes
        Schema::table('stock_movements', function (Blueprint $table) {
            // Single column indexes for common filters
            if (! $this->indexExists('stock_movements', 'stock_movements_stock_id_index')) {
                $table->index('stock_id', 'idx_movement_stock_id');
            }
            if (! $this->indexExists('stock_movements', 'stock_movements_type_index')) {
                $table->index('type', 'idx_movement_type');
            }
            if (! $this->indexExists('stock_movements', 'stock_movements_movement_date_index')) {
                $table->index('movement_date', 'idx_movement_date');
            }
            if (! $this->indexExists('stock_movements', 'stock_movements_moved_by_id_index')) {
                $table->index('moved_by_id', 'idx_movement_moved_by');
            }
            if (! $this->indexExists('stock_movements', 'stock_movements_reference_type_index')) {
                $table->index('reference_type', 'idx_movement_reference_type');
            }

            // Composite indexes for common query patterns
            if (! $this->indexExists('stock_movements', 'stock_movements_movement_date_type_index')) {
                $table->index(['movement_date', 'type'], 'idx_movement_date_type');
            }
            if (! $this->indexExists('stock_movements', 'stock_movements_stock_id_movement_date_index')) {
                $table->index(['stock_id', 'movement_date'], 'idx_movement_stock_date');
            }
            // Note: shift and department indexes already added in previous migration
        });

        // Purchase indexes
        Schema::table('purchases', function (Blueprint $table) {
            if (! $this->indexExists('purchases', 'purchases_supplier_name_index')) {
                $table->index('supplier_name', 'idx_purchase_supplier');
            }
            if (! $this->indexExists('purchases', 'purchases_payment_status_index')) {
                $table->index('payment_status', 'idx_purchase_payment_status');
            }
            if (! $this->indexExists('purchases', 'purchases_purchase_date_index')) {
                $table->index('purchase_date', 'idx_purchase_date');
            }
            if (! $this->indexExists('purchases', 'purchases_branch_id_purchase_date_index')) {
                $table->index(['branch_id', 'purchase_date'], 'idx_purchase_branch_date');
            }
            if (! $this->indexExists('purchases', 'purchases_payment_status_purchase_date_index')) {
                $table->index(['payment_status', 'purchase_date'], 'idx_purchase_status_date');
            }
        });

        // PurchaseItem indexes
        Schema::table('purchase_items', function (Blueprint $table) {
            if (! $this->indexExists('purchase_items', 'purchase_items_item_id_index')) {
                $table->index('item_id', 'idx_purchase_item_item');
            }
            if (! $this->indexExists('purchase_items', 'purchase_items_purchase_id_index')) {
                $table->index('purchase_id', 'idx_purchase_item_purchase');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if ($this->indexExists('stock_movements', 'idx_movement_stock_id')) {
                $table->dropIndex('idx_movement_stock_id');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_type')) {
                $table->dropIndex('idx_movement_type');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_date')) {
                $table->dropIndex('idx_movement_date');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_moved_by')) {
                $table->dropIndex('idx_movement_moved_by');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_reference_type')) {
                $table->dropIndex('idx_movement_reference_type');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_date_type')) {
                $table->dropIndex('idx_movement_date_type');
            }
            if ($this->indexExists('stock_movements', 'idx_movement_stock_date')) {
                $table->dropIndex('idx_movement_stock_date');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if ($this->indexExists('purchases', 'idx_purchase_supplier')) {
                $table->dropIndex('idx_purchase_supplier');
            }
            if ($this->indexExists('purchases', 'idx_purchase_payment_status')) {
                $table->dropIndex('idx_purchase_payment_status');
            }
            if ($this->indexExists('purchases', 'idx_purchase_date')) {
                $table->dropIndex('idx_purchase_date');
            }
            if ($this->indexExists('purchases', 'idx_purchase_branch_date')) {
                $table->dropIndex('idx_purchase_branch_date');
            }
            if ($this->indexExists('purchases', 'idx_purchase_status_date')) {
                $table->dropIndex('idx_purchase_status_date');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if ($this->indexExists('purchase_items', 'idx_purchase_item_item')) {
                $table->dropIndex('idx_purchase_item_item');
            }
            if ($this->indexExists('purchase_items', 'idx_purchase_item_purchase')) {
                $table->dropIndex('idx_purchase_item_purchase');
            }
        });
    }

    /**
     * Helper method to check if index exists
     */
    private function indexExists($table, $indexName)
    {
        try {
            $indexName = str_replace('idx_', '', $indexName);
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            if (method_exists($sm, 'listTableIndexes')) {
                $indexes = $sm->listTableIndexes($table);
                foreach ($indexes as $index) {
                    if (stripos($index->getName(), $indexName) !== false) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            // Fallback: assume index doesn't exist
            return false;
        }
    }
};
