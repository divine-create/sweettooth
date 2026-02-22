<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include pending_verification and accepted
        DB::statement("ALTER TABLE product_dispatches MODIFY COLUMN status ENUM('pending_verification', 'accepted', 'received', 'rejected') DEFAULT 'pending_verification'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original enum values
        DB::statement("ALTER TABLE product_dispatches MODIFY COLUMN status ENUM('dispatched', 'received', 'rejected') DEFAULT 'dispatched'");
    }
};
