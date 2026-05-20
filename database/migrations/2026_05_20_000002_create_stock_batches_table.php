<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_remaining', 12, 2);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->enum('status', ['active', 'depleted', 'expired'])->default('active');
            $table->date('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            // FEFO query path: active batches ordered by expiry date
            $table->index(['stock_id', 'status', 'expiry_date']);
            // Branch-scoped expiry alert queries
            $table->index(['branch_id', 'status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
