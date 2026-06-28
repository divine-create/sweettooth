<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_store_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');

            $table->unsignedBigInteger('from_store_id');
            $table->unsignedBigInteger('to_store_id');
            $table->unsignedBigInteger('from_department_id');
            $table->unsignedBigInteger('to_department_id');

            // Raw materials only — numeric Item id, stored as string to match the
            // varchar item_id used across production_store_stocks / movements.
            $table->string('item_id');

            $table->decimal('quantity', 12, 2);
            $table->decimal('received_quantity', 12, 2)->nullable();
            $table->string('uom')->nullable();
            $table->decimal('unit_cost', 15, 4)->default(0);

            $table->enum('status', ['pending_receipt', 'received', 'cancelled'])
                ->default('pending_receipt');
            $table->text('notes')->nullable();

            $table->uuid('sent_by_id')->nullable();
            $table->string('sent_by_type')->nullable();
            $table->uuid('received_by_id')->nullable();
            $table->string('received_by_type')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('from_store_id')->references('id')->on('production_stores')->onDelete('cascade');
            $table->foreign('to_store_id')->references('id')->on('production_stores')->onDelete('cascade');

            $table->index(['to_store_id', 'status'], 'idx_pst_to_store_status');
            $table->index(['from_store_id', 'status'], 'idx_pst_from_store_status');
            $table->index('item_id', 'idx_pst_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_store_transfers');
    }
};
