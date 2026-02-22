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
        Schema::table('production_records', function (Blueprint $table) {
            // Batch-level tracking: How much from this specific batch was sent out or used for orders
            $table->decimal('quantity_sent_out', 10, 2)->default(0)->after('quantity_approved');
            $table->decimal('quantity_for_order', 10, 2)->default(0)->after('quantity_sent_out');
            $table->decimal('quantity_remaining', 10, 2)->default(0)->after('quantity_for_order');

            // Batch identifier for easier reference (e.g., "Batch 1", "Batch 2")
            $table->string('batch_number')->nullable()->after('daily_produce_id');

            // Status to track if this batch has been fully dispatched
            $table->enum('dispatch_status', ['available', 'partial', 'fully_dispatched'])->default('available')->after('quality_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropColumn([
                'quantity_sent_out',
                'quantity_for_order',
                'quantity_remaining',
                'batch_number',
                'dispatch_status',
            ]);
        });
    }
};
