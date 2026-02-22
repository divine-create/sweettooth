<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_production_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('sales_department_id');
            $table->string('request_number')->unique();
            $table->enum('status', [
                'pending',
                'approved_by_production',
                'materials_requested',
                'materials_approved',
                'processing',
                'completed',
                'dispatched',
                'received_by_sales',
                'rejected',
                'cancelled',
            ])->default('pending');
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->uuid('requested_by_id')->nullable();
            $table->string('requested_by_type')->nullable();
            $table->uuid('approved_by_production_id')->nullable();
            $table->timestamp('approved_by_production_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id', 'spr_branch_fk')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('sales_department_id', 'spr_sales_dept_fk')->references('id')->on('departments')->onDelete('cascade');
            $table->index(['sales_department_id', 'status'], 'spr_sales_dept_status_idx');
            $table->index(['branch_id', 'created_at'], 'spr_branch_created_idx');
        });

        Schema::create('sales_production_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_production_request_id');
            $table->unsignedBigInteger('production_department_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->decimal('quantity_requested', 12, 2);
            $table->decimal('quantity_produced', 12, 2)->default(0);
            $table->enum('status', [
                'pending',
                'approved_by_production',
                'materials_requested',
                'materials_approved',
                'processing',
                'completed',
                'dispatched',
                'received_by_sales',
                'rejected',
                'cancelled',
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_production_request_id', 'spri_request_fk')
                ->references('id')
                ->on('sales_production_requests')
                ->onDelete('cascade');
            $table->foreign('production_department_id', 'spri_prod_dept_fk')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');
            $table->foreign('product_id', 'spri_product_fk')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
            $table->index(['production_department_id', 'status'], 'spri_dept_status_idx');
            $table->index(['product_id', 'status'], 'spri_product_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_production_request_items');
        Schema::dropIfExists('sales_production_requests');
    }
};
