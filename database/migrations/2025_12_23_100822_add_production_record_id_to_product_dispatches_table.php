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
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->unsignedBigInteger('production_record_id')->nullable()->after('daily_produce_id');
            $table->foreign('production_record_id')->references('id')->on('production_records')->onDelete('cascade');
            $table->index('production_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->dropForeign(['production_record_id']);
            $table->dropColumn('production_record_id');
        });
    }
};
