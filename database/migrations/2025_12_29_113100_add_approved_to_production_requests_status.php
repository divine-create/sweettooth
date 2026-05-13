<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds 'approved' to the status enum in production_requests table
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE production_requests MODIFY COLUMN status ENUM('pending', 'approved', 'in_progress', 'quality_check', 'completed', 'dispatched', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'approved' status to 'pending' to avoid data loss
        DB::table('production_requests')
            ->where('status', 'approved')
            ->update(['status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE production_requests MODIFY COLUMN status ENUM('pending', 'in_progress', 'quality_check', 'completed', 'dispatched', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending'");
        }
    }
};
