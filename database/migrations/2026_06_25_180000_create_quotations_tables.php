<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->uuid('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'converted', 'expired', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            // Set when the quotation is turned into a real sale. No FK constraint so
            // a deleted sale never blocks the quotation record.
            $table->unsignedBigInteger('converted_sale_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'department_id', 'status']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            // A quotation line is either a sellable product or a raw inventory item,
            // mirroring sale_items. Neither touches stock — these are quotes only.
            $table->uuid('product_id')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('name'); // snapshot of the name at quote time
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
