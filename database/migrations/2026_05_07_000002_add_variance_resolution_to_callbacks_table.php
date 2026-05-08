<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('callbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('callbacks', 'product_stock_id')) {
                $table->unsignedBigInteger('product_stock_id')->nullable()->after('shift_type');
                $table->foreign('product_stock_id')->references('id')->on('product_stocks')->nullOnDelete();
            }
            if (! Schema::hasColumn('callbacks', 'expected_quantity')) {
                $table->decimal('expected_quantity', 12, 2)->nullable()->after('product_stock_id');
            }
            if (! Schema::hasColumn('callbacks', 'resolution_type')) {
                $table->string('resolution_type', 20)->nullable()->after('expected_quantity');
            }
            if (! Schema::hasColumn('callbacks', 'corrected_quantity')) {
                $table->decimal('corrected_quantity', 12, 2)->nullable()->after('resolution_type');
            }
            if (! Schema::hasColumn('callbacks', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('corrected_quantity');
            }
            if (! Schema::hasColumn('callbacks', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('resolved_at');
            }
        });

        // Handle resolved_by separately: may exist with wrong type from partial prior run
        $resolvedByColInfo = collect(DB::select("
            SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'callbacks'
              AND COLUMN_NAME = 'resolved_by'
        "))->first();

        if (! $resolvedByColInfo) {
            // Column doesn't exist yet — add it
            Schema::table('callbacks', function (Blueprint $table) {
                $table->char('resolved_by', 36)->nullable()->after('corrected_quantity');
            });
        } elseif ($resolvedByColInfo->DATA_TYPE !== 'char' || $resolvedByColInfo->CHARACTER_MAXIMUM_LENGTH != 36) {
            // Wrong type — fix it
            DB::statement('ALTER TABLE callbacks MODIFY COLUMN resolved_by CHAR(36) NULL');
        }

        // Add FK on resolved_by if not already present
        $resolvedByFkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'callbacks'
              AND COLUMN_NAME = 'resolved_by'
              AND REFERENCED_TABLE_NAME = 'users'
        "))->isNotEmpty();

        if (! $resolvedByFkExists) {
            Schema::table('callbacks', function (Blueprint $table) {
                $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Indexes
        $existingIndexes = collect(DB::select('SHOW INDEX FROM callbacks'))->pluck('Key_name');
        Schema::table('callbacks', function (Blueprint $table) use ($existingIndexes) {
            if (! $existingIndexes->contains('callbacks_status_date_idx')) {
                $table->index(['status', 'callback_date'], 'callbacks_status_date_idx');
            }
            if (! $existingIndexes->contains('callbacks_product_stock_id_idx')) {
                $table->index('product_stock_id', 'callbacks_product_stock_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('callbacks', function (Blueprint $table) {
            $table->dropForeign(['product_stock_id']);
            $table->dropForeign(['resolved_by']);
            $table->dropIndex('callbacks_status_date_idx');
            $table->dropIndex('callbacks_product_stock_id_idx');
            $table->dropColumn([
                'product_stock_id',
                'expected_quantity',
                'resolution_type',
                'corrected_quantity',
                'resolved_by',
                'resolved_at',
                'resolution_notes',
            ]);
        });
    }
};
