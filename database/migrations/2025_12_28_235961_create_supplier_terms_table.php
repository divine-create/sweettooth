<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('minimum_order_quantity', 15, 4)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0); // Base discount
            $table->decimal('early_payment_discount', 5, 2)->default(0); // If paid within X days
            $table->integer('early_payment_days')->nullable();
            $table->string('delivery_terms')->nullable(); // FOB, CIF, etc.
            $table->integer('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('supplier_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_terms');
    }
};
