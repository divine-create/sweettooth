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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->uuid('recorded_by_id');
            $table->string('recorded_by_type');
            // $table->morphs('recorded_by');
            // $table->foreign('recorded_by')->references('id')->on('employees')->onDelete('restrict');
            $table->string('purchase_number')->unique();
            $table->date('purchase_date');
            $table->string('supplier_name');
            $table->string('supplier_contact')->nullable();
            $table->decimal('total_fob_fc', 12, 2)->default(0);
            $table->decimal('total_fob_ngn', 12, 2)->default(0);
            $table->decimal('other_costs', 12, 2)->default(0);
            $table->decimal('landing_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->enum('payment_status', ['paid', 'partial', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
