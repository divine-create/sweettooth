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
        Schema::create('report_annotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('reportable');
            $table->uuid('author_id')->nullable();
            $table->string('author_type')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reportable_id', 'reportable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_annotations');
    }
};
