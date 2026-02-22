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
        Schema::create('global_reports_analytics', function (Blueprint $table) {
            $table->id();
            $table->json('reports')->default(json_encode(['sales', 'purchases', 'stock', 'pl']));
            $table->string('custom_date_range', 50)->default('enabled');
            $table->string('multi_select_delete', 50)->default('enabled');
            $table->json('export')->default(json_encode(['csv', 'pdf']));
            $table->timestamps();
        });

        Schema::create('branch_reports_analytics', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->json('branch_reports')->nullable(); // 'sales,stock'
            $table->string('date_filter', 50)->nullable(); // 'enabled'
            $table->string('export', 50)->nullable(); // 'csv'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_reports_analytics');
        Schema::dropIfExists('global_reports_analytics');
    }
};
