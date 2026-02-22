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
        Schema::table('shifts', function (Blueprint $table) {
            // Add workflow state tracking
            $table->enum('workflow_state', [
                'clock_in',
                'stock_opening',
                'pos',
                'clock_out',
                'shift_closing',
                'completed',
            ])->default('clock_in')->after('status');

            // Add JSON metadata for workflow tracking
            $table->json('metadata')->nullable()->after('notes');

            // Add specific workflow completion timestamps
            $table->timestamp('stock_verified_at')->nullable()->after('metadata');
            $table->timestamp('shift_closed_at')->nullable()->after('stock_verified_at');

            // Add indexes for performance
            $table->index(['employee_id', 'shift_date', 'workflow_state'], 'shifts_employee_date_workflow_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_employee_date_workflow_idx');
            $table->dropColumn([
                'workflow_state',
                'metadata',
                'stock_verified_at',
                'shift_closed_at',
            ]);
        });
    }
};
