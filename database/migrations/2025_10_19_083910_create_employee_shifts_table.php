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
        // Schema::create('employee_shifts', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignUuid('branch_id')->constrained('branch', 'id')->cascadeOnDelete();
        //     $table->string('shift_name');
        //     $table->dateTime('start_time');
        //     $table->dateTime('end_time');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
