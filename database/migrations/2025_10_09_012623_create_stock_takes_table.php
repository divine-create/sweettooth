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
        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('stock_take_number')->unique();
            $table->date('stock_take_date');
            $table->enum('type', ['daily', 'weekly', 'monthly', 'annual', 'ad_hoc']);
            $table->uuid('conducted_by_id');
            $table->string('conducted_by_type');
            $table->enum('status', ['in_progress', 'completed', 'verified'])->default('in_progress');
            $table->uuid('verified_by_id')->nullable();
            $table->string('verified_by_type')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_takes');
    }
};
