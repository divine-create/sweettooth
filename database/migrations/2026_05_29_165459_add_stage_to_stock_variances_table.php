<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_variances', function (Blueprint $table) {
            // 'opening' = variance detected when staff enters actual opening qty
            // 'closing' = variance detected at shift close (existing behaviour)
            $table->enum('stage', ['opening', 'closing'])->default('closing')->after('shift_type');
        });
    }

    public function down(): void
    {
        Schema::table('stock_variances', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
