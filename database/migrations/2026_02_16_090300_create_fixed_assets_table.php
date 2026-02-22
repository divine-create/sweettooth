<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->string('asset_name');
            $table->string('asset_tag')->nullable();
            $table->decimal('asset_cost', 14, 2);
            $table->decimal('salvage_value', 14, 2)->default(0);
            $table->integer('useful_life_months')->default(0);
            $table->string('depreciation_method')->default('straight_line');
            $table->date('acquisition_date');
            $table->enum('funding_source', ['cash', 'ap'])->default('cash');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('accumulated_depreciation', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
            $table->text('gl_posting_error')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->index(['acquisition_date']);

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
