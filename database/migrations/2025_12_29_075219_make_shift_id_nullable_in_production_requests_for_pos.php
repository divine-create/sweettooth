<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes shift_id nullable to support direct production requests from POS
     * without requiring an active shift.
     */
    public function up(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            // Drop the existing foreign key first
            $table->dropForeign(['shift_id']);
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Change column to nullable
            $table->unsignedBigInteger('shift_id')->nullable()->change();

            // Re-add foreign key with nullable support
            $table->foreign('shift_id')
                ->nullable()
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
        });

        Schema::table('production_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable(false)->change();
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');
        });
    }
};
