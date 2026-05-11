<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_variances', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->unsignedBigInteger('product_stock_id')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('expected_quantity', 12, 2)->nullable();
            $table->string('reason', 20);
            $table->date('variance_date')->nullable();
            $table->string('shift_type', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('resolution_type', 20)->nullable();
            $table->decimal('corrected_quantity', 12, 2)->nullable();
            $table->char('resolved_by', 36)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('product_stock_id')->references('id')->on('product_stocks')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['branch_id', 'variance_date']);
            $table->index(['department_id', 'variance_date']);
            $table->index('product_stock_id');
            $table->index(['status', 'variance_date']);
        });

        // Migrate existing variance records from callbacks
        DB::statement("
            INSERT INTO stock_variances (
                branch_id, department_id, product_id, product_stock_id,
                quantity, expected_quantity, reason, variance_date,
                shift_type, notes, status, resolution_type, corrected_quantity,
                resolved_by, resolved_at, resolution_notes, created_at, updated_at
            )
            SELECT
                branch_id, department_id, product_id, product_stock_id,
                quantity, expected_quantity, reason, callback_date,
                shift_type, notes, status, resolution_type, corrected_quantity,
                resolved_by, resolved_at, resolution_notes, created_at, updated_at
            FROM callbacks
            WHERE product_stock_id IS NOT NULL
              AND reason IN ('shortage', 'excess')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_variances');
    }
};
