<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE product_stocks MODIFY COLUMN shift_type ENUM('morning', 'afternoon', 'night') NOT NULL");
        DB::statement("ALTER TABLE material_requests MODIFY COLUMN shift ENUM('morning', 'afternoon', 'night') NOT NULL DEFAULT 'morning'");
        DB::statement("ALTER TABLE daily_produces MODIFY COLUMN shift_type ENUM('morning', 'afternoon', 'night') NOT NULL");
    }

    public function down(): void
    {
        // Only safe to roll back if no night rows exist
        DB::statement("ALTER TABLE product_stocks MODIFY COLUMN shift_type ENUM('morning', 'afternoon') NOT NULL");
        DB::statement("ALTER TABLE material_requests MODIFY COLUMN shift ENUM('morning', 'afternoon') NOT NULL DEFAULT 'morning'");
        DB::statement("ALTER TABLE daily_produces MODIFY COLUMN shift_type ENUM('morning', 'afternoon') NOT NULL");
    }
};
