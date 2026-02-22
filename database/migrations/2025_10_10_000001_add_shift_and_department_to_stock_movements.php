<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds shift and department_id denormalized fields to stock_movements table
     * to fix filter issues where shift/department filters only worked for ItemRequest references.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Denormalized fields from reference (ItemRequest, Purchase, etc)
            $table->string('shift')->nullable()->after('moved_by_id')->comment('Denormalized from ItemRequest or other reference');
            $table->unsignedBigInteger('department_id')->nullable()->after('shift')->comment('Denormalized from ItemRequest or reference');

            // Indexes for filter queries (commonly filtered by shift/department + date)
            $table->index(['shift', 'movement_date'], 'idx_movement_shift_date');
            $table->index(['department_id', 'movement_date'], 'idx_movement_department_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_movement_shift_date');
            $table->dropIndex('idx_movement_department_date');
            $table->dropColumn(['shift', 'department_id']);
        });
    }
};
