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
        Schema::table('production_requests', function (Blueprint $table) {
            // Drop foreign key constraints first
            if (\DB::getDriverName() === 'mysql') {
                $table->dropForeign(['shift_id']);
                $table->dropForeign(['item_request_id']);
            }
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Make shift_id nullable (for POS requests without an active shift)
            $table->unsignedBigInteger('shift_id')->nullable()->change();
            
            // Make item_request_id nullable (for POS direct requests)
            $table->unsignedBigInteger('item_request_id')->nullable()->change();
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Re-add foreign keys with cascade delete
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');
            
            $table->foreign('item_request_id')
                ->references('id')
                ->on('item_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            // Drop foreign keys
            if (\DB::getDriverName() === 'mysql') {
                $table->dropForeign(['shift_id']);
                $table->dropForeign(['item_request_id']);
            }
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Revert to NOT NULL
            $table->unsignedBigInteger('shift_id')->nullable(false)->change();
            $table->unsignedBigInteger('item_request_id')->nullable(false)->change();
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Re-add foreign keys
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');
            
            $table->foreign('item_request_id')
                ->references('id')
                ->on('item_requests')
                ->onDelete('cascade');
        });
    }
};
