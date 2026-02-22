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
        Schema::create('global_pos_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('pos_interface', 50)->default('enabled');
            $table->json('payment_modes')->default(json_encode(['add', 'edit']));
            $table->string('receipt_template', 50)->default('custom');
            $table->string('sales_returns', 50)->default('enabled');
            $table->string('offline_mode', 50)->default('enabled');
            $table->string('online_shop_sync', 50)->default('enabled');
            $table->timestamps();
        });

        Schema::create('branch_pos_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('pos_use', 50)->nullable(); // 'enabled'
            $table->string('payment_modes', 50)->nullable(); // 'apply'
            $table->string('receipt_custom', 50)->nullable(); // 'limited'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_pos_configurations');
        Schema::dropIfExists('global_pos_configurations');
    }
};
