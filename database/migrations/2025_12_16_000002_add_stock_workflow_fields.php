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
        Schema::table('product_stocks', function (Blueprint $table) {
            // Add workflow verification tracking (check if not exists)
            if (! Schema::hasColumn('product_stocks', 'is_workflow_verified')) {
                $table->boolean('is_workflow_verified')->default(false)->after('notes');
            }
            if (! Schema::hasColumn('product_stocks', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_workflow_verified');
            }
            if (! Schema::hasColumn('product_stocks', 'verified_by')) {
                $table->uuid('verified_by')->nullable()->after('verified_at');
            }

            // Add workflow step tracking (check if not exists)
            if (! Schema::hasColumn('product_stocks', 'workflow_step')) {
                $table->enum('workflow_step', [
                    'opening_pending',
                    'opening_verified',
                    'closing_pending',
                    'closing_completed',
                ])->default('opening_pending')->after('verified_by');
            }

            // Foreign key constraint - using users table after unification
            if (! Schema::hasColumn('product_stocks', 'verified_by')) {
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            }

            // Add indexes (Laravel will handle duplicates gracefully)
            $table->index(['stock_date', 'shift_type', 'is_workflow_verified'], 'product_stocks_workflow_idx');
            $table->index(['product_id', 'stock_date', 'workflow_step'], 'product_stocks_product_workflow_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // Drop indexes (Laravel handles if they don't exist)
            try {
                $table->dropIndex('product_stocks_workflow_idx');
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            try {
                $table->dropIndex('product_stocks_product_workflow_idx');
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Drop foreign key if column exists
            if (Schema::hasColumn('product_stocks', 'verified_by')) {
                try {
                    $table->dropForeign(['verified_by']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }

            // Drop columns if they exist
            $columnsToDrop = [];
            foreach (['is_workflow_verified', 'verified_at', 'verified_by', 'workflow_step'] as $column) {
                if (Schema::hasColumn('product_stocks', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
