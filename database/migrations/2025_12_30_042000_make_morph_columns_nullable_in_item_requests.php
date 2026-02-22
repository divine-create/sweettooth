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
            // Make morph columns nullable - approved/cancelled/dispatched by are optional
            $table->uuid('approved_by_id')->nullable()->change();
            $table->string('approved_by_type')->nullable()->change();
            
            $table->uuid('cancelled_by_id')->nullable()->change();
            $table->string('cancelled_by_type')->nullable()->change();
            
            $table->uuid('dispatched_by_id')->nullable()->change();
            $table->string('dispatched_by_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            // Revert to NOT NULL
            $table->uuid('approved_by_id')->nullable(false)->change();
            $table->string('approved_by_type')->nullable(false)->change();
            
            $table->uuid('cancelled_by_id')->nullable(false)->change();
            $table->string('cancelled_by_type')->nullable(false)->change();
            
            $table->uuid('dispatched_by_id')->nullable(false)->change();
            $table->string('dispatched_by_type')->nullable(false)->change();
        });
    }
};
