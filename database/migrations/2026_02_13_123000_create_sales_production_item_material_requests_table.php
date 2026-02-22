<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_production_item_material_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_production_request_item_id');
            $table->unsignedBigInteger('item_request_id');
            $table->uuid('created_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_production_request_item_id', 'spimr_sales_item_fk')
                ->references('id')
                ->on('sales_production_request_items')
                ->onDelete('cascade');
            $table->foreign('item_request_id', 'spimr_item_request_fk')
                ->references('id')
                ->on('item_requests')
                ->onDelete('cascade');
            $table->foreign('created_by_id', 'spimr_created_by_fk')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique('item_request_id', 'spimr_item_request_uidx');
            $table->index(['sales_production_request_item_id', 'created_at'], 'spimr_sales_item_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_production_item_material_requests');
    }
};

