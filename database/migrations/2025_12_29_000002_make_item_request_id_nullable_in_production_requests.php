<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes item_request_id nullable to support direct production requests from POS
     * without requiring an item_request record.
     */
    public function up(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            // Drop the existing foreign key first
            $table->dropForeign(['item_request_id']);
        });

        Schema::table('production_requests', function (Blueprint $table) {
            // Change column to nullable
            $table->unsignedBigInteger('item_request_id')->nullable()->change();

            // Re-add foreign key with nullable support
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
        // Note: Reverting this could fail if there are records with NULL item_request_id
        Schema::table('production_requests', function (Blueprint $table) {
            $table->dropForeign(['item_request_id']);
        });

        Schema::table('production_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('item_request_id')->nullable(false)->change();
            $table->foreign('item_request_id')
                ->references('id')
                ->on('item_requests')
                ->onDelete('cascade');
        });
    }
};
