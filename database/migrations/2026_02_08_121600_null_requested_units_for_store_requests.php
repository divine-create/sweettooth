<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('production_requests')
            ->whereNull('sales_department_id')
            ->update(['requested_units' => null]);
    }

    public function down(): void
    {
        // No-op: cannot reliably restore prior values.
    }
};
