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
        Schema::table('item_requests', function (Blueprint $table) {
            // Make approved_by columns nullable since items start in pending status
            $table->char('approved_by_id', 36)->nullable()->change();
            $table->string('approved_by_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            // Revert to NOT NULL
            $table->char('approved_by_id', 36)->nullable(false)->change();
            $table->string('approved_by_type')->nullable(false)->change();
        });
    }
};
