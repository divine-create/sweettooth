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
        Schema::create('production_store_daily_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->foreign('store_id')->references('id')->on('production_stores')->onDelete('cascade');
            
            // Using string(40) for item_id as per existing production_store_stocks table
            $table->string('item_id', 40)->nullable()->index();
            
            $table->date('stock_date')->index();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            
            // Quantities
            $table->decimal('opening_quantity', 12, 2)->default(0);
            $table->decimal('addition_quantity', 12, 2)->default(0);
            $table->decimal('quantity_used', 12, 2)->default(0);
            $table->decimal('closing_quantity', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->default(0);
            
            // Workflow & Verification
            $table->enum('workflow_step', [
                'opening_pending', 
                'opening_verified', 
                'closing_pending', 
                'closing_completed'
            ])->default('opening_pending');
            
            $table->boolean('is_workflow_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->char('verified_by', 36)->nullable()->index(); // Assuming char(36) for UUID
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Unique index to prevent duplicate entries for the same item per store per day/shift
            // Shift ID is nullable, so we need to be careful with unique constraints. Some DBMS treat NULL as distinct.
            // But this will do for now.
            $table->unique(['store_id', 'item_id', 'stock_date', 'shift_id'], 'prod_store_daily_stock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_store_daily_stocks');
    }
};
