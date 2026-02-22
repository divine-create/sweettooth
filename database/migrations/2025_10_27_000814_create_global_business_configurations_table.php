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
        Schema::create('global_business_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255)->default('Your Business Name');
            $table->string('logo_upload', 50)->default('enabled');
            $table->json('contact_details')->default(json_encode(['phone', 'email', 'website', 'vat_number']));
            $table->json('business_type')->default(json_encode(['retail', 'wholesale', 'services']));
            $table->json('storage_settings')->default(json_encode(['local', 's3']));
            $table->string('subscription_plan', 50)->default('basic');
            $table->timestamps();
        });

        Schema::create('branch_business_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('company_name', 255)->nullable();
            $table->string('logo_upload', 50)->nullable();
            $table->json('contact_details')->nullable();
            $table->json('business_type')->nullable();
            $table->json('storage_settings')->nullable();
            $table->string('subscription_plan', 50)->nullable();
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_business_configurations');
        Schema::dropIfExists('global_business_configurations');
    }
};
