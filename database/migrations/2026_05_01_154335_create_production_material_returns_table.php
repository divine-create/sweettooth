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
        Schema::create('production_material_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('item_dispatch_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 12, 2);
            $table->string('uom');
            $table->enum('reason', ['damaged', 'expired', 'quality_issue', 'wrong_item', 'excess_return']);
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->text('notes')->nullable();
            
            $table->uuid('returned_by_id');
            $table->string('returned_by_type');
            
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('item_dispatch_id')->references('id')->on('item_dispatches')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_material_returns');
    }
};
