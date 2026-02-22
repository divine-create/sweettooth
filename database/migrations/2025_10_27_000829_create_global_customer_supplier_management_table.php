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
        Schema::create('global_customer_supplier_managements', function (Blueprint $table) {
            $table->id();
            $table->json('customers')->default(json_encode(['add', 'edit', 'groups']));
            $table->json('suppliers')->default(json_encode(['add', 'edit']));
            $table->string('party_import', 50)->default('enabled');
            $table->timestamps();
        });

        Schema::create('branch_customer_supplier_managements', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->json('local_customers')->nullable(); // 'add,view'
            $table->string('local_suppliers', 50)->nullable(); // 'view'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_customer_supplier_managements');
        Schema::dropIfExists('global_customer_supplier_managements');
    }
};
