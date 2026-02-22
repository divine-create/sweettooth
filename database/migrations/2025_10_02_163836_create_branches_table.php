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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name')->unique(); // Branch name
            $table->string('code')->unique(); // Unique branch code
            $table->string('location'); // Physical address
            $table->string('phone')->nullable(); // Contact phone
            $table->string('email')->unique()->nullable(); // Contact email
            $table->text('description')->nullable(); // Optional description

            $table->uuid('manager_user_id')->nullable(); // Manager ID
            // $table->foreign('manager_user_id')
            //       ->references('id')
            //       ->on('employees')
            //       ->onDelete('set null');

            // New fields for more standard branch details
            $table->string('country')->nullable(); // Country
            $table->string('state')->nullable(); // State or region
            $table->string('city')->nullable(); // City
            $table->string('postal_code')->nullable(); // Postal / Zip code
            $table->string('timezone')->nullable(); // Branch timezone

            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
            $table->softDeletes(); // Allow soft deletion
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
