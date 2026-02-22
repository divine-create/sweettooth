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
        Schema::create('branch_currency_localizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('currency_display', 50)->nullable(); // 'inherit' or specific currency
            $table->string('language', 10)->nullable(); // 'inherit' or specific language
            $table->string('units_local', 50)->nullable(); // 'inherit' or view-only
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });

        Schema::create('global_currency_localizations', function (Blueprint $table) {
            $table->id();
            $table->string('multi_currency', 50)->default('enabled');
            $table->string('primary_currency', 10)->default('NGN');
            $table->string('primary_currency_exchange_rate')->nullable();
            $table->json('currency_list')->default(json_encode(['USD', 'EUR', 'GBP', 'INR', 'NGN']));
            $table->string('multi_tax', 50)->default('enabled');
            $table->string('default_language', 10)->default('en');
            $table->json('language_options')->default(json_encode(['en', 'es', 'fr', 'ar']));
            $table->string('date_format', 50)->default('MM/DD/YYYY');
            $table->json('units_of_measure')->default(json_encode(['piece', 'kg', 'liter']));
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_currency_localizations');
        Schema::dropIfExists('branch_currency_localizations');
    }
};
