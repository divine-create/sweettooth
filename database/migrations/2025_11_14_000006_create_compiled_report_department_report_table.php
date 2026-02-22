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
        Schema::create('compiled_report_department_report', function (Blueprint $table) {
            $table->foreignUuid('compiled_report_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('department_report_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Composite primary key
            $table->primary(['compiled_report_id', 'department_report_id'], 'cr_dr_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compiled_report_department_report');
    }
};
