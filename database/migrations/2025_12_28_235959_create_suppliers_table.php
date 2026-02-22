<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            return;
        }

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Supplier code for identification
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable(); // Tax/VAT ID
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->enum('credit_rating', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->boolean('is_verified')->default(false); // Supplier verification status
            $table->integer('payment_terms_days')->default(30); // Payment terms in days
            $table->decimal('outstanding_balance', 15, 2)->default(0); // Current outstanding amount
            $table->decimal('credit_limit', 15, 2)->default(0); // Credit limit allowed
            $table->integer('avg_delivery_days')->nullable(); // Average delivery lead time
            $table->decimal('quality_rating', 3, 1)->nullable(); // 0-5 star rating
            $table->decimal('on_time_delivery_percentage', 5, 2)->nullable(); // Percentage
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('parent_supplier_id')->nullable(); // For enterprise suppliers
            $table->foreign('parent_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->uuid('branch_id')->nullable(); // For branch-specific suppliers
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('email');
            $table->index('status');
            $table->index('branch_id');
            $table->index('outstanding_balance');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
