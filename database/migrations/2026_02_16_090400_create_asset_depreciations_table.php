<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixed_asset_id');
            $table->unsignedBigInteger('accounting_period_id')->nullable();
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 14, 2);
            $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
            $table->text('gl_posting_error')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->timestamps();

            $table->index(['fixed_asset_id', 'depreciation_date']);
            $table->index('gl_posting_status');

            $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
            $table->foreign('accounting_period_id')->references('id')->on('accounting_periods')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
