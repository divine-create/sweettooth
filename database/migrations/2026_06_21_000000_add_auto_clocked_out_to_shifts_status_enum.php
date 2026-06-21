<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the 'auto_clocked_out' value to the shifts.status enum.
     *
     * The auto-clock-out feature (see AutoClockOutShifts command and the
     * 2025_12_17 migration that added auto_clocked_out_at / auto_clock_out_reason)
     * writes status = 'auto_clocked_out', but that value was never added to the
     * enum, so under STRICT_TRANS_TABLES every auto clock-out failed with
     * "1265 Data truncated for column 'status'". This adds the missing value.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `shifts` MODIFY `status` ENUM('active','closed','submitted','auto_clocked_out') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `shifts` MODIFY `status` ENUM('active','closed','submitted') NOT NULL DEFAULT 'active'");
    }
};
