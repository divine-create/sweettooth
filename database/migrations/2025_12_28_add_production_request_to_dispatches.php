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
        // Add FK to product_dispatches linking to production request if not already there
        if (Schema::hasTable('product_dispatches') && !Schema::hasColumn('product_dispatches', 'production_request_id')) {
            Schema::table('product_dispatches', function (Blueprint $table) {
                $table->foreignId('production_request_id')->nullable()->after('id')->constrained('production_requests')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_dispatches') && Schema::hasColumn('product_dispatches', 'production_request_id')) {
            Schema::table('product_dispatches', function (Blueprint $table) {
                $table->dropForeignIdFor('ProductionRequest');
                $table->dropColumn('production_request_id');
            });
        }
    }
};
