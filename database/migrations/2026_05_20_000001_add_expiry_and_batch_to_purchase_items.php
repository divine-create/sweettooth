<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('cost_per_unit');
            $table->string('batch_number', 100)->nullable()->after('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'batch_number']);
        });
    }
};
